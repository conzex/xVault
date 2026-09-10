<?php
/**
 * xVault Enterprise Password Manager
 * Security Controller (Real-time Audit Logs & Health Stats)
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class SecurityController {
    public static function handleRequest($action) {
        $user = require_auth();

        switch ($action) {
            case 'stats':
                self::getStats($user['id']);
                break;
            case 'logs':
                self::getLogs($user['id']);
                break;
            case 'update-smtp':
                self::updateSMTP();
                break;
            default:
                json_response(['error' => 'Invalid security action'], 400);
        }
    }

    public static function getStats($userId) {
        $db = getDB();

        // 1. Password Entries Audit
        $stmt = $db->prepare('SELECT id, encrypted_password FROM password_entries WHERE user_id = ?');
        $stmt->execute([$userId]);
        $passwords = $stmt->fetchAll();

        $totalPasswords = count($passwords);
        $weakCount = 0;
        $plainList = [];

        foreach ($passwords as $p) {
            $plain = decrypt_data($p['encrypted_password']);
            $plainList[] = $plain;
            if (strlen($plain) < 10 || in_array(strtolower($plain), ['123456', 'password', 'admin', 'root', '12345678'])) {
                $weakCount++;
            }
        }

        // Detect reused passwords
        $counts = array_count_values($plainList);
        $reusedCount = 0;
        foreach ($counts as $pText => $cnt) {
            if ($cnt > 1 && $pText !== '********') {
                $reusedCount += ($cnt - 1);
            }
        }

        // Real Health Score calculation
        $userObj = current_user();
        $isUnverified = empty($userObj['is_verified']);
        
        // 2. Failed logins in last 24h
        $failedStmt = $db->prepare("
            SELECT COUNT(*) as cnt FROM security_logs 
            WHERE (user_id = ? OR details LIKE ?) AND event_type = 'LOGIN_FAILED' AND created_at >= ?
        ");
        $since = date('Y-m-d H:i:s', time() - 86400);
        $failedStmt->execute([$userId, '%' . ($userObj['email'] ?? '') . '%', $since]);
        $failedCount = (int)$failedStmt->fetch()['cnt'];

        if ($totalPasswords === 0) {
            json_response([
                'success' => true,
                'has_data' => false,
                'score' => null,
                'score_text' => 'Not available yet',
                'score_message' => 'Add password entries to calculate your security posture.',
                'total_passwords' => 0,
                'weak_passwords' => 0,
                'reused_passwords' => 0,
                'failed_logins_24h' => $failedCount,
                'last_updated' => date('H:i:s')
            ]);
        }

        $deductions = ($weakCount * 15) + ($reusedCount * 10) + ($failedCount * 5) + ($isUnverified ? 20 : 0);
        $score = max(10, min(100, 100 - $deductions));

        json_response([
            'success' => true,
            'has_data' => true,
            'score' => $score,
            'score_text' => $score . '%',
            'score_message' => ($score >= 80 ? 'Strong vault security' : ($score >= 60 ? 'Fair security posture' : 'Action recommended')),
            'total_passwords' => $totalPasswords,
            'weak_passwords' => $weakCount,
            'reused_passwords' => $reusedCount,
            'failed_logins_24h' => $failedCount,
            'last_updated' => date('H:i:s')
        ]);
    }

    public static function getLogs($userId) {
        $db = getDB();
        $stmt = $db->prepare('
            SELECT id, event_type, ip_address, user_agent, details, created_at 
            FROM security_logs 
            WHERE user_id = ? OR user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 20
        ');
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll();

        json_response([
            'success' => true,
            'logs' => array_map(function($l) {
                return [
                    'id' => (int)$l['id'],
                    'event_type' => $l['event_type'],
                    'ip_address' => $l['ip_address'],
                    'details' => $l['details'],
                    'created_at' => $l['created_at'],
                    'time_ago' => self::humanTimeAgo($l['created_at'])
                ];
            }, $logs)
        ]);
    }

    public static function updateSMTP() {
        require_admin();

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
            error_log("DB updateSMTP warning: " . $e->getMessage());
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

        log_security_event('SMTP_CONFIG_UPDATED', "SMTP configuration updated by admin (Status: " . ($enabled ? 'Enabled' : 'Disabled') . ")");

        // 3. Perform real socket test if test email provided
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
            'message' => ($testResult && $testResult['success']) ? $testResult['message'] : 'SMTP Settings updated successfully.',
            'test_sent' => !empty($testResult['success'])
        ]);
    }

    private static function humanTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        if (!$timestamp) return 'recently';
        $diff = time() - $timestamp;

        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' mins ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        return date('M j, Y H:i', $timestamp);
    }
}
