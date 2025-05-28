<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Validate purchase ID
    $purchase_id = intval($_POST['purchase_id'] ?? 0);
    if ($purchase_id <= 0) {
        throw new Exception('Invalid purchase ID.');
    }

    // Check if purchase exists
    $checkStmt = $pdo->prepare("SELECT purchase_id FROM purchases WHERE purchase_id = ?");
    $checkStmt->execute([$purchase_id]);
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Purchase not found.');
    }

    // Validate file upload
    if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred.');
    }

    $file = $_FILES['invoice_file'];
    
    // Validate file type
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only PDF, JPEG, and PNG files are allowed.');
    }

    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds the 5MB limit.');
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../../uploads/invoices/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'invoice_' . $purchase_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save the uploaded file.');
    }

    // Update purchase record with file reference
    $updateStmt = $pdo->prepare("
        UPDATE purchases 
        SET invoice_file = :invoice_file 
        WHERE purchase_id = :purchase_id
    ");
    
    $updateStmt->execute([
        ':invoice_file' => 'uploads/invoices/' . $new_filename,
        ':purchase_id' => $purchase_id
    ]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="invoice.pdf"');
    readfile($file_path);
    exit;

} catch (Exception $e) {
    error_log("Invoice Upload Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
