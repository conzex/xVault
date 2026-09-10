<?php
/**
 * xVault Enterprise Password Manager
 * Production Configuration
 */

// Prevent direct file access
if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

// Application Environment ('production' or 'development')
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_NAME', 'xVault');
define('APP_VERSION', '1.0.0');

// Domain / Base URL configuration
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
define('APP_URL', getenv('APP_URL') ?: ($protocol . '://' . $host . ($scriptDir ? $scriptDir : '')));

// Database Configuration (Default: SQLite for zero-config fallback, or MySQL for cPanel)
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite'); // 'sqlite' or 'mysql'

// MySQL Settings (used if DB_DRIVER === 'mysql')
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'xvault');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// SQLite Settings (used if DB_DRIVER === 'sqlite')
define('SQLITE_FILE', __DIR__ . '/storage/xvault.db');

// Security & Encryption Key (Must be 32 bytes / 64 hex characters in production)
$secretKey = getenv('CRYPTO_SECRET') ?: 'c8a2e5d9f1b4a3c7e0d6f2a8b4c1e5f9d2a6b0c4e8f1a3b5c7d9e1f2a4b6c8d0';
define('CRYPTO_SECRET', $secretKey);

// SMTP Mailer Configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: '587');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'no-reply@xvault.local');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'xVault Security');

// Error Handling Configuration
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Error Logger
ini_set('log_errors', '1');
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app_errors.log');
