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
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $order_id = intval($input['order_id'] ?? 0);
    $new_status = trim($input['status'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $admin_id = $_SESSION['user_id'];

    if ($order_id <= 0) {
        throw new Exception("Invalid order ID.");
    }

    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception("Invalid order status.");
    }

    // Get order details
    $stmt = $pdo->prepare("
        SELECT co.*, cu.full_name, cu.email, cu.phone, cu.admin_customer_id
        FROM customer_orders co
        JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        WHERE co.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Order not found.");
    }

    $old_status = $order['order_status'];

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update customer order status
        $stmt = $pdo->prepare("
            UPDATE customer_orders 
            SET order_status = ?, updated_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$new_status, $order_id]);

        // Handle sales record creation/removal based on status
        if (in_array($new_status, ['pending', 'confirmed', 'processing', 'shipped', 'delivered'])) {
            // Create or update sales record for all valid order statuses
            createOrUpdateSalesRecord($pdo, $order, $admin_id, $new_status);
        } elseif ($new_status === 'cancelled') {
            // Remove sales record for cancelled orders
            removeSalesRecord($pdo, $order_id);
        }

        // Create status change log
        $stmt = $pdo->prepare("
            INSERT INTO order_status_logs (
                order_id, old_status, new_status, changed_by, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$order_id, $old_status, $new_status, $admin_id, $notes]);

        // Handle special status actions
        switch ($new_status) {
            case 'confirmed':
                // Send confirmation email
                sendOrderConfirmationEmail($order);
                break;
                
            case 'shipped':
                // Generate tracking number
                $tracking_number = generateTrackingNumber($order_id);
                updateOrderTracking($pdo, $order_id, $tracking_number);
                sendShippingNotification($order, $tracking_number);
                break;
                
            case 'delivered':
                // Mark as completed
                markOrderAsCompleted($pdo, $order_id);
                sendDeliveryConfirmation($order);
                break;
                
            case 'cancelled':
                // Handle cancellation
                handleOrderCancellation($pdo, $order_id, $notes);
                sendCancellationNotification($order, $notes);
                break;
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Order status updated successfully.',
            'data' => [
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'tracking_number' => $tracking_number ?? null
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Order Status Update Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Create or update sales record for all valid order statuses
 */
function createOrUpdateSalesRecord($pdo, $order, $admin_id, $status) {
    // Check if sales record already exists
    $stmt = $pdo->prepare("SELECT sale_id FROM sales WHERE customer_order_id = ?");
    $stmt->execute([$order['order_id']]);
    $existing_sale = $stmt->fetch();

    if ($existing_sale) {
        // Update existing sales record
        $stmt = $pdo->prepare("
            UPDATE sales 
            SET order_status = ?, updated_at = NOW()
            WHERE customer_order_id = ?
        ");
        $stmt->execute([$status, $order['order_id']]);
    } else {
        // Get or create customer record
        $customer_id = getOrCreateCustomer($pdo, $order);
        
        if (!$customer_id) {
            throw new Exception("Unable to create customer record for order {$order['order_number']}");
        }
        
        // Create new sales record
        $invoice_number = generateInvoiceNumber();
        
        $stmt = $pdo->prepare("
            INSERT INTO sales (
                invoice_number, customer_id, sale_date, total_amount, 
                payment_status, paid_amount, order_status, customer_order_id,
                notes, created_by, sale_type, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $invoice_number,
            $customer_id,
            $order['order_date'],
            $order['final_amount'],
            $order['payment_status'],
            $order['payment_status'] === 'paid' ? $order['final_amount'] : 0,
            $status,
            $order['order_id'],
            "Online order: {$order['order_number']} - Customer: {$order['full_name']}",
            $admin_id,
            'customer_order'
        ]);
        
        $sale_id = $pdo->lastInsertId();
        
        // Create sale details from order details
        createSaleDetails($pdo, $sale_id, $order['order_id']);
    }
}

/**
 * Get or create customer record
 */
function getOrCreateCustomer($pdo, $order) {
    // First try to use admin_customer_id if available
    if ($order['admin_customer_id']) {
        return $order['admin_customer_id'];
    }
    
    // Try to find customer by email
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
    $stmt->execute([$order['email']]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        return $customer['customer_id'];
    }
    
    // Create new customer record
    $stmt = $pdo->prepare("
        INSERT INTO customers (customer_name, email, phone, address, status, created_at)
        VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        $order['full_name'],
        $order['email'],
        $order['phone'],
        $order['shipping_address'] ?? ''
    ]);
    
    $customer_id = $pdo->lastInsertId();
    
    // Update customer_users table with the new admin_customer_id
    $stmt = $pdo->prepare("
        UPDATE customer_users 
        SET admin_customer_id = ? 
        WHERE customer_user_id = ?
    ");
    $stmt->execute([$customer_id, $order['customer_user_id']]);
    
    return $customer_id;
}

/**
 * Create sale details from order details
 */
function createSaleDetails($pdo, $sale_id, $order_id) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT item_id, quantity, unit_price, total_price
        FROM customer_order_details
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll();
    
    // Insert sale details
    $stmt = $pdo->prepare("
        INSERT INTO sale_details (sale_id, item_id, quantity, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($order_details as $detail) {
        $stmt->execute([
            $sale_id,
            $detail['item_id'],
            $detail['quantity'],
            $detail['unit_price'],
            $detail['total_price']
        ]);
    }
}

/**
 * Remove sales record for cancelled or pending orders
 */
function removeSalesRecord($pdo, $order_id) {
    // Get sale_id first
    $stmt = $pdo->prepare("SELECT sale_id FROM sales WHERE customer_order_id = ?");
    $stmt->execute([$order_id]);
    $sale = $stmt->fetch();
    
    if ($sale) {
        // Delete sale details first (due to foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
        $stmt->execute([$sale['sale_id']]);
        
        // Delete payments
        $stmt = $pdo->prepare("DELETE FROM payments WHERE sale_id = ?");
        $stmt->execute([$sale['sale_id']]);
        
        // Delete sales record
        $stmt = $pdo->prepare("DELETE FROM sales WHERE sale_id = ?");
        $stmt->execute([$sale['sale_id']]);
    }
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber() {
    $prefix = 'INV';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 8));
    return "{$prefix}-{$date}-{$random}";
}

/**
 * Generate tracking number
 */
function generateTrackingNumber($order_id) {
    return 'TRK-' . date('Ymd') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
}

/**
 * Update order with tracking number
 */
function updateOrderTracking($pdo, $order_id, $tracking_number) {
    $stmt = $pdo->prepare("
        UPDATE customer_orders 
        SET tracking_number = ?, updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->execute([$tracking_number, $order_id]);
    
    // Also update sales record if it exists
    $stmt = $pdo->prepare("
        UPDATE sales 
        SET tracking_number = ?, updated_at = NOW()
        WHERE customer_order_id = ?
    ");
    $stmt->execute([$tracking_number, $order_id]);
}

/**
 * Mark order as completed
 */
function markOrderAsCompleted($pdo, $order_id) {
    // Update completion date
    $stmt = $pdo->prepare("
        UPDATE customer_orders 
        SET completion_date = NOW(), updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
    
    // Also update sales record if it exists
    $stmt = $pdo->prepare("
        UPDATE sales 
        SET completion_date = NOW(), updated_at = NOW()
        WHERE customer_order_id = ?
    ");
    $stmt->execute([$order_id]);
}

/**
 * Handle order cancellation
 */
function handleOrderCancellation($pdo, $order_id, $notes) {
    // Update cancellation details
    $stmt = $pdo->prepare("
        UPDATE customer_orders 
        SET cancellation_date = NOW(), cancellation_reason = ?, updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->execute([$notes, $order_id]);

    // Restore inventory stock for all items in the order
    $stmt = $pdo->prepare("
        SELECT od.item_id, od.quantity
        FROM customer_order_details od
        WHERE od.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    foreach ($order_items as $item) {
        // Update inventory stock
        $updateStmt = $pdo->prepare("
            UPDATE inventory 
            SET current_stock = current_stock + ? 
            WHERE item_id = ?
        ");
        $updateStmt->execute([$item['quantity'], $item['item_id']]);

        // Log stock restoration
        $logStmt = $pdo->prepare("
            INSERT INTO stock_logs 
            (item_id, quantity, type, reason, created_at)
            VALUES (?, ?, 'addition', 'Order cancellation (admin)', NOW())
        ");
        $logStmt->execute([
            $item['item_id'], $item['quantity']
        ]);
    }
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($order) {
    // This would integrate with your email system
    error_log("Order confirmation email sent for order {$order['order_number']} to {$order['email']}");
}

/**
 * Send shipping notification
 */
function sendShippingNotification($order, $tracking_number) {
    // This would integrate with your email system
    error_log("Shipping notification sent for order {$order['order_number']} with tracking {$tracking_number}");
}

/**
 * Send delivery confirmation
 */
function sendDeliveryConfirmation($order) {
    // This would integrate with your email system
    error_log("Delivery confirmation sent for order {$order['order_number']}");
}

/**
 * Send cancellation notification
 */
function sendCancellationNotification($order, $notes) {
    // This would integrate with your email system
    error_log("Cancellation notification sent for order {$order['order_number']} with reason: {$notes}");
}
?> 