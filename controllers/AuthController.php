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
        $verificationToken = bin2hex(random_bytes(32));

        $countStmt = $db->query('SELECT COUNT(*) as cnt FROM users');
        $userCount = (int)$countStmt->fetch()['cnt'];
        $role = ($userCount === 0) ? 'admin' : 'user';
        $isVerified = ($role === 'admin') ? 1 : 0;

        $insert = $db->prepare('INSERT INTO users (email, password_hash, role, name, is_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?)');
        $insert->execute([$email, $passwordHash, $role, $name, $isVerified, $verificationToken]);
        $userId = $db->lastInsertId();

        log_security_event('USER_REGISTERED', "New account created: {$email} ({$role})", $userId);

        if (!$isVerified) {
            $verifyLink = get_app_url('/verify-email?token=' . $verificationToken);
            $body = "<p>Hi <strong>" . e($name) . "</strong>,</p><p>Thank you for creating an account on xVault Enterprise Password Manager. Please verify your email address to activate your account access.</p>";
            send_user_transactional_email($email, 'Verify Your xVault Account', 'Activate Your xVault Account', $body, $verifyLink, 'Verify Email Address', 'If you did not create this account, please ignore this email.');
            send_admin_security_notification('NEW_USER_REGISTERED', "New account registered: {$email} ({$name})");
        }

        json_response([
            'success' => true,
            'message' => $isVerified ? 'Admin account created successfully. You can log in immediately.' : 'Registration successful! Verification email sent.'
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

    public static function verifyEmail() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_REQUEST;
        $token = trim($input['token'] ?? '');

        if (empty($token)) {
            json_response(['error' => 'Verification token is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, email, name FROM users WHERE verification_token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            json_response(['error' => 'Invalid or expired verification token'], 400);
        }

        $update = $db->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?');
        $update->execute([$user['id']]);

        log_security_event('EMAIL_VERIFIED', "Email verified for {$user['email']}", $user['id']);
        send_admin_security_notification('USER_EMAIL_VERIFIED', "User account activated and email verified: {$user['email']}");

        json_response(['success' => true, 'message' => 'Email verified successfully! You can now log in.']);
    }

    public static function resendVerification() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = trim($input['email'] ?? '');

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_verified = 0');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            json_response(['error' => 'User not found or already verified'], 404);
        }

        $token = bin2hex(random_bytes(32));
        $update = $db->prepare('UPDATE users SET verification_token = ? WHERE id = ?');
        $update->execute([$token, $user['id']]);

        $verifyLink = get_app_url('/verify-email?token=' . $token);
        $body = "<p>Hi <strong>" . e($user['name']) . "</strong>,</p><p>Please verify your email address to complete your account setup and unlock your vault.</p>";
        send_user_transactional_email($email, 'Verify Your xVault Account', 'Email Verification Request', $body, $verifyLink, 'Verify Email Address', 'This link is unique to your account.');

        json_response(['success' => true, 'message' => 'Verification email resent']);
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
            $up = $db->prepare('UPDATE users SET name = ?, email = ?, is_verified = 0, verification_token = ? WHERE id = ?');
            $up->execute([$name, $email, $vToken, $user['id']]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            log_security_event('PROFILE_UPDATED', "Updated email to {$email}", $user['id']);

            json_response(['success' => true, 'message' => 'Profile updated. Please verify your new email address.', 'reverify' => true]);
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
