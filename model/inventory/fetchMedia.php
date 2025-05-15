<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method.");
    }

    // Optional: Filter by item_id if provided
    $filterByItem = false;
    $item_id = null;
    if (isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
        $filterByItem = true;
        $item_id = intval($_GET['item_id']);
    }

    // Query
    $query = "
        SELECT 
            media.media_id,
            media.item_id,
            media.file_path,
            media.uploaded_at,
            inventory.item_name,
            inventory.item_number
        FROM media
        JOIN inventory ON media.item_id = inventory.item_id
    ";

    if ($filterByItem) {
        $query .= " WHERE media.item_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$item_id]);
    } else {
        $stmt = $pdo->query($query);
    }

    $media = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $media
    ]);
} catch (Exception $e) {
    error_log("Fetch Media Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch media."
    ]);
}
