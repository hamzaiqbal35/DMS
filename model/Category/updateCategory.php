<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get and sanitize input
$input = $_POST;

$category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;
$category_name = isset($input['category_name']) ? trim($input['category_name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$status = isset($input['status']) ? trim($input['status']) : '';

if ($category_id <= 0 || empty($category_name) || !in_array($status, ['active', 'inactive'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input data.'
    ]);
    exit;
}

try {
    // Check if category exists
    $checkStmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = :category_id");
    $checkStmt->execute(['category_id' => $category_id]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Category not found.'
        ]);
        exit;
    }

    // Check for name conflict with another category
    $nameCheckStmt = $pdo->prepare("SELECT * FROM categories WHERE category_name = :category_name AND category_id != :category_id");
    $nameCheckStmt->execute([
        'category_name' => $category_name,
        'category_id' => $category_id
    ]);

    if ($nameCheckStmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Another category with this name already exists.'
        ]);
        exit;
    }

    // Update the category
    $updateStmt = $pdo->prepare("
        UPDATE categories 
        SET category_name = :category_name, description = :description, status = :status 
        WHERE category_id = :category_id
    ");

    $updateStmt->execute([
        'category_name' => $category_name,
        'description' => $description,
        'status' => $status,
        'category_id' => $category_id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Category updated successfully.'
    ]);

} catch (PDOException $e) {
    error_log("Database Error in updateCategories.php: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update category.'
    ]);
}
