<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
// Restore JWT from cookie if not set
if (!isset($_SESSION['customer_jwt_token']) && isset($_COOKIE['customer_jwt_token'])) {
    $_SESSION['customer_jwt_token'] = $_COOKIE['customer_jwt_token'];
}
// Decode JWT and set session variables
if (isset($_SESSION['customer_jwt_token'])) {
    require_once '../../inc/helpers.php';
    $decoded = decode_customer_jwt($_SESSION['customer_jwt_token']);
    if ($decoded && isset($decoded->data->customer_user_id)) {
        $_SESSION['customer_user_id'] = $decoded->data->customer_user_id;
        $_SESSION['customer_username'] = $decoded->data->username;
        $_SESSION['customer_email'] = $decoded->data->email;
        $_SESSION['customer_full_name'] = $decoded->data->full_name;
    }
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $customer_id = $_SESSION['customer_user_id'] ?? null;
    
    if (!$customer_id) {
        throw new Exception("Customer not logged in.");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data received.");
    }
    
    $current_password = trim($input['current_password'] ?? '');
    $new_password = trim($input['new_password'] ?? '');
    $confirm_password = trim($input['confirm_password'] ?? '');

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception("All password fields are required.");
    }

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        throw new Exception("New passwords do not match.");
    }

    // Validate password strength
    if (strlen($new_password) < 6) {
        throw new Exception("New password must be at least 6 characters long.");
    }

    // Get current customer data
    $stmt = $pdo->prepare("SELECT password FROM customer_users WHERE customer_user_id = ?");
    if (!$stmt) {
        throw new Exception("Database error: Failed to prepare statement.");
    }
    
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        throw new Exception("Customer not found.");
    }

    // Verify current password
    if (!password_verify($current_password, $customer['password'])) {
        throw new Exception("Current password is incorrect.");
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $stmt = $pdo->prepare("
        UPDATE customer_users 
        SET password = ?, updated_at = NOW()
        WHERE customer_user_id = ?
    ");

    if (!$stmt) {
        throw new Exception("Database error: Failed to prepare update statement.");
    }

    $result = $stmt->execute([$hashed_password, $customer_id]);

    if (!$result) {
        $error_info = $stmt->errorInfo();
        throw new Exception("Failed to update password: " . ($error_info[2] ?? 'Unknown database error'));
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception("No rows were updated. Customer may not exist.");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Password changed successfully.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 