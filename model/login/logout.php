<?php
require_once __DIR__ . '/../../inc/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Set content type for JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // For AJAX requests, we can be more lenient with CSRF for logout
        // since it's a destructive action that doesn't harm the user
        
        // Destroy session and JWT
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Return JSON response for AJAX
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Logout failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle GET requests (direct access) - redirect to login
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_unset();
    session_destroy();
    header('Location: ../../views/login.php');
    exit;
}

// Invalid request method
http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);
?>