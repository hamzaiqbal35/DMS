<?php
require_once '../../inc/config/database.php';

try {
    $stmt = $pdo->prepare("SELECT category_id, category_name, description, status, created_at FROM categories ORDER BY created_at DESC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($categories) > 0) {
        echo json_encode([
            'status' => 'success',
            'data' => $categories
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No categories found.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error in fetchCategories.php: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch categories.'
    ]);
}


