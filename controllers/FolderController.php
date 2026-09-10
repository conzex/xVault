<?php
/**
 * xVault Enterprise Password Manager
 * Folder Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class FolderController {
    public static function handleRequest($action, $id = null) {
        $user = require_auth();

        switch ($action) {
            case 'list':
                self::index($user['id']);
                break;
            case 'create':
                self::create($user['id']);
                break;
            case 'update':
                self::update($user['id'], $id);
                break;
            case 'delete':
                self::delete($user['id'], $id);
                break;
            default:
                json_response(['error' => 'Invalid folder action'], 400);
        }
    }

    public static function index($userId) {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        $folders = $stmt->fetchAll();
        json_response($folders);
    }

    public static function create($userId) {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $name = trim($input['name'] ?? '');
        $customerName = trim($input['customer_name'] ?? '');
        $customerEmail = trim($input['customer_email'] ?? '');

        if (empty($name)) {
            json_response(['error' => 'Folder name is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO folders (user_id, name, customer_name, customer_email) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $customerName, $customerEmail]);

        json_response([
            'id' => (int)$db->lastInsertId(),
            'name' => $name,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail
        ], 201);
    }

    public static function update($userId, $id) {
        if (!$id) {
            json_response(['error' => 'Folder ID is required'], 400);
        }

        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $name = trim($input['name'] ?? '');
        $customerName = trim($input['customer_name'] ?? '');
        $customerEmail = trim($input['customer_email'] ?? '');

        if (empty($name)) {
            json_response(['error' => 'Folder name is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('UPDATE folders SET name = ?, customer_name = ?, customer_email = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$name, $customerName, $customerEmail, (int)$id, $userId]);

        json_response(['success' => true]);
    }

    public static function delete($userId, $id) {
        if (!$id) {
            json_response(['error' => 'Folder ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM folders WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);

        json_response(['success' => true]);
    }
}
