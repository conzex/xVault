<?php
/**
 * xVault Enterprise Password Manager
 * Production Configuration
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

define('APP_ENV', 'production');
define('APP_NAME', 'xVault');
define('APP_VERSION', '1.0.0');
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// Database Configuration
define('DB_DRIVER', 'sqlite');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'xvault');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SQLite Settings
define('SQLITE_FILE', __DIR__ . '/storage/xvault.db');

// Security & Encryption Secret Key
define('CRYPTO_SECRET', 'f4fa9c0916fa3df85516da63bba2bf92baade26df9211b64f133e29ac0329c93');

// SMTP Configuration
define('SMTP_HOST', '127.0.0.1');
define('SMTP_PORT', 587);
define('SMTP_USER', 'smtp@conzex.com');
define('SMTP_PASS', 'pass');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'vault@conzex.com');
define('SMTP_FROM_NAME', 'xVault Security');
define('SMTP_REPLY_TO', 'vault@conzex.com');

// Production Error Logging
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('log_errors', '1');
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app_errors.log');
