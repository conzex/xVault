<?php
/**
 * xVault Enterprise Password Manager
 * Database Connection & Initialization (PDO)
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/config.php';

define('APP_DB_VERSION', '1.2.0');


function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'mysql') {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $mysqlEx) {
                // Fallback to SQLite storage if MySQL is unavailable in local environment
                $storageDir = __DIR__ . '/storage';
                if (!is_dir($storageDir)) {
                    @mkdir($storageDir, 0755, true);
                }
                $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON;');
            }
        } else {
            // SQLite Fallback
            $storageDir = __DIR__ . '/storage';
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }
            $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Enable Foreign Key constraints in SQLite
            $pdo->exec('PRAGMA foreign_keys = ON;');
        }

        initDatabaseSchema($pdo);
        check_database_health_and_version($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database Connection Error: ' . $e->getMessage());
        if (APP_ENV === 'development') {
            die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
        } else {
            die('Database connection failure. Please verify configuration.');
        }
    }
}

function initDatabaseSchema(PDO $pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                name TEXT,
                status TEXT DEFAULT 'active',
                is_verified INTEGER DEFAULT 0,
                verification_token TEXT,
                verification_token_hash TEXT,
                verification_token_expiry TIMESTAMP,
                reset_token TEXT,
                reset_token_expiry TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            "CREATE TABLE IF NOT EXISTS folders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                customer_name TEXT,
                customer_email TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS password_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
                app_name TEXT NOT NULL,
                login_url TEXT,
                username TEXT,
                encrypted_password TEXT NOT NULL,
                is_favorite INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS addresses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
                label TEXT NOT NULL,
                first_name TEXT,
                last_name TEXT,
                address_line1 TEXT,
                address_line2 TEXT,
                city TEXT,
                state TEXT,
                zip_code TEXT,
                country TEXT,
                phone TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS secure_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                type TEXT DEFAULT 'note',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS shared_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT UNIQUE NOT NULL,
                created_by INTEGER REFERENCES users(id) ON DELETE CASCADE,
                target_email TEXT,
                expires_at TIMESTAMP,
                one_time INTEGER DEFAULT 0,
                used INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS security_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                event_type TEXT NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS system_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS email_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                to_email TEXT NOT NULL,
                subject TEXT NOT NULL,
                email_type TEXT DEFAULT 'transactional',
                status TEXT DEFAULT 'sent',
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );"
        ];
        foreach ($queries as $q) {
            $pdo->exec($q);
        }
    } else {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        if ($sql) {
            $pdo->exec($sql);
        }
    }
}

/**
 * Smart Database Version & Migration Check
 */
function check_database_health_and_version(PDO $pdo) {
    try {
        // Ensure system_settings table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(255) PRIMARY KEY, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");

        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'db_version'");
        $stmt->execute();
        $installedVersion = $stmt->fetchColumn();

        if (!$installedVersion) {
            // Set current version for existing DB
            $ins = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('db_version', ?)");
            $ins->execute([APP_DB_VERSION]);
        } elseif (version_compare($installedVersion, APP_DB_VERSION, '<')) {
            // Run non-destructive database migrations
            run_db_migrations($pdo, $installedVersion);
            $up = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'db_version'");
            $up->execute([APP_DB_VERSION]);
        }
    } catch (\Throwable $e) {
        error_log("Database version check exception: " . $e->getMessage());
    }
}

/**
 * Non-destructive database migrations engine
 */
function run_db_migrations(PDO $pdo, $fromVersion) {
    // Non-destructive migrations for future schema additions
    try {
        if (version_compare($fromVersion, '1.1.0', '<')) {
            if (DB_DRIVER === 'mysql') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS email_logs (id INT AUTO_INCREMENT PRIMARY KEY, to_email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, email_type VARCHAR(100) DEFAULT 'transactional', status VARCHAR(50) DEFAULT 'sent', error_message TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS email_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, to_email TEXT NOT NULL, subject TEXT NOT NULL, email_type TEXT DEFAULT 'transactional', status TEXT DEFAULT 'sent', error_message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
            }
        }
        
        // Version 1.2.0: Email verification security enhancements
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN verification_token_hash VARCHAR(255) NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN verification_token_expiry DATETIME NULL");
        } catch (\Throwable $e) {}
    } catch (\Throwable $e) {
        error_log("DB Migration warning: " . $e->getMessage());
    }
}
