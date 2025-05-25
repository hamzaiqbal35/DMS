<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $purchaseId = $data['purchase_id'] ?? null;

    if (!$purchaseId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Purchase ID is required.'
        ]);
        exit;
    }

    try {
        $db->beginTransaction();

        // Step 1: Delete associated purchase items
        $deleteItemsQuery = "DELETE FROM purchase_items WHERE purchase_id = :purchase_id";
        $stmtItems = $db->prepare($deleteItemsQuery);
        $stmtItems->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmtItems->execute();

        // Step 2: Delete the purchase itself
        $deletePurchaseQuery = "DELETE FROM purchases WHERE purchase_id = :purchase_id";
        $stmtPurchase = $db->prepare($deletePurchaseQuery);
        $stmtPurchase->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmtPurchase->execute();

        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase deleted successfully.'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Delete Purchase Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete purchase.',
            'details' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
