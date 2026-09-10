<?php
/**
 * xVault Enterprise Password Manager
 * Helper Utilities & Security Functions
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

/**
 * Automatically detect and resolve current canonical Application Base URL
 * Handles HTTP/HTTPS, custom domains, subdomains, non-standard ports, and subfolder deployments.
 */
function get_app_url($path = '') {
    static $detectedUrl = null;

    if ($detectedUrl === null) {
        if (defined('APP_URL_OVERRIDE') && APP_URL_OVERRIDE !== '' && APP_URL_OVERRIDE !== 'auto') {
            $detectedUrl = rtrim(APP_URL_OVERRIDE, '/');
        } else {
            // Detect HTTPS / Scheme
            $isHttps = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
                       (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
                       (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            $scheme = $isHttps ? 'https' : 'http';

            // Detect Host & Port safely (Sanitize to prevent Host Header Injection)
            $rawHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            if (preg_match('/^[a-zA-Z0-9\.\-\:]+$/', $rawHost)) {
                $host = $rawHost;
            } else {
                $host = 'localhost';
            }

            // Detect Base Path for Subdirectories / Subfolders
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = rtrim(dirname($scriptName), '/\\');
            $baseDir = ($dir === '/' || $dir === '\\') ? '' : $dir;

            $detectedUrl = "{$scheme}://{$host}{$baseDir}";
        }
    }

    if ($path !== '') {
        return rtrim($detectedUrl, '/') . '/' . ltrim($path, '/');
    }

    return $detectedUrl;
}

if (!defined('APP_URL')) {
    define('APP_URL', get_app_url());
}

require_once __DIR__ . '/config.php';

/**
 * Dynamic System Settings Engine
 * Retrieves settings from database system_settings table with runtime memory caching
 */
function get_system_setting($key, $default = null) {
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $db = getDB();
            $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
            if ($stmt) {
                $cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        } catch (\Throwable $e) {
            $cache = [];
        }
    }

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    return $default;
}

/**
 * Set or Update System Setting in Database
 */
function set_system_setting($key, $value) {
    try {
        $db = getDB();
        if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
        } else {
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }
        return $stmt->execute([$key, (string)$value]);
    } catch (\Throwable $e) {
        error_log("set_system_setting error: " . $e->getMessage());
        return false;
    }
}



/**
 * Initialize secure PHP session
 */
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        session_set_cookie_params([
            'lifetime' => 7 * 24 * 60 * 60, // 7 days
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        
        session_start();
    }
}

/**
 * Regenerate Session ID safely after authentication
 */
function regenerate_session() {
    init_session();
    $oldData = $_SESSION;
    session_regenerate_id(true);
    $_SESSION = $oldData;
}

/**
 * Escape HTML strings for XSS Prevention
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get CSRF Token for Forms
 */
function csrf_token() {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    init_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Encrypt vault entry password using AES-256-CBC
 */
function encrypt_data($plaintext) {
    $rawKey = hex2bin(CRYPTO_SECRET);
    if (strlen($rawKey) !== 32) {
        // Fallback key hash if hex string isn't exactly 64 hex chars
        $rawKey = hash('sha256', CRYPTO_SECRET, true);
    }
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $rawKey, OPENSSL_RAW_DATA, $iv);
    return bin2hex($iv) . ':' . bin2hex($ciphertext);
}

/**
 * Decrypt vault entry password using AES-256-CBC
 */
function decrypt_data($encryptedText) {
    try {
        $parts = explode(':', $encryptedText);
        if (count($parts) !== 2) {
            return '********';
        }
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        $rawKey = hex2bin(CRYPTO_SECRET);
        if (strlen($rawKey) !== 32) {
            $rawKey = hash('sha256', CRYPTO_SECRET, true);
        }
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $rawKey, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : '********';
    } catch (\Throwable $e) {
        error_log('Decryption exception: ' . $e->getMessage());
        return '********';
    }
}

/**
 * Secure password hashing using bcrypt
 */
