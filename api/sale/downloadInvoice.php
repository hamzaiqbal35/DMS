<?php
// Download Invoice API: Checks due date, deletes if expired, serves file if valid

$uploadsDir = '../../uploads/invoices/';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || empty($_GET['filename'])) {
    error_log("Download Invoice Error: Invalid request method or missing filename");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$filename = basename(urldecode($_GET['filename']));
$pdfPath = $uploadsDir . $filename;
$jsonPath = $uploadsDir . pathinfo($filename, PATHINFO_FILENAME) . '.json';

// Log for debugging
error_log("Download Invoice Request: filename=$filename, pdfPath=$pdfPath, jsonPath=$jsonPath");

if (!file_exists($pdfPath)) {
    error_log("Download Invoice Error: File not found at $pdfPath");
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Invoice file not found.']);
    exit;
}

// Check file size and permissions
$fileSize = filesize($pdfPath);
$isReadable = is_readable($pdfPath);
error_log("Download Invoice Debug: fileSize=$fileSize, isReadable=" . ($isReadable ? 'true' : 'false'));

if (!$isReadable) {
    error_log("Download Invoice Error: File not readable at $pdfPath");
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'File access denied.']);
    exit;
}

// Check for metadata file (only exists for sales invoices)
if (file_exists($jsonPath)) {
    $meta = json_decode(file_get_contents($jsonPath), true);
    $dueDate = $meta['due_date'] ?? null;

    if ($dueDate && strtotime($dueDate) < strtotime('today')) {
        // Expired: delete both files
        @unlink($pdfPath);
        @unlink($jsonPath);
        error_log("Download Invoice Error: File expired and deleted - $filename");
        http_response_code(410);
        echo json_encode(['status' => 'error', 'message' => 'Invoice expired. Please generate again.']);
        exit;
    }
}

// Clear any output buffers to prevent corruption
while (ob_get_level()) {
    ob_end_clean();
}

// Set proper headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

error_log("Download Invoice Success: Serving file $filename (size: $fileSize bytes)");

// Ensure no whitespace or other output before file content
readfile($pdfPath);
exit; 