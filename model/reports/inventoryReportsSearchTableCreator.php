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
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : 'all';

    // Base query with comprehensive inventory data and transaction statistics
    $query = "
        SELECT 
            i.item_id,
            i.item_number,
            i.item_name,
            c.category_id,
            c.category_name,
            i.description,
            i.unit_of_measure,
            i.unit_price,
            i.current_stock,
            i.minimum_stock,
            i.status,
            i.created_at,
            i.updated_at,
            COALESCE(SUM(sd.quantity), 0) as total_sold,
            COALESCE(SUM(pd.quantity), 0) as total_purchased,
            COALESCE(SUM(sd.quantity * sd.unit_price), 0) as total_sales_value,
            COALESCE(SUM(pd.quantity * pd.unit_price), 0) as total_purchase_value,
            CASE 
                WHEN i.current_stock <= i.minimum_stock THEN 'low'
                WHEN i.current_stock = 0 THEN 'out'
                ELSE 'sufficient'
            END as stock_level
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN sale_details sd ON i.item_id = sd.item_id
        LEFT JOIN purchase_details pd ON i.item_id = pd.material_id
        WHERE 1=1
    ";

    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (
            i.item_name LIKE :search 
            OR i.item_number LIKE :search 
            OR i.description LIKE :search
            OR c.category_name LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    // Add category filter
    if (!empty($category_id)) {
        $query .= " AND i.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Add status filter
    if ($status !== 'all') {
        $query .= " AND i.status = :status";
        $params[':status'] = $status;
    }

    // Add stock status filter
    if ($stock_status !== 'all') {
        switch ($stock_status) {
            case 'low':
                $query .= " AND i.current_stock <= i.minimum_stock AND i.current_stock > 0";
                break;
            case 'out':
                $query .= " AND i.current_stock = 0";
                break;
            case 'sufficient':
                $query .= " AND i.current_stock > i.minimum_stock";
                break;
        }
    }

    // Group by item details
    $query .= " GROUP BY i.item_id, i.item_number, i.item_name, c.category_id, 
                c.category_name, i.description, i.unit_of_measure, i.unit_price, 
                i.current_stock, i.minimum_stock, i.status, i.created_at, i.updated_at";
    
    // Order by item name
    $query .= " ORDER BY i.item_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_items' => count($items),
        'total_categories' => count(array_unique(array_column($items, 'category_id'))),
        'total_stock_value' => array_sum(array_map(function($item) {
            return $item['current_stock'] * $item['unit_price'];
        }, $items)),
        'total_sales_value' => array_sum(array_column($items, 'total_sales_value')),
        'total_purchase_value' => array_sum(array_column($items, 'total_purchase_value')),
        'low_stock_items' => count(array_filter($items, function($item) {
            return $item['stock_level'] === 'low';
        })),
        'out_of_stock_items' => count(array_filter($items, function($item) {
            return $item['stock_level'] === 'out';
        })),
        'active_items' => count(array_filter($items, function($item) {
            return $item['status'] === 'active';
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
                'low_stock' => 0,
                'out_of_stock' => 0
            ];
        }
        $categorySummary[$category]['count']++;
        $categorySummary[$category]['total_stock'] += $item['current_stock'];
        $categorySummary[$category]['total_value'] += ($item['current_stock'] * $item['unit_price']);
        if ($item['stock_level'] === 'low') $categorySummary[$category]['low_stock']++;
        if ($item['stock_level'] === 'out') $categorySummary[$category]['out_of_stock']++;
    }

    // Group items by stock level
    $stockLevelSummary = [
        'low' => 0,
        'out' => 0,
        'sufficient' => 0
    ];
    foreach ($items as $item) {
        $stockLevelSummary[$item['stock_level']]++;
    }

    if ($items) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'items' => $items,
                'summary' => $summary,
                'category_summary' => $categorySummary,
                'stock_level_summary' => $stockLevelSummary
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
    error_log("Inventory Reports Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $search ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in inventoryReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
