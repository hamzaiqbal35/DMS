<?php
header('Content-Type: application/json');
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

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
require_once __DIR__ . '/../../inc/customer/customer-auth.php';
require_customer_jwt_auth();

// Check if customer is logged in
if (!isset($_SESSION['customer_user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to delete your account.'
    ]);
    exit();
}

$customer_id = $_SESSION['customer_user_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$reason = $input['reason'] ?? '';

// Validate password
if (empty($password)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Password is required to delete your account.'
    ]);
    exit();
}

try {
    // Verify customer exists and get their data
    $stmt = $pdo->prepare("SELECT * FROM customer_users WHERE customer_user_id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer account not found.'
        ]);
        exit();
    }

    // Verify password
    if (!password_verify($password, $customer['password'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Incorrect password. Please try again.'
        ]);
        exit();
    }

    // Check if customer has active orders
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_orders 
        FROM customer_orders 
        WHERE customer_user_id = ? AND order_status IN ('pending', 'confirmed', 'processing')
    ");
    $stmt->execute([$customer_id]);
    $activeOrders = $stmt->fetch()['active_orders'];

    if ($activeOrders > 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'You cannot delete your account while you have active orders. Please complete or cancel your orders first.'
        ]);
        exit();
    }

    // Check for sales that reference customer_orders (this might be the issue)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as sales_count 
        FROM sales 
        WHERE customer_order_id IN (SELECT order_id FROM customer_orders WHERE customer_user_id = ?)
    ");
    $stmt->execute([$customer_id]);
    $salesCount = $stmt->fetch()['sales_count'];

    if ($salesCount > 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'You cannot delete your account because there are sales records associated with your orders. Please contact support.'
        ]);
        exit();
    }

    // Check for order status logs
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as logs_count 
        FROM order_status_logs 
        WHERE order_id IN (SELECT order_id FROM customer_orders WHERE customer_user_id = ?)
    ");
    $stmt->execute([$customer_id]);
    $logsCount = $stmt->fetch()['logs_count'];

    if ($logsCount > 0) {
        // Order status logs will be deleted as part of the cleanup process
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update sales records to remove customer_order_id references
        $stmt = $pdo->prepare("
            UPDATE sales 
            SET customer_order_id = NULL 
            WHERE customer_order_id IN (SELECT order_id FROM customer_orders WHERE customer_user_id = ?)
        ");
        $stmt->execute([$customer_id]);

        // Delete order status logs first (they might not have CASCADE on changed_by)
        $stmt = $pdo->prepare("
            DELETE FROM order_status_logs 
            WHERE order_id IN (SELECT order_id FROM customer_orders WHERE customer_user_id = ?)
        ");
        $stmt->execute([$customer_id]);

        // Delete cart items
        $stmt = $pdo->prepare("DELETE FROM cart WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);

        // Delete password reset tokens
        $stmt = $pdo->prepare("DELETE FROM customer_password_resets WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);

        // Delete customer orders (this will cascade delete customer_payments, customer_order_details)
        $stmt = $pdo->prepare("DELETE FROM customer_orders WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);

        // Delete the customer user account
        $stmt = $pdo->prepare("DELETE FROM customer_users WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);

        // Delete from admin customers table if admin_customer_id exists
        if ($customer['admin_customer_id']) {
            // First, check if there are any sales records that reference this admin customer
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as sales_count 
                FROM sales 
                WHERE customer_id = ?
            ");
            $stmt->execute([$customer['admin_customer_id']]);
            $adminSalesCount = $stmt->fetch()['sales_count'];

            if ($adminSalesCount == 0) {
                // No sales reference this admin customer, safe to delete
                $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
                $stmt->execute([$customer['admin_customer_id']]);
            }
        }

        // Log the account deletion
        error_log("Customer account deleted - ID: $customer_id, Email: {$customer['email']}, Reason: $reason");

        // Commit transaction
        $pdo->commit();

        // Clear only customer session variables (don't destroy entire session)
        unset($_SESSION['customer_user_id']);
        unset($_SESSION['customer_email']);
        unset($_SESSION['customer_full_name']);
        unset($_SESSION['customer_logged_in']);
        
        // Clear any customer-specific session variables
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'customer_') === 0) {
                unset($_SESSION[$key]);
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Your account has been successfully deleted. You will be redirected to the home page.'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Database error during account deletion: " . $e->getMessage());
        throw new Exception("Database error: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error deleting customer account: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while deleting your account. Please try again.'
    ]);
}
?> 