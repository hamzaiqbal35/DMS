<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method.");
    }

    // Check for item_id in GET request
    if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
        throw new Exception("Invalid or missing item ID.");
    }

    $item_id = (int)$_GET['item_id'];

    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT item_name, current_stock, unit_of_measure FROM inventory WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if ($item) {
        echo json_encode([
            "status" => "success",
            "data" => [
                "item_name" => $item['item_name'],
                "current_stock" => $item['current_stock'],
                "unit_of_measure" => $item['unit_of_measure']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Item not found."
        ]);
    }

} catch (Exception $e) {
    error_log("Get Item Stock Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch stock. " . $e->getMessage()
    ]);
}
