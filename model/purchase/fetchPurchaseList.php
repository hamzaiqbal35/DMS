<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build SQL query with JOINs and filters
    $query = "
        SELECT 
            p.purchase_id,
            p.purchase_number,
            p.vendor_id,
            v.vendor_name,
            rm.material_name,
            pd.quantity,
            pd.unit_price,
            pd.total_price,
            p.purchase_date,
            p.payment_status,
            p.delivery_status,
            p.invoice_file
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if (!empty($_GET['vendor_id'])) {
        $query .= " AND p.vendor_id = :vendor_id";
        $params[':vendor_id'] = $_GET['vendor_id'];
    }

    if (!empty($_GET['date_from'])) {
        $query .= " AND p.purchase_date >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $query .= " AND p.purchase_date <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }

    if (!empty($_GET['min_amount'])) {
        $query .= " AND pd.total_price >= :min_amount";
        $params[':min_amount'] = $_GET['min_amount'];
    }

    if (!empty($_GET['max_amount'])) {
        $query .= " AND pd.total_price <= :max_amount";
        $params[':max_amount'] = $_GET['max_amount'];
    }

    if (!empty($_GET['payment_status'])) {
        $query .= " AND p.payment_status = :payment_status";
        $params[':payment_status'] = $_GET['payment_status'];
    }

    $query .= " ORDER BY p.purchase_date DESC, p.purchase_id DESC";

    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($purchases) {
        echo json_encode([
            'status' => 'success',
            'data' => $purchases
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No purchases found.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Fetch Purchase List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error. Please try again later.'
    ]);
}
?>