<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    $material_id = intval($_GET['material_id'] ?? 0);
    
    if ($material_id <= 0) {
        throw new Exception('Invalid material ID.');
    }

    // Get current stock and minimum stock
    $stmt = $pdo->prepare("
        SELECT current_stock, minimum_stock 
        FROM raw_materials 
        WHERE material_id = ?
    ");
    $stmt->execute([$material_id]);
    $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentStock) {
         throw new Exception('Material not found.');
    }

    // Get stock history from purchases (addition)
    $purchaseStmt = $pdo->prepare("
        SELECT 
            p.purchase_date as date,
            pd.quantity as amount,
            'addition' as type,
            p.purchase_number as reference,
            'Purchase' as source
        FROM purchases p
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        WHERE pd.material_id = ?
    ");
    $purchaseStmt->execute([$material_id]);
    $purchaseHistory = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stock history from raw material stock logs (reduction, etc.)
    $logStmt = $pdo->prepare("
        SELECT 
            created_at as date,
            quantity as amount,
            type,
            reason as reference,
            'Log' as source
        FROM raw_material_stock_logs
        WHERE material_id = ?
    ");
     $logStmt->execute([$material_id]);
    $logHistory = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine and sort history
    $history = array_merge($purchaseHistory, $logHistory);

    // Sort by date descending
    usort($history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode([
        'status' => 'success',
        'data' => [
            'current_stock' => $currentStock['current_stock'],
            'minimum_stock' => $currentStock['minimum_stock'],
            'history' => $history
        ]
    ]);

} catch (Exception $e) {
    error_log("Get Stock History Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Get Stock History Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
} 