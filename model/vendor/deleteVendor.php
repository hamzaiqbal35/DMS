<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Get vendor_id from POST
    $vendor_id = $_POST['vendor_id'] ?? null;

    if (!$vendor_id || !is_numeric($vendor_id)) {
        throw new Exception("Invalid or missing vendor ID.");
    }

    // Prepare and execute delete query
    $stmt = $pdo->prepare("DELETE FROM vendors WHERE vendor_id = ?");
    $stmt->execute([$vendor_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Vendor deleted successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Vendor not found or already deleted."
        ]);
    }

} catch (Exception $e) {
    error_log("Delete Vendor Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete vendor. " . $e->getMessage()
    ]);
}
