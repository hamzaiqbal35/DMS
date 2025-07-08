<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');
session_start();

try {
    $stmt = $pdo->prepare("SELECT category_id, category_name, description, status FROM categories ORDER BY category_id ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $categories
    ]);
} catch (PDOException $e) {
    error_log("Fetch Categories Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch categories"
    ]);
}
?>
