<?php
/**
 * xVault Enterprise Password Manager
 * Enterprise Share Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class ShareController {
    public static function handleRequest($action, $tokenOrId = null) {
        switch ($action) {
            case 'generate':
                self::generateLink();
                break;
            case 'list':
                self::listLinks();
                break;
            case 'revoke':
                self::revokeLink($tokenOrId);
                break;
            case 'validate':
                self::validateToken($tokenOrId);
                break;
            default:
                json_response(['error' => 'Invalid share action'], 400);
        }
    }

    public static function generateLink() {
        $admin = require_admin();

        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $email = trim($input['email'] ?? '');
        $expiry = $input['expiry'] ?? '24h';
        $oneTime = !empty($input['oneTime']) || !empty($input['one_time']);
        $customHours = (int)($input['customHours'] ?? 0);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Valid customer email is required'], 400);
        }

        $token = bin2hex(random_bytes(24));
        $expiresAt = null;
        $expiresInText = 'Lifetime';

        if ($expiry !== 'lifetime') {
            $now = time();
            if ($expiry === 'custom' && $customHours > 0) {
                $now += ($customHours * 3600);
                $expiresInText = "{$customHours} Hours";
            } else {
                switch ($expiry) {
                    case '24h':
                        $now += 86400;
                        $expiresInText = '24 Hours';
                        break;
                    case '7d':
                        $now += (7 * 86400);
                        $expiresInText = '7 Days';
                        break;
                    case '30d':
                        $now += (30 * 86400);
                        $expiresInText = '30 Days';
                        break;
                    default:
                        $now += 86400;
                        $expiresInText = '24 Hours';
                }
            }
            $expiresAt = date('Y-m-d H:i:s', $now);
        }

        $db = getDB();
        $stmt = $db->prepare('
            INSERT INTO shared_links (token, created_by, target_email, expires_at, one_time)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$token, $admin['id'], $email, $expiresAt, $oneTime ? 1 : 0]);

        $shareUrl = get_app_url('/share/' . $token);

        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #D32F2F;'>xVault Secure Access Granted</h2>
                <p>You have been sent a secure vault access link.</p>
                <p><strong>Expiration:</strong> {$expiresInText}</p>
                <div style='margin: 25px 0;'>
                    <a href='{$shareUrl}' style='background: #D32F2F; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Access Secure Link</a>
                </div>
                <p style='color: #666; font-size: 13px;'>Or copy link: <br/>{$shareUrl}</p>
            </div>
        ";

        $sent = send_app_email($email, '🔐 Secure Vault Access Link', $html);

        json_response([
            'success' => true,
            'token' => $token,
            'url' => $shareUrl,
            'sent' => $sent,
            'message' => 'Secure link generated successfully'
        ], 201);
    }

    public static function listLinks() {
        $admin = require_admin();
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM shared_links ORDER BY created_at DESC');
        $stmt->execute();
        $links = $stmt->fetchAll();

        $result = array_map(function($l) {
            return [
                'id' => (int)$l['id'],
                'token' => $l['token'],
                'customerEmail' => $l['target_email'],
                'customer_email' => $l['target_email'],
                'expiresAt' => $l['expires_at'],
                'expires_at' => $l['expires_at'],
                'isOneTime' => (bool)$l['one_time'],
                'one_time' => (bool)$l['one_time'],
                'isUsed' => (bool)$l['used'],
                'used' => (bool)$l['used'],
                'created_at' => $l['created_at']
            ];
        }, $links);

        json_response($result);
    }

    public static function revokeLink($id) {
        $admin = require_admin();
        if (!$id) {
            json_response(['error' => 'Link ID required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM shared_links WHERE id = ?');
        $stmt->execute([(int)$id]);

        json_response(['success' => true, 'message' => 'Link revoked successfully']);
    }

    public static function validateToken($token) {
        if (empty($token)) {
            json_response(['error' => 'Invalid share token'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM shared_links WHERE token = ?');
        $stmt->execute([$token]);
        $link = $stmt->fetch();

        if (!$link) {
            json_response(['error' => 'Invalid or non-existent share link'], 404);
        }

        if (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
            json_response(['error' => 'This share link has expired'], 410);
        }

        if (!empty($link['one_time']) && !empty($link['used'])) {
            json_response(['error' => 'This one-time share link has already been used'], 410);
        }

        // If one time, mark as used
        if (!empty($link['one_time'])) {
            $up = $db->prepare('UPDATE shared_links SET used = 1 WHERE id = ?');
            $up->execute([$link['id']]);
        }

        // Get passwords matching target_email or folders for target_email
        $passStmt = $db->prepare('
            SELECT p.id, p.app_name, p.login_url, p.username, p.encrypted_password, p.created_at
            FROM password_entries p
            LEFT JOIN folders f ON p.folder_id = f.id
            WHERE f.customer_email = ? OR p.user_id = ?
            ORDER BY p.created_at DESC
        ');
        $passStmt->execute([$link['target_email'], $link['created_by']]);
        $passwords = $passStmt->fetchAll();

        $decryptedItems = array_map(function($p) {
            return [
                'id' => (int)$p['id'],
                'appName' => $p['app_name'],
                'app_name' => $p['app_name'],
                'loginUrl' => $p['login_url'],
                'login_url' => $p['login_url'],
                'username' => $p['username'],
                'decryptedPassword' => decrypt_data($p['encrypted_password'])
            ];
        }, $passwords);

        json_response([
            'success' => true,
            'email' => $link['target_email'],
            'expires_at' => $link['expires_at'],
            'is_one_time' => (bool)$link['one_time'],
            'items' => $decryptedItems
        ]);
    }
}
