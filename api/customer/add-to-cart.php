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
    
    // Check customer status
    $status = get_customer_status($pdo, $customer_id);
    if ($status !== 'active') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Your account is inactive. Please contact support.'
        ]);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $item_id = intval($input['item_id'] ?? 0);
    $quantity = max(1, intval($input['quantity'] ?? 1));
    
    if (!$item_id) {
        throw new Exception("Invalid item ID");
    }
    
    // Check if item exists and is available
    $stmt = $pdo->prepare("
        SELECT item_id, item_name, customer_price, unit_price, current_stock, show_on_website, status
        FROM inventory 
        WHERE item_id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception("Product not found");
    }
    
    if ($item['show_on_website'] != 1 || $item['status'] != 'active') {
        throw new Exception("Product is not available");
    }
    
    if ($item['current_stock'] < $quantity) {
        throw new Exception("Insufficient stock available");
    }
    
    $price = $item['customer_price'] ?? $item['unit_price'];
    $total_price = $price * $quantity;
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE customer_user_id = ? AND item_id = ?");
    $stmt->execute([$customer_id, $item_id]);
    $existing_item = $stmt->fetch();
    
    if ($existing_item) {
        // Update existing cart item
        $new_quantity = $existing_item['quantity'] + $quantity;
        $new_total = $price * $new_quantity;
        
        $stmt = $pdo->prepare("
            UPDATE cart 
            SET quantity = ?, total_price = ?, updated_at = NOW()
            WHERE cart_id = ?
        ");
        $stmt->execute([$new_quantity, $new_total, $existing_item['cart_id']]);
        
        $message = "Cart updated successfully";
    } else {
        // Add new cart item
        $stmt = $pdo->prepare("
            INSERT INTO cart (customer_user_id, item_id, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customer_id, $item_id, $quantity, $price, $total_price]);
        
        $message = "Product added to cart successfully!";
    }
    
    // Get updated cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE customer_user_id = ?");
    $stmt->execute([$customer_id]);
    $cart_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 