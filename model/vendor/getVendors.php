<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT vendor_id, vendor_name 
        FROM vendors 
        WHERE status = 'active'
        ORDER BY vendor_name ASC
    ");
    $stmt->execute();

    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($vendors)) {
        echo json_encode([
            'status' => 'success',
            'data' => $vendors
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No vendors found.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Get Vendors Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch vendors.'
    ]);
}
