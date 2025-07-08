<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

// Start session and restore from JWT if needed
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['jwt_token'])) {
    $decoded = decode_jwt($_SESSION['jwt_token']);
    if ($decoded && isset($decoded->data->user_id) && isset($decoded->data->role_id)) {
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['role_id'] = $decoded->data->role_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
    }
}

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Build base query
    $query = "
        SELECT 
            co.order_id,
            co.order_number,
            co.order_date,
            co.total_amount,
            co.tax_amount,
            co.shipping_amount,
            co.discount_amount,
            co.final_amount,
            co.payment_method,
            co.payment_status,
            co.order_status,
            co.tracking_number,
            co.completion_date,
            co.cancellation_date,
            co.cancellation_reason,
            co.shipping_address,
            co.notes,
            cu.full_name as customer_name,
            cu.email as customer_email,
            cu.phone as customer_phone,
            COUNT(cod.order_detail_id) as item_count,
            COALESCE(SUM(cp.amount), 0) as total_paid
        FROM customer_orders co
        JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        LEFT JOIN customer_order_details cod ON co.order_id = cod.order_id
        LEFT JOIN customer_payments cp ON co.order_id = cp.order_id AND cp.payment_status = 'completed'
        WHERE 1=1
    ";

    $params = [];
    $filterClauses = [];

    // Apply search filter
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $filterClauses[] = "(co.order_number LIKE ? OR cu.full_name LIKE ? OR cu.email LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Apply status filter
    if (!empty($_GET['status'])) {
        $filterClauses[] = "co.order_status = ?";
        $params[] = $_GET['status'];
    }

    // Apply payment status filter
    if (!empty($_GET['payment_status'])) {
        $filterClauses[] = "co.payment_status = ?";
        $params[] = $_GET['payment_status'];
    }

    // Apply date range filters
    if (!empty($_GET['date_from'])) {
        $filterClauses[] = "DATE(co.order_date) >= ?";
        $params[] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $filterClauses[] = "DATE(co.order_date) <= ?";
        $params[] = $_GET['date_to'];
    }

    // Apply amount range filters
    if (!empty($_GET['min_amount'])) {
        $filterClauses[] = "co.final_amount >= ?";
        $params[] = floatval($_GET['min_amount']);
    }

    if (!empty($_GET['max_amount'])) {
        $filterClauses[] = "co.final_amount <= ?";
        $params[] = floatval($_GET['max_amount']);
    }

    // Append filter clauses to query
    if (!empty($filterClauses)) {
        $query .= " AND " . implode(" AND ", $filterClauses);
    }

    // Always filter out admin-deleted orders
    $query .= " AND (co.is_deleted_admin = 0 OR co.is_deleted_admin IS NULL)";

    // Group by order details
    $query .= " GROUP BY co.order_id, co.order_number, co.order_date, co.total_amount, 
                co.tax_amount, co.shipping_amount, co.discount_amount, co.final_amount,
                co.payment_method, co.payment_status, co.order_status, co.tracking_number,
                co.completion_date, co.cancellation_date, co.cancellation_reason,
                co.shipping_address, co.notes, cu.full_name, cu.email, cu.phone";

    // Order by order date (newest first)
    $query .= " ORDER BY co.order_date DESC, co.order_id DESC";

    // Apply pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = intval($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;

    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(DISTINCT co.order_id) as total
        FROM customer_orders co
        JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        WHERE 1=1
    ";

    if (!empty($filterClauses)) {
        $countQuery .= " AND " . implode(" AND ", $filterClauses);
    }

    // Always filter out admin-deleted orders
    $countQuery .= " AND (co.is_deleted_admin = 0 OR co.is_deleted_admin IS NULL)";

    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalCount = $stmt->fetch()['total'];

    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);

    // When returning orders, force payment_method to 'cod' and payment_status to only be 'pending', 'partial', or 'paid' for customer orders.
    foreach ($orders as &$order) {
        if (isset($order['payment_method'])) $order['payment_method'] = 'cod';
        if (!in_array($order['payment_status'], ['pending','partial','paid'])) $order['payment_status'] = 'pending';
    }

    echo json_encode([
        'status' => 'success',
        'data' => $orders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalCount,
            'records_per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);

} catch (Exception $e) {
    error_log("Get Orders Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load orders: ' . $e->getMessage()
    ]);
}
?> 