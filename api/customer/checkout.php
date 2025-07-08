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
    
    // Get form fields
    $shipping_address = trim($input['shipping_address'] ?? '');
    $city = trim($input['city'] ?? '');
    $state = trim($input['state'] ?? '');
    $zip_code = trim($input['zip_code'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $payment_method = 'cod';
    $notes = trim($input['order_notes'] ?? '');
    
    if (empty($shipping_address) || empty($city) || empty($state) || empty($zip_code)) {
        throw new Exception("Please provide complete shipping address");
    }
    
    if (empty($payment_method)) {
        throw new Exception("Please select a payment method");
    }
    
    // Format complete shipping address
    $complete_shipping_address = $shipping_address . "\n" . $city . ", " . $state . " " . $zip_code;
    if (!empty($phone)) {
        $complete_shipping_address .= "\nPhone: " . $phone;
    }
    
    // Get cart items
    $stmt = $pdo->prepare("
        SELECT c.cart_id, c.item_id, c.quantity, c.unit_price, c.total_price,
               i.item_name, i.current_stock, i.show_on_website
        FROM cart c
        JOIN inventory i ON c.item_id = i.item_id
        WHERE c.customer_user_id = ?
    ");
    $stmt->execute([$customer_id]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        throw new Exception("Your cart is empty");
    }
    
    // Validate stock and availability
    foreach ($cart_items as $item) {
        if ($item['show_on_website'] != 1) {
            throw new Exception("Product '{$item['item_name']}' is no longer available");
        }
        if ($item['current_stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for '{$item['item_name']}'");
        }
    }
    
    // Calculate total
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['total_price'];
    }
    
    // Set default shipping amount
    $shipping_amount = 500.00;
    $final_amount = $subtotal + $shipping_amount;
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create order
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $stmt = $pdo->prepare("
            INSERT INTO customer_orders 
            (customer_user_id, order_number, order_date, total_amount, shipping_amount, final_amount, shipping_address, 
             payment_method, notes, order_status, payment_status)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");
        $stmt->execute([
            $customer_id, $order_number, $subtotal, $shipping_amount, $final_amount, $complete_shipping_address,
            $payment_method, $notes
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Create order details and update inventory
        foreach ($cart_items as $item) {
            // Insert order detail
            $stmt = $pdo->prepare("
                INSERT INTO customer_order_details 
                (order_id, item_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, $item['item_id'], $item['quantity'], 
                $item['unit_price'], $item['total_price']
            ]);
            
            // Update inventory stock
            $stmt = $pdo->prepare("
                UPDATE inventory 
                SET current_stock = current_stock - ? 
                WHERE item_id = ?
            ");
            $stmt->execute([$item['quantity'], $item['item_id']]);
            
            // Log stock change
            $stmt = $pdo->prepare("
                INSERT INTO stock_logs 
                (item_id, quantity, type, reason, created_at)
                VALUES (?, ?, 'reduction', 'Customer order', NOW())
            ");
            $stmt->execute([
                $item['item_id'], $item['quantity']
            ]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Order placed successfully!',
            'order_id' => $order_id,
            'order_number' => $order_number
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 