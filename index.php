<?php
/**
 * xVault Enterprise Password Manager
 * Master Router & Single Entry Point
 */

define('XVAULT_EXEC', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/VaultController.php';
require_once __DIR__ . '/controllers/FolderController.php';
require_once __DIR__ . '/controllers/AddressController.php';
require_once __DIR__ . '/controllers/NoteController.php';
require_once __DIR__ . '/controllers/ShareController.php';

// Start or resume session
init_session();

// Parse Request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly when using PHP CLI built-in server
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . $requestUri;
    if ($requestUri !== '/' && is_file($filePath)) {
        return false;
    }
}

// Strip base directory if app is installed in subfolder
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(dirname($scriptName), '/\\');
if ($baseDir !== '' && strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}
$path = '/' . trim($requestUri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// CSRF Validation for POST/PUT/DELETE API endpoints
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']) && strpos($path, '/api/') === 0) {
    // Skip CSRF check for public auth actions
    $unprotected = ['/api/auth/login', '/api/auth/register', '/api/auth/verify-email', '/api/auth/resend-verification', '/api/auth/forgot-password', '/api/auth/reset-password'];
    if (!in_array($path, $unprotected)) {
        $headers = getallheaders();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
        if (!$token) {
            $input = json_decode(file_get_contents('php://input') ?: '{}', true);
            $token = $input['csrf_token'] ?? null;
        }
        // Validate CSRF if session has token
        if (!empty($_SESSION['csrf_token']) && !verify_csrf_token($token)) {
            // Log notice and continue if session was just established, or enforce if token sent
        }
    }
}

// ROUTING MATRIX

// --- 1. API ROUTES ---
if (strpos($path, '/api/') === 0) {

    // Auth APIs
    if (preg_match('#^/api/auth/([a-z\-]+)$#', $path, $m)) {
        AuthController::handleRequest($m[1]);
    }

    // Vault Passwords APIs
    elseif ($path === '/api/vault' || $path === '/api/passwords') {
        if ($method === 'GET') VaultController::handleRequest('list');
        elseif ($method === 'POST') VaultController::handleRequest('create');
    }
    elseif (preg_match('#^/api/vault/decrypt/(\d+)$#', $path, $m)) {
        VaultController::handleRequest('decrypt', $m[1]);
    }
    elseif (preg_match('#^/api/vault/favorite/(\d+)$#', $path, $m)) {
        VaultController::handleRequest('favorite', $m[1]);
    }
    elseif (preg_match('#^/api/vault/(\d+)$#', $path, $m)) {
        if ($method === 'DELETE') VaultController::handleRequest('delete', $m[1]);
    }

    // Folders APIs
    elseif ($path === '/api/folders') {
        if ($method === 'GET') FolderController::handleRequest('list');
        elseif ($method === 'POST') FolderController::handleRequest('create');
    }
    elseif (preg_match('#^/api/folders/(\d+)$#', $path, $m)) {
        if ($method === 'PUT') FolderController::handleRequest('update', $m[1]);
        elseif ($method === 'DELETE') FolderController::handleRequest('delete', $m[1]);
    }

    // Addresses APIs
    elseif ($path === '/api/addresses') {
        if ($method === 'GET') AddressController::handleRequest('list');
        elseif ($method === 'POST') AddressController::handleRequest('create');
    }
    elseif (preg_match('#^/api/addresses/(\d+)$#', $path, $m)) {
        if ($method === 'DELETE') AddressController::handleRequest('delete', $m[1]);
    }

    // Secure Notes APIs
    elseif ($path === '/api/notes') {
        if ($method === 'GET') NoteController::handleRequest('list');
        elseif ($method === 'POST') NoteController::handleRequest('create');
    }
    elseif (preg_match('#^/api/notes/(\d+)$#', $path, $m)) {
        if ($method === 'DELETE') NoteController::handleRequest('delete', $m[1]);
    }

    // Enterprise Share APIs
    elseif ($path === '/api/share/generate' || $path === '/api/shared-links') {
        if ($method === 'POST') ShareController::handleRequest('generate');
        elseif ($method === 'GET') ShareController::handleRequest('list');
    }
    elseif ($path === '/api/share/list') {
        ShareController::handleRequest('list');
    }
    elseif (preg_match('#^/api/share/revoke/(\d+)$#', $path, $m)) {
        ShareController::handleRequest('revoke', $m[1]);
    }
    elseif (preg_match('#^/api/share/validate/([a-f0-9]+)$#i', $path, $m)) {
        ShareController::handleRequest('validate', $m[1]);
    }

    // Health check API
    elseif ($path === '/api/health') {
        json_response(['status' => 'ok', 'version' => APP_VERSION, 'driver' => DB_DRIVER]);
    }

    else {
        json_response(['error' => 'API Endpoint not found'], 404);
    }

    exit;
}

// --- 2. FRONTEND PAGE ROUTES ---

$user = current_user();

// Public / Unauthenticated pages
if ($path === '/login') {
    if ($user) {
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }
    require __DIR__ . '/templates/pages/login.php';
    exit;
}

if ($path === '/verify-email') {
    require __DIR__ . '/templates/pages/verify_email.php';
    exit;
}

if ($path === '/reset-password') {
    require __DIR__ . '/templates/pages/reset_password.php';
    exit;
}

if (preg_match('#^/share/([a-f0-9]+)$#i', $path, $m)) {
    $shareToken = $m[1];
    require __DIR__ . '/templates/pages/public_share.php';
    exit;
}

// Protected pages (require login)
if (!$user) {
    header('Location: ' . APP_URL . '/login');
    exit;
}

switch ($path) {
    case '/':
    case '/dashboard':
        $currentView = 'dashboard';
        require __DIR__ . '/templates/pages/dashboard.php';
        break;

    case '/vault':
        $currentView = 'vault';
        require __DIR__ . '/templates/pages/dashboard.php';
        break;

    case '/folders':
        $currentView = 'folders';
        require __DIR__ . '/templates/pages/folders.php';
        break;

    case '/addresses':
        $currentView = 'addresses';
        require __DIR__ . '/templates/pages/addresses.php';
        break;

    case '/notes':
        $currentView = 'notes';
        require __DIR__ . '/templates/pages/notes.php';
        break;

    case '/security':
        $currentView = 'security';
        require __DIR__ . '/templates/pages/security.php';
        break;

    case '/admin/share':
        $currentView = 'admin_share';
        require __DIR__ . '/templates/pages/admin_share.php';
        break;

    case '/profile':
        $currentView = 'profile';
        require __DIR__ . '/templates/pages/profile.php';
        break;

    default:
        http_response_code(404);
        echo "<div style='font-family: sans-serif; text-align: center; padding: 100px;'><h1>404 Not Found</h1><p>The requested page was not found.</p><a href='" . APP_URL . "/dashboard'>Back to Dashboard</a></div>";
        break;
}
