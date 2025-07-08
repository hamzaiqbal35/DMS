<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
require_once '../../vendor/autoload.php';

// Set timezone to Pakistan
if(function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Karachi');
}

use Dompdf\Dompdf;
use Dompdf\Options;
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    $sale_id = intval($_POST['sale_id'] ?? 0);
    $invoice_date = trim($_POST['invoice_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    
    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }
    
    if (empty($invoice_date)) {
        throw new Exception('Invoice date is required.');
    }
    
    if (empty($due_date)) {
        throw new Exception('Due date is required.');
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

    // --- Sale Type, Customer Info, and Badge Logic ---
    $isOrderSale = ($sale['sale_type'] === 'From Customer Order' || ($sale['customer_order_id'] && $sale['order_final_amount'] !== 'N/A'));
    // Customer info
    if ($isOrderSale) {
        $customer_name = ($sale['customer_user_name'] !== 'N/A' && !empty($sale['customer_user_name'])) ? $sale['customer_user_name'] : $sale['customer_name'];
        $customer_email = ($sale['customer_user_email'] !== 'N/A' && !empty($sale['customer_user_email'])) ? $sale['customer_user_email'] : $sale['customer_email'];
        $customer_phone = ($sale['customer_user_phone'] !== 'N/A' && !empty($sale['customer_user_phone'])) ? $sale['customer_user_phone'] : $sale['customer_phone'];
        $customer_address = ($sale['shipping_address'] !== 'N/A' && !empty($sale['shipping_address'])) ? $sale['shipping_address'] : $sale['customer_address'];
    } else {
        $customer_name = $sale['customer_name'];
        $customer_email = $sale['customer_email'];
        $customer_phone = $sale['customer_phone'];
        $customer_address = $sale['customer_address'];
    }
    // Sale type badge
    $sale_type_badge = $isOrderSale
        ? '<span style="background:#17a2b8;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;">From Customer Order</span>'
        : '<span style="background:#6c757d;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;">Direct Sale</span>';

    // Generate PDF invoice
    $primaryColor = '#198754'; // Bootstrap green
    $shippingAmount = isset($sale['order_shipping_amount']) && is_numeric($sale['order_shipping_amount']) ? floatval($sale['order_shipping_amount']) : 0.00;
    // Use absolute URL for dompdf image embedding
    $logoUrl = 'http://localhost/DMS/assets/images/logo.png';
    // Start building HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice - ' . $sale['invoice_number'] . '</title>
        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                font-size: 11.2px;
                color: #222;
                background: #fff;
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                background: #fff;
                max-width: 700px;
                margin: 0 auto;
                border-radius: 8px;
                box-shadow: none;
                padding: 18px 18px 16px 18px;
            }
            .header {
                display: flex;
                align-items: center;
                border-bottom: 1.5px solid ' . $primaryColor . ';
                padding-bottom: 10px;
                margin-bottom: 16px;
            }
            .company-logo {
                max-width: 60px;
                max-height: 60px;
                width: auto;
                height: auto;
                display: block;
                margin-right: 14px;
                object-fit: contain;
                vertical-align: middle;
            }
            .company-details {
                flex: 1;
            }
            .company-details h2 {
                margin: 0 0 2px 0;
                font-size: 1.15rem;
                color: ' . $primaryColor . ';
                letter-spacing: 0.5px;
            }
            .company-details p {
                margin: 0;
                color: #444;
                font-size: 0.95rem;
            }
            .invoice-info {
                text-align: right;
            }
            .invoice-info h1 {
                margin: 0 0 2px 0;
                font-size: 1.25rem;
                color: #222;
                letter-spacing: 1px;
            }
            .invoice-info p {
                margin: 1px 0;
                color: #666;
                font-size: 0.95rem;
            }
            .section-title {
                font-size: 1rem;
                color: ' . $primaryColor . ';
                margin-bottom: 4px;
                margin-top: 14px;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            .customer-info, .order-info {
                margin-bottom: 10px;
                background: #f1f6fb;
                border-radius: 5px;
                padding: 8px 10px 6px 10px;
                border-left: 3px solid ' . $primaryColor . ';
            }
            .customer-info h3, .order-info h4 {
                margin: 0 0 4px 0;
                color: ' . $primaryColor . ';
                font-size: 1rem;
            }
            .customer-info p, .order-info p {
                margin: 2px 0;
                color: #333;
                font-size: 0.97em;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
                background: #fff;
                border-radius: 4px;
                overflow: hidden;
                box-shadow: none;
            }
            .items-table th, .items-table td {
                border: 1px solid #e3e6f0;
                padding: 5px 4px;
                text-align: left;
            }
            .items-table th {
                background: ' . $primaryColor . ';
                color: #fff;
                font-weight: 600;
                font-size: 0.98rem;
            }
            .items-table tr:nth-child(even) td {
                background: #f7f7f9;
            }
            .total-section {
                text-align: right;
                margin-top: 8px;
                margin-bottom: 8px;
            }
            .total-row {
                margin: 2px 0;
                font-size: 1rem;
            }
            .total-amount {
                font-size: 1.08rem;
                font-weight: bold;
                color: ' . $primaryColor . ';
            }
            .footer {
                margin-top: 18px;
                text-align: center;
                font-size: 0.93rem;
                color: #888;
                border-top: 1px solid #e3e6f0;
                padding-top: 8px;
            }
            .badge {
                display: inline-block;
                padding: 1px 7px;
                border-radius: 10px;
                font-size: 0.93rem;
                color: #fff;
                background: ' . $primaryColor . ';
                margin-right: 3px;
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <img src="' . $logoUrl . '" class="company-logo" />
                <div class="company-details">
                    <h2>Allied Steel Works</h2>
                    <p>Your Trusted Steel Solutions Partner</p>
                    <p>Lahore, Pakistan</p>
                    <p>Email: info@alliedsteelworks.com | Phone: +92-300-123-4567</p>
                </div>
                <div class="invoice-info">
                    <h1>INVOICE</h1>
                    <p><strong>Invoice #:</strong> ' . $sale['invoice_number'] . '</p>
                    <p><strong>Invoice Date:</strong> ' . date('F j, Y', strtotime($invoice_date)) . '</p>
                    <p><strong>Due Date:</strong> ' . date('F j, Y', strtotime($due_date)) . '</p>
                    <p><strong>Sale Date:</strong> ' . date('F j, Y', strtotime($sale['sale_date'])) . '</p>
                    <p><strong>Sale Type:</strong> ' . $sale_type_badge . '</p>
                </div>
            </div>';

    // Add customer information
    $html .= '
        <div class="customer-info">
            <h3>Bill To:</h3>
            <p><strong>' . $customer_name . '</strong><br>';
    if ($customer_phone !== 'N/A') {
        $html .= 'Phone: ' . $customer_phone . '<br>';
    }
    if ($customer_email !== 'N/A') {
        $html .= 'Email: ' . $customer_email . '<br>';
    }
    if ($customer_address !== 'N/A') {
        $html .= $customer_address . '<br>';
    }
    if ($sale['customer_city'] !== 'N/A' || $sale['customer_state'] !== 'N/A' || $sale['customer_zip_code'] !== 'N/A') {
        $html .= $sale['customer_city'] . ', ' . $sale['customer_state'] . ' ' . $sale['customer_zip_code'];
    }
    $html .= '</p></div>';

    // Add order information if sale is from customer order
    if ($sale['customer_order_id'] && $sale['order_number'] !== 'N/A') {
        $html .= '
        <div class="order-info">
            <h4>Original Order Information</h4>
            <p><strong>Order Number:</strong> ' . $sale['order_number'] . '<br>
            <strong>Order Date:</strong> ' . ($sale['order_date'] !== 'N/A' ? date('F j, Y', strtotime($sale['order_date'])) : 'N/A') . '<br>
            <strong>Order Payment Method:</strong> ' . $sale['order_payment_method'] . '<br>
            <strong>Ordered By:</strong> ' . $sale['customer_user_name'] . ' (' . $sale['customer_user_email'] . ')</p>';
        if ($sale['shipping_address'] !== 'N/A') {
            $html .= '<p><strong>Shipping Address:</strong><br>' . $sale['shipping_address'] . '</p>';
        }
        $html .= '</div>';
    }

    // Add items table
    $html .= '
        <div class="section-title">Order Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($sale['items'] as $item) {
        $html .= '
            <tr>
                <td>' . $item['item_name'] . '</td>
                <td>' . $item['item_number'] . '</td>
                <td>' . ($item['description'] ?: 'N/A') . '</td>
                <td>' . $item['unit_of_measure'] . '</td>
                <td>' . number_format($item['quantity'], 2) . '</td>
                <td>PKR ' . number_format($item['unit_price'], 2) . '</td>
                <td>PKR ' . number_format($item['total_price'], 2) . '</td>
            </tr>';
    }
    $html .= '</tbody></table>';


    // --- Unified total/paid/pending logic ---
    $display_total = $isOrderSale ? $sale['order_final_amount'] : $sale['total_amount'];
    $display_paid = $sale['paid_amount'];
    $display_pending = number_format(floatval($display_total) - floatval($display_paid), 2, '.', '');

    // Add totals
    $html .= '
        <div class="total-section">';
    if ($shippingAmount > 0) {
        $html .= '<div class="total-row">
                <strong>Shipping Amount:</strong> PKR ' . number_format($shippingAmount, 2) . '
            </div>';
    }
    $html .= '<div class="total-row">
                <strong>Subtotal:</strong> PKR ' . number_format($sale['total_amount'], 2) . '
            </div>';
    $html .= '<div class="total-row">
                <strong>Paid Amount:</strong> PKR ' . number_format($display_paid, 2) . '
            </div>';
    $html .= '<div class="total-row total-amount">
                <strong>Pending Amount:</strong> PKR ' . number_format($display_pending, 2) . '
            </div>';
    $html .= '</div>';

    // Add additional information
    if ($sale['tracking_number'] !== 'N/A') {
        $html .= '<p><strong>Tracking Number:</strong> ' . $sale['tracking_number'] . '</p>';
    }
    if ($sale['notes'] !== 'N/A') {
        $html .= '<p><strong>Notes:</strong> ' . $sale['notes'] . '</p>';
    }

    // Add footer
    $html .= '
        <div class="footer">
            <p>Thank you for your business!<br>
            This invoice was generated on ' . date('F j, Y \a\t g:i A') . ' by ' . $sale['created_by_name'] . '</p>
        </div>
    </div>
    </body>
    </html>';

    // --- DOMPDF PDF GENERATION ---
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Generate filename
    $filename = 'invoice_' . $sale['invoice_number'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
    $filepath = '../../uploads/invoices/' . $filename;

    // Save PDF to file
    file_put_contents($filepath, $dompdf->output());

    // Save due date metadata for expiry logic
    $meta = [
        'due_date' => $due_date
    ];
    $meta_path = '../../uploads/invoices/' . pathinfo($filename, PATHINFO_FILENAME) . '.json';
    file_put_contents($meta_path, json_encode($meta));

    // Update sale record with invoice file path
    $updateStmt = $pdo->prepare("
        UPDATE sales 
        SET invoice_file = :invoice_file 
        WHERE sale_id = :sale_id
    ");
    $updateStmt->execute([
        'invoice_file' => $filename,
        'sale_id' => $sale_id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Invoice generated successfully.',
        'data' => [
            'filename' => $filename,
            'filepath' => $filepath,
            'invoice_number' => $sale['invoice_number']
        ]
    ]);

} catch (Exception $e) {
    error_log("Generate Invoice Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Generate Invoice DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred.'
    ]);
}
?>
