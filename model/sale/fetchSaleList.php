<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Build base query - Join necessary tables to get all sale data
    $query = "
        SELECT 
            s.sale_id,
            s.invoice_number,
            s.customer_id,
            c.customer_name,
            s.sale_date,
            s.total_amount,
            s.payment_status,
            COALESCE(s.paid_amount, 0) as paid_amount,
            (s.total_amount - COALESCE(s.paid_amount, 0)) as pending_amount,
            s.order_status,
            s.customer_order_id,
            COALESCE(s.tracking_number, 'N/A') as tracking_number,
            COALESCE(s.completion_date, 'N/A') as completion_date,
            COALESCE(s.cancellation_date, 'N/A') as cancellation_date,
            COALESCE(s.cancellation_reason, 'N/A') as cancellation_reason,
            COALESCE(s.invoice_file, 'N/A') as invoice_file,
            COALESCE(s.notes, 'N/A') as notes,
            u.full_name as created_by_name,
            s.created_at,
            s.updated_at,
            -- Order information if sale is from customer order
            COALESCE(co.order_number, 'N/A') as order_number,
            COALESCE(co.order_date, 'N/A') as order_date,
            COALESCE(co.final_amount, 'N/A') as order_final_amount,
            COALESCE(co.payment_method, 'N/A') as order_payment_method,
            COALESCE(co.shipping_address, 'N/A') as shipping_address,
            -- Customer user information if available
            COALESCE(cu.full_name, 'N/A') as customer_user_name,
            COALESCE(cu.email, 'N/A') as customer_user_email,
            COALESCE(cu.phone, 'N/A') as customer_user_phone,
            -- Item details - Fixed GROUP_CONCAT to prevent duplicates
            (
                SELECT GROUP_CONCAT(
                    CONCAT(
                        i2.item_name, ' (', 
                        sd2.quantity, ' x ', 
                        FORMAT(sd2.unit_price, 2), ' = ', 
                        FORMAT(sd2.total_price, 2), ')'
                    ) SEPARATOR '; '
                )
                FROM sale_details sd2
                JOIN inventory i2 ON sd2.item_id = i2.item_id
                WHERE sd2.sale_id = s.sale_id
            ) as items_details,
            (
                SELECT COUNT(DISTINCT sd3.item_id)
                FROM sale_details sd3
                WHERE sd3.sale_id = s.sale_id
            ) as total_items,
            (
                SELECT SUM(sd4.quantity)
                FROM sale_details sd4
                WHERE sd4.sale_id = s.sale_id
            ) as total_quantity,
            -- Sale type indicator
            CASE 
                WHEN s.customer_order_id IS NOT NULL THEN 'From Customer Order'
                ELSE 'Direct Sale'
            END as sale_type,
            -- Order source (for backward compatibility)
            CASE 
                WHEN s.customer_order_id IS NOT NULL THEN 'Customer Panel'
                ELSE 'Admin Panel'
            END as order_source
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN users u ON s.created_by = u.user_id
        LEFT JOIN customer_orders co ON s.customer_order_id = co.order_id
        LEFT JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        WHERE 1=1
        AND (
            s.customer_order_id IS NULL
            OR (s.customer_order_id IS NOT NULL AND (co.order_status NOT IN ('pending', 'cancelled')))
        )
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

    // Apply order status filter
    if (!empty($_GET['order_status'])) {
        $filterClauses[] = "s.order_status = :order_status";
        $params[':order_status'] = $_GET['order_status'];
    }

    // Apply sale type filter
    if (!empty($_GET['sale_type'])) {
        if ($_GET['sale_type'] === 'direct') {
            $filterClauses[] = "s.customer_order_id IS NULL";
        } elseif ($_GET['sale_type'] === 'from_order') {
            $filterClauses[] = "s.customer_order_id IS NOT NULL";
        }
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

    // Apply order source filter (for backward compatibility)
    if (!empty($_GET['order_source'])) {
        if ($_GET['order_source'] === 'online') {
            $filterClauses[] = "s.customer_order_id IS NOT NULL";
        } elseif ($_GET['order_source'] === 'admin') {
            $filterClauses[] = "s.customer_order_id IS NULL";
        }
    }

    // Apply search filter
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $filterClauses[] = "(
            c.customer_name LIKE :search OR 
            s.invoice_number LIKE :search OR 
            s.notes LIKE :search OR
            COALESCE(co.order_number, '') LIKE :search OR
            COALESCE(s.tracking_number, '') LIKE :search OR
            COALESCE(cu.full_name, '') LIKE :search OR
            COALESCE(cu.email, '') LIKE :search
        )";
        $params[':search'] = $searchTerm;
    }

    // Append filter clauses to query
    if (!empty($filterClauses)) {
        $query .= " AND " . implode(" AND ", $filterClauses);
    }

    // Order by sale date and ID - No GROUP BY needed since we're using subqueries
    $query .= " ORDER BY s.sale_date DESC, s.sale_id DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results to ensure no null values
    foreach ($sales as &$sale) {
        // Ensure all numeric fields are properly formatted
        $sale['total_amount'] = number_format(floatval($sale['total_amount']), 2, '.', '');
        $sale['paid_amount'] = number_format(floatval($sale['paid_amount']), 2, '.', '');
        $sale['pending_amount'] = number_format(floatval($sale['pending_amount']), 2, '.', '');
        
        // Ensure date fields are properly formatted
        if ($sale['sale_date'] !== 'N/A') {
            $sale['sale_date'] = date('Y-m-d', strtotime($sale['sale_date']));
        }
        if ($sale['completion_date'] !== 'N/A') {
            $sale['completion_date'] = date('Y-m-d H:i:s', strtotime($sale['completion_date']));
        }
        if ($sale['cancellation_date'] !== 'N/A') {
            $sale['cancellation_date'] = date('Y-m-d H:i:s', strtotime($sale['cancellation_date']));
        }
        if ($sale['order_date'] !== 'N/A') {
            $sale['order_date'] = date('Y-m-d H:i:s', strtotime($sale['order_date']));
        }
        
        // Ensure order final amount is formatted if not N/A
        if ($sale['order_final_amount'] !== 'N/A') {
            $sale['order_final_amount'] = number_format(floatval($sale['order_final_amount']), 2, '.', '');
        }

        // --- NEW LOGIC: Add display fields for correct totals ---
        if ($sale['customer_order_id'] && $sale['order_final_amount'] !== 'N/A') {
            // Use order's final amount for total, sale's paid for paid, pending = total - paid
            $sale['display_total_amount'] = $sale['order_final_amount'];
            $sale['display_paid_amount'] = $sale['paid_amount'];
            $sale['display_pending_amount'] = number_format(floatval($sale['order_final_amount']) - floatval($sale['paid_amount']), 2, '.', '');
        } else {
            // Use sale's own fields
            $sale['display_total_amount'] = $sale['total_amount'];
            $sale['display_paid_amount'] = $sale['paid_amount'];
            $sale['display_pending_amount'] = $sale['pending_amount'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $sales
    ]);

} catch (Exception $e) {
    error_log("Fetch Sale List Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch sales data.'
    ]);
} catch (PDOException $e) {
    error_log("Fetch Sale List DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred.'
    ]);
}
?>