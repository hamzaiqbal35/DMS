<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

try {
    // Get search parameters from request
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Base query
    $query = "
        SELECT 
            c.customer_id,
            c.customer_name,
            c.phone,
            c.email,
            c.address,
            c.city,
            c.state,
            c.zip_code,
            c.status,
            c.created_at,
            COUNT(s.sale_id) as total_orders,
            COALESCE(SUM(s.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN sales s ON c.customer_id = s.customer_id
        WHERE 1=1
    ";

    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (
            c.customer_name LIKE :search 
            OR c.email LIKE :search 
            OR c.phone LIKE :search
            OR c.address LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    // Add status filter
    if ($status !== 'all') {
        $query .= " AND c.status = :status";
        $params[':status'] = $status;
    }

    // Add date range filter if provided
    if ($startDate && $endDate) {
        $query .= " AND c.created_at BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    }

    // Group by customer details
    $query .= " GROUP BY c.customer_id, c.customer_name, c.phone, c.email, c.address, c.city, c.state, c.zip_code, c.status, c.created_at";
    
    // Order by customer name
    $query .= " ORDER BY c.customer_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $totalCustomers = count($customers);
    $totalRevenue = array_sum(array_column($customers, 'total_spent'));
    $averageOrderValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'customers' => $customers,
            'summary' => [
                'total_customers' => $totalCustomers,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue
            ]
        ]
    ]);

} catch (PDOException $e) {
    error_log("Customer Reports Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate customer reports'
    ]);
}
