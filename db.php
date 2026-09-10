<?php
/**
 * xVault Enterprise Password Manager
 * Database Connection & Initialization (PDO)
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/config.php';

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'mysql') {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
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
    if (DB_DRIVER === 'sqlite') {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                name TEXT,
                is_verified INTEGER DEFAULT 0,
                verification_token TEXT,
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
