<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Get total inventory statistics (without reorder_level)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT i.item_id) as total_items,
            COALESCE(SUM(i.current_stock), 0) as total_stock
        FROM inventory i
        WHERE i.status = 'active'
    ");
    $stmt->execute();
    $inventoryStats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_items' => $inventoryStats['total_items'],
            'total_stock' => $inventoryStats['total_stock']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Fetch Inventory Stats Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
