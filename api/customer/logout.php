<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    // Unset all customer session variables
    $_SESSION = [];
    session_unset();

    // Remove JWT and remember me cookies
    setcookie('customer_jwt_token', '', time() - 3600, '/');
    setcookie('customer_remember_token', '', time() - 3600, '/');

    // Destroy session and session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode([
        "status" => "success",
        "message" => "Logged out successfully."
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Logout failed. Please try again."
    ]);
}
?> 