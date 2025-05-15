<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Fetch item_id and item_name from inventory
    $stmt = $pdo->prepare("SELECT item_id, item_name FROM inventory ORDER BY item_name ASC");
    $stmt->execute();
    $items = $stmt->fetchAll();

    if ($items) {
        echo json_encode([
            "status" => "success",
            "data" => $items
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No items found."
        ]);
    }

} catch (Exception $e) {
    error_log("Show Item Names Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch item names."
    ]);
}
