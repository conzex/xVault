<?php
/**
 * xVault Enterprise Password Manager
 * Production Configuration
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_NAME', 'xVault');
define('APP_VERSION', '2.0');

// Domain / Base URL configuration ('auto' dynamically auto-detects current host, domain, & subfolder)
define('APP_URL_OVERRIDE', getenv('APP_URL') ?: 'auto');
if (!defined('APP_URL')) {
    define('APP_URL', function_exists('get_app_url') ? get_app_url() : '');
}

// Database Configuration
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'xvault_db');
define('DB_USER', getenv('DB_USER') ?: 'xvault_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// SQLite Settings (Fallback)
define('SQLITE_FILE', __DIR__ . '/storage/xvault.db');

// Security & Encryption Secret Key
define('CRYPTO_SECRET', getenv('CRYPTO_SECRET') ?: 'c8a2e5d9f1b4a3c7e0d6f2a8b4c1e5f9d2a6b0c4e8f1a3b5c7d9e1f2a4b6c8d0');

// SMTP Configuration (Optional but Recommended)
define('SMTP_ENABLED', filter_var(getenv('SMTP_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'xVault Security');
define('SMTP_REPLY_TO', getenv('SMTP_REPLY_TO') ?: '');

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
