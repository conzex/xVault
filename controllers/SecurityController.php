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
        $deductions = ($weakCount * 15) + ($reusedCount * 10);
        $score = $totalPasswords > 0 ? max(20, min(100, 100 - $deductions)) : 100;

        // 2. Failed logins in last 24h
        $failedStmt = $db->prepare("
            SELECT COUNT(*) as cnt FROM security_logs 
            WHERE (user_id = ? OR details LIKE ?) AND event_type = 'LOGIN_FAILED' AND created_at >= ?
        ");
        $since = date('Y-m-d H:i:s', time() - 86400);
        $userObj = current_user();
        $failedStmt->execute([$userId, '%' . ($userObj['email'] ?? '') . '%', $since]);
        $failedCount = (int)$failedStmt->fetch()['cnt'];

        json_response([
            'success' => true,
            'score' => $score,
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
