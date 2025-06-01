<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build base query
    $query = "
        SELECT 
            s.sale_id,
            s.invoice_number,
            s.customer_id,
            c.customer_name,
            i.item_name,
            sd.quantity,
            sd.unit_price,
            sd.total_price,
            s.sale_date,
            s.payment_status,
            s.notes,
            u.full_name as created_by_name
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN sale_details sd ON s.sale_id = sd.sale_id
        JOIN inventory i ON sd.item_id = i.item_id
        JOIN users u ON s.created_by = u.user_id
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if (!empty($_GET['customer_id'])) {
        $query .= " AND s.customer_id = :customer_id";
        $params[':customer_id'] = $_GET['customer_id'];
    }

    if (!empty($_GET['date_from'])) {
        $query .= " AND s.sale_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $query .= " AND s.sale_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    if (!empty($_GET['min_amount'])) {
        $query .= " AND sd.total_price >= :min_amount";
        $params[':min_amount'] = $_GET['min_amount'];
    }

    if (!empty($_GET['max_amount'])) {
        $query .= " AND sd.total_price <= :max_amount";
        $params[':max_amount'] = $_GET['max_amount'];
    }

    if (!empty($_GET['payment_status'])) {
        $query .= " AND s.payment_status = :payment_status";
        $params[':payment_status'] = $_GET['payment_status'];
    }

    $query .= " ORDER BY s.sale_date DESC, s.sale_id DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sales) {
        echo json_encode([
            'status' => 'success',
            'data' => $sales
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No sales found.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Fetch Sale List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error. Please try again later.'
    ]);
}
