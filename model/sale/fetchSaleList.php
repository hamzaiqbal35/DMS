<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build base query - Join necessary tables to get item details
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
            sd.item_id,
            sd.quantity,
            sd.unit_price,
            sd.total_price,
            i.item_name,
            i.item_number
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

    // Append filter clauses to query
    if (!empty($filterClauses)) {
        $query .= " AND " . implode(" AND ", $filterClauses);
    }

    // Order by sale date and ID
    $query .= " ORDER BY s.sale_date DESC, s.sale_id DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sales) {
        echo json_encode([
            'status' => 'success',
            'data' => $sales,
            'count' => count($sales)
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No sales found.',
            'search_term' => $_GET['search'] ?? '',
            'query_executed' => true
        ]);
    }

} catch (PDOException $e) {
    error_log("Database Error in fetchSaleList: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'search_term' => $_GET['search'] ?? ''
    ]);
} catch (Exception $e) {
    error_log("General Error in fetchSaleList: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>