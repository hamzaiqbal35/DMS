<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Fetch the last item_number and increment it
    $stmt = $pdo->query("SELECT item_number FROM inventory ORDER BY item_id DESC LIMIT 1");
    $lastItem = $stmt->fetch();

    // If no items exist yet, start from a base code
    if ($lastItem && isset($lastItem['item_number'])) {
        // Extract number part from format like "ITEM-0001"
        $parts = explode('-', $lastItem['item_number']);
        $number = isset($parts[1]) ? (int)$parts[1] + 1 : 1;
    } else {
        $number = 1;
    }

    // Generate new item number in format ITEM-0001
    $newItemNumber = 'ITEM-' . str_pad($number, 4, '0', STR_PAD_LEFT);

    echo json_encode([
        "status" => "success",
        "item_number" => $newItemNumber
    ]);
} catch (Exception $e) {
    error_log("Show Item Number Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to generate item number."
    ]);
}
