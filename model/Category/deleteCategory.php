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

$input = $_POST;
$category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid category ID.'
    ]);
    exit;
}

try {
    $inventoryCheck = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE category_id = :category_id");
    $inventoryCheck->execute(['category_id' => $category_id]);
    if ($inventoryCheck->fetchColumn() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete category. It is assigned to one or more inventory items.'
        ]);
        exit;
    }

    $deleteStmt = $pdo->prepare("DELETE FROM categories WHERE category_id = :category_id");
    $deleteStmt->execute(['category_id' => $category_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Category deleted successfully.'
    ]);
} catch (PDOException $e) {
    error_log("Database Error in deleteCategories.php: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to delete category.'
    ]);
}