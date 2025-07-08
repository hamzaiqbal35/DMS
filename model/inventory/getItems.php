<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

try {
    // Fetch only active items with their categories
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            i.unit_of_measure,
            i.unit_price,
            i.customer_price,
            i.current_stock,
            i.show_on_website,
            i.is_featured,
            c.category_name
        FROM inventory i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE i.status = 'active'
        ORDER BY i.item_name ASC
    ");
    
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $items
    ]);

} catch (PDOException $e) {
    error_log("Get Items Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch items'
    ]);
} 