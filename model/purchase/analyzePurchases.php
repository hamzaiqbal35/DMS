<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get monthly total purchases
    $monthlyPurchases = $pdo->query("
        SELECT 
            DATE_FORMAT(purchase_date, '%Y-%m') as month,
            SUM(total_amount) as total_amount,
            COUNT(*) as purchase_count
        FROM purchases
        WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get top vendors by value
    $topVendors = $pdo->query("
        SELECT 
            v.vendor_name,
            COUNT(p.purchase_id) as purchase_count,
            SUM(p.total_amount) as total_value
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        WHERE p.purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY v.vendor_id, v.vendor_name
        ORDER BY total_value DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get most frequently purchased items
    $topItems = $pdo->query("
        SELECT 
            rm.material_name,
            COUNT(pd.purchase_detail_id) as purchase_count,
            SUM(pd.quantity) as total_quantity,
            SUM(pd.total_price) as total_value
        FROM purchase_details pd
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        JOIN purchases p ON pd.purchase_id = p.purchase_id
        WHERE p.purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY rm.material_id, rm.material_name
        ORDER BY purchase_count DESC, total_value DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get purchase status distribution
    $statusDistribution = $pdo->query("
        SELECT 
            delivery_status,
            COUNT(*) as count,
            SUM(total_amount) as total_value
        FROM purchases
        WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY delivery_status
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get payment status distribution
    $paymentDistribution = $pdo->query("
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(total_amount) as total_value
        FROM purchases
        WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY payment_status
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'monthly_purchases' => $monthlyPurchases,
            'top_vendors' => $topVendors,
            'top_items' => $topItems,
            'status_distribution' => $statusDistribution,
            'payment_distribution' => $paymentDistribution
        ]
    ]);

} catch (PDOException $e) {
    error_log("Purchase Analytics Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch purchase analytics data.'
    ]);
}
?>
