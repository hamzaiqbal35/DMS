<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../inc/config/database.php";
header('Content-Type: application/json');

try {
    // Get sales data for the last 6 months
    $query = "
        SELECT 
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            COUNT(*) as total_sales,
            SUM(total_amount) as total_amount
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for Chart.js
    $labels = [];
    $salesCount = [];
    $salesAmount = [];

    foreach ($salesData as $data) {
        // Format month label (e.g., "Jan 2024")
        $date = DateTime::createFromFormat('Y-m', $data['month']);
        $labels[] = $date->format('M Y');
        
        $salesCount[] = (int)$data['total_sales'];
        $salesAmount[] = (float)$data['total_amount'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Number of Sales',
                    'data' => $salesCount,
                    'borderColor' => '#4361ee',
                    'backgroundColor' => 'rgba(67, 97, 238, 0.1)',
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Total Amount (PKR)',
                    'data' => $salesAmount,
                    'borderColor' => '#4cc9f0',
                    'backgroundColor' => 'rgba(76, 201, 240, 0.1)',
                    'yAxisID' => 'y1'
                ]
            ]
        ]
    ]);

} catch (PDOException $e) {
    error_log("Sales Chart Data Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while fetching sales chart data.'
    ]);
} catch (Exception $e) {
    error_log("Sales Chart Data Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.'
    ]);
} 