<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_GET['purchase_id']) || !is_numeric($_GET['purchase_id'])) {
        throw new Exception("Invalid or missing purchase ID.");
    }

    $purchase_id = intval($_GET['purchase_id']);

    // Fetch main purchase info
    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.vendor_id,
            v.vendor_name,
            p.purchase_date,
            p.payment_status,
            p.delivery_status as status,
            p.notes
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        WHERE p.purchase_id = ?
    ");
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception("Purchase not found.");
    }

    // Fetch purchase detail (only one material per purchase in this setup)
    $detailStmt = $pdo->prepare("
        SELECT 
            pd.material_id,
            rm.material_name,
            pd.quantity,
            pd.unit_price,
            pd.total_price
        FROM purchase_details pd
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        WHERE pd.purchase_id = ?
    ");
    $detailStmt->execute([$purchase_id]);
    $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);

    // Combine purchase and detail data for easier access in frontend
    $result = array_merge($purchase, [
        'material_id' => $detail['material_id'],
        'material_name' => $detail['material_name'],
        'quantity' => $detail['quantity'],
        'unit_price' => $detail['unit_price'],
        'total_price' => $detail['total_price']
    ]);

    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);

} catch (Exception $e) {
    error_log("Get Purchase Details Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>