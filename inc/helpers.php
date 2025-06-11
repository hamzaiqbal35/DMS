<?php
// Load Composer dependencies (JWT etc.)
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/config/database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('JWT_SECRET', 'GPGaVfGbmZe8'); // Secure this properly
define('JWT_EXPIRY', 180); // Token expiry in minutes
define('JWT_ISSUER', 'AlliedSteelWorks'); // Issuer of the token
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
?>
