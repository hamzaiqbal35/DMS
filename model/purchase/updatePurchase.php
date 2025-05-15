<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Parse input
$data = json_decode(file_get_contents("php://input"), true);

$purchase_id = intval($data['purchase_id'] ?? 0);
$purchase_number = trim($data['purchase_number'] ?? '');
$vendor_id = intval($data['vendor_id'] ?? 0);
$purchase_date = trim($data['purchase_date'] ?? '');
$expected_delivery = trim($data['expected_delivery'] ?? '');
$payment_status = strtolower(trim($data['payment_status'] ?? 'pending'));
$delivery_status = strtolower(trim($data['delivery_status'] ?? 'pending'));
$notes = trim($data['notes'] ?? '');
$total_amount = floatval($data['total_amount'] ?? 0);
$items = $data['items'] ?? [];

$allowed_payment = ['pending', 'partial', 'paid'];
$allowed_delivery = ['pending', 'in_transit', 'delivered', 'delayed'];

// Validation
if (
    $purchase_id <= 0 || empty($purchase_number) || $vendor_id <= 0 ||
    empty($purchase_date) || !in_array($payment_status, $allowed_payment) ||
    !in_array($delivery_status, $allowed_delivery) || $total_amount < 0 ||
    empty($items) || !is_array($items)
) {
    echo json_encode(['status' => 'error', 'message' => 'Validation failed. Please check all fields.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update purchases table
    $updatePurchase = $pdo->prepare("
        UPDATE purchases SET
            purchase_number = :purchase_number,
            vendor_id = :vendor_id,
            purchase_date = :purchase_date,
            expected_delivery = :expected_delivery,
            total_amount = :total_amount,
            payment_status = :payment_status,
            delivery_status = :delivery_status,
            notes = :notes,
            updated_at = NOW()
        WHERE purchase_id = :purchase_id
    ");
    $updatePurchase->execute([
        'purchase_number' => $purchase_number,
        'vendor_id' => $vendor_id,
        'purchase_date' => $purchase_date,
        'expected_delivery' => $expected_delivery ?: null,
        'total_amount' => $total_amount,
        'payment_status' => $payment_status,
        'delivery_status' => $delivery_status,
        'notes' => $notes,
        'purchase_id' => $purchase_id
    ]);

    // Clear old purchase details
    $pdo->prepare("DELETE FROM purchase_details WHERE purchase_id = ?")->execute([$purchase_id]);

    // Insert new purchase details
    $insertDetail = $pdo->prepare("
        INSERT INTO purchase_details (
            purchase_id, item_id, quantity, unit_price, discount, tax, total_price
        ) VALUES (
            :purchase_id, :item_id, :quantity, :unit_price, :discount, :tax, :total_price
        )
    ");

    foreach ($items as $item) {
        $item_id = intval($item['item_id'] ?? 0);
        $quantity = floatval($item['quantity'] ?? 0);
        $unit_price = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);
        $tax = floatval($item['tax'] ?? 0);
        $total_price = floatval($item['total_price'] ?? 0);

        if ($item_id <= 0 || $quantity <= 0 || $unit_price < 0 || $total_price < 0) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Invalid item data provided.']);
            exit;
        }

        $insertDetail->execute([
            'purchase_id' => $purchase_id,
            'item_id' => $item_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'discount' => $discount,
            'tax' => $tax,
            'total_price' => $total_price
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Purchase updated successfully.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Update Purchase Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error while updating purchase.']);
}
