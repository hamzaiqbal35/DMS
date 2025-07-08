<?php
// downloadExport.php: Securely serve exported report files
session_name('admin_session');
session_start();
require_once '../inc/config/auth.php';
require_jwt_auth();

$exportsDir = realpath(__DIR__ . '/../uploads/exports');
if (!$exportsDir) {
    http_response_code(404);
    echo 'Export directory not found.';
    exit;
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    echo 'Missing file parameter.';
    exit;
}

$filename = basename($_GET['file']); // Prevent directory traversal
$filePath = $exportsDir . DIRECTORY_SEPARATOR . $filename;

// Security: Only allow files inside the exports directory
if (strpos(realpath($filePath), $exportsDir) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Stream the file
readfile($filePath);
exit; 