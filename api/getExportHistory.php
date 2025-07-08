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
        error_log("Get Export History Error: No user ID in session. Session data: " . json_encode($_SESSION));
        throw new Exception('User not authenticated');
    }
    
    error_log("Get Export History Debug: Fetching history for user_id: $userId");

    // Check if specific export ID is requested
    $exportId = $_GET['export_id'] ?? null;
    
    if ($exportId) {
        // Get specific export details
        $stmt = $pdo->prepare("
            SELECT 
                eh.export_id,
                eh.export_type,
                eh.file_name,
                eh.file_path,
                eh.file_size,
                eh.filters_applied,
                eh.export_date,
                u.full_name as exported_by
            FROM export_history eh
            JOIN users u ON eh.user_id = u.user_id
            WHERE eh.export_id = ? AND eh.user_id = ?
        ");
        $stmt->execute([$exportId, $userId]);
        $export = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$export) {
            throw new Exception('Export record not found');
        }
        
        // Format the data for frontend
        $formattedExport = [
            'id' => $export['export_id'],
            'date' => date('d M Y, H:i', strtotime($export['export_date'])),
            'type' => ucfirst($export['export_type']),
            'format' => strtoupper(pathinfo($export['file_name'], PATHINFO_EXTENSION)),
            'range' => 'All Time', // This could be extracted from filters_applied if needed
            'user' => $export['exported_by'],
            'size' => $export['file_size'] ? formatBytes($export['file_size']) : 'N/A',
            'filename' => $export['file_name'],
            'file_path' => '/DMS/api/downloadExport.php?file=' . urlencode($export['file_name'])
        ];
        
        echo json_encode([
            'status' => 'success',
            'data' => [$formattedExport]
        ]);
        
    } else {
        // Get all export history for the user
        $stmt = $pdo->prepare("
            SELECT 
                eh.export_id,
                eh.export_type,
                eh.file_name,
                eh.file_path,
                eh.file_size,
                eh.filters_applied,
                eh.export_date,
                u.full_name as exported_by
            FROM export_history eh
            JOIN users u ON eh.user_id = u.user_id
            WHERE eh.user_id = ?
            ORDER BY eh.export_date DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $exports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for frontend
        $formattedExports = [];
        foreach ($exports as $export) {
            // Extract date range from filters if available
            $dateRange = 'All Time';
            if ($export['filters_applied']) {
                $filters = json_decode($export['filters_applied'], true);
                if (isset($filters['date_range_label'])) {
                    $dateRange = $filters['date_range_label'];
                } elseif (isset($filters['date_from']) && isset($filters['date_to'])) {
                    $dateRange = $filters['date_from'] . ' to ' . $filters['date_to'];
                }
            }
            
            $formattedExports[] = [
                'id' => $export['export_id'],
                'date' => date('d M Y, H:i', strtotime($export['export_date'])),
                'type' => ucfirst($export['export_type']),
                'format' => strtoupper(pathinfo($export['file_name'], PATHINFO_EXTENSION)),
                'range' => $dateRange,
                'user' => $export['exported_by'],
                'size' => $export['file_size'] ? formatBytes($export['file_size']) : 'N/A',
                'filename' => $export['file_name'],
                'file_path' => '/DMS/api/downloadExport.php?file=' . urlencode($export['file_name'])
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $formattedExports,
            'count' => count($formattedExports)
        ]);
    }

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