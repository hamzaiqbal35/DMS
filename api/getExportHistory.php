<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../inc/config/database.php";
require_once "../inc/helpers.php";
header('Content-Type: application/json');

try {
    // Get current user ID
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('User not authenticated');
    }

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
    
    // Filter history for current user
    $userHistory = array_filter($history, function($export) use ($userId) {
        return $export['user_id'] == $userId;
    });
    
    // Sort by date (newest first)
    usort($userHistory, function($a, $b) {
        return strtotime($b['export_date']) - strtotime($a['export_date']);
    });
    
    // Limit to last 50 exports
    $userHistory = array_slice($userHistory, 0, 50);
    
    // Format the data for frontend
    $formattedExports = [];
    foreach ($userHistory as $export) {
        $formattedExports[] = [
            'id' => $export['id'],
            'date' => date('d M Y, H:i', strtotime($export['export_date'])),
            'type' => ucfirst($export['export_type']),
            'format' => strtoupper($export['export_format']),
            'range' => $export['date_range'] ?: 'All Time',
            'user' => $export['exported_by'],
            'size' => $export['file_size'] ? formatBytes($export['file_size']) : 'N/A',
            'filename' => $export['file_name'] ?? 'N/A',
            'file_path' => isset($export['file_path']) ? $export['file_path'] : null
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formattedExports,
        'count' => count($formattedExports)
    ]);

} catch (Exception $e) {
    error_log("Get Export History Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch export history'
    ]);
}

// Helper function to format file size
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?> 