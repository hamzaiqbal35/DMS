<?php
// Load Composer dependencies (JWT etc.)
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/config/database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('JWT_SECRET', 'GPGaVfGbmZe8'); // Secure this properly
define('JWT_EXPIRY', 180); // Token expiry in minutes
define('JWT_ISSUER', 'AlliedSteelWorks'); // Issuer of the token
define('ROLE_ADMIN', 1);
define('ROLE_MANAGER', 2);
define('ROLE_SALESPERSON', 3);
define('ROLE_INVENTORY_MANAGER', 4);

// Generate JWT token
function generate_jwt($payload, $expiry_minutes = 180) {
    $issuedAt = time();
    $expire = $issuedAt + ($expiry_minutes * 180);

    $token = [
        "iss" => "AlliedSteelWorks",
        "iat" => $issuedAt,
        "exp" => $expire,
        "data" => $payload
    ];

    return JWT::encode($token, JWT_SECRET, 'HS256');
}

// Decode JWT token
function decode_jwt($token) {
    try {
        return JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

// Check if user is logged in based on session
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Flash messaging
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION["flash_$type"] = $message;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function hasAccess($module, $role_id = null) {
    if ($role_id === null) {
        $role_id = $_SESSION['role_id'] ?? 0;
    }
    if ($role_id == ROLE_ADMIN) return true;
    $access = [
        'dashboard' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON, ROLE_INVENTORY_MANAGER],
        'customers' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON],
        'vendors' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'inventory' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'stock_alerts' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'media_catalog' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'categories' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'sales' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON],
        'sale_reports' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON],
        'sale_invoices' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON],
        'purchases' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'purchase_reports' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'purchase_invoices' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'purchase_analytics' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'raw_materials' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_INVENTORY_MANAGER],
        'user_management' => [ROLE_ADMIN],
        'reports' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON, ROLE_INVENTORY_MANAGER],
        'export_data' => [ROLE_ADMIN, ROLE_MANAGER, ROLE_SALESPERSON, ROLE_INVENTORY_MANAGER],
    ];
    return isset($access[$module]) && in_array($role_id, $access[$module]);
}

function hasAnyAccess($modules, $role_id = null) {
    foreach ($modules as $m) {
        if (hasAccess($m, $role_id)) return true;
    }
    return false;
}

function getRoleName($role_id) {
    $map = [1=>'Admin',2=>'Manager',3=>'Salesperson',4=>'Inventory Manager'];
    return $map[$role_id] ?? 'Unknown';
}
?>
