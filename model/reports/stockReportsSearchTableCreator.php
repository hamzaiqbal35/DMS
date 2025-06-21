<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get search parameters from request
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
    $stock_level = isset($_GET['stock_level']) ? $_GET['stock_level'] : 'all';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Base query with comprehensive stock data and analysis
    $query = "
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            c.category_id,
            c.category_name,
            i.unit_of_measure,
            i.unit_price,
            i.current_stock,
            i.minimum_stock,
            i.status,
            i.created_at,
            i.updated_at,
            CASE 
                WHEN i.current_stock = 0 THEN 'out_of_stock'
                WHEN i.current_stock <= i.minimum_stock THEN 'low_stock'
                ELSE 'sufficient'
            END as stock_status,
            CASE 
                WHEN i.current_stock = 0 THEN 0
                WHEN i.current_stock <= i.minimum_stock THEN 1
                ELSE 2
            END as stock_level_priority,
            COALESCE(SUM(sd.quantity), 0) as total_sold_last_month,
            COALESCE(SUM(pd.quantity), 0) as total_purchased_last_month,
            (i.current_stock * i.unit_price) as current_stock_value,
            (i.minimum_stock * i.unit_price) as minimum_stock_value,
            CASE 
                WHEN i.current_stock = 0 THEN 'Immediate attention required'
                WHEN i.current_stock <= i.minimum_stock THEN 'Stock level below minimum'
                ELSE 'Stock level is sufficient'
            END as alert_message
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN sale_details sd ON i.item_id = sd.item_id 
            AND sd.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        LEFT JOIN purchase_details pd ON i.item_id = pd.material_id 
            AND pd.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        WHERE 1=1
    ";

    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (
            i.item_name LIKE :search 
            OR i.item_number LIKE :search 
            OR c.category_name LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    // Add category filter
    if (!empty($category_id)) {
        $query .= " AND i.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Add stock level filter
    if ($stock_level !== 'all') {
        switch ($stock_level) {
            case 'out_of_stock':
                $query .= " AND i.current_stock = 0";
                break;
            case 'low_stock':
                $query .= " AND i.current_stock <= i.minimum_stock AND i.current_stock > 0";
                break;
            case 'sufficient':
                $query .= " AND i.current_stock > i.minimum_stock";
                break;
        }
    }

    // Add status filter
    if ($status !== 'all') {
        $query .= " AND i.status = :status";
        $params[':status'] = $status;
    }

    // Group by item details
    $query .= " GROUP BY i.item_id, i.item_number, i.item_name, c.category_id, 
                c.category_name, i.unit_of_measure, i.unit_price, i.current_stock, 
                i.minimum_stock, i.status, i.created_at, i.updated_at";
    
    // Order by stock level priority and item name
    $query .= " ORDER BY stock_level_priority ASC, i.item_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_items' => count($items),
        'total_categories' => count(array_unique(array_column($items, 'category_id'))),
        'total_stock_value' => array_sum(array_column($items, 'current_stock_value')),
        'total_minimum_stock_value' => array_sum(array_column($items, 'minimum_stock_value')),
        'out_of_stock_items' => count(array_filter($items, function($item) {
            return $item['stock_status'] === 'out_of_stock';
        })),
        'low_stock_items' => count(array_filter($items, function($item) {
            return $item['stock_status'] === 'low_stock';
        })),
        'sufficient_stock_items' => count(array_filter($items, function($item) {
            return $item['stock_status'] === 'sufficient';
        }))
    ];

    // Group items by category
    $categorySummary = [];
    foreach ($items as $item) {
        $category = $item['category_name'];
        if (!isset($categorySummary[$category])) {
            $categorySummary[$category] = [
                'count' => 0,
                'total_stock' => 0,
                'total_value' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'sufficient' => 0
            ];
        }
        $categorySummary[$category]['count']++;
        $categorySummary[$category]['total_stock'] += $item['current_stock'];
        $categorySummary[$category]['total_value'] += $item['current_stock_value'];
        $categorySummary[$category][$item['stock_status']]++;
    }

    // Calculate stock movement analysis
    $stockMovement = [
        'high_demand_items' => array_filter($items, function($item) {
            return $item['total_sold_last_month'] > $item['current_stock'];
        }),
        'slow_moving_items' => array_filter($items, function($item) {
            return $item['total_sold_last_month'] < ($item['current_stock'] * 0.1);
        })
    ];

    if ($items) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'items' => $items,
                'summary' => $summary,
                'category_summary' => $categorySummary,
                'stock_movement' => [
                    'high_demand_count' => count($stockMovement['high_demand_items']),
                    'slow_moving_count' => count($stockMovement['slow_moving_items'])
                ]
            ],
            'count' => count($items)
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No inventory items found for the selected criteria.',
            'search_term' => $search,
            'query_executed' => true
        ]);
    }

} catch (PDOException $e) {
    error_log("Stock Reports Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $search ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in stockReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
