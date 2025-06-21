<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../inc/config/database.php";
require_once "../inc/helpers.php";

function saveExportHistory($exportData) {
    try {
        // Get current user ID
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return false;

        // File to store export history
        $historyFile = '../uploads/export_history.json';
        
        // Create directory if it doesn't exist
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0755, true);
        }
        
        // Read existing history or create empty array
        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true) ?? [];
        } else {
            $history = [];
        }
        
        // Prevent duplicate export records (same user_id, export_type, export_format, and filters_applied in last 2 minutes)
        $now = time();
        $duplicate = false;
        $newFilters = json_encode($exportData['filters'] ?? []);
        foreach (array_reverse($history) as $record) {
            if (
                $record['user_id'] == $userId &&
                $record['export_type'] == $exportData['export_type'] &&
                $record['export_format'] == $exportData['export_format'] &&
                $record['filters_applied'] == $newFilters
            ) {
                $recordTime = strtotime($record['export_date']);
                if (abs($now - $recordTime) < 120) { // 2 minutes
                    $duplicate = true;
                    break;
                }
            }
        }
        if ($duplicate) return true;

        // Add new export record
        $newExport = [
            'id' => uniqid(),
            'user_id' => $userId,
            'export_type' => $exportData['export_type'],
            'export_format' => $exportData['export_format'],
            'date_range' => $exportData['date_range'] ?? '',
            'filters_applied' => json_encode($exportData['filters'] ?? []),
            'file_name' => $exportData['file_name'],
            'file_size' => $exportData['file_size'] ?? null,
            'file_path' => $exportData['file_path'] ?? null,
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown User'
        ];
        
        // Add to history
        $history[] = $newExport;
        
        // Keep only last 1000 exports to prevent file from getting too large
        if (count($history) > 1000) {
            $history = array_slice($history, -1000);
        }
        
        // Save back to file
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving export history: " . $e->getMessage());
        return false;
    }
}
?> 