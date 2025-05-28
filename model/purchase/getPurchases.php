<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Fetch purchases with vendor name and total amount
    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchase_number,
            v.vendor_name,
            p.purchase_date,
            p.total_amount,
            p.payment_status,
            p.delivery_status,
            p.notes,
            p.created_at
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($purchases && count($purchases) > 0) {
        echo json_encode([
            'status' => 'success',
            'data' => $purchases
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No purchases found.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Error in getPurchases.php: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error while fetching purchases.'
    ]);
}
