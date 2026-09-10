<?php
/**
 * xVault Enterprise Password Manager
 * Helper Utilities & Security Functions
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/config.php';

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
 * Send Transactional Email
 */
function send_app_email($toEmail, $subject, $htmlBody) {
    if (empty($toEmail)) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Reply-To: ' . SMTP_FROM_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];

    // Use native mail() function for cPanel/Apache compatibility
    $sent = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
    if (!$sent) {
        error_log("Failed to send email to {$toEmail}");
    }
    return $sent;
}
