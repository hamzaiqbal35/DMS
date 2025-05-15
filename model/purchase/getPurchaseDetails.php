<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$purchase_id = isset($_GET['purchase_id']) ? intval($_GET['purchase_id']) : 0;

if ($purchase_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid purchase ID.']);
    exit;
}

try {
    // Fetch main purchase information
    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.vendor_id,
            v.vendor_name,
            p.purchase_date,
            p.expected_delivery,
            p.total_amount,
            p.payment_status,
            p.delivery_status,
            p.invoice_file,
            p.notes
        FROM purchases p
        INNER JOIN vendors v ON p.vendor_id = v.vendor_id
        WHERE p.purchase_id = :purchase_id
    ");
    $stmt->execute(['purchase_id' => $purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        echo json_encode(['status' => 'error', 'message' => 'Purchase not found.']);
        exit;
    }

    // Fetch itemized details
    $detailStmt = $pdo->prepare("
        SELECT 
            pd.purchase_detail_id,
            pd.item_id,
            i.item_name,
            pd.quantity,
            pd.unit_price,
            pd.discount,
            pd.tax,
            pd.total_price
        FROM purchase_details pd
        INNER JOIN inventory i ON pd.item_id = i.item_id
        WHERE pd.purchase_id = :purchase_id
    ");
    $detailStmt->execute(['purchase_id' => $purchase_id]);
    $items = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    // Return both main and detail records
    echo json_encode([
        'status' => 'success',
        'purchase' => $purchase,
        'items' => $items
    ]);

} catch (PDOException $e) {
    error_log("Get Purchase Details Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve purchase details.'
    ]);
}