function hash_master_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verify_master_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get current logged in user array or null
 */
function current_user() {
    init_session();
    return $_SESSION['user'] ?? null;
}

/**
 * Require user authentication
 */
function require_auth() {
    $user = current_user();
    if (!$user) {
        if (is_api_request()) {
            json_response(['error' => 'Unauthorized access'], 401);
        } else {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }
    return $user;
}

/**
 * Require admin role
 */
function require_admin() {
    $user = require_auth();
    if (($user['role'] ?? '') !== 'admin') {
        if (is_api_request()) {
            json_response(['error' => 'Admin privileges required'], 403);
        } else {
            set_flash('error', 'Access denied. Administrator privileges required.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }
    return $user;
}

/**
 * Detect API Request
 */
function is_api_request() {
    return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
}

/**
 * Standard JSON Response
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Flash message setter
 */
function set_flash($type, $message) {
    init_session();
    $_SESSION['flash'][$type] = $message;
}

/**
 * Flash message getter & clearer
 */
function get_flash($type = null) {
    init_session();
    if ($type) {
        $msg = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Render Centralized Branded Email Template
 */
function render_email_template($title, $bodyHtml, $ctaUrl = null, $ctaText = null, $securityNote = null) {
    $appUrl = get_app_url();
    $appName = defined('APP_NAME') ? APP_NAME : 'xVault';
    $fullTitle = e($title);

    $ctaButtonHtml = '';
    if (!empty($ctaUrl) && !empty($ctaText)) {
        $ctaButtonHtml = '
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 28px 0 20px 0;">
            <tr>
                <td align="center" style="border-radius: 8px; background: #D32F2F;">
                    <a href="' . e($ctaUrl) . '" target="_blank" style="border: 1px solid #D32F2F; border-radius: 8px; color: #ffffff; display: inline-block; font-size: 14px; font-weight: 700; padding: 12px 28px; text-decoration: none;">
                        ' . e($ctaText) . '
                    </a>
                </td>
            </tr>
        </table>';
    }

    $securityBoxHtml = '';
    if (!empty($securityNote)) {
        $securityBoxHtml = '
        <div style="background-color: #FEF2F2; border-left: 4px solid #D32F2F; border-radius: 6px; padding: 14px 18px; margin: 24px 0;">
            <p style="margin: 0; font-size: 13px; font-weight: 600; color: #991B1B;">Security Notice</p>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #7F1D1D; line-height: 1.5;">' . e($securityNote) . '</p>
        </div>';
    }

    $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $fullTitle . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #1E293B; -webkit-font-smoothing: antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="background-color: #FFFFFF; padding: 24px 32px; border-bottom: 1px solid #F1F5F9;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left">
                                        <div style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px;">
                                            <span style="color: #D32F2F;">x</span>Vault
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 800; color: #0F172A; line-height: 1.3;">' . $fullTitle . '</h1>
                            <div style="font-size: 14px; line-height: 1.6; color: #334155;">
                                ' . $bodyHtml . '
                            </div>
                            ' . $ctaButtonHtml . '
                            ' . $securityBoxHtml . '
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #F8FAFC; padding: 24px 32px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #64748B; line-height: 1.5;">
                            <p style="margin: 0 0 6px 0; font-weight: 600; color: #475569;">' . e($appName) . ' Enterprise Password Manager</p>
                            <p style="margin: 0 0 8px 0;">Automated security notification from <a href="' . e($appUrl) . '" style="color: #D32F2F; text-decoration: none;" target="_blank">' . e($host) . '</a>. Do not reply to this email.</p>
                            <p style="margin: 0; font-size: 11px; color: #94A3B8;">&copy; ' . date('Y') . ' ' . e($appName) . '. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Dynamic SMTP Configuration Loader
 * Reads stored SMTP settings from database system_settings table with fallback to config.php
 */
function get_smtp_config() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'");
        $dbSettings = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    } catch (\Throwable $e) {
        $dbSettings = [];
    }

    $enabled = isset($dbSettings['smtp_enabled'])
        ? filter_var($dbSettings['smtp_enabled'], FILTER_VALIDATE_BOOLEAN)
        : (defined('SMTP_ENABLED') ? filter_var(SMTP_ENABLED, FILTER_VALIDATE_BOOLEAN) : false);

    $host = $dbSettings['smtp_host'] ?? (defined('SMTP_HOST') ? SMTP_HOST : '');
    $port = isset($dbSettings['smtp_port']) ? (int)$dbSettings['smtp_port'] : (defined('SMTP_PORT') ? (int)SMTP_PORT : 587);
    $user = $dbSettings['smtp_user'] ?? (defined('SMTP_USER') ? SMTP_USER : '');
    $pass = $dbSettings['smtp_pass'] ?? (defined('SMTP_PASS') ? SMTP_PASS : '');
    $enc = $dbSettings['smtp_enc'] ?? $dbSettings['smtp_encryption'] ?? (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls');
    $fromEmail = $dbSettings['smtp_from_email'] ?? (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '');
    $fromName = $dbSettings['smtp_from_name'] ?? (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'xVault Security');

    return [
        'enabled' => $enabled,
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'pass' => $pass,
        'encryption' => $enc,
        'from_email' => $fromEmail,
        'from_name' => $fromName
    ];
}

/**
 * Deep Socket & Authentication Test for SMTP Server
 */
function test_smtp_connection($testEmail = null) {
    $cfg = get_smtp_config();

    if (empty($cfg['host']) || empty($cfg['from_email'])) {
        return [
            'success' => false,
            'status' => 'not_configured',
            'message' => 'SMTP Host and From Email must be configured.'
        ];
    }

    $protocol = (strtolower($cfg['encryption']) === 'ssl') ? 'ssl://' : '';
    $host = $cfg['host'];
    $port = $cfg['port'];

    $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, 8);
    if (!$socket) {
        $tip = "";
        if ($errno === 110 || strpos(strtolower($errstr), 'timed out') !== false) {
            $tip = " (Connection timed out. Check if port {$port} is open and correct on your mail server. Standard SMTP ports are 587 for TLS/STARTTLS, 465 for SSL, or 25 for plain text.)";
        } elseif ($errno === 111 || strpos(strtolower($errstr), 'refused') !== false) {
            $tip = " (Connection refused on port {$port}. Verify SMTP server status.)";
        }
        return [
            'success' => false,
            'status' => 'connection_failed',
            'message' => "SMTP Connection failed to {$host}:{$port} - {$errstr} (code {$errno})" . $tip
        ];
    }
    stream_set_timeout($socket, 8);

    fgets($socket, 512);
    $clientHost = gethostname() ?: 'localhost';
    fputs($socket, "EHLO {$clientHost}\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    $enc = strtolower($cfg['encryption']);
    if (in_array($enc, ['tls', 'starttls'])) {
        fputs($socket, "STARTTLS\r\n");
        $resp = fgets($socket, 512);
        if (substr($resp, 0, 3) === '220') {
            $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$cryptoOk) {
                fclose($socket);
                return [
                    'success' => false,
                    'status' => 'tls_failed',
                    'message' => 'TLS/SSL handshake negotiation failed.'
                ];
            }
            fputs($socket, "EHLO {$clientHost}\r\n");
            while ($line = fgets($socket, 512)) {
                if (substr($line, 3, 1) === ' ') break;
            }
        }
    }

    if (!empty($cfg['user']) && !empty($cfg['pass'])) {
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($cfg['user']) . "\r\n");
        fgets($socket, 512);
        fputs($socket, base64_encode($cfg['pass']) . "\r\n");
        $authRes = fgets($socket, 512);
        if (substr($authRes, 0, 3) !== '235') {
            fclose($socket);
            return [
                'success' => false,
                'status' => 'auth_failed',
                'message' => 'SMTP Authentication failed: ' . trim($authRes)
            ];
        }
    }

    if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $body = render_email_template('SMTP Diagnostic Test', '<p>This is a live test email sent from xVault Configuration System.</p>', get_app_url('/config'), 'Open Configuration', 'Your SMTP server is configured and connected successfully.');
        fputs($socket, "MAIL FROM:<{$cfg['from_email']}>\r\n");
        fgets($socket, 512);
        fputs($socket, "RCPT TO:<{$testEmail}>\r\n");
        fgets($socket, 512);
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);
        $headersStr = "MIME-Version: 1.0\r\n" .
                      "Content-Type: text/html; charset=UTF-8\r\n" .
                      "From: {$cfg['from_name']} <{$cfg['from_email']}>\r\n" .
                      "To: {$testEmail}\r\n" .
                      "Subject: xVault SMTP Connectivity Test\r\n" .
                      "Date: " . date('r') . "\r\n\r\n";
        fputs($socket, $headersStr . $body . "\r\n.\r\n");
        fgets($socket, 512);
        log_email_delivery($testEmail, 'xVault SMTP Connectivity Test', 'test', 'sent');
    }

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return [
        'success' => true,
        'status' => 'success',
        'message' => !empty($testEmail) ? "SMTP connection, authentication, and test email delivery to {$testEmail} succeeded!" : "SMTP connection and authentication verified successfully!"
    ];
}

/**
 * Send Transactional Email with SMTP Check
 */
function send_app_email($toEmail, $subject, $htmlBody) {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        log_email_delivery($toEmail ?? 'invalid', $subject, 'transactional', 'failed', 'Invalid recipient email address');
        return false;
    }

    $cfg = get_smtp_config();

    // Check if SMTP is enabled & configured
    if (!$cfg['enabled'] || empty($cfg['host'])) {
        error_log("SMTP disabled: Email sending to {$toEmail} skipped.");
        log_email_delivery($toEmail, $subject, 'transactional', 'failed', 'SMTP is not enabled or configured in application settings');
        return false;
    }

    $fromName = !empty($cfg['from_name']) ? $cfg['from_name'] : 'xVault Security';
    $fromEmail = !empty($cfg['from_email']) ? $cfg['from_email'] : 'vault@' . (parse_url(get_app_url(), PHP_URL_HOST) ?: 'localhost');

    // Local / Sendmail mode
    if (in_array(strtolower($cfg['host']), ['localhost', '127.0.0.1'])) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            'X-Mailer: xVault-Mailer/' . (defined('APP_VERSION') ? APP_VERSION : '1.0.0')
        ];
        $res = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        log_email_delivery($toEmail, $subject, 'transactional', $res ? 'sent' : 'failed', $res ? null : 'sendmail mail() function returned false');
        return $res;
    }

    // TCP Socket SMTP Delivery
    try {
        $protocol = (strtolower($cfg['encryption']) === 'ssl') ? 'ssl://' : '';
        $targetHost = $cfg['host'];
        $port = (int)$cfg['port'];

        $socket = @fsockopen($protocol . $targetHost, $port, $errno, $errstr, 8);
        if (!$socket) {
            error_log("SMTP socket connection to {$targetHost}:{$port} failed - {$errstr}");
            log_email_delivery($toEmail, $subject, 'transactional', 'failed', "SMTP connection to {$targetHost}:{$port} failed - {$errstr}");
            return false;
        }

        fgets($socket, 512);
        $clientHost = gethostname() ?: 'localhost';
        fputs($socket, "EHLO {$clientHost}\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) === ' ') break;
        }

        $enc = strtolower($cfg['encryption']);
        if (in_array($enc, ['tls', 'starttls'])) {
            fputs($socket, "STARTTLS\r\n");
            $resp = fgets($socket, 512);
            if (substr($resp, 0, 3) === '220') {
                @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
                fputs($socket, "EHLO {$clientHost}\r\n");
                while ($line = fgets($socket, 512)) {
                    if (substr($line, 3, 1) === ' ') break;
                }
            }
        }

        if (!empty($cfg['user']) && !empty($cfg['pass'])) {
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($cfg['user']) . "\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($cfg['pass']) . "\r\n");
            $authRes = fgets($socket, 512);
            if (substr($authRes, 0, 3) !== '235') {
                fclose($socket);
                error_log("SMTP Auth failed for user " . $cfg['user']);
                log_email_delivery($toEmail, $subject, 'transactional', 'failed', "SMTP Authentication failed: " . trim($authRes));
                return false;
            }
        }

        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        fgets($socket, 512);
        fputs($socket, "RCPT TO:<{$toEmail}>\r\n");
        fgets($socket, 512);
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $headersStr = "MIME-Version: 1.0\r\n" .
                      "Content-Type: text/html; charset=UTF-8\r\n" .
                      "From: {$fromName} <{$fromEmail}>\r\n" .
                      "To: {$toEmail}\r\n" .
                      "Subject: {$subject}\r\n" .
                      "Date: " . date('r') . "\r\n" .
                      "X-Mailer: xVault Security Mailer\r\n\r\n";

        fputs($socket, $headersStr . $htmlBody . "\r\n.\r\n");
        fgets($socket, 512);
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        log_email_delivery($toEmail, $subject, 'transactional', 'sent');
        return true;
    } catch (\Throwable $e) {
        error_log("SMTP exception sending email to {$toEmail}: " . $e->getMessage());
        log_email_delivery($toEmail, $subject, 'transactional', 'failed', $e->getMessage());
        return false;
    }
}

