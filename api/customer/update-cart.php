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
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cart_id = intval($input['cart_id'] ?? 0);
    $quantity = max(1, intval($input['quantity'] ?? 1));
    
    if (!$cart_id) {
        throw new Exception("Invalid cart item ID");
    }
    
    // Verify cart item belongs to customer
    $stmt = $pdo->prepare("
        SELECT c.cart_id, c.item_id, c.quantity, c.unit_price,
               i.item_name, i.current_stock, i.show_on_website
        FROM cart c
        JOIN inventory i ON c.item_id = i.item_id
        WHERE c.cart_id = ? AND c.customer_user_id = ?
    ");
    $stmt->execute([$cart_id, $customer_id]);
    $cart_item = $stmt->fetch();
    
    if (!$cart_item) {
        throw new Exception("Cart item not found");
    }
    
    if ($cart_item['show_on_website'] != 1) {
        throw new Exception("Product is no longer available");
    }
    
    if ($cart_item['current_stock'] < $quantity) {
        throw new Exception("Insufficient stock available");
    }
    
    // Update cart item
    $total_price = $cart_item['unit_price'] * $quantity;
    
    $stmt = $pdo->prepare("
        UPDATE cart 
        SET quantity = ?, total_price = ?, updated_at = NOW()
        WHERE cart_id = ?
    ");
    $stmt->execute([$quantity, $total_price, $cart_id]);
    
    // Get updated cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE customer_user_id = ?");
    $stmt->execute([$customer_id]);
    $cart_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    error_log("Update Cart Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 