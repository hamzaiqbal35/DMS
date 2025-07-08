<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            c.category_name,
            i.category_id,
            i.description,
            i.unit_of_measure,
            i.unit_price,
            i.customer_price,
            i.current_stock,
            i.minimum_stock,
            i.status,
            i.is_featured,
            i.show_on_website,
            i.seo_title,
            i.seo_description,
            i.created_at,
            i.updated_at
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        ORDER BY i.item_id ASC
    ");
    
    $stmt->execute();
    $items = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $items
    ]);
} catch (PDOException $e) {
    error_log("Fetch Item List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch inventory items."
    ]);
}
