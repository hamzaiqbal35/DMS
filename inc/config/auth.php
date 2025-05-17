<?php
// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Correct paths to database.php and helpers.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../helpers.php';

// Function to check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect user if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: /DMS/views/login.php");
        exit();
    }
}

// Redirect user if they don't have the required role
function require_role($role) {
    if (!has_role($role)) {
        header("Location: /DMS/views/unauthorized.php");
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header("Location: /DMS/views/login.php");
    exit();
}
?>