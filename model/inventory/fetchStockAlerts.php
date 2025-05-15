<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Prepare the query to find low stock items
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            c.category_name,
            i.unit_of_measure,
            i.unit_price,
            i.current_stock,
            i.minimum_stock,
            i.status
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.current_stock < i.minimum_stock
        ORDER BY i.current_stock ASC
    ");
    
    $stmt->execute();
    $items = $stmt->fetchAll();

    if ($items) {
        echo json_encode([
            "status" => "success",
            "data" => $items
        ]);
    } else {
        echo json_encode([
            "status" => "empty",
            "message" => "No stock alerts at the moment."
        ]);
    }

} catch (Exception $e) {
    error_log("Fetch Stock Alerts Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch stock alerts."
    ]);
}
