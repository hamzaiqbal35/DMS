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
    // Sanitize & validate input
    $purchase_id     = intval($_POST['purchase_id'] ?? 0);
    $vendor_id       = intval($_POST['vendor_id'] ?? 0);
    $material_id     = intval($_POST['material_id'] ?? 0);
    $quantity        = floatval($_POST['quantity'] ?? 0);
    $unit_price      = floatval($_POST['unit_price'] ?? 0);
    $tax_rate        = floatval($_POST['tax_rate'] ?? 0);
    $discount_rate   = floatval($_POST['discount_rate'] ?? 0);
    $purchase_date   = trim($_POST['purchase_date'] ?? '');
    $payment_status  = trim($_POST['payment_status'] ?? '');
    $delivery_status = trim($_POST['status'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');
    $expected_delivery = trim($_POST['expected_delivery'] ?? null);

    // Validate required fields
    if ($purchase_id <= 0) {
        throw new Exception('Invalid purchase ID.');
    }
    if ($vendor_id <= 0) {
        throw new Exception('Please select a valid vendor.');
    }
    if ($material_id <= 0) {
        throw new Exception('Please select a valid material.');
    }
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0.');
    }
    if ($unit_price <= 0) {
        throw new Exception('Unit price must be greater than 0.');
    }
    if (empty($purchase_date)) {
        throw new Exception('Purchase date is required.');
    }
    if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
        throw new Exception('Invalid payment status.');
    }
    if (!in_array($delivery_status, ['pending', 'in_transit', 'delivered', 'delayed'])) {
        throw new Exception('Invalid delivery status.');
    }

    // Calculate amounts
    $subtotal = $quantity * $unit_price;
    $tax_amount = ($subtotal * $tax_rate) / 100;
    $discount_amount = ($subtotal * $discount_rate) / 100;
    $total_amount = $subtotal + $tax_amount - $discount_amount;

    // Start transaction
    $pdo->beginTransaction();

    // Update purchase table
    $updatePurchase = $pdo->prepare("
        UPDATE purchases
        SET vendor_id = :vendor_id,
            purchase_date = :purchase_date,
            total_amount = :total_amount,
            payment_status = :payment_status,
            delivery_status = :delivery_status,
            expected_delivery = :expected_delivery,
            notes = :notes,
            updated_at = NOW()
        WHERE purchase_id = :purchase_id
    ");
    $updatePurchase->execute([
        'vendor_id'       => $vendor_id,
        'purchase_date'   => $purchase_date,
        'total_amount'    => $total_amount,
        'payment_status'  => $payment_status,
        'delivery_status' => $delivery_status,
        'expected_delivery'=> $expected_delivery ?: null,
        'notes'           => $notes,
        'purchase_id'     => $purchase_id
    ]);

    // Update purchase_details table
    $updateDetail = $pdo->prepare("
        UPDATE purchase_details
        SET material_id = :material_id,
            quantity = :quantity,
            unit_price = :unit_price,
            tax = :tax,
            discount = :discount,
            total_price = :total_price
        WHERE purchase_id = :purchase_id
    ");
    $updateDetail->execute([
        'material_id'  => $material_id,
        'quantity'     => $quantity,
        'unit_price'   => $unit_price,
        'tax'          => $tax_amount,
        'discount'     => $discount_amount,
        'total_price'  => $total_amount,
        'purchase_id'  => $purchase_id
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Purchase updated successfully.',
        'data' => [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_amount' => number_format($tax_amount, 2, '.', ''),
            'discount_amount' => number_format($discount_amount, 2, '.', ''),
            'total_amount' => number_format($total_amount, 2, '.', '')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update Purchase Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update Purchase Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
?>