/**
 * Log Email Delivery Attempt in Database
 */
function log_email_delivery($toEmail, $subject, $emailType = 'transactional', $status = 'sent', $errorMessage = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO email_logs (to_email, subject, email_type, status, error_message) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$toEmail, $subject, $emailType, $status, $errorMessage]);
    } catch (\Throwable $e) {
        error_log("Email log write exception: " . $e->getMessage());
    }
}

/**
 * Send Super Administrator Security Notification
 */
function send_admin_security_notification($eventType, $details = '') {
    try {
        if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
            return false;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT email FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
        $stmt->execute();
        $adminEmail = $stmt->fetchColumn();

        if (empty($adminEmail)) {
            return false;
        }

        $title = "Security Event: " . e($eventType);
        $body = "<p>A critical security event has occurred on your xVault installation:</p>" .
                "<div style='background: #F1F5F9; padding: 14px; border-radius: 8px; font-family: monospace; margin: 16px 0;'>" .
                "<strong>Event Type:</strong> " . e($eventType) . "<br>" .
                "<strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "<br>" .
                "<strong>IP Address:</strong> " . e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . "<br>" .
                "<strong>Details:</strong> " . e($details) .
                "</div>";

        $html = render_email_template($title, $body, get_app_url('/security'), "Open Security Dashboard", "Review your Security Dashboard audit logs if this action was unexpected.");
        return send_app_email($adminEmail, "xVault Alert: {$eventType}", $html);
    } catch (\Throwable $e) {
        error_log("Admin notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Customer/User Transactional Email
 */
function send_user_transactional_email($toEmail, $subject, $title, $bodyContent, $ctaUrl = null, $ctaText = null, $securityNote = null) {
    $html = render_email_template($title, $bodyContent, $ctaUrl, $ctaText, $securityNote);
    return send_app_email($toEmail, $subject, $html);
}

/**
 * Log Security Event in Database Audit Log
 */
function log_security_event($eventType, $details = null, $userId = null) {
    try {
        if ($userId === null) {
            $user = current_user();
            $userId = $user['id'] ?? null;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);

        $db = getDB();
        $stmt = $db->prepare('
            INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $eventType, $ip, $ua, $details]);
    } catch (\Throwable $e) {
        error_log("Security log write exception: " . $e->getMessage());
    }
}

/**
 * Check if application installation has been completed & locked
 */
function is_installed() {
    return file_exists(__DIR__ . '/storage/installed.lock');
}

