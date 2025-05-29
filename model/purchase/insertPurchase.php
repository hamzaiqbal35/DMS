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
    $vendor_id       = intval($_POST['vendor_id'] ?? 0);
    $material_id     = intval($_POST['material_id'] ?? 0);
    $quantity        = floatval($_POST['quantity'] ?? 0);
    $unit_price      = floatval($_POST['unit_price'] ?? 0);
    $tax_rate        = floatval($_POST['tax_rate'] ?? 0);
    $discount_rate   = floatval($_POST['discount_rate'] ?? 0);
    $purchase_date   = trim($_POST['purchase_date'] ?? '');
    $payment_status  = trim($_POST['payment_status'] ?? 'pending');
    $delivery_status = trim($_POST['status'] ?? 'pending');
    $notes           = trim($_POST['notes'] ?? '');
    $created_by      = $_SESSION['user_id'] ?? 1;

    // Validate required fields
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

    // Generate unique purchase number
    $purchase_number = 'PUR-' . strtoupper(bin2hex(random_bytes(4)));

    // Insert into purchases table
    $insertPurchase = $pdo->prepare("
        INSERT INTO purchases (
            purchase_number, vendor_id, purchase_date, total_amount,
            payment_status, delivery_status, notes, created_by
        ) VALUES (
            :purchase_number, :vendor_id, :purchase_date, :total_amount,
            :payment_status, :delivery_status, :notes, :created_by
        )
    ");
    $insertPurchase->execute([
        'purchase_number'  => $purchase_number,
        'vendor_id'        => $vendor_id,
        'purchase_date'    => $purchase_date,
        'total_amount'     => $total_amount,
        'payment_status'   => $payment_status,
        'delivery_status'  => $delivery_status,
        'notes'            => $notes ?: null,
        'created_by'       => $created_by
    ]);
    
    $purchase_id = $pdo->lastInsertId();
    
    // Insert into purchase_details table
    $insertDetail = $pdo->prepare("
        INSERT INTO purchase_details (
            purchase_id, material_id, quantity, unit_price, 
            tax, discount, total_price
        ) VALUES (
            :purchase_id, :material_id, :quantity, :unit_price,
            :tax, :discount, :total_price
        )
    ");
    $insertDetail->execute([
        'purchase_id'   => $purchase_id,
        'material_id'   => $material_id,
        'quantity'      => $quantity,
        'unit_price'    => $unit_price,
        'tax'           => $tax_amount,
        'discount'      => $discount_amount,
        'total_price'   => $total_amount
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase added successfully.',
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
    error_log("Insert Purchase Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Purchase Database Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
