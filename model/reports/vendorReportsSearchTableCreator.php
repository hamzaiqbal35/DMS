<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get search parameters from request
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Base query with comprehensive vendor data and purchase statistics
    $query = "
        SELECT 
            v.vendor_id,
            v.vendor_name,
            v.contact_person,
            v.phone,
            v.email,
            v.address,
            v.city,
            v.state,
            v.zip_code,
            v.status,
            v.created_at,
            COUNT(DISTINCT p.purchase_id) as total_purchases,
            COALESCE(SUM(p.total_amount), 0) as total_spent,
            COALESCE(SUM(py.amount), 0) as total_paid,
            COALESCE(SUM(p.total_amount - COALESCE(py.amount, 0)), 0) as total_pending,
            COUNT(DISTINCT pd.material_id) as total_materials_purchased,
            SUM(pd.quantity) as total_quantity_purchased,
            MAX(p.purchase_date) as last_purchase_date
        FROM vendors v
        LEFT JOIN purchases p ON v.vendor_id = p.vendor_id
        LEFT JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        LEFT JOIN payments py ON p.purchase_id = py.purchase_id
        WHERE 1=1
    ";

    $params = [];

    // Add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (
            v.vendor_name LIKE :search 
            OR v.contact_person LIKE :search 
            OR v.email LIKE :search
            OR v.phone LIKE :search
            OR v.address LIKE :search
            OR v.city LIKE :search
            OR v.state LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    // Add status filter
    if ($status !== 'all') {
        $query .= " AND v.status = :status";
        $params[':status'] = $status;
    }

    // Add date range filter if provided
    if ($startDate && $endDate) {
        $query .= " AND v.created_at BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    }

    // Group by vendor details
    $query .= " GROUP BY v.vendor_id, v.vendor_name, v.contact_person, v.phone, 
                v.email, v.address, v.city, v.state, v.zip_code, v.status, v.created_at";
    
    // Order by vendor name
    $query .= " ORDER BY v.vendor_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_vendors' => count($vendors),
        'active_vendors' => count(array_filter($vendors, function($v) { return $v['status'] === 'active'; })),
        'inactive_vendors' => count(array_filter($vendors, function($v) { return $v['status'] === 'inactive'; })),
        'total_purchases' => array_sum(array_column($vendors, 'total_purchases')),
        'total_spent' => array_sum(array_column($vendors, 'total_spent')),
        'total_paid' => array_sum(array_column($vendors, 'total_paid')),
        'total_pending' => array_sum(array_column($vendors, 'total_pending')),
        'total_materials_purchased' => array_sum(array_column($vendors, 'total_materials_purchased')),
        'total_quantity_purchased' => array_sum(array_column($vendors, 'total_quantity_purchased')),
        'average_purchase_value' => count($vendors) > 0 ? 
            array_sum(array_column($vendors, 'total_spent')) / count($vendors) : 0
    ];

    // Group vendors by status
    $statusSummary = [];
    foreach ($vendors as $vendor) {
        $status = $vendor['status'];
        if (!isset($statusSummary[$status])) {
            $statusSummary[$status] = [
                'count' => 0,
                'total_spent' => 0,
                'total_purchases' => 0
            ];
        }
        $statusSummary[$status]['count']++;
        $statusSummary[$status]['total_spent'] += $vendor['total_spent'];
        $statusSummary[$status]['total_purchases'] += $vendor['total_purchases'];
    }

    if ($vendors) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'vendors' => $vendors,
                'summary' => $summary,
                'status_summary' => $statusSummary
            ],
            'count' => count($vendors)
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No vendors found for the selected criteria.',
            'search_term' => $search,
            'query_executed' => true
        ]);
    }

} catch (PDOException $e) {
    error_log("Vendor Reports Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $search ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in vendorReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
