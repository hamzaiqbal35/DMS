<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Sanitize & validate input
    $customer_id         = intval($_POST['customer_id'] ?? 0);
    $customer_order_id   = intval($_POST['customer_order_id'] ?? 0); // New field for sales from orders
    $item_id             = intval($_POST['item_id'] ?? 0);
    $quantity            = floatval($_POST['quantity'] ?? 0);
    $unit_price          = floatval($_POST['unit_price'] ?? 0);
    $sale_date           = trim($_POST['sale_date'] ?? '');
    $payment_status      = trim($_POST['payment_status'] ?? 'pending');
    $order_status        = trim($_POST['order_status'] ?? 'pending');
    $tracking_number     = trim($_POST['tracking_number'] ?? '');
    $notes               = trim($_POST['notes'] ?? '');
    $created_by          = $_SESSION['user_id'] ?? 1;

    // Determine sale_type for database
    $sale_type = ($customer_order_id > 0) ? 'customer_order' : 'direct';

    // Validations
    if ($customer_id <= 0) {
        throw new Exception('Please select a valid customer.');
    }
    if (empty($sale_date)) {
        throw new Exception('Sale date is required.');
    }
    if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
        throw new Exception('Invalid payment status.');
    }
    if (!in_array($order_status, ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        throw new Exception('Invalid order status.');
    }

    // If this is a customer order sale, force payment_method and restrict payment_status
    if (!empty($customer_order_id)) {
        $payment_method = 'cod';
        if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
            $payment_status = 'pending';
        }
    }

    // If this is a sale from customer order, validate and fetch order details
    $order_details = [];
    $total_amount = 0;
    $completion_date = null;
    $cancellation_date = null;
    $cancellation_reason = null;
    
    if ($customer_order_id > 0) {
        // Prevent duplicate sale for the same customer order
        $existingSaleStmt = $pdo->prepare("SELECT sale_id FROM sales WHERE customer_order_id = ?");
        $existingSaleStmt->execute([$customer_order_id]);
        if ($existingSaleStmt->fetch()) {
            throw new Exception('A sale has already been created for this customer order.');
        }
        // Validate customer order exists and fetch status
        $orderStmt = $pdo->prepare("
            SELECT co.*, cu.admin_customer_id 
            FROM customer_orders co 
            JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id 
            WHERE co.order_id = ?
        ");
        $orderStmt->execute([$customer_order_id]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new Exception('Customer order not found.');
        }
        // Block sale creation if order is pending or cancelled
        if (in_array($order['order_status'], ['pending', 'cancelled'])) {
            throw new Exception('Cannot create a sale for an order that is pending or cancelled.');
        }
        // Verify customer matches
        if ($order['admin_customer_id'] != $customer_id) {
            throw new Exception('Customer order does not match selected customer.');
        }
        
        // Fetch order details
        $orderDetailsStmt = $pdo->prepare("
            SELECT cod.*, i.item_name, i.item_number 
            FROM customer_order_details cod 
            JOIN inventory i ON cod.item_id = i.item_id 
            WHERE cod.order_id = ?
        ");
        $orderDetailsStmt->execute([$customer_order_id]);
        $order_details = $orderDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($order_details)) {
            throw new Exception('No items found in customer order.');
        }
        
        // Calculate total from order details
        foreach ($order_details as $detail) {
            $total_amount += $detail['total_price'];
        }
        
        // Use order data for sale
        $tracking_number = $order['tracking_number'] ?: $tracking_number;
        $notes = $order['notes'] ?: $notes;
        // Enforce sale_type as 'customer_order' for customer order sales
        $sale_type = 'customer_order';
        // Set completion and cancellation dates based on order status
        if ($order_status === 'delivered') {
            $completion_date = date('Y-m-d H:i:s');
        } elseif ($order_status === 'cancelled') {
            $cancellation_date = date('Y-m-d H:i:s');
            $cancellation_reason = $notes ?: 'Cancelled by admin';
        }
    } else {
        // Direct sale validation
        if ($item_id <= 0) {
            throw new Exception('Please select a valid item.');
        }
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0.');
        }
        if ($unit_price <= 0) {
            throw new Exception('Unit price must be greater than 0.');
        }
        
        $total_amount = $quantity * $unit_price;
        
        // Set completion and cancellation dates based on order status
        if ($order_status === 'delivered') {
            $completion_date = date('Y-m-d H:i:s');
        } elseif ($order_status === 'cancelled') {
            $cancellation_date = date('Y-m-d H:i:s');
            $cancellation_reason = $notes ?: 'Cancelled by admin';
        }
    }

    // Generate invoice number
    $invoice_number = generateInvoiceNumber();

    // Calculate paid amount based on payment status
    $paid_amount = ($payment_status === 'paid') ? $total_amount : 0;

    // Insert into sales with all fields
    $saleStmt = $pdo->prepare("
        INSERT INTO sales (
            invoice_number,
            customer_id,
            customer_order_id,
            sale_date,
            payment_status,
            order_status,
            tracking_number,
            notes,
            created_by,
            total_amount,
            paid_amount,
            sale_type
        ) VALUES (
            :invoice_number,
            :customer_id,
            :customer_order_id,
            :sale_date,
            :payment_status,
            :order_status,
            :tracking_number,
            :notes,
            :created_by,
            :total_amount,
            :paid_amount,
            :sale_type
        )
    ");
    $saleStmt->execute([
        'invoice_number'    => $invoice_number,
        'customer_id'        => $customer_id,
        'customer_order_id'  => $customer_order_id > 0 ? $customer_order_id : null,
        'sale_date'          => $sale_date,
        'payment_status'     => $payment_status,
        'order_status'       => $order_status,
        'tracking_number'    => $tracking_number,
        'notes'              => $notes,
        'created_by'         => $created_by,
        'total_amount'       => $total_amount,
        'paid_amount'        => $paid_amount,
        'sale_type'          => $sale_type
    ]);

    $sale_id = $pdo->lastInsertId();

    // Insert sale details
    if ($customer_order_id > 0) {
        // Copy from order details
        foreach ($order_details as $detail) {
            $detailStmt = $pdo->prepare("
                INSERT INTO sale_details (
                    sale_id, item_id, quantity, unit_price, total_price
                ) VALUES (
                    :sale_id, :item_id, :quantity, :unit_price, :total_price
                )
            ");
            $detailStmt->execute([
                'sale_id'     => $sale_id,
                'item_id'     => $detail['item_id'],
                'quantity'    => $detail['quantity'],
                'unit_price'  => $detail['unit_price'],
                'total_price' => $detail['total_price']
            ]);
            
            // Log stock reduction for each item
            $logStmt = $pdo->prepare("
                INSERT INTO stock_logs (item_id, quantity, type, reason)
                VALUES (:item_id, :quantity, 'reduction', :reason)
            ");
            $logStmt->execute([
                'item_id'  => $detail['item_id'],
                'quantity' => $detail['quantity'],
                'reason'   => 'Sale from Order #' . $customer_order_id . ' - Invoice #' . $invoice_number
            ]);
        }
        
        // Update customer order status
        $updateOrderStmt = $pdo->prepare("
            UPDATE customer_orders 
            SET order_status = 'confirmed', updated_at = NOW() 
            WHERE order_id = ?
        ");
        $updateOrderStmt->execute([$customer_order_id]);
        
    } else {
        // Direct sale - single item
        $detailStmt = $pdo->prepare("
            INSERT INTO sale_details (
                sale_id, item_id, quantity, unit_price, total_price
            ) VALUES (
                :sale_id, :item_id, :quantity, :unit_price, :total_price
            )
        ");
        $detailStmt->execute([
            'sale_id'     => $sale_id,
            'item_id'     => $item_id,
            'quantity'    => $quantity,
            'unit_price'  => $unit_price,
            'total_price' => $total_amount
        ]);

        // Log stock reduction
        $logStmt = $pdo->prepare("
            INSERT INTO stock_logs (item_id, quantity, type, reason)
            VALUES (:item_id, :quantity, 'reduction', :reason)
        ");
        $logStmt->execute([
            'item_id'  => $item_id,
            'quantity' => $quantity,
            'reason'   => 'Direct Sale - Invoice #' . $invoice_number
        ]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Sale recorded successfully.',
        'data' => [
            'sale_id'        => $sale_id,
            'invoice_number' => $invoice_number,
            'total_amount'   => number_format($total_amount, 2, '.', ''),
            'is_from_order'  => $customer_order_id > 0
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Sale Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Sale DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}

// Helper function to generate invoice number
function generateInvoiceNumber() {
    $prefix = 'INV';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 8));
    return "{$prefix}-{$date}-{$random}";
}
?>
