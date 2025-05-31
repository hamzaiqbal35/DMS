<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get current date
    $current_date = date('Y-m-d');

    // Update purchases that are past their expected delivery date and not delivered
    $updateStmt = $pdo->prepare("
        UPDATE purchases 
        SET delivery_status = 'delayed'
        WHERE expected_delivery IS NOT NULL 
        AND expected_delivery < :current_date
        AND delivery_status NOT IN ('delivered', 'delayed')
    ");

    $updateStmt->execute([':current_date' => $current_date]);
    $affected_rows = $updateStmt->rowCount();

    // Get count of currently delayed deliveries
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as delayed_count
        FROM purchases
        WHERE delivery_status = 'delayed'
    ");
    $countStmt->execute();
    $delayed_count = $countStmt->fetch(PDO::FETCH_ASSOC)['delayed_count'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'newly_flagged' => $affected_rows,
            'total_delayed' => $delayed_count
        ]
    ]);

} catch (PDOException $e) {
    error_log("Flag Delayed Deliveries Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error while flagging delayed deliveries.'
    ]);
}
?>
