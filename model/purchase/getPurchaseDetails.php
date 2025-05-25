<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchaseId = $_POST['purchase_id'] ?? null;

    if (!$purchaseId || !is_numeric($purchaseId)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing purchase ID'
        ]);
        exit;
    }

    try {
        // Fetch purchase header
        $queryHeader = "
            SELECT 
                p.purchase_id,
                p.purchase_number,
                p.vendor_id,
                v.vendor_name,
                p.purchase_date,
                p.expected_delivery,
                p.total_amount,
                p.notes,
                u.username AS created_by,
                p.created_at
            FROM purchases p
            INNER JOIN vendors v ON p.vendor_id = v.vendor_id
            INNER JOIN users u ON p.created_by = u.user_id
            WHERE p.purchase_id = :purchase_id
            LIMIT 1
        ";
        $stmtHeader = $db->prepare($queryHeader);
        $stmtHeader->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmtHeader->execute();
        $purchase = $stmtHeader->fetch(PDO::FETCH_ASSOC);

        if (!$purchase) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Purchase not found'
            ]);
            exit;
        }

        // Fetch line items (raw materials)
        $queryItems = "
            SELECT 
                pi.item_id,
                rm.material_name,
                pi.quantity,
                pi.unit_price,
                pi.total_price
            FROM purchase_items pi
            INNER JOIN raw_materials rm ON pi.material_id = rm.material_id
            WHERE pi.purchase_id = :purchase_id
        ";
        $stmtItems = $db->prepare($queryItems);
        $stmtItems->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
        $stmtItems->execute();
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Final response
        echo json_encode([
            'status' => 'success',
            'purchase' => $purchase,
            'items' => $items
        ]);
    } catch (Exception $e) {
        error_log("Get Purchase Details Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch purchase details',
            'details' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
