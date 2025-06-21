<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build SQL query with comprehensive purchase data
    $query = "
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.vendor_id,
            v.vendor_name,
            p.purchase_date,
            p.payment_status,
            p.delivery_status,
            p.notes,
            p.total_amount,
            COALESCE(SUM(py.amount), 0) as paid_amount,
            (p.total_amount - COALESCE(SUM(py.amount), 0)) as pending_amount,
            u.full_name as created_by_name,
            p.invoice_file,
            GROUP_CONCAT(
                CONCAT(
                    rm.material_name, ' (', pd.quantity, ' x ', pd.unit_price, ')'
                ) SEPARATOR '; '
            ) as materials_details,
            COUNT(DISTINCT pd.material_id) as total_materials,
            SUM(pd.quantity) as total_quantity
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        JOIN users u ON p.created_by = u.user_id
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        LEFT JOIN payments py ON p.purchase_id = py.purchase_id
        WHERE 1=1
    ";

    $params = [];
    $filterClauses = [];

    // Apply vendor filter
    if (!empty($_GET['vendor_id'])) {
        $filterClauses[] = "p.vendor_id = :vendor_id";
        $params[':vendor_id'] = $_GET['vendor_id'];
    }

    // Apply date filters
    if (!empty($_GET['date_from'])) {
        $filterClauses[] = "p.purchase_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $filterClauses[] = "p.purchase_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    // Apply payment status filter
    if (!empty($_GET['payment_status'])) {
        $filterClauses[] = "p.payment_status = :payment_status";
        $params[':payment_status'] = $_GET['payment_status'];
    }

    // Apply delivery status filter
    if (!empty($_GET['delivery_status'])) {
        $filterClauses[] = "p.delivery_status = :delivery_status";
        $params[':delivery_status'] = $_GET['delivery_status'];
    }

    // Apply amount range filters
    if (!empty($_GET['min_amount'])) {
        $filterClauses[] = "p.total_amount >= :min_amount";
        $params[':min_amount'] = floatval($_GET['min_amount']);
    }

    if (!empty($_GET['max_amount'])) {
        $filterClauses[] = "p.total_amount <= :max_amount";
        $params[':max_amount'] = floatval($_GET['max_amount']);
    }

    // Apply search filter
    if (!empty($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $filterClauses[] = "(
            p.purchase_number LIKE :search 
            OR v.vendor_name LIKE :search 
            OR rm.material_name LIKE :search
            OR u.full_name LIKE :search
        )";
        $params[':search'] = "%$searchTerm%";
    }

    // Append filter clauses to query
    if (!empty($filterClauses)) {
        $query .= " AND " . implode(" AND ", $filterClauses);
    }

    // Group by purchase details
    $query .= " GROUP BY p.purchase_id, p.purchase_number, p.vendor_id, v.vendor_name, 
                p.purchase_date, p.payment_status, p.delivery_status, p.notes, 
                p.total_amount, u.full_name, p.invoice_file";

    // Order by purchase date and ID
    $query .= " ORDER BY p.purchase_date DESC, p.purchase_id DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_purchases' => count($purchases),
        'total_amount' => array_sum(array_column($purchases, 'total_amount')),
        'total_paid' => array_sum(array_column($purchases, 'paid_amount')),
        'total_pending' => array_sum(array_column($purchases, 'pending_amount')),
        'average_purchase_amount' => count($purchases) > 0 ? 
            array_sum(array_column($purchases, 'total_amount')) / count($purchases) : 0,
        'total_materials_purchased' => array_sum(array_column($purchases, 'total_quantity')),
        'unique_materials_purchased' => array_sum(array_column($purchases, 'total_materials'))
    ];

    // Group purchases by payment status
    $paymentStatusSummary = [];
    foreach ($purchases as $purchase) {
        $status = $purchase['payment_status'];
        if (!isset($paymentStatusSummary[$status])) {
            $paymentStatusSummary[$status] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $paymentStatusSummary[$status]['count']++;
        $paymentStatusSummary[$status]['amount'] += $purchase['total_amount'];
    }

    // Group purchases by delivery status
    $deliveryStatusSummary = [];
    foreach ($purchases as $purchase) {
        $status = $purchase['delivery_status'];
        if (!isset($deliveryStatusSummary[$status])) {
            $deliveryStatusSummary[$status] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $deliveryStatusSummary[$status]['count']++;
        $deliveryStatusSummary[$status]['amount'] += $purchase['total_amount'];
    }

    if ($purchases) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'purchases' => $purchases,
                'summary' => $summary,
                'payment_status_summary' => $paymentStatusSummary,
                'delivery_status_summary' => $deliveryStatusSummary
            ],
            'count' => count($purchases)
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No purchases found for the selected criteria.',
            'search_term' => $_GET['search'] ?? '',
            'query_executed' => true
        ]);
    }

} catch (PDOException $e) {
    error_log("Database Error in purchaseReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $_GET['search'] ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in purchaseReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
