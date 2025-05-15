<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Get and sanitize inputs
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity_to_add = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

    // Validate inputs
    if ($item_id <= 0 || $quantity_to_add <= 0) {
        throw new Exception("Item and quantity are required and must be valid.");
    }

    // Check if item exists
    $check = $pdo->prepare("SELECT current_stock FROM inventory WHERE item_id = ?");
    $check->execute([$item_id]);
    $item = $check->fetch();

    if (!$item) {
        throw new Exception("Item not found.");
    }

    // Update stock
    $update = $pdo->prepare("UPDATE inventory SET current_stock = current_stock + ? WHERE item_id = ?");
    $update->execute([$quantity_to_add, $item_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Stock added successfully.'
    ]);
} catch (Exception $e) {
    error_log("Add Stock Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
