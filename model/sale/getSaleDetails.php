<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    $sale_id = intval($_GET['sale_id'] ?? 0);
    
    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }

    // Fetch complete sale information
    $stmt = $pdo->prepare("
        SELECT 
            s.sale_id,
            s.invoice_number,
            s.customer_id,
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
            s.created_at,
            s.updated_at,
            -- Customer information
            c.customer_name,
            COALESCE(c.phone, 'N/A') as customer_phone,
            COALESCE(c.email, 'N/A') as customer_email,
            COALESCE(c.address, 'N/A') as customer_address,
            COALESCE(c.city, 'N/A') as customer_city,
            COALESCE(c.state, 'N/A') as customer_state,
            COALESCE(c.zip_code, 'N/A') as customer_zip_code,
            -- Created by user
            u.full_name as created_by_name,
            -- Order information if sale is from customer order
            COALESCE(co.order_number, 'N/A') as order_number,
            COALESCE(co.order_date, 'N/A') as order_date,
            COALESCE(co.total_amount, 'N/A') as order_total_amount,
            COALESCE(co.tax_amount, 'N/A') as order_tax_amount,
            COALESCE(co.shipping_amount, 'N/A') as order_shipping_amount,
            COALESCE(co.discount_amount, 'N/A') as order_discount_amount,
            COALESCE(co.final_amount, 'N/A') as order_final_amount,
            COALESCE(co.payment_method, 'N/A') as order_payment_method,
            COALESCE(co.payment_status, 'N/A') as order_payment_status,
            COALESCE(co.order_status, 'N/A') as order_status,
            COALESCE(co.tracking_number, 'N/A') as order_tracking_number,
            COALESCE(co.completion_date, 'N/A') as order_completion_date,
            COALESCE(co.cancellation_date, 'N/A') as order_cancellation_date,
            COALESCE(co.cancellation_reason, 'N/A') as order_cancellation_reason,
            COALESCE(co.shipping_address, 'N/A') as shipping_address,
            COALESCE(co.notes, 'N/A') as order_notes,
            -- Customer user information if available
            COALESCE(cu.full_name, 'N/A') as customer_user_name,
            COALESCE(cu.email, 'N/A') as customer_user_email,
            COALESCE(cu.phone, 'N/A') as customer_user_phone,
            COALESCE(cu.address, 'N/A') as customer_user_address,
            COALESCE(cu.city, 'N/A') as customer_user_city,
            COALESCE(cu.state, 'N/A') as customer_user_state,
            COALESCE(cu.zip_code, 'N/A') as customer_user_zip_code,
            -- Sale type indicator
            CASE 
                WHEN s.customer_order_id IS NOT NULL THEN 'From Customer Order'
                ELSE 'Direct Sale'
            END as sale_type
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN users u ON s.created_by = u.user_id
        LEFT JOIN customer_orders co ON s.customer_order_id = co.order_id
        LEFT JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
        WHERE s.sale_id = :sale_id
    ");
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found.');
    }

    // Fetch all sale items for this sale
    $itemsStmt = $pdo->prepare("
        SELECT 
            sd.sale_detail_id,
            sd.item_id,
            sd.quantity,
            sd.unit_price,
            sd.total_price,
            i.item_name,
            i.item_number,
            i.description,
            i.unit_of_measure,
            i.current_stock
        FROM sale_details sd
        JOIN inventory i ON sd.item_id = i.item_id
        WHERE sd.sale_id = :sale_id
        ORDER BY sd.sale_detail_id ASC
    ");
    $itemsStmt->execute(['sale_id' => $sale_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    $sale['items'] = $items;

    // Fetch all payments for this sale
    $paymentsStmt = $pdo->prepare("
        SELECT
            payment_id,
            payment_date,
            amount,
            method,
            COALESCE(notes, 'N/A') as notes,
            created_at
        FROM payments
        WHERE sale_id = :sale_id
        ORDER BY payment_date ASC, created_at ASC
    ");
    $paymentsStmt->execute(['sale_id' => $sale_id]);
    $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    $sale['payments'] = $payments;

    // Format numeric values
    $sale['total_amount'] = number_format(floatval($sale['total_amount']), 2, '.', '');
    $sale['paid_amount'] = number_format(floatval($sale['paid_amount']), 2, '.', '');
    $sale['pending_amount'] = number_format(floatval($sale['pending_amount']), 2, '.', '');

    // Format order amounts if not N/A
    if ($sale['order_total_amount'] !== 'N/A') {
        $sale['order_total_amount'] = number_format(floatval($sale['order_total_amount']), 2, '.', '');
    }
    if ($sale['order_tax_amount'] !== 'N/A') {
        $sale['order_tax_amount'] = number_format(floatval($sale['order_tax_amount']), 2, '.', '');
    }
    if ($sale['order_shipping_amount'] !== 'N/A') {
        $sale['order_shipping_amount'] = number_format(floatval($sale['order_shipping_amount']), 2, '.', '');
    }
    if ($sale['order_discount_amount'] !== 'N/A') {
        $sale['order_discount_amount'] = number_format(floatval($sale['order_discount_amount']), 2, '.', '');
    }
    if ($sale['order_final_amount'] !== 'N/A') {
        $sale['order_final_amount'] = number_format(floatval($sale['order_final_amount']), 2, '.', '');
    }

    // Format item amounts
    foreach ($sale['items'] as &$item) {
        $item['quantity'] = number_format(floatval($item['quantity']), 2, '.', '');
        $item['unit_price'] = number_format(floatval($item['unit_price']), 2, '.', '');
        $item['total_price'] = number_format(floatval($item['total_price']), 2, '.', '');
        $item['current_stock'] = number_format(floatval($item['current_stock']), 2, '.', '');
    }

    // Format payment amounts
    foreach ($sale['payments'] as &$payment) {
        $payment['amount'] = number_format(floatval($payment['amount']), 2, '.', '');
    }

    // --- FIX: Use order final amount for totals if sale is from customer order ---
    if ($sale['customer_order_id'] && $sale['order_final_amount'] !== 'N/A') {
        $sale['display_total_amount'] = $sale['order_final_amount'];
        $sale['display_paid_amount'] = $sale['paid_amount'];
        $sale['display_pending_amount'] = number_format(floatval($sale['order_final_amount']) - floatval($sale['paid_amount']), 2, '.', '');
    } else {
        $sale['display_total_amount'] = $sale['total_amount'];
        $sale['display_paid_amount'] = $sale['paid_amount'];
        $sale['display_pending_amount'] = $sale['pending_amount'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $sale
    ]);

} catch (Exception $e) {
    error_log("Get Sale Details Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Get Sale Details DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred.'
    ]);
}
