<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Check for valid request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Get media_id from POST
    $media_id = $_POST['media_id'] ?? null;

    if (!$media_id || !is_numeric($media_id)) {
        throw new Exception("Invalid or missing media ID.");
    }

    // Step 1: Fetch file path before deleting from DB
    $stmt = $pdo->prepare("SELECT file_path FROM media WHERE media_id = ?");
    $stmt->execute([$media_id]);
    $media = $stmt->fetch();

    if (!$media) {
        throw new Exception("Media not found.");
    }

    $file_path = "../../" . $media['file_path'];

    // Step 2: Delete record from database
    $stmt = $pdo->prepare("DELETE FROM media WHERE media_id = ?");
    $stmt->execute([$media_id]);

    // Step 3: Remove the file from server if exists
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Media deleted successfully."
    ]);
} catch (Exception $e) {
    error_log("Delete Media Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete media. " . $e->getMessage()
    ]);
}
