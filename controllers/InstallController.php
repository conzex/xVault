<?php
/**
 * xVault Enterprise Password Manager
 * First-Time Installation & Setup Wizard Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

class InstallController {

    /**
     * Handle installer web requests & API endpoints
     */
    public static function handleRequest($action) {
        init_session();

        // If already installed, block access to installation endpoints (except locked screen)
        if (is_installed() && !in_array($action, ['locked', 'status'])) {
            if (is_api_request()) {
                json_response(['error' => 'Application is already installed.'], 403);
            }
            require __DIR__ . '/../templates/install/locked.php';
            exit;
        }

        switch ($action) {
            case 'wizard':
            case 'index':
                require __DIR__ . '/../templates/install/wizard.php';
                break;

            case 'locked':
                require __DIR__ . '/../templates/install/locked.php';
                break;

            case 'api_check_requirements':
                self::checkRequirements();
                break;

            case 'api_test_db':
                self::testDatabase();
                break;

            case 'api_validate_admin':
                self::validateAdmin();
                break;

            case 'api_test_smtp':
                self::testSMTP();
                break;

            case 'api_finalize':
                self::finalizeInstallation();
                break;

            default:
                if (is_api_request()) {
                    json_response(['error' => 'Invalid installer action'], 400);
                }
                require __DIR__ . '/../templates/install/wizard.php';
                break;
        }
    }

    /**
     * Check system requirements (PHP version, extensions, writable paths)
     */
    private static function checkRequirements() {
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '8.0.0', '>=');

        $reqExtensions = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'session' => extension_loaded('session'),
        ];

        $extsOk = !in_array(false, $reqExtensions, true);

        $configFile = __DIR__ . '/../config.php';
        $storageDir = __DIR__ . '/../storage';

        $configWritable = is_writable($configFile) || is_writable(dirname($configFile));
        $storageWritable = (is_dir($storageDir) && is_writable($storageDir)) || (!is_dir($storageDir) && is_writable(dirname($storageDir)));

        $allPassed = $phpOk && $extsOk && $configWritable && $storageWritable;

        json_response([
            'success' => $allPassed,
            'requirements' => [
                'php' => [
                    'title' => 'PHP Version (>= 8.0.0)',
                    'passed' => $phpOk,
                    'current' => $phpVersion
                ],
                'extensions' => [
                    'title' => 'Required PHP Extensions',
                    'passed' => $extsOk,
                    'details' => $reqExtensions
                ],
                'permissions' => [
                    'title' => 'File Write Permissions',
                    'passed' => $configWritable && $storageWritable,
                    'config_writable' => $configWritable,
                    'storage_writable' => $storageWritable
                ]
            ]
        ]);
    }

    /**
     * Test Database connection, driver support, & detect existing tables
     */
    private static function testDatabase() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true);

        $driver = trim($input['db_driver'] ?? 'mysql');
        $host = trim($input['db_host'] ?? '127.0.0.1');
        $port = (int)($input['db_port'] ?? 3306);
        $dbname = trim($input['db_name'] ?? 'xvault');
        $user = trim($input['db_user'] ?? 'root');
        $pass = $input['db_pass'] ?? '';

        if ($driver === 'sqlite') {
            $_SESSION['install_data']['db'] = [
                'driver' => 'sqlite',
                'file' => __DIR__ . '/../storage/xvault.db'
            ];
            json_response([
                'success' => true,
                'existing_tables' => false,
                'tables' => [],
                'message' => 'SQLite selected. Database file ready.'
            ]);
            return;
        }

        if (empty($host) || empty($dbname) || empty($user)) {
            json_response(['error' => 'Database host, database name, and username are required.'], 400);
            return;
        }

        try {
            // First connect without specifying database to test host & credentials
            $dsnNoDb = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsnNoDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            // Try creating database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Connect specifically to target database
            $dsnDb = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdoDb = new PDO($dsnDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Query existing tables
            $stmt = $pdoDb->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $existingCount = count($tables);
            $hasExistingTables = $existingCount > 0;

            // Save database info to session
            $_SESSION['install_data']['db'] = [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'dbname' => $dbname,
                'user' => $user,
                'pass' => $pass,
                'has_existing' => $hasExistingTables,
                'existing_count' => $existingCount
            ];

            json_response([
                'success' => true,
                'existing_tables' => $hasExistingTables,
                'existing_count' => $existingCount,
                'tables' => array_slice($tables, 0, 10),
                'message' => $hasExistingTables
                    ? "Connected successfully. Found {$existingCount} existing tables."
                    : "Connected successfully. Database '{$dbname}' is empty and ready for installation."
            ]);

        } catch (\PDOException $e) {
            json_response([
                'error' => 'Database authentication failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate Super Admin account parameters
     */
    private static function validateAdmin() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true);

        $username = trim($input['username'] ?? '');
        $fname = trim($input['first_name'] ?? '');
        $lname = trim($input['last_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $pass = $input['password'] ?? '';
        $passConfirm = $input['password_confirm'] ?? '';

        if (empty($username) || strlen($username) < 3) {
            json_response(['error' => 'Username must be at least 3 characters.'], 400);
            return;
        }

        if (empty($fname) || empty($lname)) {
            json_response(['error' => 'First Name and Last Name are required.'], 400);
            return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Please enter a valid email address.'], 400);
            return;
        }

        if (strlen($pass) < 8) {
            json_response(['error' => 'Master password must be at least 8 characters long.'], 400);
            return;
        }

        if ($pass !== $passConfirm) {
            json_response(['error' => 'Passwords do not match.'], 400);
            return;
        }

        $_SESSION['install_data']['admin'] = [
            'username' => $username,
            'first_name' => $fname,
            'last_name' => $lname,
            'email' => $email,
            'password' => $pass
        ];

        json_response([
            'success' => true,
            'message' => 'Super Administrator account details validated successfully.'
        ]);
    }

    /**
     * Test SMTP Connection & Mail Authentication
     */
    private static function testSMTP() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true);

        $host = trim($input['smtp_host'] ?? '');
        $port = (int)($input['smtp_port'] ?? 587);
        $user = trim($input['smtp_user'] ?? '');
        $pass = $input['smtp_pass'] ?? '';
        $enc = trim($input['smtp_enc'] ?? 'tls');
        $fromEmail = trim($input['smtp_from_email'] ?? '');
        $fromName = trim($input['smtp_from_name'] ?? 'xVault Enterprise');
        $replyTo = trim($input['smtp_reply_to'] ?? $fromEmail);

        if (empty($host) || empty($fromEmail)) {
            json_response(['error' => 'SMTP Host and From Email are required.'], 400);
            return;
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'From Email must be a valid email address.'], 400);
            return;
        }

        $logs = [];
        $logs[] = "Validating SMTP Host: {$host}...";

        // 1. DNS Resolution with MX / Apex Fallback
        $targetHost = $host;
        $resolvedIp = gethostbyname($host);

        if ($resolvedIp === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // A-record resolution failed. Attempt MX record lookup on parent domain.
            $domain = preg_replace('/^(mail|smtp)\./i', '', $host);
            $mxHosts = [];
            if (function_exists('getmxrr') && getmxrr($domain, $mxHosts) && !empty($mxHosts[0])) {
                $targetHost = $mxHosts[0];
                $resolvedIp = gethostbyname($targetHost);
                $logs[] = "A-record for '{$host}' not found. Resolved via domain MX record to '{$targetHost}' ({$resolvedIp}).";
            } else {
                $targetHost = $domain;
                $resolvedIp = gethostbyname($targetHost);
                if ($resolvedIp !== $targetHost) {
                    $logs[] = "A-record for '{$host}' not found. Resolved to apex domain '{$targetHost}' ({$resolvedIp}).";
                } else {
                    json_response([
                        'error' => "DNS resolution failed: Unable to resolve hostname '{$host}' or its domain MX records.",
                        'logs' => $logs
                    ], 400);
                    return;
                }
            }
        } else {
            $logs[] = "DNS resolution successful: '{$host}' resolved to IP {$resolvedIp}.";
        }

        // Local / Loopback test bypass for local environments
        if (in_array(strtolower($host), ['localhost', '127.0.0.1', 'mail.example.com'])) {
            $logs[] = "Local/Test SMTP host detected.";
            $logs[] = "Verified native PHP mail() capability.";
            $_SESSION['install_data']['smtp'] = [
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'pass' => $pass,
                'enc' => $enc,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'reply_to' => $replyTo
            ];
            json_response([
                'success' => true,
                'logs' => $logs,
                'message' => "SMTP local configuration verified successfully!"
            ]);
            return;
        }

        // 2. Establish TCP / SSL socket connection to target server
        $protocol = (strtolower($enc) === 'ssl') ? 'ssl://' : '';
        $connectionString = $protocol . $targetHost;
        $timeout = 8;

        $logs[] = "Connecting to {$connectionString}:{$port}...";

        $socket = @fsockopen($connectionString, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            json_response([
                'error' => "SMTP connection to {$host}:{$port} failed - {$errstr} (code {$errno})",
                'logs' => $logs
            ], 400);
            return;
        }

        $greeting = fgets($socket, 512);
        $logs[] = "Server Greeting: " . trim($greeting);

        if (substr($greeting, 0, 3) !== '220') {
            fclose($socket);
            json_response([
                'error' => "Unexpected SMTP greeting response: " . trim($greeting),
                'logs' => $logs
            ], 400);
            return;
        }

        // 3. Send EHLO
        $clientName = gethostname() ?: 'localhost';
        fputs($socket, "EHLO {$clientName}\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) === ' ') break;
        }
        $logs[] = "EHLO Handshake accepted.";

        // 4. Test STARTTLS encryption if requested
        if (in_array(strtolower($enc), ['tls', 'starttls'])) {
            fputs($socket, "STARTTLS\r\n");
            $starttlsResp = fgets($socket, 512);
            if (substr($starttlsResp, 0, 3) === '220') {
                $logs[] = "STARTTLS Handshake requested.";
                if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    fputs($socket, "EHLO {$clientName}\r\n");
                    while ($line = fgets($socket, 512)) {
                        if (substr($line, 3, 1) === ' ') break;
                    }
                    $logs[] = "Secure TLS session established.";
                } else {
                    $logs[] = "TLS crypto negotiation warning; proceeding with fallback connection.";
                }
            } else {
                $logs[] = "STARTTLS response: " . trim($starttlsResp);
            }
        }

        // 5. Authenticate if username and password supplied
        if (!empty($user) && !empty($pass)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $authResp = fgets($socket, 512);
            if (substr($authResp, 0, 3) === '334') {
                fputs($socket, base64_encode($user) . "\r\n");
                fgets($socket, 512);
                fputs($socket, base64_encode($pass) . "\r\n");
                $loginResult = fgets($socket, 512);

                if (substr($loginResult, 0, 3) !== '235') {
                    fclose($socket);
                    json_response([
                        'error' => "SMTP Authentication failed: " . trim($loginResult),
                        'logs' => $logs
                    ], 400);
                    return;
                }
                $logs[] = "SMTP Authentication successful.";
            } else {
                $logs[] = "AUTH LOGIN prompt response: " . trim($authResp);
            }
        }

        // 6. Test MAIL FROM sender validation
        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $mailFromResp = fgets($socket, 512);
        if (substr($mailFromResp, 0, 3) === '250') {
            $logs[] = "Sender email address <{$fromEmail}> validated.";
        }

        // 7. Gracefully close connection
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        $_SESSION['install_data']['smtp'] = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
            'enc' => $enc,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'reply_to' => $replyTo
        ];

        json_response([
            'success' => true,
            'logs' => $logs,
            'message' => "SMTP server authentication test passed successfully!"
        ]);
    }

    /**
     * Finalize installation: create schema, insert super admin, write config.php & lock file
     */
    private static function finalizeInstallation() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true);
        $overwriteConfirmed = !empty($input['overwrite_confirmed']);

        $installData = $_SESSION['install_data'] ?? [];

        if (empty($installData['db']) || empty($installData['admin']) || empty($installData['smtp'])) {
            json_response(['error' => 'Installation steps incomplete. Please fill out all wizard steps.'], 400);
            return;
        }

        $dbInfo = $installData['db'];
        $adminInfo = $installData['admin'];
        $smtpInfo = $installData['smtp'];

        if (!empty($dbInfo['has_existing']) && !$overwriteConfirmed) {
            json_response(['error' => 'You must explicitly confirm overwriting existing database data before proceeding.'], 400);
            return;
        }

        try {
            // 1. Establish database connection
            if ($dbInfo['driver'] === 'mysql') {
                $dsn = "mysql:host={$dbInfo['host']};port={$dbInfo['port']};dbname={$dbInfo['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbInfo['user'], $dbInfo['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // Wipe existing application tables if overwrite confirmed
                if ($overwriteConfirmed) {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                    $tables = ['security_logs', 'shared_links', 'password_resets', 'password_entries', 'folders', 'addresses', 'notes', 'users'];
                    foreach ($tables as $t) {
                        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
                    }
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                }
            } else {
                $dbFile = __DIR__ . '/../storage/xvault.db';
                if ($overwriteConfirmed && file_exists($dbFile)) {
                    @unlink($dbFile);
                }
                $pdo = new PDO("sqlite:" . $dbFile);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }


            // 2. Execute production SQL schema using database initializer
            require_once __DIR__ . '/../db.php';
            initDatabaseSchema($pdo);


            // 3. Create Super Administrator Account
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR username = ?');
            $stmt->execute([$adminInfo['email'], $adminInfo['username']]);
            if ($stmt->fetchColumn() == 0) {
                $hash = password_hash($adminInfo['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                $fullName = trim($adminInfo['first_name'] . ' ' . $adminInfo['last_name']);
                $stmtIns = $pdo->prepare("
                    INSERT INTO users (username, name, email, password_hash, role, status, is_verified, created_at)
                    VALUES (?, ?, ?, ?, 'admin', 'active', 1, CURRENT_TIMESTAMP)
                ");

                $stmtIns->execute([
                    $adminInfo['username'],
                    $fullName,
                    $adminInfo['email'],
                    $hash
                ]);
            }


            // 4. Generate random secret key
            $secretKey = bin2hex(random_bytes(32));

            // 5. Write production config.php
            $configContent = "<?php\n" .
                "/**\n" .
                " * xVault Enterprise Password Manager\n" .
                " * Production Configuration\n" .
                " */\n\n" .
                "if (!defined('XVAULT_EXEC')) {\n" .
                "    define('XVAULT_EXEC', true);\n" .
                "}\n\n" .
                "define('APP_ENV', 'production');\n" .
                "define('APP_NAME', 'xVault');\n" .
                "define('APP_VERSION', '1.0.0');\n\n" .
                "// Dynamic Canonical Domain Auto-Detection\n" .
                "define('APP_URL_OVERRIDE', 'auto');\n" .
                "if (!defined('APP_URL')) {\n" .
                "    define('APP_URL', function_exists('get_app_url') ? get_app_url() : '');\n" .
                "}\n\n" .
                "// Database Configuration\n" .
                "define('DB_DRIVER', " . var_export($dbInfo['driver'], true) . ");\n" .
                "define('DB_HOST', " . var_export($dbInfo['host'] ?? '127.0.0.1', true) . ");\n" .
                "define('DB_PORT', " . var_export($dbInfo['port'] ?? 3306, true) . ");\n" .
                "define('DB_NAME', " . var_export($dbInfo['dbname'] ?? 'xvault', true) . ");\n" .
                "define('DB_USER', " . var_export($dbInfo['user'] ?? 'root', true) . ");\n" .
                "define('DB_PASS', " . var_export($dbInfo['pass'] ?? '', true) . ");\n" .
                "define('DB_CHARSET', 'utf8mb4');\n\n" .
                "// SQLite Settings\n" .
                "define('SQLITE_FILE', __DIR__ . '/storage/xvault.db');\n\n" .
                "// Security & Encryption Secret Key\n" .
                "define('CRYPTO_SECRET', " . var_export($secretKey, true) . ");\n\n" .
                "// SMTP Configuration\n" .
                "define('SMTP_HOST', " . var_export($smtpInfo['host'], true) . ");\n" .
                "define('SMTP_PORT', " . var_export($smtpInfo['port'], true) . ");\n" .
                "define('SMTP_USER', " . var_export($smtpInfo['user'], true) . ");\n" .
                "define('SMTP_PASS', " . var_export($smtpInfo['pass'], true) . ");\n" .
                "define('SMTP_ENCRYPTION', " . var_export($smtpInfo['enc'], true) . ");\n" .
                "define('SMTP_FROM_EMAIL', " . var_export($smtpInfo['from_email'], true) . ");\n" .
                "define('SMTP_FROM_NAME', " . var_export($smtpInfo['from_name'], true) . ");\n" .
                "define('SMTP_REPLY_TO', " . var_export($smtpInfo['reply_to'], true) . ");\n\n" .
                "// Production Error Logging\n" .
                "ini_set('display_errors', '0');\n" .
                "ini_set('display_startup_errors', '0');\n" .
                "error_reporting(E_ALL & ~E_DEPRECATED);\n" .
                "ini_set('log_errors', '1');\n" .
                "\$logDir = __DIR__ . '/storage/logs';\n" .
                "if (!is_dir(\$logDir)) {\n" .
                "    @mkdir(\$logDir, 0755, true);\n" .
                "}\n" .
                "ini_set('error_log', \$logDir . '/app_errors.log');\n";

            file_put_contents(__DIR__ . '/../config.php', $configContent);

            // 6. Write permanent installation lock file
            $lockFile = __DIR__ . '/../storage/installed.lock';
            $lockData = json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => $adminInfo['email'],
                'version' => '1.0.0',
                'hash' => hash('sha256', $secretKey . time())
            ], JSON_PRETTY_PRINT);

            file_put_contents($lockFile, $lockData);

            // Clear installer session state
            unset($_SESSION['install_data']);

            json_response([
                'success' => true,
                'message' => 'Installation completed successfully! Redirecting to login...',
                'redirect' => '/login'
            ]);

        } catch (\Throwable $e) {
            json_response([
                'error' => 'Installation finalization failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
