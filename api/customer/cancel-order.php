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
    
    $order_id = intval($input['order_id'] ?? 0);
    $cancellation_reason = trim($input['cancellation_reason'] ?? '');
    
    if (!$order_id) {
        throw new Exception("Invalid order ID");
    }
    
    // Verify order belongs to customer and can be cancelled
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, order_status, total_amount
        FROM customer_orders 
        WHERE order_id = ? AND customer_user_id = ?
    ");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception("Order not found");
    }
    
    if ($order['order_status'] === 'cancelled') {
        throw new Exception("Order is already cancelled");
    }
    
    if (in_array($order['order_status'], ['shipped', 'delivered', 'completed'])) {
        throw new Exception("Cannot cancel order that has been shipped or delivered");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update order status and set cancellation reason/date
        $stmt = $pdo->prepare("
            UPDATE customer_orders 
            SET order_status = 'cancelled', 
                cancellation_date = NOW(),
                cancellation_reason = ?,
                updated_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$cancellation_reason, $order_id]);
        
        // Log status change
        $admin_user_id = 1; // Use admin user_id = 1 for customer cancellations
        $customer_note = "[Customer ID: $customer_id] " . ($cancellation_reason ?: "No reason provided");
        $stmt = $pdo->prepare("
            INSERT INTO order_status_logs 
            (order_id, old_status, new_status, changed_by, notes, created_at)
            VALUES (?, ?, 'cancelled', ?, ?, NOW())
        ");
        $stmt->execute([$order_id, $order['order_status'], $admin_user_id, $customer_note]);
        
        // Restore inventory stock
        $stmt = $pdo->prepare("
            SELECT od.item_id, od.quantity, i.current_stock, i.item_name
            FROM customer_order_details od
            JOIN inventory i ON od.item_id = i.item_id
            WHERE od.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
        
        foreach ($order_items as $item) {
            // Update inventory stock
            $stmt = $pdo->prepare("
                UPDATE inventory 
                SET current_stock = current_stock + ? 
                WHERE item_id = ?
            ");
            $stmt->execute([$item['quantity'], $item['item_id']]);
            
            // Log stock restoration
            $stmt = $pdo->prepare("
                INSERT INTO stock_logs 
                (item_id, quantity, type, reason, created_at)
                VALUES (?, ?, 'addition', 'Order cancellation', NOW())
            ");
            $stmt->execute([
                $item['item_id'], $item['quantity']
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Order cancelled successfully. Stock has been restored.',
            'order_number' => $order['order_number']
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Cancel Order Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 