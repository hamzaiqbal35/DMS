<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

require_once __DIR__ . '/../helpers.php';

$public_pages = [
    'login.php',
    'register.php',
    'forgot-password.php',
    'reset-password.php'
];

$current_page = basename($_SERVER['PHP_SELF']);

function require_customer_jwt_auth() {
    global $public_pages, $current_page;
    if (in_array($current_page, $public_pages)) return true;
    if (!isset($_SESSION['customer_jwt_token'])) {
        session_unset();
        session_destroy();
        header("Location: /DMS/customer.php?page=login");
        exit();
    }
    $decoded = decode_customer_jwt($_SESSION['customer_jwt_token']);
    if (!$decoded || !isset($decoded->data->customer_user_id)) {
        session_unset();
        session_destroy();
        header("Location: /DMS/customer.php?page=login");
        exit();
    }
    // Optionally refresh token if close to expiry
    if (isset($decoded->exp) && ($decoded->exp - time()) < 300) {
        $payload = [
            "customer_user_id" => $decoded->data->customer_user_id,
            "username" => $decoded->data->username,
            "email" => $decoded->data->email,
            "full_name" => $decoded->data->full_name
        ];
        $_SESSION['customer_jwt_token'] = generate_customer_jwt($payload);
    }
    // Set session variables from JWT
    $_SESSION['customer_user_id'] = $decoded->data->customer_user_id;
    $_SESSION['customer_username'] = $decoded->data->username;
    $_SESSION['customer_email'] = $decoded->data->email;
    $_SESSION['customer_full_name'] = $decoded->data->full_name;
}
?> 