<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Fetch all sale IDs from the sales table
    $stmt = $pdo->prepare("
        SELECT 
            sale_id
        FROM sales 
        ORDER BY sale_id ASC
    ");
    
    $stmt->execute();
    $saleIds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $saleIds
    ]);

} catch (PDOException $e) {
    error_log("Show Sale IDs Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while fetching sale IDs.'
    ]);
} catch (Exception $e) {
    error_log("Show Sale IDs Error: " . $e->getMessage(), 3, '../../error_log.log');
     echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.'
    ]);
}
