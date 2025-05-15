<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve inputs
    $category_name = trim($_POST['category_name'] ?? '');
    $status = strtolower(trim($_POST['status'] ?? 'active'));
    $description = trim($_POST['description'] ?? '');

    // Validate required inputs
    if (empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
        exit;
    }

    if (!in_array($status, ['active', 'inactive'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status provided.']);
        exit;
    }

    try {
        // Check for duplicate category name
        $checkQuery = "SELECT category_id FROM categories WHERE category_name = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$category_name]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category already exists.']);
            exit;
        }

        // Insert new category with description
        $insertQuery = "INSERT INTO categories (category_name, description, status, created_at) VALUES (?, ?, ?, NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$category_name, $description, $status]);

        echo json_encode(['status' => 'success', 'message' => 'Category added successfully.']);
    } catch (PDOException $e) {
        error_log("Database Error in addCategory.php: " . $e->getMessage(), 3, '../../error_log.log');
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again later.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
