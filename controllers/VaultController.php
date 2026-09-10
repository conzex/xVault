<?php
/**
 * xVault Enterprise Password Manager
 * Vault Controller (Passwords Management)
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class VaultController {
    public static function handleRequest($action, $id = null) {
        $user = require_auth();

        switch ($action) {
            case 'list':
                self::index($user['id']);
                break;
            case 'create':
                self::create($user['id']);
                break;
            case 'favorite':
                self::toggleFavorite($user['id'], $id);
                break;
            case 'decrypt':
                self::decrypt($user['id'], $id);
                break;
            case 'delete':
                self::delete($user['id'], $id);
                break;
            default:
                json_response(['error' => 'Invalid vault action'], 400);
        }
    }

    public static function index($userId) {
        $db = getDB();
        $stmt = $db->prepare('
            SELECT p.*, f.name as folder_name 
            FROM password_entries p 
            LEFT JOIN folders f ON p.folder_id = f.id 
            WHERE p.user_id = ? 
            ORDER BY p.is_favorite DESC, p.created_at DESC
        ');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $entries = array_map(function($r) {
            return [
                'id' => (int)$r['id'],
                'user_id' => (int)$r['user_id'],
                'folder_id' => $r['folder_id'] ? (int)$r['folder_id'] : null,
                'folder_name' => $r['folder_name'] ?? null,
                'appName' => $r['app_name'],
                'app_name' => $r['app_name'],
                'loginUrl' => $r['login_url'],
                'login_url' => $r['login_url'],
                'username' => $r['username'],
                'encryptedPassword' => $r['encrypted_password'],
                'encrypted_password' => $r['encrypted_password'],
                'isFavorite' => (bool)$r['is_favorite'],
                'is_favorite' => (bool)$r['is_favorite'],
                'created_at' => $r['created_at']
            ];
        }, $rows);

        json_response($entries);
    }

    public static function create($userId) {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $appName = trim($input['app_name'] ?? $input['appName'] ?? '');
        $loginUrl = trim($input['login_url'] ?? $input['loginUrl'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $folderId = !empty($input['folder_id']) ? (int)$input['folder_id'] : null;

        if (empty($appName) || empty($password)) {
            json_response(['error' => 'Application name and password are required'], 400);
        }

        $encryptedPassword = encrypt_data($password);

        $db = getDB();
        $stmt = $db->prepare('
            INSERT INTO password_entries (user_id, folder_id, app_name, login_url, username, encrypted_password)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $folderId, $appName, $loginUrl, $username, $encryptedPassword]);

        json_response([
            'success' => true,
            'id' => (int)$db->lastInsertId(),
            'message' => 'Password entry saved'
        ], 201);
    }

    public static function toggleFavorite($userId, $id) {
        if (!$id) {
            json_response(['error' => 'Item ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, is_favorite FROM password_entries WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            json_response(['error' => 'Item not found'], 404);
        }

        $newFav = $entry['is_favorite'] ? 0 : 1;
        $up = $db->prepare('UPDATE password_entries SET is_favorite = ? WHERE id = ? AND user_id = ?');
        $up->execute([$newFav, (int)$id, $userId]);

        json_response(['success' => true, 'is_favorite' => (bool)$newFav]);
    }

    public static function decrypt($userId, $id) {
        if (!$id) {
            json_response(['error' => 'Item ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT encrypted_password FROM password_entries WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            json_response(['error' => 'Item not found'], 404);
        }

        $decrypted = decrypt_data($entry['encrypted_password']);
        json_response(['success' => true, 'password' => $decrypted]);
    }

    public static function delete($userId, $id) {
        if (!$id) {
            json_response(['error' => 'Item ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM password_entries WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);

        json_response(['success' => true, 'message' => 'Item deleted']);
    }
}
