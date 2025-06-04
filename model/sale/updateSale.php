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

    // Start transaction
    $pdo->beginTransaction();

    // Update sales table
    $updateSale = $pdo->prepare("
        UPDATE sales
        SET customer_id = :customer_id,
            sale_date = :sale_date,
            total_amount = :total_amount,
            payment_status = :payment_status,
            notes = :notes,
            updated_at = NOW()
        WHERE sale_id = :sale_id
    ");
    $updateSale->execute([
        'customer_id'    => $customer_id,
        'sale_date'      => $sale_date,
        'total_amount'   => $total_amount,
        'payment_status' => $payment_status,
        'notes'          => $notes,
        'sale_id'        => $sale_id
    ]);

    // Update sale_details table
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
