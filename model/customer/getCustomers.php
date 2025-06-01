<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

try {
    // Fetch only active customers
    $stmt = $pdo->prepare("
        SELECT 
            customer_id,
            customer_name,
            phone,
            email,
            address,
            city,
            state,
            zip_code
        FROM customers 
        WHERE status = 'active'
        ORDER BY customer_name ASC
    ");
    
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $customers
    ]);

} catch (PDOException $e) {
    error_log("Get Customers Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch customers'
    ]);
} 