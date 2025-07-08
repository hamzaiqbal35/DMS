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
    // Sanitize input
    $sale_id         = intval($_POST['sale_id'] ?? 0);
    $customer_id     = intval($_POST['customer_id'] ?? 0);
    $item_id         = intval($_POST['item_id'] ?? 0);
    $quantity        = floatval($_POST['quantity'] ?? 0);
    $unit_price      = floatval($_POST['unit_price'] ?? 0);
    $sale_date       = trim($_POST['sale_date'] ?? '');
    $payment_status  = trim($_POST['payment_status'] ?? '');
    $order_status    = trim($_POST['order_status'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');

    // Validate required fields
    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }
    if ($customer_id <= 0) {
        throw new Exception('Please select a valid customer.');
    }
    if ($item_id <= 0) {
        throw new Exception('Please select a valid item.');
    }
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0.');
    }
    if ($unit_price <= 0) {
        throw new Exception('Unit price must be greater than 0.');
    }
    if (empty($sale_date)) {
        throw new Exception('Sale date is required.');
    }
    if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
        throw new Exception('Invalid payment status.');
    }

    // Check if this sale is a direct sale or from a customer order
    $saleTypeStmt = $pdo->prepare("SELECT customer_order_id FROM sales WHERE sale_id = ?");
    $saleTypeStmt->execute([$sale_id]);
    $customer_order_id = $saleTypeStmt->fetchColumn();

    // Block update for customer order sales
    if (!empty($customer_order_id)) {
        echo json_encode(['success' => false, 'message' => 'Customer order sales cannot be edited.']);
        exit;
    }

    // If this is a sale from a customer order, do not allow order_status or sale_details update here
    if (!empty($customer_order_id)) {
        // Prevent update of order_status and sale_details for customer order sales
        $order_status = null; // Prevent update
        $preventSaleDetailsUpdate = true;
        // For customer order sales, use final_amount from customer_orders as total_amount
        $orderStmt = $pdo->prepare("SELECT final_amount FROM customer_orders WHERE order_id = ?");
        $orderStmt->execute([$customer_order_id]);
        $final_amount = $orderStmt->fetchColumn();
        $total_amount = $final_amount !== false ? $final_amount : 0;
    } else {
        $preventSaleDetailsUpdate = false;
        // For direct sales, recalculate total as quantity * unit_price
        $total_amount = $quantity * $unit_price;
    }

    if (!in_array($order_status, ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']) && is_null($order_status) === false) {
        throw new Exception('Invalid order status.');
    }

    // Recalculate total
    $total_amount = $quantity * $unit_price;

    // Get current paid amount
    $paidStmt = $pdo->prepare("SELECT paid_amount FROM sales WHERE sale_id = ?");
    $paidStmt->execute([$sale_id]);
    $paid_amount = $paidStmt->fetchColumn() ?: 0;

    // Recalculate payment status based on new total and paid amount
    if ($paid_amount >= $total_amount) {
        $payment_status = 'paid';
    } elseif ($paid_amount > 0) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'pending';
    }

    // If this is a customer order sale, force payment_method and restrict payment_status
    if (!empty($customer_order_id)) {
        $payment_method = 'cod';
        if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
            $payment_status = 'pending';
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update sales table
    $updateSaleQuery = "
        UPDATE sales
        SET customer_id = :customer_id,
            sale_date = :sale_date,
            total_amount = :total_amount,
            payment_status = :payment_status,
            notes = :notes,
            updated_at = NOW()";
    $updateParams = [
        'customer_id'    => $customer_id,
        'sale_date'      => $sale_date,
        'total_amount'   => $total_amount,
        'payment_status' => $payment_status,
        'notes'          => $notes,
        'sale_id'        => $sale_id
    ];
    if (empty($customer_order_id)) {
        // Only allow order_status update for direct sales
        $updateSaleQuery .= ", order_status = :order_status";
        $updateParams['order_status'] = $order_status;
    }
    $updateSaleQuery .= " WHERE sale_id = :sale_id";
    $updateSale = $pdo->prepare($updateSaleQuery);
    $updateSale->execute($updateParams);

    // Update sale_details table
    if (!$preventSaleDetailsUpdate) {
        // For direct sales, ensure only one sale_detail exists
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sale_details WHERE sale_id = ?");
        $countStmt->execute([$sale_id]);
        $detailCount = $countStmt->fetchColumn();
        if ($detailCount > 1) {
            throw new Exception('Direct sales should only have one sale detail record.');
        }
        $updateDetail = $pdo->prepare("
            UPDATE sale_details
            SET item_id = :item_id,
                quantity = :quantity,
                unit_price = :unit_price,
                total_price = :total_price
            WHERE sale_id = :sale_id
        ");
        $updateDetail->execute([
            'item_id'     => $item_id,
            'quantity'    => $quantity,
            'unit_price'  => $unit_price,
            'total_price' => $total_amount,
            'sale_id'     => $sale_id
        ]);
    }

    // Commit changes
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Sale updated successfully.',
        'data' => [
            'total_amount' => number_format($total_amount, 2, '.', '')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Update Sale Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Update Sale Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
