<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            p.purchase_id,
            v.vendor_name,
            p.purchase_date
        FROM 
            purchases p
        LEFT JOIN 
            vendors v ON p.vendor_id = v.vendor_id
        ORDER BY 
            p.purchase_id DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);
} catch (Exception $e) {
    error_log("Show Purchase IDs Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch purchase IDs.',
        'details' => $e->getMessage()
    ]);
}
