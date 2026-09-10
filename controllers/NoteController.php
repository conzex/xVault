<?php
/**
 * xVault Enterprise Password Manager
 * Secure Notes Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class NoteController {
    public static function handleRequest($action, $id = null) {
        $user = require_auth();

        switch ($action) {
            case 'list':
                self::index($user['id']);
                break;
            case 'create':
                self::create($user['id']);
                break;
            case 'delete':
                self::delete($user['id'], $id);
                break;
            default:
                json_response(['error' => 'Invalid note action'], 400);
        }
    }

    public static function index($userId) {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM secure_notes WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        json_response($stmt->fetchAll());
    }

    public static function create($userId) {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $type = trim($input['type'] ?? 'note');
        $folderId = !empty($input['folder_id']) ? (int)$input['folder_id'] : null;

        if (empty($title) || empty($content)) {
            json_response(['error' => 'Title and content are required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('INSERT INTO secure_notes (user_id, folder_id, title, content, type) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $folderId, $title, $content, $type]);

        json_response(['id' => (int)$db->lastInsertId(), 'message' => 'Secure note created'], 201);
    }

    public static function delete($userId, $id) {
        if (!$id) {
            json_response(['error' => 'ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM secure_notes WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);

        json_response(['success' => true]);
    }
}
