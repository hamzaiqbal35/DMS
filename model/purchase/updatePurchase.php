<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $purchaseId = $data['purchase_id'] ?? null;
    $vendorId = $data['vendor_id'] ?? null;
    $purchaseDate = $data['purchase_date'] ?? null;
    $expectedDelivery = $data['expected_delivery'] ?? null;
    $notes = $data['notes'] ?? '';
    $totalAmount = $data['total_amount'] ?? 0;
    $items = $data['items'] ?? [];

    if (!$purchaseId || !$vendorId || !$purchaseDate || !$expectedDelivery || empty($items)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields or line items.'
        ]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Update the purchases table
        $updateQuery = "
            UPDATE purchases
            SET vendor_id = :vendor_id,
                purchase_date = :purchase_date,
                expected_delivery = :expected_delivery,
                notes = :notes,
                total_amount = :total_amount,
                updated_at = NOW()
            WHERE purchase_id = :purchase_id
        ";
        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);
        $stmt->bindParam(':purchase_date', $purchaseDate);
        $stmt->bindParam(':expected_delivery', $expectedDelivery);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmt->execute();

        // 2. Delete existing line items
        $deleteQuery = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
        $stmtDelete = $db->prepare($deleteQuery);
        $stmtDelete->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmtDelete->execute();

        // 3. Insert updated line items
        $insertQuery = "
            INSERT INTO purchase_items (purchase_id, material_id, quantity, unit_price, total_price)
            VALUES (:purchase_id, :material_id, :quantity, :unit_price, :total_price)
        ";
        $stmtInsert = $db->prepare($insertQuery);

        foreach ($items as $item) {
            if (!isset($item['material_id'], $item['quantity'], $item['unit_price'], $item['total_price'])) {
                throw new Exception("Invalid item format.");
            }

            $stmtInsert->execute([
                ':purchase_id' => $purchaseId,
                ':material_id' => $item['material_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':total_price' => $item['total_price']
            ]);
        }

        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase updated successfully.'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update Purchase Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update purchase.',
            'details' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
