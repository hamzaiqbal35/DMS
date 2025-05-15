<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Validate item_id
    if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
        throw new Exception("Item ID is required.");
    }

    $item_id = intval($_POST['item_id']);

    if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No image uploaded or upload failed.");
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $file = $_FILES['media_file'];

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Only JPG, JPEG, PNG, or WEBP images are allowed.");
    }

    // Uploads folder
    $uploadDir = '../../assets/images/inventory/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('inv_', true) . '.' . $extension;
    $destination = $uploadDir . $fileName;
    $relativePath = 'assets/images/inventory/' . $fileName;

    // Save file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to save uploaded file.");
    }

    // Insert record
    $stmt = $pdo->prepare("INSERT INTO media (item_id, file_path) VALUES (?, ?)");
    $stmt->execute([$item_id, $relativePath]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Image uploaded successfully.',
        'file_path' => $relativePath
    ]);
} catch (Exception $e) {
    error_log("Upload Image Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        'status' => 'error',
        'message' => "Upload Image Error: " . $e->getMessage()
    ]);
}
