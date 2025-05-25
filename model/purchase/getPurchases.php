<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            p.purchase_id,
            p.purchase_number,
            v.vendor_name,
            p.purchase_date,
            p.total_amount,
            p.payment_status,
            p.delivery_status,
            p.expected_delivery,
            u.full_name AS created_by,
            p.created_at
        FROM 
            purchases p
        LEFT JOIN 
            vendors v ON p.vendor_id = v.vendor_id
        LEFT JOIN 
            users u ON p.created_by = u.user_id
        ORDER BY 
            p.purchase_date DESC, p.purchase_id DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $purchases
    ]);
} catch (Exception $e) {
    error_log("Get Purchases Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch purchases.',
        'details' => $e->getMessage()
    ]);
}
