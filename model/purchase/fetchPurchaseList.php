<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.purchase_date,
            p.total_amount,
            p.payment_status,
            p.delivery_status,
            p.expected_delivery,
            p.invoice_file,
            p.notes,
            v.vendor_id,
            v.vendor_name,
            u.user_id,
            u.username AS created_by,
            p.created_at
        FROM purchases p
        INNER JOIN vendors v ON p.vendor_id = v.vendor_id
        INNER JOIN users u ON p.created_by = u.user_id
        ORDER BY p.purchase_date DESC
    ");
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $purchases
    ]);
} catch (PDOException $e) {
    error_log("Fetch Purchase List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch purchases.'
    ]);
}
