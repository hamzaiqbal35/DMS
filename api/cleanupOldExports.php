<?php
// Run this script manually or as a scheduled task (cron job)
$exportsDir = __DIR__ . '/../uploads/exports/';
$days = 365; // Files older than 365 days will be deleted
$now = time();
$deleted = [];
$kept = [];

if (!is_dir($exportsDir)) {
    echo json_encode(['status' => 'error', 'message' => 'Exports directory does not exist.']);
    exit;
}

$files = glob($exportsDir . '*');
foreach ($files as $file) {
    if (is_file($file)) {
        $fileAge = ($now - filemtime($file)) / (60 * 60 * 24); // Age in days
        if ($fileAge > $days) {
            if (@unlink($file)) {
                $deleted[] = basename($file);
            } else {
                $kept[] = basename($file);
            }
        } else {
            $kept[] = basename($file);
        }
    }
}
echo json_encode([
    'status' => 'success',
    'deleted' => $deleted,
    'kept' => $kept,
    'message' => 'Cleanup complete. Deleted ' . count($deleted) . ' old export files.'
]); 