<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Calculate date 30 days ago
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

    // Get total purchases amount for last 30 days
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(p.total_amount), 0) as total_purchases,
            COUNT(p.purchase_id) as total_orders
        FROM purchases p
        WHERE p.purchase_date >= :thirty_days_ago
    ");

    $stmt->execute(['thirty_days_ago' => $thirtyDaysAgo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format the total amount with 2 decimal places
    $totalPurchases = number_format($result['total_purchases'], 2, '.', '');
    $totalOrders = $result['total_orders'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_purchases' => $totalPurchases,
            'total_orders' => $totalOrders,
            'period' => 'Last 30 days'
        ]
    ]);

} catch (PDOException $e) {
    error_log("Fetch Purchase Data Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error. Please try again later.'
    ]);
}
