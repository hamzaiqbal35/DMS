<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    $customer_id = $_SESSION['customer_user_id'] ?? null;
    
    if (!$customer_id) {
        throw new Exception("Customer not logged in");
    }
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Only show orders not deleted by customer (future: add is_deleted_customer if needed)
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM customer_orders 
        WHERE customer_user_id = ?
          AND (is_deleted_customer = 0 OR is_deleted_customer IS NULL)
    ");
    $count_stmt->execute([$customer_id]);
    $total_count = $count_stmt->fetch()['total'];
    
    // Get orders
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, order_date, total_amount, 
               order_status, payment_status, shipping_address, shipping_city, 
               shipping_state, shipping_zip, shipping_phone, payment_method, notes
        FROM customer_orders 
        WHERE customer_user_id = ?
          AND (is_deleted_customer = 0 OR is_deleted_customer IS NULL)
        ORDER BY order_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customer_id, $limit, $offset]);
    $orders = $stmt->fetchAll();
    
    // Get order details for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT od.order_detail_id, od.quantity, od.unit_price, od.total_price,
                   i.item_name, i.item_description
            FROM customer_order_details od
            JOIN inventory i ON od.item_id = i.item_id
            WHERE od.order_id = ?
        ");
        $stmt->execute([$order['order_id']]);
        $order['items'] = $stmt->fetchAll();
        
        // When returning orders, force payment_method to 'cod' and payment_status to only be 'pending', 'partial', or 'paid'.
        if (isset($order['payment_method'])) $order['payment_method'] = 'cod';
        if (!in_array($order['payment_status'], ['pending','partial','paid'])) $order['payment_status'] = 'pending';
    }
    
    echo json_encode([
        'status' => 'success',
        'orders' => $orders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total_count / $limit),
            'total_items' => $total_count,
            'items_per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get Orders Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 