<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    $purchase_id = intval($_GET['purchase_id'] ?? 0);
    
    if ($purchase_id <= 0) {
        throw new Exception('Invalid purchase ID.');
    }

    $stmt = $pdo->prepare("
        SELECT p.*, pd.material_id, pd.quantity, pd.unit_price, 
               pd.tax as tax_amount, pd.discount as discount_amount,
               (pd.tax / (pd.quantity * pd.unit_price) * 100) as tax_rate,
               (pd.discount / (pd.quantity * pd.unit_price) * 100) as discount_rate
        FROM purchases p
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        WHERE p.purchase_id = :purchase_id
    ");
    
    $stmt->execute(['purchase_id' => $purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        throw new Exception('Purchase not found.');
    }

    echo json_encode([
        'status' => 'success',
        'data' => $purchase
    ]);

} catch (Exception $e) {
    error_log("Get Purchase Details Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Get Purchase Details Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
?>