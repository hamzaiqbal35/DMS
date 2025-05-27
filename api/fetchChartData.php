<?php
require_once '../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Get count of items by category with their total stock AND individual products
    $stmt = $pdo->prepare("
        SELECT 
            c.category_name,
            c.category_id,
            COUNT(i.item_id) as item_count,
            COALESCE(SUM(i.current_stock), 0) as total_stock
        FROM categories c
        LEFT JOIN inventory i ON c.category_id = i.category_id
        WHERE i.status = 'active'
        GROUP BY c.category_id, c.category_name
        HAVING total_stock > 0
    ");
    $stmt->execute();
    $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get individual products for each category
    $productsStmt = $pdo->prepare("
        SELECT 
            i.item_name,
            i.current_stock,
            i.category_id,
            c.category_name
        FROM inventory i
        JOIN categories c ON i.category_id = c.category_id
        WHERE i.status = 'active' AND i.current_stock > 0
        ORDER BY c.category_name, i.item_name
    ");
    $productsStmt->execute();
    $productsData = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group products by category
    $productsByCategory = [];
    foreach ($productsData as $product) {
        $categoryId = $product['category_id'];
        if (!isset($productsByCategory[$categoryId])) {
            $productsByCategory[$categoryId] = [];
        }
        $productsByCategory[$categoryId][] = [
            'name' => $product['item_name'],
            'stock' => $product['current_stock']
        ];
    }

    // Prepare data for chart
    $labels = [];
    $data = [];
    $categoryProducts = [];
    $backgroundColors = [
        'rgba(255, 99, 132, 0.8)',   // Red
        'rgba(54, 162, 235, 0.8)',   // Blue
        'rgba(255, 206, 86, 0.8)',   // Yellow
        'rgba(75, 192, 192, 0.8)',   // Teal
        'rgba(153, 102, 255, 0.8)',  // Purple
        'rgba(255, 159, 64, 0.8)'    // Orange
    ];
    $borderColors = [
        'rgba(255, 99, 132, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)'
    ];

    foreach ($categoryData as $index => $category) {
        $labels[] = $category['category_name'];
        $data[] = $category['total_stock'];
        $categoryId = $category['category_id'];
        $categoryProducts[] = isset($productsByCategory[$categoryId]) ? $productsByCategory[$categoryId] : [];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'labels' => $labels,
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                'borderColor' => array_slice($borderColors, 0, count($data)),
                'borderWidth' => 1
            ]],
            'categoryProducts' => $categoryProducts // Add products data
        ]
    ]);

} catch (PDOException $e) {
    error_log("Fetch Chart Data Error: " . $e->getMessage(), 3, "../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch chart data.'
    ]);
}
?>