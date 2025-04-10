<?php
require_once __DIR__ . '/../../inc/config/database.php'; // Database connection
require_once __DIR__ . '/../../inc/config/auth.php';    // Authentication handling
require_once __DIR__ . '/../../inc/helpers.php';        // Utility functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Verification (Optional but Recommended)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('error', 'Invalid logout request!');
        redirect('../../views/dashboard.php');
        exit;
    }
}

// Destroy session securely
session_unset();  // Unset session variables
session_destroy(); // Destroy session
setcookie(session_name(), '', time() - 3600, '/'); // Remove session cookie

// Redirect to login page with success message
set_flash_message('success', 'You have been logged out successfully.');
redirect('../../views/login.php');
exit;
