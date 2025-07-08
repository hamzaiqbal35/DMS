<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}

require_once "../inc/config/database.php";
require_once "../inc/helpers.php";
header('Content-Type: application/json');

try {
    global $pdo;
    
    // Get current user ID
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('User not authenticated');
    }

    // Get export ID to delete
    $exportId = $_POST['export_id'] ?? null;
    if (!$exportId) {
        throw new Exception('Export ID is required');
    }

    // First, get the export record to check ownership and get file path
    $stmt = $pdo->prepare("
        SELECT file_path, file_name 
        FROM export_history 
        WHERE export_id = ? AND user_id = ?
    ");
    $stmt->execute([$exportId, $userId]);
    $export = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$export) {
        throw new Exception('Export record not found or access denied');
    }

    // Delete the physical file if it exists
    $filePath = $export['file_path'];
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            error_log("Failed to delete export file: $filePath");
            // Continue with database deletion even if file deletion fails
        }
    }

    // Delete the database record
    $stmt = $pdo->prepare("
        DELETE FROM export_history 
        WHERE export_id = ? AND user_id = ?
    ");
    $result = $stmt->execute([$exportId, $userId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Export record deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete export record');
    }

} catch (Exception $e) {
    error_log("Delete Export History Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 