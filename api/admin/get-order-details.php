<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
// Start session and restore from JWT if needed
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['jwt_token'])) {
    $decoded = decode_jwt($_SESSION['jwt_token']);
    if ($decoded && isset($decoded->data->user_id) && isset($decoded->data->role_id)) {
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['role_id'] = $decoded->data->role_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
    }
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if (!$order_id) {
        throw new Exception("Invalid order ID");
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT co.*, cu.full_name as customer_name, cu.email as customer_email, cu.phone as customer_phone
        FROM customer_orders co
        JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        WHERE co.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT cod.*, i.item_name, i.item_number, m.file_path as image_path
        FROM customer_order_details cod
        JOIN inventory i ON cod.item_id = i.item_id
        LEFT JOIN media m ON i.item_id = m.item_id
        WHERE cod.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $stmt = $pdo->prepare("
        SELECT *
        FROM customer_payments
        WHERE order_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$order_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // When returning order details, force payment_method to 'cod' and payment_status to only be 'pending', 'partial', or 'paid' for customer orders.
    if (isset($order['payment_method'])) $order['payment_method'] = 'cod';
    if (!in_array($order['payment_status'], ['pending','partial','paid'])) $order['payment_status'] = 'pending';
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'order' => $order,
            'items' => $items,
            'payments' => $payments
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get Order Details Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load order details: ' . $e->getMessage()
    ]);
}
?> 