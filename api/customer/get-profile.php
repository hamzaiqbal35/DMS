<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
require_once __DIR__ . '/../../inc/customer/customer-auth.php';
require_customer_jwt_auth();

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    $customer_id = $_SESSION['customer_user_id'] ?? null;
    
    if (!$customer_id) {
        throw new Exception("Customer not logged in");
    }
    
    // Get customer profile
    $stmt = $pdo->prepare("
        SELECT customer_user_id, username, email, full_name, phone, 
               address, city, state, zip_code, status, created_at, last_login
        FROM customer_users 
        WHERE customer_user_id = ?
    ");
    $stmt->execute([$customer_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        throw new Exception("Profile not found");
    }
    
    echo json_encode([
        'status' => 'success',
        'profile' => $profile
    ]);
    
} catch (Exception $e) {
    error_log("Get Profile Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 