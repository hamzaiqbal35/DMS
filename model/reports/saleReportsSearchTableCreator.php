<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build base query with comprehensive sales data
    $query = "
        SELECT 
            s.sale_id,
            s.invoice_number,
            s.customer_id,
            c.customer_name,
            s.sale_date,
            s.payment_status,
            s.notes,
            s.total_amount,
            COALESCE(s.paid_amount, 0) as paid_amount,
            (s.total_amount - COALESCE(s.paid_amount, 0)) as pending_amount,
            u.full_name as created_by_name,
            s.invoice_file,
            GROUP_CONCAT(
                CONCAT(
                    i.item_name, ' (', sd.quantity, ' x ', sd.unit_price, ')'
                ) SEPARATOR '; '
            ) as items_details,
            COUNT(DISTINCT sd.item_id) as total_items,
            SUM(sd.quantity) as total_quantity
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN users u ON s.created_by = u.user_id
        JOIN sale_details sd ON s.sale_id = sd.sale_id
        JOIN inventory i ON sd.item_id = i.item_id
        WHERE 1=1
    ";

    $params = [];
    $filterClauses = [];

    // Apply customer filter
    if (!empty($_GET['customer_id'])) {
        $filterClauses[] = "s.customer_id = :customer_id";
        $params[':customer_id'] = $_GET['customer_id'];
    }

    // Apply date filters
    if (!empty($_GET['date_from'])) {
        $filterClauses[] = "s.sale_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $filterClauses[] = "s.sale_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    // Apply payment status filter
    if (!empty($_GET['payment_status'])) {
        $filterClauses[] = "s.payment_status = :payment_status";
        $params[':payment_status'] = $_GET['payment_status'];
    }

    // Apply amount range filters
    if (!empty($_GET['min_amount'])) {
        $filterClauses[] = "s.total_amount >= :min_amount";
        $params[':min_amount'] = floatval($_GET['min_amount']);
    }

    if (!empty($_GET['max_amount'])) {
        $filterClauses[] = "s.total_amount <= :max_amount";
        $params[':max_amount'] = floatval($_GET['max_amount']);
    }

    // Apply search filter
    if (!empty($_GET['search'])) {
        $searchTerm = $_GET['search'];
        $filterClauses[] = "(
            s.invoice_number LIKE :search 
            OR c.customer_name LIKE :search 
            OR i.item_name LIKE :search
            OR u.full_name LIKE :search
        )";
        $params[':search'] = "%$searchTerm%";
    }

    // Append filter clauses to query
    if (!empty($filterClauses)) {
        $query .= " AND " . implode(" AND ", $filterClauses);
    }

    // Group by sale details
    $query .= " GROUP BY s.sale_id, s.invoice_number, s.customer_id, c.customer_name, 
                s.sale_date, s.payment_status, s.notes, s.total_amount, s.paid_amount, 
                u.full_name, s.invoice_file";

    // Order by sale date and ID
    $query .= " ORDER BY s.sale_date DESC, s.sale_id DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_sales' => count($sales),
        'total_revenue' => array_sum(array_column($sales, 'total_amount')),
        'total_paid' => array_sum(array_column($sales, 'paid_amount')),
        'total_pending' => array_sum(array_column($sales, 'pending_amount')),
        'average_sale_amount' => count($sales) > 0 ? 
            array_sum(array_column($sales, 'total_amount')) / count($sales) : 0,
        'total_items_sold' => array_sum(array_column($sales, 'total_quantity')),
        'unique_items_sold' => array_sum(array_column($sales, 'total_items'))
    ];

    // Group sales by payment status
    $paymentStatusSummary = [];
    foreach ($sales as $sale) {
        $status = $sale['payment_status'];
        if (!isset($paymentStatusSummary[$status])) {
            $paymentStatusSummary[$status] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $paymentStatusSummary[$status]['count']++;
        $paymentStatusSummary[$status]['amount'] += $sale['total_amount'];
    }

    if ($sales) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'sales' => $sales,
                'summary' => $summary,
                'payment_status_summary' => $paymentStatusSummary
            ],
            'count' => count($sales)
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No sales found for the selected criteria.',
            'search_term' => $_GET['search'] ?? '',
            'query_executed' => true
        ]);
    }

} catch (PDOException $e) {
    error_log("Database Error in saleReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $_GET['search'] ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in saleReportsSearchTableCreator: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
