<?php
/**
 * xVault Enterprise Password Manager
 * Enterprise Single-Record Share Controller
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
        $user = require_auth();

        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $entryId = (int)($input['entry_id'] ?? $input['id'] ?? 0);
        $email = trim($input['email'] ?? '');
        $expiry = $input['expiry'] ?? '24h';
        $oneTime = !empty($input['oneTime']) || !empty($input['one_time']);
        $maxUses = isset($input['max_uses']) ? (int)$input['max_uses'] : ($oneTime ? 1 : null);
        $customHours = (int)($input['customHours'] ?? 0);

        if (!$entryId) {
            json_response(['error' => 'Valid credential record ID is required for sharing'], 400);
        }

        $db = getDB();

        // Ensure user owns entry or is admin
        if (($user['role'] ?? '') === 'admin') {
            $stmt = $db->prepare('SELECT id, app_name FROM password_entries WHERE id = ?');
            $stmt->execute([$entryId]);
        } else {
            $stmt = $db->prepare('SELECT id, app_name FROM password_entries WHERE id = ? AND user_id = ?');
            $stmt->execute([$entryId, $user['id']]);
        }

        $entry = $stmt->fetch();
        if (!$entry) {
            json_response(['error' => 'Credential record not found or access denied'], 404);
        }

        $token = bin2hex(random_bytes(32)); // Cryptographically secure 64-char hex token
        $expiresAt = null;
        $expiresInText = 'Never (Permanent)';

        if ($expiry !== 'lifetime' && $expiry !== 'never') {
            $now = time();
            if ($expiry === 'custom' && $customHours > 0) {
                $now += ($customHours * 3600);
                $expiresInText = "{$customHours} Hours";
            } else {
                switch ($expiry) {
                    case '1h':
                        $now += 3600;
                        $expiresInText = '1 Hour';
                        break;
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

        $insert = $db->prepare('
            INSERT INTO shared_links (token, entry_id, created_by, target_email, expires_at, one_time, max_uses, access_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ');
        $insert->execute([$token, $entryId, $user['id'], !empty($email) ? $email : null, $expiresAt, $oneTime ? 1 : 0, $maxUses]);

        $shareUrl = get_app_url('/share/' . $token);

        log_security_event('SHARE_LINK_CREATED', "Generated share link for entry #{$entryId} ({$entry['app_name']})", $user['id']);

        $sent = false;
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $body = "<p>You have been granted secure access to a specific credential record in xVault Enterprise Password Manager:</p>" .
                    "<p><strong>Record:</strong> " . e($entry['app_name']) . "<br/>" .
                    "<strong>Expiration:</strong> {$expiresInText}<br/>" .
                    "<strong>Access Rule:</strong> " . ($oneTime ? 'One-time view only' : 'Time-restricted link') . "</p>" .
                    "<p>Click below to securely view this record.</p>";
            $sent = send_user_transactional_email($email, '🔒 Shared Credential Access Link', 'Secure Credential Share', $body, $shareUrl, 'View Shared Credential', 'This link grants access ONLY to the single specified credential record.');
        }

        json_response([
            'success' => true,
            'token' => $token,
            'url' => $shareUrl,
            'sent' => $sent,
            'message' => 'Secure share link generated successfully'
        ], 201);
    }

    public static function listLinks() {
        $user = require_auth();
        $db = getDB();

        if (($user['role'] ?? '') === 'admin') {
            $stmt = $db->prepare('
                SELECT s.*, p.app_name, u.email as owner_email
                FROM shared_links s
                LEFT JOIN password_entries p ON s.entry_id = p.id
                LEFT JOIN users u ON s.created_by = u.id
                ORDER BY s.created_at DESC
            ');
            $stmt->execute();
        } else {
            $stmt = $db->prepare('
                SELECT s.*, p.app_name, u.email as owner_email
                FROM shared_links s
                LEFT JOIN password_entries p ON s.entry_id = p.id
                LEFT JOIN users u ON s.created_by = u.id
                WHERE s.created_by = ?
                ORDER BY s.created_at DESC
            ');
            $stmt->execute([$user['id']]);
        }

        $links = $stmt->fetchAll();

        $result = array_map(function($l) {
            $isExpired = (!empty($l['expires_at']) && strtotime($l['expires_at']) < time());
            $isUsed = (!empty($l['one_time']) && !empty($l['used'])) || (!empty($l['max_uses']) && $l['access_count'] >= $l['max_uses']);
            
            $status = 'active';
            if ($isUsed) $status = 'used';
            elseif ($isExpired) $status = 'expired';

            return [
                'id' => (int)$l['id'],
                'token' => $l['token'],
                'entry_id' => (int)$l['entry_id'],
                'appName' => $l['app_name'] ?: 'Credential #' . $l['entry_id'],
                'app_name' => $l['app_name'] ?: 'Credential #' . $l['entry_id'],
                'customerEmail' => $l['target_email'],
                'customer_email' => $l['target_email'],
                'expiresAt' => $l['expires_at'],
                'expires_at' => $l['expires_at'],
                'isOneTime' => (bool)$l['one_time'],
                'one_time' => (bool)$l['one_time'],
                'access_count' => (int)($l['access_count'] ?? 0),
                'last_accessed_at' => $l['last_accessed_at'],
                'status' => $status,
                'created_at' => $l['created_at']
            ];
        }, $links);

        json_response($result);
    }

    public static function revokeLink($id) {
        $user = require_auth();
        if (!$id) {
            json_response(['error' => 'Link ID required'], 400);
        }

        $db = getDB();
        if (($user['role'] ?? '') === 'admin') {
            $stmt = $db->prepare('DELETE FROM shared_links WHERE id = ? OR token = ?');
            $stmt->execute([(int)$id, $id]);
        } else {
            $stmt = $db->prepare('DELETE FROM shared_links WHERE (id = ? OR token = ?) AND created_by = ?');
            $stmt->execute([(int)$id, $id, $user['id']]);
        }

        log_security_event('SHARE_LINK_REVOKED', "Revoked share link ID: {$id}", $user['id']);

        json_response(['success' => true, 'message' => 'Share link revoked successfully']);
    }

    public static function validateToken($token) {
        if (empty($token)) {
            json_response(['error' => 'Invalid share token'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('
            SELECT s.*, p.app_name, p.login_url, p.username, p.encrypted_password, p.created_at as item_created_at
            FROM shared_links s
            JOIN password_entries p ON s.entry_id = p.id
            WHERE s.token = ?
        ');
        $stmt->execute([$token]);
        $link = $stmt->fetch();

        if (!$link) {
            json_response(['error' => 'Invalid, revoked, or non-existent share link'], 404);
        }

        if (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
            json_response(['error' => 'This share link has expired and is no longer accessible.'], 410);
        }

        if (!empty($link['one_time']) && !empty($link['used'])) {
            json_response(['error' => 'This one-time share link has already been used and is now invalidated.'], 410);
        }

        if (!empty($link['max_uses']) && (int)$link['access_count'] >= (int)$link['max_uses']) {
            json_response(['error' => 'This share link has reached its maximum allowed access count.'], 410);
        }

        // Increment access metrics
        $newCount = (int)$link['access_count'] + 1;
        $nowStr = date('Y-m-d H:i:s');
        $isUsedNow = (!empty($link['one_time']) || (!empty($link['max_uses']) && $newCount >= (int)$link['max_uses'])) ? 1 : 0;

        $up = $db->prepare('UPDATE shared_links SET access_count = ?, last_accessed_at = ?, used = ? WHERE id = ?');
        $up->execute([$newCount, $nowStr, $isUsedNow, $link['id']]);

        log_security_event('SHARE_LINK_ACCESSED', "Share link accessed for item #{$link['entry_id']} ({$link['app_name']})", null);

        // EXPOSE ONLY THE SPECIFIC SELECTED CREDENTIAL ENTRY
        json_response([
            'success' => true,
            'item' => [
                'id' => (int)$link['entry_id'],
                'appName' => $link['app_name'],
                'app_name' => $link['app_name'],
                'loginUrl' => $link['login_url'],
                'login_url' => $link['login_url'],
                'username' => $link['username'],
                'decryptedPassword' => decrypt_data($link['encrypted_password']),
                'created_at' => $link['item_created_at']
            ],
            'expires_at' => $link['expires_at'],
            'is_one_time' => (bool)$link['one_time'],
            'access_count' => $newCount
        ]);
    }
}

