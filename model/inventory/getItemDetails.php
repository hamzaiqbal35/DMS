<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
        throw new Exception("Item ID is required.");
    }

    $item_id = intval($_GET['item_id']);

    // Prepare query to fetch item with category name
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            c.category_name,
            i.description,
            i.unit_of_measure,
            i.unit_price,
            i.current_stock,
            i.minimum_stock,
            i.status,
            i.created_at,
            i.updated_at
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.item_id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if ($item) {
        echo json_encode([
            'status' => 'success',
            'data' => $item
        ]);
    } else {
        throw new Exception("Item not found.");
    }

} catch (Exception $e) {
    error_log("Get Item Details Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
