<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../inc/config/database.php";
require_once "../inc/helpers.php";
header('Content-Type: application/json');

// Utility function to send JSON response
function sendResponse($status, $data = [], $message = '') {
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Handle specific API actions first
    $action = $_GET['action'] ?? '';
    
    // =====================
    // 1. Profit Margin Summary
    // =====================
    if ($action === 'get_profit_margin') {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        try {
            // Total Sales
            $salesStmt = $pdo->prepare("SELECT SUM(total_amount) AS total_sales FROM sales WHERE sale_date BETWEEN :start AND :end AND payment_status != 'cancelled'");
            $salesStmt->execute([':start' => $startDate, ':end' => $endDate]);
            $salesResult = $salesStmt->fetch(PDO::FETCH_ASSOC);
            $totalSales = floatval($salesResult['total_sales'] ?? 0);
            
            // Total Purchases
            $purchaseStmt = $pdo->prepare("SELECT SUM(total_amount) AS total_purchases FROM purchases WHERE purchase_date BETWEEN :start AND :end AND payment_status != 'cancelled'");
            $purchaseStmt->execute([':start' => $startDate, ':end' => $endDate]);
            $purchaseResult = $purchaseStmt->fetch(PDO::FETCH_ASSOC);
            $totalPurchases = floatval($purchaseResult['total_purchases'] ?? 0);
            
            // Calculate actual profit margin
            $grossProfit = $totalSales - $totalPurchases;
            $profitMarginPercentage = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
            
            sendResponse('success', [
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'gross_profit' => $grossProfit,
                'profit_margin_percentage' => round($profitMarginPercentage, 2)
            ]);
        } catch (PDOException $e) {
            sendResponse('error', [], 'Database error: ' . $e->getMessage());
        }
    }

    // =====================
    // 2. Inventory Value Summary
    // =====================
    if ($action === 'get_inventory_value') {
        $categoryId = $_GET['category_id'] ?? null;
        
        try {
            if ($categoryId) {
                $stmt = $pdo->prepare("SELECT SUM(current_stock * unit_price) AS total_value FROM inventory WHERE category_id = :category_id AND status = 'active'");
                $stmt->execute([':category_id' => $categoryId]);
            } else {
                $stmt = $pdo->query("SELECT SUM(current_stock * unit_price) AS total_value FROM inventory WHERE status = 'active'");
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalValue = floatval($result['total_value'] ?? 0);
            
            sendResponse('success', [
                'total_value' => $totalValue
            ]);
        } catch (PDOException $e) {
            sendResponse('error', [], 'Database error: ' . $e->getMessage());
        }
    }

    // Get and validate parameters for main report types
    $reportType = $_GET['type'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $categoryId = $_GET['category_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $paymentStatus = $_GET['payment_status'] ?? '';

    if (empty($reportType)) {
        throw new Exception('Report type is required');
    }

    // Get data based on report type
    $data = [];
    $summary = [];
    $trends = [];
    $categorySummary = [];

    switch ($reportType) {
        case 'sales':
            // Get sales data
            $stmt = $pdo->prepare("
                SELECT 
                    s.*,
                    c.customer_name,
                    GROUP_CONCAT(
                        CONCAT(i.item_name, ' (', sd.quantity, ' x ', sd.unit_price, ')')
                        SEPARATOR '; '
                    ) as items_details
                FROM sales s
                JOIN customers c ON s.customer_id = c.customer_id
                JOIN sale_details sd ON s.sale_id = sd.sale_id
                JOIN inventory i ON sd.item_id = i.item_id
                WHERE 1=1
                " . ($dateFrom ? "AND s.sale_date >= :date_from" : "") . "
                " . ($dateTo ? "AND s.sale_date <= :date_to" : "") . "
                " . ($status ? "AND s.status = :status" : "") . "
                " . ($paymentStatus ? "AND s.payment_status = :payment_status" : "") . "
                GROUP BY s.sale_id, s.invoice_number, s.customer_id, s.sale_date, 
                         s.total_amount, s.payment_status, s.invoice_file, s.notes, 
                         s.created_by, s.created_at, s.updated_at, c.customer_name
                ORDER BY s.sale_date DESC
            ");

            $params = [];
            if ($dateFrom) $params[':date_from'] = $dateFrom;
            if ($dateTo) $params[':date_to'] = $dateTo;
            if ($status) $params[':status'] = $status;
            if ($paymentStatus) $params[':payment_status'] = $paymentStatus;

            $stmt->execute($params);
            $data['sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $summary = [
                'total_sales' => count($data['sales']),
                'total_revenue' => array_sum(array_column($data['sales'], 'total_amount')),
                'total_pending' => array_sum(array_column($data['sales'], 'pending_amount')),
                'unique_customers' => count(array_unique(array_column($data['sales'], 'customer_id')))
            ];

            // Get sales trend
            $trends = getSalesTrend($dateFrom, $dateTo);
            break;

        case 'purchases':
            // Get purchase data
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    v.vendor_name,
                    GROUP_CONCAT(
                        CONCAT(rm.material_name, ' (', pd.quantity, ' x ', pd.unit_price, ')')
                        SEPARATOR '; '
                    ) as materials_details
                FROM purchases p
                JOIN vendors v ON p.vendor_id = v.vendor_id
                JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
                JOIN raw_materials rm ON pd.material_id = rm.material_id
                WHERE 1=1
                " . ($dateFrom ? "AND p.purchase_date >= :date_from" : "") . "
                " . ($dateTo ? "AND p.purchase_date <= :date_to" : "") . "
                " . ($status ? "AND p.status = :status" : "") . "
                " . ($paymentStatus ? "AND p.payment_status = :payment_status" : "") . "
                GROUP BY p.purchase_id
                ORDER BY p.purchase_date DESC
            ");

            $params = [];
            if ($dateFrom) $params[':date_from'] = $dateFrom;
            if ($dateTo) $params[':date_to'] = $dateTo;
            if ($status) $params[':status'] = $status;
            if ($paymentStatus) $params[':payment_status'] = $paymentStatus;

            $stmt->execute($params);
            $data['purchases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $summary = [
                'total_purchases' => count($data['purchases']),
                'total_amount' => array_sum(array_column($data['purchases'], 'total_amount')),
                'total_pending' => array_sum(array_column($data['purchases'], 'pending_amount')),
                'unique_vendors' => count(array_unique(array_column($data['purchases'], 'vendor_id')))
            ];

            // Get purchase trend
            $trends = getPurchaseTrend($dateFrom, $dateTo);
            break;

        case 'inventory':
            // Get inventory data
            $stmt = $pdo->prepare("
                SELECT 
                    i.*,
                    c.category_name,
                    CASE 
                        WHEN i.current_stock = 0 THEN 'out_of_stock'
                        WHEN i.current_stock <= i.minimum_stock THEN 'low_stock'
                        ELSE 'sufficient'
                    END as stock_status
                FROM inventory i
                JOIN categories c ON i.category_id = c.category_id
                WHERE 1=1
                " . ($categoryId ? "AND i.category_id = :category_id" : "") . "
                " . ($status ? "AND i.status = :status" : "") . "
                ORDER BY i.item_name ASC
            ");

            $params = [];
            if ($categoryId) $params[':category_id'] = $categoryId;
            if ($status) $params[':status'] = $status;

            $stmt->execute($params);
            $data['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $summary = [
                'total_items' => count($data['items']),
                'total_stock_value' => array_sum(array_map(function($item) {
                    return $item['current_stock'] * $item['unit_price'];
                }, $data['items'])),
                'low_stock_items' => count(array_filter($data['items'], function($item) {
                    return $item['stock_status'] === 'low_stock';
                })),
                'out_of_stock_items' => count(array_filter($data['items'], function($item) {
                    return $item['stock_status'] === 'out_of_stock';
                }))
            ];

            // Get category summary
            $categorySummary = getCategorySummary($categoryId);
            break;

        case 'customers':
            $query = "
                SELECT 
                    c.*,
                    COUNT(s.sale_id) as total_orders,
                    COALESCE(SUM(s.total_amount), 0) as total_spent
                FROM customers c
                LEFT JOIN sales s ON c.customer_id = s.customer_id
                WHERE 1=1
            ";

            if ($dateFrom && $dateTo) {
                $query .= " AND c.created_at BETWEEN :date_from AND :date_to";
            }
            if ($status) {
                $query .= " AND c.status = :status";
            }

            $query .= " GROUP BY c.customer_id";
            $query .= " ORDER BY c.customer_name ASC";

            $stmt = $pdo->prepare($query);
            if ($dateFrom && $dateTo) {
                $stmt->bindParam(':date_from', $dateFrom);
                $stmt->bindParam(':date_to', $dateTo);
            }
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            $data['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $summary = [
                'total_customers' => count($data['customers']),
                'total_revenue' => array_sum(array_column($data['customers'], 'total_spent')),
                'average_order_value' => count($data['customers']) > 0 ? 
                    array_sum(array_column($data['customers'], 'total_spent')) / count($data['customers']) : 0
            ];
            break;

        case 'vendors':
            $query = "
                SELECT 
                    v.*,
                    COUNT(p.purchase_id) as total_orders,
                    COALESCE(SUM(p.total_amount), 0) as total_spent
                FROM vendors v
                LEFT JOIN purchases p ON v.vendor_id = p.vendor_id
                WHERE 1=1
            ";

            if ($dateFrom && $dateTo) {
                $query .= " AND v.created_at BETWEEN :date_from AND :date_to";
            }
            if ($status) {
                $query .= " AND v.status = :status";
            }

            $query .= " GROUP BY v.vendor_id";
            $query .= " ORDER BY v.vendor_name ASC";

            $stmt = $pdo->prepare($query);
            if ($dateFrom && $dateTo) {
                $stmt->bindParam(':date_from', $dateFrom);
                $stmt->bindParam(':date_to', $dateTo);
            }
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            $data['vendors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $summary = [
                'total_vendors' => count($data['vendors']),
                'total_spent' => array_sum(array_column($data['vendors'], 'total_spent')),
                'average_purchase_value' => count($data['vendors']) > 0 ? 
                    array_sum(array_column($data['vendors'], 'total_spent')) / count($data['vendors']) : 0
            ];
            break;

        default:
            throw new Exception('Invalid report type');
    }

    // Add data to response
    $response = [
        'status' => 'success',
        'data' => $data,
        'summary' => $summary
    ];

    // Add trend data for sales and purchases
    if ($reportType === 'sales' || $reportType === 'purchases') {
        $response['sales_trend'] = getSalesTrend($dateFrom, $dateTo);
        $response['purchases_trend'] = getPurchaseTrend($dateFrom, $dateTo);
    }

    // Add category summary for inventory
    if ($reportType === 'inventory') {
        $response['category_summary'] = getCategorySummary($categoryId);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Fetch Report Data Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Helper functions
function getSalesTrend($dateFrom, $dateTo) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(sale_date) as date,
            COUNT(*) as count,
            SUM(total_amount) as amount
        FROM sales
        WHERE 1=1
        " . ($dateFrom ? "AND sale_date >= :date_from" : "") . "
        " . ($dateTo ? "AND sale_date <= :date_to" : "") . "
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ");

    $params = [];
    if ($dateFrom) $params[':date_from'] = $dateFrom;
    if ($dateTo) $params[':date_to'] = $dateTo;

    $stmt->execute($params);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($trends, 'date'),
        'data' => array_column($trends, 'amount')
    ];
}

function getPurchaseTrend($dateFrom, $dateTo) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(purchase_date) as date,
            COUNT(*) as count,
            SUM(total_amount) as amount
        FROM purchases
        WHERE 1=1
        " . ($dateFrom ? "AND purchase_date >= :date_from" : "") . "
        " . ($dateTo ? "AND purchase_date <= :date_to" : "") . "
        GROUP BY DATE(purchase_date)
        ORDER BY date ASC
    ");

    $params = [];
    if ($dateFrom) $params[':date_from'] = $dateFrom;
    if ($dateTo) $params[':date_to'] = $dateTo;

    $stmt->execute($params);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($trends, 'date'),
        'data' => array_column($trends, 'amount')
    ];
}

function getCategorySummary($categoryId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.category_name,
            COUNT(i.item_id) as count,
            SUM(i.current_stock * i.unit_price) as total_value
        FROM categories c
        LEFT JOIN inventory i ON c.category_id = i.category_id
        WHERE 1=1
        " . ($categoryId ? "AND c.category_id = :category_id" : "") . "
        GROUP BY c.category_id, c.category_name
        ORDER BY c.category_name ASC
    ");

    $params = [];
    if ($categoryId) $params[':category_id'] = $categoryId;

    $stmt->execute($params);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($summary as $row) {
        $result[$row['category_name']] = [
            'count' => $row['count'],
            'total_value' => $row['total_value']
        ];
    }

    return $result;
}

function getProfitMargin($startDate, $endDate) {
    global $pdo;
    
    try {
        // Get total sales for the period
        $salesQuery = "
            SELECT 
                COALESCE(SUM(s.total_amount), 0) as total_sales
            FROM sales s
            WHERE s.sale_date BETWEEN :start_date AND :end_date
            AND s.payment_status != 'cancelled'
        ";
        $salesStmt = $pdo->prepare($salesQuery);
        $salesStmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        $totalSales = $salesStmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

        // Get total purchases for the period
        $purchasesQuery = "
            SELECT 
                COALESCE(SUM(p.total_amount), 0) as total_purchases
            FROM purchases p
            WHERE p.purchase_date BETWEEN :start_date AND :end_date
            AND p.payment_status != 'cancelled'
        ";
        $purchasesStmt = $pdo->prepare($purchasesQuery);
        $purchasesStmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        $totalPurchases = $purchasesStmt->fetch(PDO::FETCH_ASSOC)['total_purchases'];

        // Calculate actual profit margin
        $grossProfit = $totalSales - $totalPurchases;
        $profitMarginPercentage = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

        // Log the calculation for debugging
        error_log("Profit Margin Calculation for period $startDate to $endDate:");
        error_log("Total Sales: $totalSales");
        error_log("Total Purchases: $totalPurchases");
        error_log("Gross Profit: $grossProfit");
        error_log("Profit Margin: $profitMarginPercentage%");

        return [
            'status' => 'success',
            'data' => [
                'total_sales' => (float)$totalSales,
                'total_purchases' => (float)$totalPurchases,
                'gross_profit' => (float)$grossProfit,
                'profit_margin_percentage' => round($profitMarginPercentage, 2)
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error calculating profit margin: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to calculate profit margin: ' . $e->getMessage()
        ];
    }
}

function getInventoryValue($categoryId = null) {
    global $pdo;
    
    try {
        $query = "
            SELECT 
                COALESCE(SUM(i.current_stock * i.unit_price), 0) as total_value
            FROM inventory i
            WHERE i.status = 'active'
            AND i.current_stock > 0
        ";
        $params = [];

        if ($categoryId) {
            $query .= " AND i.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Inventory Value Calculation - Total Value: {$result['total_value']}");

        return [
            'status' => 'success',
            'data' => [
                'total_value' => (float)$result['total_value']
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error calculating inventory value: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to calculate inventory value: ' . $e->getMessage()
        ];
    }
}
?>