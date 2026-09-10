<?php
/**
 * xVault Enterprise Password Manager
 * Platform Configuration Controller (Super Administrator Only)
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class ConfigController {
    public static function handleRequest($action) {
        $user = require_admin();

        switch ($action) {
            case 'stats':
                self::getPlatformStats();
                break;
            case 'shared-links':
                self::getSharedLinks();
                break;
            case 'revoke-link':
                self::revokeSharedLink();
                break;
            case 'users':
                self::getUsers();
                break;
            case 'toggle-user-status':
                self::toggleUserStatus();
                break;
            case 'update-settings':
                self::updateSettings();
                break;
            default:
                json_response(['error' => 'Invalid configuration action'], 400);
        }
    }

    /**
     * Get 100% Database-Driven Platform Statistics
     */
    public static function getPlatformStats() {
        $db = getDB();

        // 1. User Metrics
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $inactiveUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status != 'active'")->fetchColumn();

        // 2. Client / Folder Metrics
        $totalFolders = (int)$db->query("SELECT COUNT(*) FROM folders")->fetchColumn();
        $totalClients = (int)$db->query("SELECT COUNT(*) FROM folders WHERE customer_email IS NOT NULL AND customer_email != ''")->fetchColumn();

        // 3. Vault & Credentials Metrics
        $totalCredentials = (int)$db->query("SELECT COUNT(*) FROM password_entries")->fetchColumn();
        $totalNotes = (int)$db->query("SELECT COUNT(*) FROM secure_notes")->fetchColumn();
        $totalAddresses = (int)$db->query("SELECT COUNT(*) FROM addresses")->fetchColumn();

        // 4. Shared Links Metrics
        $now = date('Y-m-d H:i:s');
        $totalLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links")->fetchColumn();
        $activeLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE used = 0 AND (expires_at IS NULL OR expires_at > '{$now}')")->fetchColumn();
        $expiredLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE expires_at IS NOT NULL AND expires_at <= '{$now}' AND used = 0")->fetchColumn();
        $revokedLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE used = 1")->fetchColumn();

        // 5. Email Delivery Metrics
        $totalEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
        $successfulEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'sent'")->fetchColumn();
        $failedEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'")->fetchColumn();

        // 6. Security & Audit Metrics
        $totalSecurityEvents = (int)$db->query("SELECT COUNT(*) FROM security_logs")->fetchColumn();
        $criticalEvents = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE event_type LIKE '%CRITICAL%' OR event_type LIKE '%FAILED%' OR event_type LIKE '%LOCKOUT%'")->fetchColumn();
        $failedLogins = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE event_type = 'LOGIN_FAILED'")->fetchColumn();
        $passwordResets = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE event_type LIKE '%RESET%'")->fetchColumn();

        json_response([
            'success' => true,
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers
                ],
                'clients' => [
                    'total' => $totalClients,
                    'folders' => $totalFolders
                ],
                'vault' => [
                    'credentials' => $totalCredentials,
                    'notes' => $totalNotes,
                    'addresses' => $totalAddresses
                ],
                'shared_links' => [
                    'total' => $totalLinks,
                    'active' => $activeLinks,
                    'expired' => $expiredLinks,
                    'revoked' => $revokedLinks
                ],
                'emails' => [
                    'total' => $totalEmails,
                    'successful' => $successfulEmails,
                    'failed' => $failedEmails,
                    'smtp_enabled' => defined('SMTP_ENABLED') ? SMTP_ENABLED : false
                ],
                'security' => [
                    'total_events' => $totalSecurityEvents,
                    'critical_events' => $criticalEvents,
                    'failed_logins' => $failedLogins,
                    'password_resets' => $passwordResets
                ],
                'system' => [
                    'app_version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
                    'db_version' => defined('APP_DB_VERSION') ? APP_DB_VERSION : '1.1.0',
                    'php_version' => phpversion(),
                    'db_driver' => defined('DB_DRIVER') ? DB_DRIVER : 'mysql',
                    'server_time' => date('Y-m-d H:i:s T')
                ]
            ]
        ]);
    }

    /**
     * Get All Shared Links for Administration (No Secret Exposure)
     */
    public static function getSharedLinks() {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT s.id, s.token, s.target_email, s.expires_at, s.one_time, s.used, s.created_at,
                   u.name as owner_name, u.email as owner_email
            FROM shared_links s
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.created_at DESC
            LIMIT 100
        ");
        $stmt->execute();
        $links = $stmt->fetchAll();

        $now = date('Y-m-d H:i:s');
        $formatted = array_map(function($l) use ($now) {
            $isExpired = (!empty($l['expires_at']) && $l['expires_at'] <= $now);
            $isUsed = (!empty($l['used']) && (int)$l['used'] === 1);
            $status = $isUsed ? 'Revoked/Used' : ($isExpired ? 'Expired' : 'Active');

            return [
                'id' => (int)$l['id'],
                'token' => $l['token'],
                'share_url' => get_app_url('/share/' . $l['token']),
                'owner' => ($l['owner_name'] ?: $l['owner_email']) ?: 'System',
                'target_email' => $l['target_email'] ?: 'Public Link',
                'expires_at' => $l['expires_at'] ? date('M j, Y H:i', strtotime($l['expires_at'])) : 'Never',
                'one_time' => (bool)$l['one_time'],
                'status' => $status,
                'created_at' => date('M j, Y H:i', strtotime($l['created_at']))
            ];
        }, $links);

        json_response(['success' => true, 'links' => $formatted]);
    }

    /**
     * Revoke / Deactivate a Shared Link
     */
    public static function revokeSharedLink() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $linkId = (int)($input['id'] ?? 0);

        if (!$linkId) {
            json_response(['error' => 'Invalid shared link ID'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare("UPDATE shared_links SET used = 1, expires_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$linkId]);

        log_security_event('SHARED_LINK_REVOKED_ADMIN', "Shared link ID {$linkId} revoked by Super Admin");
        json_response(['success' => true, 'message' => 'Shared link revoked successfully.']);
    }

    /**
     * Get All Platform Users
     */
    public static function getUsers() {
        $db = getDB();
        $stmt = $db->query("SELECT id, username, email, name, role, status, is_verified, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll();

        json_response(['success' => true, 'users' => $users]);
    }

    /**
     * Toggle User Account Activation Status
     */
    public static function toggleUserStatus() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $userId = (int)($input['user_id'] ?? 0);
        $newStatus = trim($input['status'] ?? 'active');

        if (!$userId) {
            json_response(['error' => 'Invalid user ID'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch();

        if ($targetUser && $targetUser['role'] === 'admin' && $newStatus !== 'active') {
            json_response(['error' => 'Cannot deactivate the root Super Administrator account.'], 400);
        }

        $up = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $up->execute([$newStatus, $userId]);

        log_security_event('USER_STATUS_CHANGED', "User ID {$userId} status changed to {$newStatus}");
        json_response(['success' => true, 'message' => "User status updated to {$newStatus}."]);
    }

    /**
     * Update Central Platform Settings (Config & SMTP)
     */
    public static function updateSettings() {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;

        $enabled = !empty($input['smtp_enabled']);
        $host = trim($input['smtp_host'] ?? '');
        $port = (int)($input['smtp_port'] ?? 587);
        $user = trim($input['smtp_user'] ?? '');
        $pass = $input['smtp_pass'] ?? (defined('SMTP_PASS') ? SMTP_PASS : '');
        $enc = trim($input['smtp_enc'] ?? 'tls');
        $fromEmail = trim($input['smtp_from_email'] ?? '');
        $fromName = trim($input['smtp_from_name'] ?? 'xVault Security');
        $testEmail = trim($input['test_email'] ?? '');

        if ($enabled && (empty($host) || empty($fromEmail))) {
            json_response(['error' => 'SMTP Host and From Email are required when SMTP is enabled.'], 400);
        }

        // 1. Save to Database system_settings
        try {
            $db = getDB();
            $settingsToSave = [
                'smtp_enabled' => $enabled ? 'true' : 'false',
                'smtp_host' => $host,
                'smtp_port' => (string)$port,
                'smtp_user' => $user,
                'smtp_pass' => $pass,
                'smtp_enc' => $enc,
                'smtp_from_email' => $fromEmail,
                'smtp_from_name' => $fromName
            ];

            foreach ($settingsToSave as $k => $v) {
                if (DB_DRIVER === 'sqlite') {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
                } else {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                }
                $stmt->execute([$k, $v]);
            }
        } catch (\Throwable $e) {
            error_log("DB updateSettings warning: " . $e->getMessage());
        }

        // 2. Sync to config.php file
        $configPath = __DIR__ . '/../config.php';
        if (file_exists($configPath)) {
            $configStr = file_get_contents($configPath);
            $configStr = preg_replace("/define\('SMTP_ENABLED',\s*[^)]+\);/", "define('SMTP_ENABLED', " . var_export($enabled, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_HOST',\s*[^)]+\);/", "define('SMTP_HOST', " . var_export($host, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_PORT',\s*[^)]+\);/", "define('SMTP_PORT', {$port});", $configStr);
            $configStr = preg_replace("/define\('SMTP_USER',\s*[^)]+\);/", "define('SMTP_USER', " . var_export($user, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_PASS',\s*[^)]+\);/", "define('SMTP_PASS', " . var_export($pass, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_ENCRYPTION',\s*[^)]+\);/", "define('SMTP_ENCRYPTION', " . var_export($enc, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_FROM_EMAIL',\s*[^)]+\);/", "define('SMTP_FROM_EMAIL', " . var_export($fromEmail, true) . ");", $configStr);
            $configStr = preg_replace("/define\('SMTP_FROM_NAME',\s*[^)]+\);/", "define('SMTP_FROM_NAME', " . var_export($fromName, true) . ");", $configStr);
            @file_put_contents($configPath, $configStr);
        }

        log_security_event('PLATFORM_CONFIG_UPDATED', "Platform Configuration updated by Super Admin");

        // 3. Test SMTP connection if test email provided
        $testResult = null;
        if ($enabled && !empty($testEmail)) {
            $testResult = test_smtp_connection($testEmail);
            if (!$testResult['success']) {
                json_response([
                    'success' => false,
                    'error' => $testResult['message'],
                    'status' => $testResult['status']
                ], 400);
            }
        }

        json_response([
            'success' => true,
            'message' => ($testResult && $testResult['success']) ? $testResult['message'] : 'Platform settings updated successfully.',
            'test_sent' => !empty($testResult['success'])
        ]);
    }
}
