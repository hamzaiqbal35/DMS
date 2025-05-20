<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../helpers.php';

// If CSRF doesn't exist yet, create it
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Secure view access via JWT
function require_jwt_auth() {
    if (!isset($_SESSION['jwt_token'])) {
        header("Location: /DMS/views/login.php");
        exit();
    }

    $decoded = decode_jwt($_SESSION['jwt_token']);

    if (!$decoded || empty($decoded->data->user_id)) {
        session_unset();
        session_destroy();
        header("Location: /DMS/views/login.php");
        exit();
    }

    // Optional: You can define $_SESSION values from the decoded JWT for further use
    $_SESSION['user_id'] = $decoded->data->user_id;
    $_SESSION['username'] = $decoded->data->username;
    $_SESSION['email'] = $decoded->data->email;
    $_SESSION['role_id'] = $decoded->data->role_id;
}
?>
