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

// Validate core purchase data
$vendor_id         = intval($_POST['vendor_id'] ?? 0);
$purchase_date     = trim($_POST['purchase_date'] ?? '');
$payment_status    = trim($_POST['payment_status'] ?? 'pending');
$delivery_status   = trim($_POST['delivery_status'] ?? 'pending');
$expected_delivery = trim($_POST['expected_delivery'] ?? null);
$notes             = trim($_POST['notes'] ?? '');
$created_by        = $_SESSION['user_id'] ?? 0;

$items = $_POST['items'] ?? [];

if (
    $vendor_id <= 0 || empty($purchase_date) || 
    !in_array($payment_status, ['pending', 'partial', 'paid']) ||
    !in_array($delivery_status, ['pending', 'in_transit', 'delivered', 'delayed']) ||
    empty($items) || $created_by <= 0
) {
    echo json_encode(['status' => 'error', 'message' => 'Validation failed: All required fields must be provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Generate unique purchase number
    $purchase_number = 'PUR-' . time();

    // Calculate total cost from item details
    $total_amount = 0;
    foreach ($items as $item) {
        $quantity   = floatval($item['quantity'] ?? 0);
        $unit_price = floatval($item['unit_price'] ?? 0);
        $discount   = floatval($item['discount'] ?? 0);
        $tax        = floatval($item['tax'] ?? 0);

        if ($quantity <= 0 || $unit_price < 0) {
            throw new Exception("Invalid item quantity or unit price.");
        }

        $item_total = ($quantity * $unit_price) - $discount + $tax;
        $total_amount += $item_total;
    }

    // Insert into purchases
    $stmt = $pdo->prepare("
        INSERT INTO purchases (
            purchase_number, vendor_id, purchase_date, total_amount, 
            payment_status, delivery_status, expected_delivery, 
            invoice_file, notes, created_by
        ) VALUES (
            :purchase_number, :vendor_id, :purchase_date, :total_amount, 
            :payment_status, :delivery_status, :expected_delivery, 
            :invoice_file, :notes, :created_by
        )
    ");

    $invoice_file = null; // Handle invoice file later if needed
    $stmt->execute([
        'purchase_number'    => $purchase_number,
        'vendor_id'          => $vendor_id,
        'purchase_date'      => $purchase_date,
        'total_amount'       => $total_amount,
        'payment_status'     => $payment_status,
        'delivery_status'    => $delivery_status,
        'expected_delivery'  => $expected_delivery ?: null,
        'invoice_file'       => $invoice_file,
        'notes'              => $notes,
        'created_by'         => $created_by
    ]);

    $purchase_id = $pdo->lastInsertId();

    // Insert into purchase_details
    $detailStmt = $pdo->prepare("
        INSERT INTO purchase_details (
            purchase_id, item_id, quantity, unit_price, discount, tax, total_price
        ) VALUES (
            :purchase_id, :item_id, :quantity, :unit_price, :discount, :tax, :total_price
        )
    ");

    foreach ($items as $item) {
        $item_id    = intval($item['item_id']);
        $quantity   = floatval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        $discount   = floatval($item['discount'] ?? 0);
        $tax        = floatval($item['tax'] ?? 0);

        $total_price = ($quantity * $unit_price) - $discount + $tax;

        $detailStmt->execute([
            'purchase_id' => $purchase_id,
            'item_id'     => $item_id,
            'quantity'    => $quantity,
            'unit_price'  => $unit_price,
            'discount'    => $discount,
            'tax'         => $tax,
            'total_price' => $total_price
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase recorded successfully.',
        'purchase_id' => $purchase_id
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Purchase Insertion Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert purchase.']);
}
