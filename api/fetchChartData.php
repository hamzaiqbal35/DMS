<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../inc/config/database.php";
header('Content-Type: application/json');

try {
    $type = $_GET['type'] ?? '';
    $period = $_GET['period'] ?? '30'; // Default to last 30 days

    $response = [
        'status' => 'success',
        'data' => []
    ];

    switch ($type) {
        case 'trend':
            $response['data'] = [
                'sales' => getSalesTrend($period),
                'purchases' => getPurchaseTrend($period)
            ];
            break;

        case 'category':
            $response['data'] = getCategorySummary();
            break;

        case 'stock':
            $response['data'] = getStockData();
            break;

        case 'payment':
            $response['data'] = getPaymentStatusSummary();
            break;

        default:
            throw new Exception("Invalid chart type");
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Get sales trend data
function getSalesTrend($period) {
    global $pdo;
    
    $query = "
        SELECT 
            DATE(sale_date) as date,
            SUM(total_amount) as amount
        FROM sales
        WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':period' => $period]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get purchase trend data
function getPurchaseTrend($period) {
    global $pdo;
    
    $query = "
        SELECT 
            DATE(purchase_date) as date,
            SUM(total_amount) as amount
        FROM purchases
        WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
        GROUP BY DATE(purchase_date)
        ORDER BY date ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':period' => $period]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get category summary data
function getCategorySummary() {
    global $pdo;
    
    $query = "
        SELECT 
            c.category_name,
            COUNT(i.item_id) as count,
            SUM(i.current_stock) as total_stock,
            SUM(i.current_stock * i.unit_price) as total_value
        FROM categories c
        LEFT JOIN inventory i ON c.category_id = i.category_id
        WHERE c.status = 'active'
        GROUP BY c.category_id, c.category_name
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easier chart processing
    $summary = [];
    foreach ($results as $row) {
        $summary[$row['category_name']] = [
            'count' => (int)$row['count'],
            'total_stock' => (float)$row['total_stock'],
            'total_value' => (float)$row['total_value']
        ];
    }
    
    return $summary;
}

// Get stock level data
function getStockData() {
    global $pdo;
    $stockStatus = isset($_GET['stock_status']) ? $_GET['stock_status'] : 'all';
    $query = "
        SELECT 
            item_id,
            item_name,
            current_stock,
            minimum_stock,
            unit_of_measure
        FROM inventory
        WHERE status = 'active'\n";
    if ($stockStatus === 'low') {
        $query .= " AND current_stock <= minimum_stock AND current_stock > 0";
    } else if ($stockStatus === 'out') {
        $query .= " AND current_stock = 0";
    }
    $query .= " ORDER BY current_stock DESC LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get payment status summary
function getPaymentStatusSummary() {
    global $pdo;
    $period = isset($_GET['period']) ? intval($_GET['period']) : 30;
    $source = isset($_GET['source']) ? $_GET['source'] : 'combined';
    
    // Initialize empty summary
    $summary = [];
    
    if ($source === 'sales' || $source === 'combined') {
        // Get all unique payment statuses from sales
        $salesStatusQuery = "
            SELECT DISTINCT payment_status
            FROM sales
            WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
            AND payment_status IS NOT NULL
            AND payment_status != ''
        ";
        $stmt = $pdo->prepare($salesStatusQuery);
        $stmt->execute([':period' => $period]);
        $salesStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get sales data for each status
        $salesQuery = "
            SELECT 
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as amount
            FROM sales
            WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
            AND payment_status IS NOT NULL
            AND payment_status != ''
            GROUP BY payment_status
        ";
        $stmt = $pdo->prepare($salesQuery);
        $stmt->execute([':period' => $period]);
        $salesResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($salesResults as $row) {
            $status = strtolower(trim($row['payment_status']));
            if (!isset($summary[$status])) {
                $summary[$status] = ['count' => 0, 'amount' => 0];
            }
            $summary[$status]['count'] += (int)$row['count'];
            $summary[$status]['amount'] += (float)$row['amount'];
        }
    }
    
    if ($source === 'purchases' || $source === 'combined') {
        // Get all unique payment statuses from purchases
        $purchasesStatusQuery = "
            SELECT DISTINCT payment_status
            FROM purchases
            WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
            AND payment_status IS NOT NULL
            AND payment_status != ''
        ";
        $stmt = $pdo->prepare($purchasesStatusQuery);
        $stmt->execute([':period' => $period]);
        $purchasesStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get purchases data for each status
        $purchasesQuery = "
            SELECT 
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as amount
            FROM purchases
            WHERE purchase_date >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
            AND payment_status IS NOT NULL
            AND payment_status != ''
            GROUP BY payment_status
        ";
        $stmt = $pdo->prepare($purchasesQuery);
        $stmt->execute([':period' => $period]);
        $purchasesResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($purchasesResults as $row) {
            $status = strtolower(trim($row['payment_status']));
            if (!isset($summary[$status])) {
                $summary[$status] = ['count' => 0, 'amount' => 0];
            }
            $summary[$status]['count'] += (int)$row['count'];
            $summary[$status]['amount'] += (float)$row['amount'];
        }
    }
    
    // Filter out statuses with zero count
    $filteredSummary = [];
    foreach ($summary as $status => $data) {
        if ($data['count'] > 0) {
            $filteredSummary[$status] = $data;
        }
    }
    
    // If no data found, return empty result with message
    if (empty($filteredSummary)) {
        return [
            'no_data' => true,
            'message' => "No payment data found for the last {$period} days",
            'period' => $period,
            'source' => $source
        ];
    }
    
    return $filteredSummary;
}
?>