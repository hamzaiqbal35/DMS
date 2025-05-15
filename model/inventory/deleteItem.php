<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Get and validate item_id
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

    if ($item_id <= 0) {
        throw new Exception("Invalid item ID.");
    }

    // Optional: Check if item exists before deleting
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE item_id = ?");
    $checkStmt->execute([$item_id]);
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception("Item not found.");
    }

    // Perform deletion
    $deleteStmt = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
    $success = $deleteStmt->execute([$item_id]);

    if ($success) {
        echo json_encode([
            "status" => "success",
            "message" => "Item deleted successfully."
        ]);
    } else {
        throw new Exception("Item deletion failed.");
    }

} catch (Exception $e) {
    error_log("Delete Item Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete item: " . $e->getMessage()
    ]);
}
