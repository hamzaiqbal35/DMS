<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Get total number of active customers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_customers FROM customers WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_customers' => $result['total_customers']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Fetch Customer Data Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
