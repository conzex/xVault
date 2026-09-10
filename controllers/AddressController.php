<?php
/**
 * xVault Enterprise Password Manager
 * Address Controller
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class AddressController {
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
                json_response(['error' => 'Invalid address action'], 400);
        }
    }

    public static function index($userId) {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        json_response($stmt->fetchAll());
    }

    public static function create($userId) {
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $label = trim($input['label'] ?? '');
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $line1 = trim($input['address_line1'] ?? '');
        $line2 = trim($input['address_line2'] ?? '');
        $city = trim($input['city'] ?? '');
        $state = trim($input['state'] ?? '');
        $zip = trim($input['zip_code'] ?? '');
        $country = trim($input['country'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $folderId = !empty($input['folder_id']) ? (int)$input['folder_id'] : null;

        if (empty($label)) {
            json_response(['error' => 'Address label is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('
            INSERT INTO addresses (user_id, folder_id, label, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $folderId, $label, $firstName, $lastName, $line1, $line2, $city, $state, $zip, $country, $phone]);

        json_response(['id' => (int)$db->lastInsertId(), 'message' => 'Address added'], 201);
    }

    public static function delete($userId, $id) {
        if (!$id) {
            json_response(['error' => 'ID is required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
        $stmt->execute([(int)$id, $userId]);

        json_response(['success' => true]);
    }
}
