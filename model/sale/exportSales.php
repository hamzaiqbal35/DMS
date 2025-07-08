<?php
session_name('admin_session');
session_start();

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
require_once __DIR__ . '/../../inc/config/auth.php';
require_jwt_auth();

require_once '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Get filter parameters
$customer_id = $_REQUEST['customer_id'] ?? '';
$date_from = $_REQUEST['date_from'] ?? '';
$date_to = $_REQUEST['date_to'] ?? '';
$min_amount = $_REQUEST['min_amount'] ?? '';
$max_amount = $_REQUEST['max_amount'] ?? '';
$payment_status = $_REQUEST['payment_status'] ?? '';
$order_status = $_REQUEST['order_status'] ?? '';
$sale_type = $_REQUEST['sale_type'] ?? '';
$export_format = $_REQUEST['format'] ?? 'csv';
$max_export_rows = 1000; 

// If no date range is provided, default to last 30 days
if (empty($date_from) && empty($date_to)) {
    $date_from = date('Y-m-d', strtotime('-30 days'));
    $date_to = date('Y-m-d');
}

try {
    $query = "
        SELECT 
            s.sale_id,
            s.invoice_number,
            c.customer_name,
            COALESCE(c.phone, 'N/A') as customer_phone,
            COALESCE(c.email, 'N/A') as customer_email,
            COALESCE(c.address, 'N/A') as customer_address,
            COALESCE(c.city, 'N/A') as customer_city,
            COALESCE(c.state, 'N/A') as customer_state,
            COALESCE(c.zip_code, 'N/A') as customer_zip_code,
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
            COALESCE(s.notes, 'N/A') as notes,
            s.created_at,
            u.full_name as created_by_name,
            COALESCE(co.order_number, 'N/A') as order_number,
            COALESCE(co.order_date, 'N/A') as order_date,
            COALESCE(co.total_amount, 'N/A') as order_total_amount,
            COALESCE(co.tax_amount, 'N/A') as order_tax_amount,
            COALESCE(co.shipping_amount, 'N/A') as order_shipping_amount,
            COALESCE(co.discount_amount, 'N/A') as order_discount_amount,
            COALESCE(co.final_amount, 'N/A') as order_final_amount,
            COALESCE(co.payment_method, 'N/A') as order_payment_method,
            COALESCE(co.payment_status, 'N/A') as order_payment_status,
            COALESCE(co.order_status, 'N/A') as order_status_original,
            COALESCE(co.tracking_number, 'N/A') as order_tracking_number,
            COALESCE(co.shipping_address, 'N/A') as shipping_address,
            COALESCE(cu.full_name, 'N/A') as customer_user_name,
            COALESCE(cu.email, 'N/A') as customer_user_email,
            COALESCE(cu.phone, 'N/A') as customer_user_phone,
            CASE 
                WHEN s.customer_order_id IS NOT NULL THEN 'From Customer Order'
                ELSE 'Direct Sale'
            END as sale_type,
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
            ) as items_count
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

    if (!empty($customer_id)) {
        $query .= " AND s.customer_id = :customer_id";
        $params[':customer_id'] = $customer_id;
    }

    if (!empty($date_from)) {
        $query .= " AND s.sale_date >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $query .= " AND s.sale_date <= :date_to";
        $params[':date_to'] = $date_to;
    }

    if (!empty($min_amount)) {
        $query .= " AND s.total_amount >= :min_amount";
        $params[':min_amount'] = $min_amount;
    }

    if (!empty($max_amount)) {
        $query .= " AND s.total_amount <= :max_amount";
        $params[':max_amount'] = $max_amount;
    }

    if (!empty($payment_status)) {
        $query .= " AND s.payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    if (!empty($order_status)) {
        $query .= " AND s.order_status = :order_status";
        $params[':order_status'] = $order_status;
    }

    if (!empty($sale_type)) {
        if ($sale_type === 'direct') {
            $query .= " AND s.customer_order_id IS NULL";
        } elseif ($sale_type === 'from_order') {
            $query .= " AND s.customer_order_id IS NOT NULL";
        }
    }

    $query .= " ORDER BY s.sale_date DESC, s.sale_id DESC";
    // Add hard row limit for export
    $query .= " LIMIT $max_export_rows";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process data to ensure no null values
    $processed_sales = [];
    foreach ($sales as $sale) {
        $processed_sale = $sale; // Create a copy to avoid reference issues
        
        // Format numeric values
        $processed_sale['total_amount'] = number_format(floatval($sale['total_amount']), 2, '.', '');
        $processed_sale['paid_amount'] = number_format(floatval($sale['paid_amount']), 2, '.', '');
        $processed_sale['pending_amount'] = number_format(floatval($sale['pending_amount']), 2, '.', '');
        
        // Format order amounts if not N/A
        if ($sale['order_total_amount'] !== 'N/A') {
            $processed_sale['order_total_amount'] = number_format(floatval($sale['order_total_amount']), 2, '.', '');
        }
        if ($sale['order_tax_amount'] !== 'N/A') {
            $processed_sale['order_tax_amount'] = number_format(floatval($sale['order_tax_amount']), 2, '.', '');
        }
        if ($sale['order_shipping_amount'] !== 'N/A') {
            $processed_sale['order_shipping_amount'] = number_format(floatval($sale['order_shipping_amount']), 2, '.', '');
        }
        if ($sale['order_discount_amount'] !== 'N/A') {
            $processed_sale['order_discount_amount'] = number_format(floatval($sale['order_discount_amount']), 2, '.', '');
        }
        if ($sale['order_final_amount'] !== 'N/A') {
            $processed_sale['order_final_amount'] = number_format(floatval($sale['order_final_amount']), 2, '.', '');
        }
        
        // Format dates
        if ($sale['sale_date'] !== 'N/A') {
            $processed_sale['sale_date'] = date('Y-m-d', strtotime($sale['sale_date']));
        }
        if ($sale['completion_date'] !== 'N/A') {
            $processed_sale['completion_date'] = date('Y-m-d H:i:s', strtotime($sale['completion_date']));
        }
        if ($sale['cancellation_date'] !== 'N/A') {
            $processed_sale['cancellation_date'] = date('Y-m-d H:i:s', strtotime($sale['cancellation_date']));
        }
        if ($sale['order_date'] !== 'N/A') {
            $processed_sale['order_date'] = date('Y-m-d H:i:s', strtotime($sale['order_date']));
        }
        if ($sale['created_at'] !== 'N/A') {
            $processed_sale['created_at'] = date('Y-m-d H:i:s', strtotime($sale['created_at']));
        }
        
        $processed_sales[] = $processed_sale;
    }
    
    // Use the processed sales array
    $sales = $processed_sales;

    if ($export_format === 'csv') {
        // Export as CSV (streaming, not loading all rows into memory)
        $filename = 'sales_export_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        $output = fopen('php://output', 'w');
        $headers = [
            'Invoice Number', 'Customer Name', 'Customer Phone', 'Customer Email', 'Customer Address', 'Customer City', 'Customer State', 'Customer Zip Code',
            'Sale Date', 'Sale Type', 'Total Amount', 'Paid Amount', 'Pending Amount', 'Payment Status', 'Order Status', 'Tracking Number',
            'Completion Date', 'Cancellation Date', 'Cancellation Reason', 'Notes', 'Created By', 'Created At', 'Order Number', 'Order Date',
            'Order Total Amount', 'Order Tax Amount', 'Order Shipping Amount', 'Order Discount Amount', 'Order Final Amount', 'Order Payment Method',
            'Order Payment Status', 'Order Status (Original)', 'Order Tracking Number', 'Shipping Address', 'Customer User Name', 'Customer User Email',
            'Customer User Phone', 'Items Details', 'Items Count'
        ];
        fputcsv($output, $headers);
        $record_count = 0;
        $total_amount = 0;
        $total_paid = 0;
        $total_pending = 0;
        while ($sale = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $record_count++;
            // Format/clean values as before
            foreach ($sale as $k => $v) {
                if ($v === 'N/A') $sale[$k] = '-';
            }
            $sale['total_amount'] = number_format(floatval($sale['total_amount']), 2, '.', '');
            $sale['paid_amount'] = number_format(floatval($sale['paid_amount']), 2, '.', '');
            $sale['pending_amount'] = number_format(floatval($sale['pending_amount']), 2, '.', '');
            $total_amount += floatval($sale['total_amount']);
            $total_paid += floatval($sale['paid_amount']);
            $total_pending += floatval($sale['pending_amount']);
            $row = [
                $sale['invoice_number'], $sale['customer_name'], $sale['customer_phone'], $sale['customer_email'], $sale['customer_address'],
                $sale['customer_city'], $sale['customer_state'], $sale['customer_zip_code'], $sale['sale_date'], $sale['sale_type'],
                $sale['total_amount'], $sale['paid_amount'], $sale['pending_amount'], $sale['payment_status'], $sale['order_status'],
                $sale['tracking_number'], $sale['completion_date'], $sale['cancellation_date'], $sale['cancellation_reason'], $sale['notes'],
                $sale['created_by_name'], $sale['created_at'], $sale['order_number'], $sale['order_date'], $sale['order_total_amount'],
                $sale['order_tax_amount'], $sale['order_shipping_amount'], $sale['order_discount_amount'], $sale['order_final_amount'],
                $sale['order_payment_method'], $sale['order_payment_status'], $sale['order_status_original'], $sale['order_tracking_number'],
                $sale['shipping_address'], $sale['customer_user_name'], $sale['customer_user_email'], $sale['customer_user_phone'],
                $sale['items_details'], $sale['items_count']
            ];
            fputcsv($output, $row);
        }
        // Add summary row
        $summary = array_fill(0, count($headers), '-');
        $summary[0] = 'TOTAL SUMMARY';
        $summary[1] = 'Total Records: ' . $record_count;
        $summary[9] = 'Totals:';
        $summary[10] = number_format($total_amount, 2);
        $summary[11] = number_format($total_paid, 2);
        $summary[12] = number_format($total_pending, 2);
        fputcsv($output, []); // blank line
        fputcsv($output, $summary);
        fclose($output);
        exit;
    } elseif ($export_format === 'pdf') {
        // Export as PDF using Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // Set timezone to Pakistan
        date_default_timezone_set('Asia/Karachi');
        $generated_date = date('F j, Y \a\t g:i A');
        $company_name = 'Allied Steel Works';
        $company_address = 'Allied Steel Works (Pvt) Ltd., Service Road, Bhamma, Lahore, Pakistan';
        $company_email = 'info@alliedsteelworks.pk';
        $company_phone = '+92-300-1234567';
        $logo_path = __DIR__ . '/../../assets/images/logo.png';
        $logo_data = '';
        if (file_exists($logo_path)) {
            $logo_data = base64_encode(file_get_contents($logo_path));
        }

        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Sales Report - Allied Steel Works</title>
            <style>
                @page { margin: 20px; }
                body { 
                    font-family: "Poppins", Arial, sans-serif; 
                    font-size: 11px; 
                    color: #222; 
                    background: #fff; 
                    margin: 0; 
                    padding: 0; 
                    line-height: 1.4;
                }
                .header { 
                    text-align: left; 
                    margin-bottom: 20px; 
                    background: #1976d2;
                    padding: 18px 20px 14px 20px;
                    border-radius: 8px 8px 0 0;
                    color: #fff;
                    display: flex;
                    align-items: center;
                }
                .logo { 
                    width: 60px; 
                    height: 60px; 
                    margin-right: 18px; 
                    border-radius: 8px; 
                    border: 2px solid #fff; 
                    background: #fff; 
                    object-fit: cover;
                }
                .company-info { font-size: 13px; color: #fff; }
                .company-info h1 { margin: 0 0 2px 0; font-size: 22px; color: #fff; letter-spacing: 1px; font-weight: 700; }
                .company-info p { margin: 0; color: #e3e3e3; font-size: 12px; }
                .report-title { text-align: right; font-size: 20px; color: #fff; font-weight: 700; margin-left: auto; }
                .report-info {
                    margin-bottom: 16px;
                    font-size: 12px;
                    color: #222;
                    background: #e3f2fd;
                    padding: 10px 14px;
                    border-radius: 6px;
                    border-left: 4px solid #1976d2;
                }
                .summary {
                    margin: 16px 0;
                    padding: 10px 14px;
                    background: #e3f2fd;
                    border-radius: 6px;
                    font-size: 12px;
                    border-left: 4px solid #1976d2;
                    color: #222;
                }
                .summary h3 { margin: 0 0 6px 0; font-size: 15px; color: #1976d2; }
                .summary p { margin: 4px 0; color: #222; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 18px; background: #fff; border-radius: 8px; overflow: hidden; }
                th, td { border: 1px solid #e0e0e0; padding: 7px 5px; text-align: left; font-size: 10px; }
                th { background: #1976d2; color: #fff; font-weight: 700; font-size: 11px; text-transform: uppercase; }
                tr:nth-child(even) { background: #f6f8fa; }
                tr:nth-child(odd) { background: #fff; }
                .status-badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 9px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #fff;
                    text-align: center;
                    min-width: 60px;
                }
                .status-paid { background: #27ae60; }
                .status-pending { background: #f39c12; }
                .status-partial { background: #3498db; }
                .status-confirmed { background: #3498db; }
                .status-processing { background: #9b59b6; }
                .status-shipped { background: #e67e22; }
                .status-delivered { background: #27ae60; }
                .status-cancelled { background: #e74c3c; }
                .sale-type-badge {
                    display: inline-block;
                    padding: 2px 6px;
                    border-radius: 8px;
                    font-size: 8px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                    color: #fff;
                }
                .sale-type-direct { background: #95a5a6; }
                .sale-type-order { background: #3498db; }
                .amount-cell { font-weight: 600; color: #2c3e50; }
                .footer { text-align: center; font-size: 11px; color: #7f8c8d; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ecf0f1; }
            </style>
        </head>
        <body>';

        // Header with logo and company info
        $html .= '<div class="header">';
        if ($logo_data) {
            $html .= '<img src="data:image/png;base64,' . $logo_data . '" class="logo" alt="Allied Steel Works Logo">';
        }
        $html .= '<div class="company-info">
            <h1>' . htmlspecialchars($company_name) . '</h1>
            <p>' . htmlspecialchars($company_address) . '</p>
            <p>' . htmlspecialchars($company_email) . ' | ' . htmlspecialchars($company_phone) . '</p>
        </div>';
        $html .= '<div class="report-title">Sales Report</div>';
        $html .= '</div>';

        // Report info
        $html .= '<div class="report-info">';
        $html .= '<span><strong>Generated on:</strong> ' . $generated_date . '</span>';
        if ($date_from || $date_to) {
            $html .= '<br><strong>Date Range:</strong> ' . ($date_from ?: 'Start') . ' to ' . ($date_to ?: 'End');
        }
        if ($payment_status) {
            $html .= '<br><strong>Payment Status:</strong> ' . ucfirst($payment_status);
        }
        if ($order_status) {
            $html .= '<br><strong>Order Status:</strong> ' . ucfirst($order_status);
        }
        if ($sale_type) {
            $html .= '<br><strong>Sale Type:</strong> ' . ($sale_type === 'direct' ? 'Direct Sale' : 'From Customer Order');
        }
        $html .= '</div>';

        // Add summary
        $total_amount = array_sum(array_column($sales, 'total_amount'));
        $total_paid = array_sum(array_column($sales, 'paid_amount'));
        $total_pending = array_sum(array_column($sales, 'pending_amount'));
        $html .= '<div class="summary">';
        $html .= '<h3>Report Summary</h3>';
        $html .= '<p><strong>Total Records:</strong> ' . count($sales) . '</p>';
        $html .= '<p><strong>Total Amount:</strong> PKR ' . number_format($total_amount, 2) . '</p>';
        $html .= '<p><strong>Total Paid:</strong> PKR ' . number_format($total_paid, 2) . '</p>';
        $html .= '<p><strong>Total Pending:</strong> PKR ' . number_format($total_pending, 2) . '</p>';
        $html .= '</div>';

        // Add table
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Invoice #</th>';
        $html .= '<th>Customer</th>';
        $html .= '<th>Sale Type</th>';
        $html .= '<th>Date</th>';
        $html .= '<th>Total</th>';
        $html .= '<th>Paid</th>';
        $html .= '<th>Pending</th>';
        $html .= '<th>Payment Status</th>';
        $html .= '<th>Order Status</th>';
        $html .= '<th>Items</th>';
        $html .= '</tr></thead><tbody>';
        
        $record_count = 0;
        foreach ($sales as $sale) {
            $record_count++;
            $sale_type_class = ($sale['sale_type'] === 'Direct Sale') ? 'sale-type-direct' : 'sale-type-order';
            $payment_status_class = 'status-' . strtolower($sale['payment_status']);
            $order_status_class = 'status-' . strtolower($sale['order_status']);
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($sale['invoice_number']) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($sale['customer_name']) . '</td>';
            $html .= '<td><span class="sale-type-badge ' . $sale_type_class . '">' . htmlspecialchars($sale['sale_type']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($sale['sale_date']) . '</td>';
            $html .= '<td class="amount-cell">PKR ' . htmlspecialchars($sale['total_amount']) . '</td>';
            $html .= '<td class="amount-cell">PKR ' . htmlspecialchars($sale['paid_amount']) . '</td>';
            $html .= '<td class="amount-cell">PKR ' . htmlspecialchars($sale['pending_amount']) . '</td>';
            $html .= '<td><span class="status-badge ' . $payment_status_class . '">' . ucfirst(htmlspecialchars($sale['payment_status'])) . '</span></td>';
            $html .= '<td><span class="status-badge ' . $order_status_class . '">' . ucfirst(htmlspecialchars($sale['order_status'])) . '</span></td>';
            $html .= '<td style="max-width: 200px; word-wrap: break-word;">' . htmlspecialchars($sale['items_details']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="footer">Report generated by Allied Steel Works &mdash; Powered by DMS | ' . $generated_date . '</div>';
        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $dompdf->stream($filename, ['Attachment' => true]);
    }

} catch (Exception $e) {
    error_log("Export Sales Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Export failed: ' . $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Export Sales DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred during export.'
    ]);
}
?>