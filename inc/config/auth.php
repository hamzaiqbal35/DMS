<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../helpers.php';

// If CSRF doesn't exist yet, create it
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Secure view access via JWT
function require_jwt_auth() {
    // Check if JWT token exists in session
    if (!isset($_SESSION['jwt_token'])) {
        session_unset();
        session_destroy();
        header("Location: /DMS/views/login.php");
        exit();
    }

    try {
        $decoded = decode_jwt($_SESSION['jwt_token']);
        
        // Validate token structure and required claims
        if (!$decoded || 
            !isset($decoded->data->user_id) || 
            !isset($decoded->data->username) || 
            !isset($decoded->data->email) || 
            !isset($decoded->data->role_id)) {
            throw new Exception('Invalid token structure');
        }

        // Check if token is expired
        if (isset($decoded->exp) && $decoded->exp < time()) {
            throw new Exception('Token expired');
        }

        // Set session variables from JWT
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
        $_SESSION['role_id'] = $decoded->data->role_id;

        // Optional: Refresh token if it's close to expiring (e.g., within 5 minutes)
        if (isset($decoded->exp) && ($decoded->exp - time()) < 300) {
            $payload = [
                'user_id' => $decoded->data->user_id,
                'username' => $decoded->data->username,
                'email' => $decoded->data->email,
                'role_id' => $decoded->data->role_id
            ];
            $_SESSION['jwt_token'] = generate_jwt($payload);
        }

    } catch (Exception $e) {
        // Log the error (you should implement proper logging)
        error_log("JWT Authentication Error: " . $e->getMessage());
        
        // Clear session and redirect to login
        session_unset();
        session_destroy();
        header("Location: /DMS/views/login.php");
        exit();
    }
}

// Function to check if user has required role
function require_role($required_role_id) {
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $required_role_id) {
        header("Location: /DMS/views/unauthorized.php");
        exit();
    }
}
?>
