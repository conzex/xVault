<?php
/**
 * xVault Enterprise Password Manager
 * Auth Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class AuthController {
    private static $abusiveEmails = ['spam', 'abuse', 'root', 'webmaster', 'support'];
    private static $restrictedPasswords = ['admin', 'root', 'password', '123456', 'password123'];

    public static function handleRequest($action) {
        switch ($action) {
            case 'login':
                self::login();
                break;
            case 'register':
                self::register();
                break;
            case 'logout':
                self::logout();
                break;
            case 'lock':
                self::lock();
                break;
            case 'verify-email':
                self::verifyEmail();
                break;
            case 'resend-verification':
                self::resendVerification();
                break;
            case 'forgot-password':
                self::forgotPassword();
                break;
            case 'reset-password':
                self::resetPassword();
                break;
            case 'update-profile':
                self::updateProfile();
                break;
            case 'change-password':
                self::changePassword();
                break;
            case 'me':
                self::me();
                break;
            default:
                json_response(['error' => 'Invalid action'], 400);
        }
    }

    public static function login() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            json_response(['error' => 'Email and password are required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !verify_master_password($password, $user['password_hash'])) {
            log_security_event('LOGIN_FAILED', "Failed login attempt for email: {$email}", $user['id'] ?? null);
            json_response(['error' => 'Invalid email or password'], 401);
        }

        if ($user['role'] !== 'admin' && empty($user['is_verified'])) {
            log_security_event('LOGIN_FAILED', "Unverified account login attempt: {$email}", $user['id']);
            json_response(['error' => 'Please verify your email address before logging in', 'unverified' => true], 403);
        }

        init_session();
        regenerate_session();

        $sessionUser = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?: $user['email'],
            'role' => $user['role']
        ];
        $_SESSION['user'] = $sessionUser;

        log_security_event('LOGIN_SUCCESS', "User logged in successfully", $user['id']);

        json_response([
            'success' => true,
            'user' => $sessionUser,
            'csrf_token' => csrf_token()
        ]);
    }

    public static function register() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $name = trim($input['name'] ?? '');

        if (empty($email) || empty($password) || empty($name)) {
            json_response(['error' => 'All fields are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Invalid email format'], 400);
        }

        $emailUser = explode('@', $email)[0];
        if (in_array($emailUser, self::$abusiveEmails)) {
            json_response(['error' => 'This email address username is restricted'], 400);
        }

        if (in_array(strtolower($password), self::$restrictedPasswords) || strlen($password) < 6) {
            json_response(['error' => 'Password is too weak or restricted'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            json_response(['error' => 'An account with this email already exists'], 400);
        }

        $passwordHash = hash_master_password($password);
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $tokenExpiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        $countStmt = $db->query('SELECT COUNT(*) as cnt FROM users');
        $userCount = (int)$countStmt->fetch()['cnt'];
        $role = ($userCount === 0) ? 'admin' : 'user';
        $isVerified = ($role === 'admin') ? 1 : 0;
        $status = ($role === 'admin') ? 'active' : 'pending_verification';
        $emailVerifiedAt = ($role === 'admin') ? date('Y-m-d H:i:s') : null;

        $insert = $db->prepare('INSERT INTO users (email, password_hash, role, name, status, is_verified, email_verified_at, verification_token, verification_token_hash, verification_token_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$email, $passwordHash, $role, $name, $status, $isVerified, $emailVerifiedAt, $rawToken, $tokenHash, $tokenExpiry]);
        $userId = $db->lastInsertId();

        log_security_event('USER_REGISTERED', "New account created: {$email} ({$role})", $userId);

        $emailSent = false;
        if (!$isVerified) {
            $verifyLink = get_app_url('/verify-email/' . $rawToken);
            $body = "<p>Hi <strong>" . e($name) . "</strong>,</p>" .
                    "<p>Thank you for creating an account on xVault Enterprise Password Manager. Please verify your email address to activate your account access and unlock your vault.</p>" .
                    "<p>This verification link is secure and valid for <strong>24 hours</strong>. If you did not create this account, no action is required.</p>" .
                    "<p style='word-break: break-all; margin-top: 15px;'><small>Alternative link: <a href='" . e($verifyLink) . "'>" . e($verifyLink) . "</a></small></p>";
            
            $emailSent = send_user_transactional_email($email, 'Verify Your xVault Account', 'Activate Your xVault Account', $body, $verifyLink, 'Verify Email Address', 'This link is unique to your account and expires in 24 hours.');
            send_admin_security_notification('NEW_USER_REGISTERED', "New account registered: {$email} ({$name})");
        }

        $responseMsg = $isVerified 
            ? 'Admin account created successfully. You can log in immediately.' 
            : ($emailSent 
                ? 'Registration successful! Verification email sent. Please check your inbox.' 
                : 'Registration successful! However, email delivery is currently unavailable/unconfigured. Please contact system administrator.');

        json_response([
            'success' => true,
            'email_sent' => $emailSent,
            'message' => $responseMsg
        ], 201);
    }

    public static function logout() {
        $user = current_user();
        if ($user) {
            log_security_event('LOGOUT', 'User logged out', $user['id']);
        }
        init_session();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        json_response(['success' => true]);
    }

    public static function lock() {
        $user = current_user();
        if ($user) {
            log_security_event('VAULT_LOCKED', 'Vault manually locked by user', $user['id']);
        }
        init_session();
        $_SESSION = [];
        session_destroy();
        json_response(['success' => true, 'message' => 'Vault locked']);
    }

    public static function verifyEmail($routeToken = null) {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_REQUEST;
        $token = trim($routeToken ?: ($input['token'] ?? ''));

        $status = 'invalid';
        $message = 'The verification link is invalid, malformed, revoked, or has already been used.';
        $userEmail = '';

        if (!empty($token)) {
            $tokenHash = hash('sha256', $token);
            $db = getDB();
            $stmt = $db->prepare('SELECT id, email, name, is_verified, verification_token_expiry FROM users WHERE verification_token_hash = ? OR verification_token = ?');
            $stmt->execute([$tokenHash, $token]);
            $user = $stmt->fetch();

            if ($user) {
                $userEmail = $user['email'];
                if (!empty($user['is_verified'])) {
                    $status = 'already_verified';
                    $message = 'Your email address has already been verified. You can now log in.';
                } elseif (!empty($user['verification_token_expiry']) && strtotime($user['verification_token_expiry']) < time()) {
                    $status = 'expired';
                    $message = 'Your email verification link has expired. Please request a new verification email below.';
                } else {
                    $update = $db->prepare("UPDATE users SET is_verified = 1, status = 'active', email_verified_at = CURRENT_TIMESTAMP, verification_token = NULL, verification_token_hash = NULL, verification_token_expiry = NULL WHERE id = ?");
                    $update->execute([$user['id']]);

                    log_security_event('EMAIL_VERIFIED', "Email verified for {$user['email']}", $user['id']);
                    send_admin_security_notification('USER_EMAIL_VERIFIED', "User account activated and email verified: {$user['email']}");

                    $status = 'success';
                    $message = 'Your email address has been successfully verified! You can now log in to your vault.';
                }
            }
        }

        if (is_api_request()) {
            $statusCode = ($status === 'success' || $status === 'already_verified') ? 200 : 400;
            json_response([
                'success' => ($status === 'success' || $status === 'already_verified'),
                'status' => $status,
                'message' => $message,
                'email' => $userEmail
            ], $statusCode);
        }

        // Web view rendering context
        $verificationResult = [
            'status' => $status,
            'message' => $message,
            'email' => $userEmail
        ];

        require __DIR__ . '/../templates/pages/verify_email.php';
        exit;
    }

    public static function resendVerification() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = strtolower(trim($input['email'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Please enter a valid email address'], 400);
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $db = getDB();

        // Rate Limit: Max 3 resends per 15 minutes per IP or Email
        $rateStmt = $db->prepare("SELECT COUNT(*) FROM security_logs WHERE event_type = 'RESEND_VERIFICATION' AND (ip_address = ? OR details LIKE ?) AND created_at > ?");
        $rateStmt->execute([$ip, "%{$email}%", date('Y-m-d H:i:s', time() - 900)]);
        $recentCount = (int)$rateStmt->fetchColumn();

        if ($recentCount >= 3) {
            log_security_event('RATE_LIMIT_EXCEEDED', "Verification resend rate limit exceeded for {$email}", null);
            json_response(['error' => 'Too many verification resend requests. Please wait 15 minutes before trying again.'], 429);
        }

        log_security_event('RESEND_VERIFICATION', "Resend verification request for {$email}", null);

        $stmt = $db->prepare('SELECT id, email, name, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Prevent email enumeration while appearing successful
            json_response(['success' => true, 'message' => 'If an unverified account with this email address exists, a verification link has been sent.']);
        }

        if (!empty($user['is_verified'])) {
            json_response([
                'success' => true, 
                'already_verified' => true, 
                'message' => 'Your email address has already been verified. You can now log in.'
            ]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $tokenExpiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        $update = $db->prepare('UPDATE users SET verification_token = ?, verification_token_hash = ?, verification_token_expiry = ? WHERE id = ?');
        $update->execute([$rawToken, $tokenHash, $tokenExpiry, $user['id']]);

        $verifyLink = get_app_url('/verify-email/' . $rawToken);
        $body = "<p>Hi <strong>" . e($user['name']) . "</strong>,</p>" .
                "<p>We received a request to resend your email verification link for xVault Enterprise Password Manager.</p>" .
                "<p>Please click the button below to complete your account activation. This link is valid for <strong>24 hours</strong>.</p>" .
                "<p style='word-break: break-all; margin-top: 15px;'><small>Alternative link: <a href='" . e($verifyLink) . "'>" . e($verifyLink) . "</a></small></p>";

        $sent = send_user_transactional_email($email, 'Verify Your xVault Account', 'Email Verification Request', $body, $verifyLink, 'Verify Email Address', 'This activation link is unique to your account and expires in 24 hours.');

        if ($sent) {
            json_response(['success' => true, 'message' => 'A new verification email has been sent. Please check your inbox.']);
        } else {
            json_response(['error' => 'Unable to send verification email. Email delivery is unavailable or SMTP is not configured.'], 503);
        }
    }

    public static function forgotPassword() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = trim($input['email'] ?? '');

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            json_response(['error' => 'If this email exists, a password reset link has been sent.'], 200);
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600);

        $update = $db->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
        $update->execute([$token, $expiry, $user['id']]);

        $resetLink = get_app_url('/reset-password?token=' . $token);
        $body = "<p>Hi <strong>" . e($user['name'] ?: $user['email']) . "</strong>,</p><p>A password reset request was submitted for your master password. Click the button below to set a new password. This link is valid for 1 hour.</p>";
        send_user_transactional_email($email, 'Reset Your Master Password', 'Master Password Reset Request', $body, $resetLink, 'Reset Master Password', 'If you did not request a password reset, your account remains secure and no action is required.');

        log_security_event('PASSWORD_RESET_REQUEST', "Password reset link requested for {$email}", $user['id']);
        send_admin_security_notification('PASSWORD_RESET_REQUESTED', "Password reset link requested for account: {$email}");

        json_response(['success' => true, 'message' => 'Password reset email sent']);
    }

    public static function resetPassword() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $token = trim($input['token'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($token) || empty($password)) {
            json_response(['error' => 'Token and new password are required'], 400);
        }

        if (in_array(strtolower($password), self::$restrictedPasswords) || strlen($password) < 6) {
            json_response(['error' => 'New password is too weak or restricted'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > ?');
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        $user = $stmt->fetch();

        if (!$user) {
            json_response(['error' => 'Invalid or expired reset token'], 400);
        }

        $passwordHash = hash_master_password($password);
        $update = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
        $update->execute([$passwordHash, $user['id']]);

        log_security_event('PASSWORD_RESET_SUCCESS', 'Master password reset via email link', $user['id']);

        $body = "<p>Hi <strong>" . e($user['name'] ?: $user['email']) . "</strong>,</p><p>Your master password has been reset successfully. You can now log into your vault with your new password.</p>";
        send_user_transactional_email($user['email'], 'Master Password Changed Confirmation', 'Master Password Reset Successful', $body, get_app_url('/login'), 'Log In to Vault', 'If you did not perform this password reset, contact your system administrator immediately.');
        send_admin_security_notification('PASSWORD_RESET_SUCCESSFUL', "User master password was reset via email token for: {$user['email']}");

        json_response(['success' => true, 'message' => 'Password reset successfully. You can now log in with your new password.']);
    }

    public static function updateProfile() {
        $user = require_auth();
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $name = trim($input['name'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));

        if (empty($name)) {
            json_response(['error' => 'Name cannot be empty'], 400);
        }

        $db = getDB();
        if (!empty($email) && $email !== $user['email']) {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                json_response(['error' => 'Email is already in use'], 400);
            }

            $vToken = bin2hex(random_bytes(32));
            $vHash = hash('sha256', $vToken);
            $vExpiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            $up = $db->prepare('UPDATE users SET name = ?, email = ?, is_verified = 0, verification_token = ?, verification_token_hash = ?, verification_token_expiry = ? WHERE id = ?');
            $up->execute([$name, $email, $vToken, $vHash, $vExpiry, $user['id']]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            log_security_event('PROFILE_UPDATED', "Updated email to {$email}", $user['id']);

            $verifyLink = get_app_url('/verify-email/' . $vToken);
            $body = "<p>Hi <strong>" . e($name) . "</strong>,</p>" .
                    "<p>You recently updated your account email address to <strong>" . e($email) . "</strong>.</p>" .
                    "<p>Please verify your new email address to maintain full access to your vault. This link is valid for <strong>24 hours</strong>.</p>" .
                    "<p style='word-break: break-all; margin-top: 15px;'><small>Alternative link: <a href='" . e($verifyLink) . "'>" . e($verifyLink) . "</a></small></p>";
            send_user_transactional_email($email, 'Verify Your New Email Address', 'Email Address Change Verification', $body, $verifyLink, 'Verify New Email', 'This link is unique to your account.');

            json_response(['success' => true, 'message' => 'Profile updated. A verification link has been sent to your new email address.', 'reverify' => true]);
        } else {
            $up = $db->prepare('UPDATE users SET name = ? WHERE id = ?');
            $up->execute([$name, $user['id']]);

            $_SESSION['user']['name'] = $name;
            log_security_event('PROFILE_UPDATED', "Updated profile name to {$name}", $user['id']);
            json_response(['success' => true, 'message' => 'Profile updated successfully']);
        }
    }

    public static function changePassword() {
        $user = require_auth();
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $currentPassword = $input['currentPassword'] ?? '';
        $newPassword = $input['newPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            json_response(['error' => 'Current password and new password are required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!$row || !verify_master_password($currentPassword, $row['password_hash'])) {
            log_security_event('PASSWORD_CHANGE_FAILED', 'Incorrect current master password attempt', $user['id']);
            json_response(['error' => 'Incorrect current master password'], 401);
        }

        if (in_array(strtolower($newPassword), self::$restrictedPasswords) || strlen($newPassword) < 6) {
            json_response(['error' => 'New password is too weak or restricted'], 400);
        }

        $newHash = hash_master_password($newPassword);
        $up = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $up->execute([$newHash, $user['id']]);

        log_security_event('PASSWORD_CHANGED', 'Master password changed successfully', $user['id']);

        $body = "<p>Hi <strong>" . e($user['name'] ?: $user['email']) . "</strong>,</p><p>Your master password was updated successfully from your account dashboard.</p>";
        send_user_transactional_email($user['email'], 'Master Password Changed Confirmation', 'Master Password Updated', $body, get_app_url('/login'), 'Log In to Vault', 'If you did not make this change, contact your system administrator immediately.');
        send_admin_security_notification('USER_PASSWORD_CHANGED', "User changed master password: {$user['email']}");

        json_response(['success' => true, 'message' => 'Master password updated successfully']);
    }

    public static function me() {
        $user = current_user();
        if (!$user) {
            json_response(['authenticated' => false], 401);
        }
        json_response(['authenticated' => true, 'user' => $user]);
    }
}
