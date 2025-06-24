<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../vendor/autoload.php';
require_once '../../inc/helpers.php';
date_default_timezone_set('Asia/Karachi');

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

try {
    $sale_id = intval($_POST['sale_id'] ?? 0);
    $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));

    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }

    // Fetch sale details with customer and item information
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            c.address as customer_address,
            c.city as customer_city,
            c.state as customer_state,
            c.zip_code as customer_zip,
            i.item_name,
            i.item_number,
            sd.quantity,
            sd.unit_price,
            sd.total_price,
            u.full_name as created_by_name,
            COALESCE(s.paid_amount, 0) as paid_amount,
            (s.total_amount - COALESCE(s.paid_amount, 0)) as remaining_amount
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN sale_details sd ON s.sale_id = sd.sale_id
        JOIN inventory i ON sd.item_id = i.item_id
        JOIN users u ON s.created_by = u.user_id
        WHERE s.sale_id = :sale_id
    ");
    
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found.');
    }

    // Fetch payment history
    $paymentsStmt = $pdo->prepare("
        SELECT 
            payment_date,
            amount,
            method,
            notes
        FROM payments 
        WHERE sale_id = :sale_id 
        ORDER BY payment_date ASC, created_at ASC
    ");
    $paymentsStmt->execute(['sale_id' => $sale_id]);
    $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Configure DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    
    // Generate HTML for invoice
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice #<?php echo $sale['invoice_number']; ?></title>
        <style>
            @page {
                margin: 10px;
                size: A4;
            }
            
            body {
                font-family: 'DejaVu Sans', Arial, sans-serif;
                font-size: 10px;
                line-height: 1.3;
                color: #333;
                margin: 0;
                padding: 0;
            }
            
            .header {
                text-align: center;
                margin-bottom: 15px;
                border-bottom: 2px solid #28a745;
                padding-bottom: 10px;
            }
            
            .header h1 {
                font-size: 20px;
                margin: 0 0 5px 0;
            }
            
            .company-info {
                margin-bottom: 10px;
                font-size: 9px;
            }
            
            .invoice-details {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            
            .customer-details, .invoice-meta {
                width: 45%;
                font-size: 9px;
            }
            
            .invoice-meta {
                text-align: right;
            }
            
            .items-table, .payments-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                font-size: 9px;
            }
            
            .items-table th, .payments-table th {
                background: #f8f9fa;
                padding: 5px;
                text-align: left;
                border-bottom: 1px solid #dee2e6;
            }
            
            .items-table td, .payments-table td {
                padding: 5px;
                border-bottom: 1px solid #dee2e6;
            }
            
            .total-section {
                text-align: right;
                margin-top: 10px;
                background: #f8f9fa;
                padding: 8px;
                border-radius: 3px;
                font-size: 9px;
            }
            
            .total-row {
                margin: 3px 0;
                display: flex;
                justify-content: flex-end;
                align-items: center;
            }
            
            .total-row .label {
                width: 120px;
                text-align: right;
                padding-right: 10px;
            }
            
            .total-row .value {
                width: 120px;
                text-align: right;
                font-weight: bold;
            }
            
            .grand-total {
                font-size: 12px;
                font-weight: bold;
                color: #28a745;
                margin-top: 5px;
                padding-top: 5px;
                border-top: 1px solid #dee2e6;
            }
            
            .footer {
                margin-top: 15px;
                text-align: center;
                font-size: 8px;
                color: #666;
                border-top: 1px solid #dee2e6;
                padding-top: 5px;
            }
            
            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 8px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .status-paid { background: #d4edda; color: #155724; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-partial { background: #cce5ff; color: #004085; }

            .payment-history {
                margin-top: 15px;
                padding: 8px;
                background: #f8f9fa;
                border-radius: 3px;
                font-size: 9px;
            }

            .payment-history h4 {
                color: #28a745;
                margin-bottom: 8px;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 5px;
                font-size: 11px;
            }

            .notes-section {
                margin-top: 15px;
                font-size: 9px;
            }

            .notes-section h4 {
                font-size: 11px;
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>INVOICE</h1>
            <div class="company-info">
                <h2>Allied Steel Works</h2>
                <p>Allied Steel Works (Pvt) Ltd., Service Road, Bhamma Lahore Lahore,</p>
                <p>Phone: +92 300 8889918 | Email: info@alliedsteel.com</p>
            </div>
        </div>

        <div class="invoice-details">
            <div class="customer-details">
                <h3>Bill To:</h3>
                <p><strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($sale['customer_address']); ?></p>
                <p>
                    <?php 
                    echo htmlspecialchars($sale['customer_city']);
                    if ($sale['customer_state']) echo ', ' . htmlspecialchars($sale['customer_state']);
                    if ($sale['customer_zip']) echo ' ' . htmlspecialchars($sale['customer_zip']);
                    ?>
                </p>
                <p>Phone: <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                <?php if ($sale['customer_email']): ?>
                    <p>Email: <?php echo htmlspecialchars($sale['customer_email']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="invoice-meta">
                <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($sale['invoice_number']); ?></p>
                <p><strong>Invoice Date:</strong> <?php echo date('d M Y', strtotime($invoice_date)); ?></p>
                <p><strong>Due Date:</strong> <?php echo date('d M Y', strtotime($due_date)); ?></p>
                <p>
                    <strong>Status:</strong>
                    <span class="status-badge status-<?php echo strtolower($sale['payment_status']); ?>">
                        <?php echo ucfirst($sale['payment_status']); ?>
                    </span>
                </p>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Code</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($sale['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($sale['item_number']); ?></td>
                    <td><?php echo number_format($sale['quantity'], 2); ?></td>
                    <td>PKR <?php echo number_format($sale['unit_price'], 2); ?></td>
                    <td>PKR <?php echo number_format($sale['total_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span class="label">Total Amount:</span>
                <span class="value">PKR <?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            <div class="total-row">
                <span class="label">Paid Amount:</span>
                <span class="value">PKR <?php echo number_format($sale['paid_amount'], 2); ?></span>
            </div>
            <div class="total-row grand-total">
                <span class="label">Remaining Amount:</span>
                <span class="value">PKR <?php echo number_format($sale['remaining_amount'], 2); ?></span>
            </div>
        </div>

        <?php if (!empty($payments)): ?>
        <div class="payment-history">
            <h4>Payment History</h4>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                        <td>PKR <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['method']); ?></td>
                        <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($sale['notes']): ?>
            <div class="notes-section" style="margin-top: 30px;">
                <h4>Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>This is a computer-generated invoice. No signature is required.</p>
            <p>Generated on <?php echo date('d M Y H:i:s'); ?> by <?php echo htmlspecialchars($sale['created_by_name']); ?></p>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // Generate PDF
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();

    // Create directory if it doesn't exist
    $invoiceDir = '../../uploads/invoices';
    if (!file_exists($invoiceDir)) {
        mkdir($invoiceDir, 0777, true);
    }

    // Generate filename
    $filename = $invoiceDir . '/INV-' . $sale['invoice_number'] . '.pdf';
    
    // Save PDF
    file_put_contents($filename, $dompdf->output());

    // Update sale record with invoice file path
    $updateStmt = $pdo->prepare("
        UPDATE sales 
        SET invoice_file = :invoice_file 
        WHERE sale_id = :sale_id
    ");
    $updateStmt->execute([
        'invoice_file' => 'uploads/invoices/INV-' . $sale['invoice_number'] . '.pdf',
        'sale_id' => $sale_id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Invoice generated successfully.',
        'data' => [
            'invoice_file' => 'uploads/invoices/INV-' . $sale['invoice_number'] . '.pdf'
        ]
    ]);

} catch (Exception $e) {
    error_log("Generate Invoice Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
