<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    $exportId = $_POST['export_id'] ?? null;
    if (!$exportId) {
        throw new Exception('Export ID is required');
    }
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('User not authenticated');
    }
    $historyFile = '../uploads/export_history.json';
    if (!file_exists($historyFile)) {
        throw new Exception('No export history found');
    }
    $history = json_decode(file_get_contents($historyFile), true) ?? [];
    $newHistory = [];
    $deleted = false;
    foreach ($history as $record) {
        if ($record['id'] == $exportId && $record['user_id'] == $userId) {
            // Delete file if exists
            if (!empty($record['file_path']) && file_exists($record['file_path'])) {
                @unlink($record['file_path']);
            }
            $deleted = true;
            continue;
        }
        $newHistory[] = $record;
    }
    if (!$deleted) {
        throw new Exception('Export record not found or not authorized');
    }
    file_put_contents($historyFile, json_encode($newHistory, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 