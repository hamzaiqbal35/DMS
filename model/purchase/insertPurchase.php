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
    // Collect and sanitize input
    $vendor_id       = intval($_POST['vendor_id'] ?? 0);
    $material_id     = intval($_POST['material_id'] ?? 0);
    $quantity        = floatval($_POST['quantity'] ?? 0);
    $unit_price      = floatval($_POST['unit_price'] ?? 0);
    $purchase_date   = trim($_POST['purchase_date'] ?? '');
    $payment_status  = trim($_POST['payment_status'] ?? 'pending');
    $delivery_status = trim($_POST['status'] ?? 'pending');
    $notes           = trim($_POST['notes'] ?? '');
    $created_by      = $_SESSION['user_id'] ?? 1;

    // Validate input
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

    $pdo->beginTransaction();

    // Generate unique purchase number
    $purchase_number = 'PUR-' . strtoupper(bin2hex(random_bytes(4)));

    // Calculate total amount
    $total_amount = $quantity * $unit_price;

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
        ':purchase_number'  => $purchase_number,
        ':vendor_id'        => $vendor_id,
        ':purchase_date'    => $purchase_date,
        ':total_amount'     => $total_amount,
        ':payment_status'   => $payment_status,
        ':delivery_status'  => $delivery_status,
        ':notes'            => $notes ?: null,
        ':created_by'       => $created_by
    ]);

    $purchase_id = $pdo->lastInsertId();

    // Insert into purchase_details table
    $insertDetail = $pdo->prepare("
        INSERT INTO purchase_details (
            purchase_id, material_id, quantity, unit_price, total_price
        ) VALUES (
            :purchase_id, :material_id, :quantity, :unit_price, :total_price
        )
    ");
    $insertDetail->execute([
        ':purchase_id'   => $purchase_id,
        ':material_id'   => $material_id,
        ':quantity'      => $quantity,
        ':unit_price'    => $unit_price,
        ':total_price'   => $total_amount
    ]);

    // Update raw materials stock
    $updateStock = $pdo->prepare("
        UPDATE raw_materials 
        SET current_stock = current_stock + :quantity 
        WHERE material_id = :material_id
    ");
    $updateStock->execute([
        ':quantity' => $quantity,
        ':material_id' => $material_id
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase added successfully.',
        'purchase_id' => $purchase_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Purchase Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
