<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build SQL query with JOINs
    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.vendor_id,
            v.vendor_name,
            rm.material_name,
            pd.quantity,
            pd.unit_price,
            pd.total_price,
            p.purchase_date,
            p.payment_status,
            p.delivery_status,
            p.invoice_file
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        ORDER BY p.purchase_date DESC, p.purchase_id DESC
    ");

    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($purchases) {
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
    error_log("Fetch Purchase List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error. Please try again later.'
    ]);
}
?>