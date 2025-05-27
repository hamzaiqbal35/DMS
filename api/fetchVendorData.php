<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Get total number of active vendors
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_vendors FROM vendors WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_vendors' => $result['total_vendors']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Fetch Vendor Data Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
