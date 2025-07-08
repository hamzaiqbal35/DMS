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
    $customer_id = $_SESSION['customer_user_id'] ?? null;
    
    if (!$customer_id) {
        throw new Exception("Customer not logged in");
    }
    
    // Get cart items with product details
    $stmt = $pdo->prepare("
        SELECT c.cart_id, c.item_id, c.quantity, c.unit_price, c.total_price,
               i.item_name, i.description, i.current_stock, i.show_on_website
        FROM cart c
        JOIN inventory i ON c.item_id = i.item_id
        WHERE c.customer_user_id = ?
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$customer_id]);
    $cart_items = $stmt->fetchAll();
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['total_price'];
    }
    
    echo json_encode([
        'status' => 'success',
        'cart_items' => $cart_items,
        'subtotal' => $subtotal,
        'item_count' => count($cart_items)
    ]);
    
} catch (Exception $e) {
    error_log("Get Cart Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 