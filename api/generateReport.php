<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get report parameters
    $reportType = isset($_GET['type']) ? $_GET['type'] : 'all';
    $period = isset($_GET['period']) ? intval($_GET['period']) : 12; // Default to 12 months
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

    // Set date range
    if ($startDate && $endDate) {
        $dateFilter = "BETWEEN :startDate AND :endDate";
        $params = [':startDate' => $startDate, ':endDate' => $endDate];
    } else {
        $dateFilter = ">= DATE_SUB(CURRENT_DATE, INTERVAL :period MONTH)";
        $params = [':period' => $period];
    }

    $response = ['status' => 'success', 'data' => []];

    // Customer Analytics
    if ($reportType === 'all' || $reportType === 'customers') {
        // Get customer statistics
        $customerStats = $pdo->prepare("
            SELECT 
                COUNT(*) as total_customers,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_customers,
                COUNT(DISTINCT CASE WHEN s.sale_id IS NOT NULL THEN c.customer_id END) as customers_with_purchases
            FROM customers c
            LEFT JOIN sales s ON c.customer_id = s.customer_id AND s.sale_date $dateFilter
        ");
        $customerStats->execute($params);
        $response['data']['customer_stats'] = $customerStats->fetch(PDO::FETCH_ASSOC);

        // Get top customers by purchase value
        $topCustomers = $pdo->prepare("
            SELECT 
                c.customer_name,
                COUNT(s.sale_id) as total_orders,
                SUM(s.total_amount) as total_spent,
                MAX(s.sale_date) as last_purchase_date
            FROM customers c
            JOIN sales s ON c.customer_id = s.customer_id
            WHERE s.sale_date $dateFilter
            GROUP BY c.customer_id, c.customer_name
            ORDER BY total_spent DESC
            LIMIT 5
        ");
        $topCustomers->execute($params);
        $response['data']['top_customers'] = $topCustomers->fetchAll(PDO::FETCH_ASSOC);
    }

    // Purchase Analysis
    if ($reportType === 'all' || $reportType === 'purchases') {
        // Get monthly purchase trends
        $monthlyPurchases = $pdo->prepare("
            SELECT 
                DATE_FORMAT(purchase_date, '%Y-%m') as month,
                COUNT(*) as purchase_count,
                SUM(total_amount) as total_amount,
                AVG(total_amount) as average_amount
            FROM purchases
            WHERE purchase_date $dateFilter
            GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $monthlyPurchases->execute($params);
        $response['data']['monthly_purchases'] = $monthlyPurchases->fetchAll(PDO::FETCH_ASSOC);

        // Get top vendors
        $topVendors = $pdo->prepare("
            SELECT 
                v.vendor_name,
                COUNT(p.purchase_id) as purchase_count,
                SUM(p.total_amount) as total_value,
                AVG(p.total_amount) as average_value
            FROM purchases p
            JOIN vendors v ON p.vendor_id = v.vendor_id
            WHERE p.purchase_date $dateFilter
            GROUP BY v.vendor_id, v.vendor_name
            ORDER BY total_value DESC
            LIMIT 5
        ");
        $topVendors->execute($params);
        $response['data']['top_vendors'] = $topVendors->fetchAll(PDO::FETCH_ASSOC);

        // Get purchase status distribution
        $purchaseStatus = $pdo->prepare("
            SELECT 
                payment_status,
                delivery_status,
                COUNT(*) as count,
                SUM(total_amount) as total_value
            FROM purchases
            WHERE purchase_date $dateFilter
            GROUP BY payment_status, delivery_status
        ");
        $purchaseStatus->execute($params);
        $response['data']['purchase_status'] = $purchaseStatus->fetchAll(PDO::FETCH_ASSOC);
    }

    // Sales Analysis
    if ($reportType === 'all' || $reportType === 'sales') {
        // Get monthly sales trends
        $monthlySales = $pdo->prepare("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                COUNT(*) as sale_count,
                SUM(total_amount) as total_amount,
                AVG(total_amount) as average_amount,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM sales
            WHERE sale_date $dateFilter
            GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $monthlySales->execute($params);
        $response['data']['monthly_sales'] = $monthlySales->fetchAll(PDO::FETCH_ASSOC);

        // Get top selling items
        $topItems = $pdo->prepare("
            SELECT 
                i.item_name,
                COUNT(sd.sale_detail_id) as times_sold,
                SUM(sd.quantity) as total_quantity,
                SUM(sd.total_price) as total_revenue
            FROM sale_details sd
            JOIN inventory i ON sd.item_id = i.item_id
            JOIN sales s ON sd.sale_id = s.sale_id
            WHERE s.sale_date $dateFilter
            GROUP BY i.item_id, i.item_name
            ORDER BY total_revenue DESC
            LIMIT 5
        ");
        $topItems->execute($params);
        $response['data']['top_items'] = $topItems->fetchAll(PDO::FETCH_ASSOC);

        // Get payment status distribution
        $paymentStatus = $pdo->prepare("
            SELECT 
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as paid_amount
            FROM sales
            WHERE sale_date $dateFilter
            GROUP BY payment_status
        ");
        $paymentStatus->execute($params);
        $response['data']['payment_status'] = $paymentStatus->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inventory Analysis
    if ($reportType === 'all' || $reportType === 'inventory') {
        // Get inventory status
        $inventoryStatus = $pdo->prepare("
            SELECT 
                c.category_name,
                COUNT(i.item_id) as total_items,
                SUM(i.current_stock) as total_stock,
                SUM(i.current_stock * i.unit_price) as total_value,
                COUNT(CASE WHEN i.current_stock = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN i.current_stock <= i.minimum_stock AND i.current_stock > 0 THEN 1 END) as low_stock
            FROM inventory i
            JOIN categories c ON i.category_id = c.category_id
            GROUP BY c.category_id, c.category_name
            ORDER BY total_value DESC
        ");
        $inventoryStatus->execute();
        $response['data']['inventory_status'] = $inventoryStatus->fetchAll(PDO::FETCH_ASSOC);

        // Get stock movement analysis
        $stockMovement = $pdo->prepare("
            SELECT 
                i.item_name,
                i.current_stock,
                i.minimum_stock,
                COALESCE(SUM(sd.quantity), 0) as total_sold,
                COALESCE(SUM(pd.quantity), 0) as total_purchased
            FROM inventory i
            LEFT JOIN sale_details sd ON i.item_id = sd.item_id 
                AND sd.created_at $dateFilter
            LEFT JOIN purchase_details pd ON i.item_id = pd.material_id 
                AND pd.created_at $dateFilter
            GROUP BY i.item_id, i.item_name, i.current_stock, i.minimum_stock
            HAVING total_sold > 0 OR total_purchased > 0
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $stockMovement->execute($params);
        $response['data']['stock_movement'] = $stockMovement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate overall summary
    if ($reportType === 'all') {
        $response['data']['summary'] = [
            'total_customers' => $response['data']['customer_stats']['total_customers'] ?? 0,
            'active_customers' => $response['data']['customer_stats']['active_customers'] ?? 0,
            'total_sales' => array_sum(array_column($response['data']['monthly_sales'] ?? [], 'sale_count')),
            'total_purchases' => array_sum(array_column($response['data']['monthly_purchases'] ?? [], 'purchase_count')),
            'total_inventory_value' => array_sum(array_column($response['data']['inventory_status'] ?? [], 'total_value')),
            'total_sales_value' => array_sum(array_column($response['data']['monthly_sales'] ?? [], 'total_amount')),
            'total_purchase_value' => array_sum(array_column($response['data']['monthly_purchases'] ?? [], 'total_amount'))
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Report Generation Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while generating report.'
    ]);
} catch (Exception $e) {
    error_log("Report Generation Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.'
    ]);
}
