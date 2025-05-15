<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT purchase_id, purchase_number 
        FROM purchases 
        ORDER BY created_at DESC
    ");
    $stmt->execute();

    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($purchases) {
        echo json_encode([
            'status' => 'success',
            'data' => $purchases
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No purchases found.'
        ]);
    }

} catch (PDOException $e) {
    error_log("showPurchaseIDs.php Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch purchase IDs.'
    ]);
}
