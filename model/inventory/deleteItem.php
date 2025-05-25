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

    // Start transaction
    $pdo->beginTransaction();

    try {
        // First, get all media files associated with this item
        $mediaStmt = $pdo->prepare("SELECT media_id, file_path FROM media WHERE item_id = ?");
        $mediaStmt->execute([$item_id]);
        $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // Delete media records from database
        $deleteMediaStmt = $pdo->prepare("DELETE FROM media WHERE item_id = ?");
        $deleteMediaStmt->execute([$item_id]);

        // Delete physical media files
        foreach ($mediaFiles as $media) {
            $file_path = "../../" . $media['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete associated stock logs
        $deleteStockLogsStmt = $pdo->prepare("DELETE FROM stock_logs WHERE item_id = ?");
        $deleteStockLogsStmt->execute([$item_id]);

        // Now delete the inventory item
        $deleteStmt = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
        $success = $deleteStmt->execute([$item_id]);

        if ($success) {
            $pdo->commit();
            echo json_encode([
                "status" => "success",
                "message" => "Item and associated records deleted successfully."
            ]);
        } else {
            throw new Exception("Item deletion failed.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Delete Item Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete item: " . $e->getMessage()
    ]);
}
