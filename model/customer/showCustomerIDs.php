<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    // Fetch active customers (you can modify to include all if needed)
    $stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customers WHERE status = 'active' ORDER BY customer_name ASC");
    $stmt->execute();

    $customers = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $customers
    ]);
    
} catch (Exception $e) {
    error_log("ShowCustomerIDs Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch customer list."
    ]);
}
