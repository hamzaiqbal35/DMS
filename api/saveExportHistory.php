<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix path issues by checking if we're in the api directory or root
$basePath = file_exists("../inc/config/database.php") ? "../" : "";
require_once $basePath . "inc/config/database.php";
require_once $basePath . "inc/helpers.php";

function saveExportHistory($exportData) {
    try {
        global $pdo;
        
        // Get current user ID
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("Export History Error: No user ID in session. Session data: " . json_encode($_SESSION));
            return false;
        }
        
        error_log("Export History Debug: Attempting to save export for user_id: $userId, export_type: " . ($exportData['export_type'] ?? 'unknown'));

        // Check for duplicate export records (same user_id, export_type, and filters_applied in last 2 minutes)
        $now = date('Y-m-d H:i:s');
        $twoMinutesAgo = date('Y-m-d H:i:s', strtotime('-2 minutes'));
        
        $filtersJson = json_encode($exportData['filters'] ?? []);
        
        $checkDuplicate = $pdo->prepare("
            SELECT export_id FROM export_history 
            WHERE user_id = ? 
            AND export_type = ? 
            AND filters_applied = ? 
            AND export_date > ?
        ");
        $checkDuplicate->execute([$userId, $exportData['export_type'], $filtersJson, $twoMinutesAgo]);
        
        if ($checkDuplicate->rowCount() > 0) {
            // Duplicate found, return true to avoid error
            return true;
        }

        // Insert new export record
        $stmt = $pdo->prepare("
            INSERT INTO export_history (
                user_id, 
                export_type, 
                file_name, 
                file_path, 
                file_size, 
                filters_applied, 
                export_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            $exportData['export_type'],
            $exportData['file_name'],
            $exportData['file_path'],
            $exportData['file_size'] ?? 0,
            $filtersJson,
            $now
        ]);
        
        if ($result) {
            error_log("Export history saved successfully for user $userId: " . $exportData['file_name']);
            return true;
        } else {
            error_log("Failed to save export history for user $userId");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error saving export history: " . $e->getMessage());
        return false;
    }
}
?> 