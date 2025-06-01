<?php
require_once '../../inc/config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$exportFormat = $_POST['export_format'] ?? 'csv';

// Get filters from POST
$customer_id = $_POST['customer_id'] ?? '';
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$min_amount = $_POST['min_amount'] ?? '';
$max_amount = $_POST['max_amount'] ?? '';
$payment_status = $_POST['payment_status'] ?? '';

// Fetch filtered data from DB
try {
    $query = "
        SELECT 
            s.invoice_number,
            c.customer_name,
            s.sale_date,
            i.item_name,
            sd.quantity,
            sd.unit_price,
            sd.total_price,
            s.payment_status,
            u.full_name as created_by_name
        FROM sales s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN sale_details sd ON s.sale_id = sd.sale_id
        JOIN inventory i ON sd.item_id = i.item_id
        JOIN users u ON s.created_by = u.user_id
        WHERE 1=1
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
        $query .= " AND sd.total_price >= :min_amount";
        $params[':min_amount'] = $min_amount;
    }

    if (!empty($max_amount)) {
        $query .= " AND sd.total_price <= :max_amount";
        $params[':max_amount'] = $max_amount;
    }

    if (!empty($payment_status)) {
        $query .= " AND s.payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    $query .= " ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$sales) {
        echo "No data found for export.";
        exit;
    }

    // Calculate totals and statistics
    $totalAmount = array_sum(array_column($sales, 'total_price'));
    $totalRecords = count($sales);
    $avgAmount = $totalAmount / $totalRecords;
    
    // Group by payment status for summary
    $paymentSummary = [];
    foreach ($sales as $sale) {
        $status = $sale['payment_status'];
        if (!isset($paymentSummary[$status])) {
            $paymentSummary[$status] = ['count' => 0, 'amount' => 0];
        }
        $paymentSummary[$status]['count']++;
        $paymentSummary[$status]['amount'] += $sale['total_price'];
    }

    // EXPORT AS PDF
    if ($exportFormat === 'pdf') {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Sales Report</title>
            <style>
                @page {
                    margin: 20px;
                    size: A4 landscape;
                }
                
                body {
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 25px;
                    border-bottom: 3px solid #28a745;
                    padding-bottom: 15px;
                }
                
                .header h1 {
                    color: #28a745;
                    font-size: 24px;
                    margin: 0 0 10px 0;
                    font-weight: bold;
                }
                
                .header .company-info {
                    color: #666;
                    font-size: 12px;
                    margin: 5px 0;
                }
                
                .report-meta {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    background: #f8f9fa;
                    padding: 12px;
                    border-radius: 5px;
                    border-left: 4px solid #28a745;
                }
                
                .report-meta .left, .report-meta .right {
                    width: 48%;
                }
                
                .report-meta strong {
                    color: #28a745;
                }
                
                .summary-section {
                    margin-bottom: 20px;
                    background: #f1f8f3;
                    padding: 15px;
                    border-radius: 8px;
                }
                
                .summary-title {
                    color: #28a745;
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }
                
                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 15px;
                    margin-bottom: 15px;
                }
                
                .summary-item {
                    text-align: center;
                    background: white;
                    padding: 10px;
                    border-radius: 5px;
                    border: 1px solid #e0e0e0;
                }
                
                .summary-item .label {
                    font-size: 9px;
                    color: #666;
                    margin-bottom: 5px;
                }
                
                .summary-item .value {
                    font-size: 12px;
                    font-weight: bold;
                    color: #28a745;
                }
                
                .payment-status-summary {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 10px;
                }
                
                .status-item {
                    background: white;
                    padding: 8px;
                    border-radius: 4px;
                    border-left: 3px solid #28a745;
                    font-size: 9px;
                }
                
                .status-item.pending { border-left-color: #ffc107; }
                .status-item.paid { border-left-color: #28a745; }
                .status-item.overdue { border-left-color: #dc3545; }
                
                .main-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    border-radius: 8px;
                    overflow: hidden;
                }
                
                .main-table th {
                    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
                    color: white;
                    padding: 12px 8px;
                    font-weight: bold;
                    font-size: 9px;
                    text-align: center;
                    border: none;
                }
                
                .main-table td {
                    padding: 8px 6px;
                    border-bottom: 1px solid #e0e0e0;
                    font-size: 9px;
                    vertical-align: middle;
                }
                
                .main-table tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                
                .main-table tbody tr:hover {
                    background-color: #e3f2fd;
                }
                
                .currency {
                    text-align: right;
                    font-weight: 600;
                    color: #28a745;
                }
                
                .number {
                    text-align: center;
                    font-weight: 500;
                }
                
                .status-badge {
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 8px;
                    font-weight: bold;
                    text-transform: uppercase;
                    text-align: center;
                }
                
                .status-paid {
                    background-color: #d4edda;
                    color: #155724;
                }
                
                .status-pending {
                    background-color: #fff3cd;
                    color: #856404;
                }
                
                .status-overdue {
                    background-color: #f8d7da;
                    color: #721c24;
                }
                
                .total-row {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    font-weight: bold;
                    border-top: 2px solid #28a745;
                }
                
                .total-row td {
                    padding: 12px 8px;
                    font-size: 10px;
                }
                
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    color: #666;
                    font-size: 8px;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                
                .page-break {
                    page-break-before: always;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Sales Report</h1>
                <div class="company-info">
                    <strong>Allied Steel Works</strong><br>
                    Complete Sales Analysis & Summary
                </div>
            </div>

            <div class="report-meta">
                <div class="left">
                    <strong>Report Generated:</strong> <?php echo date('d M Y, H:i:s'); ?><br>
                    <strong>Date Range:</strong> 
                    <?php 
                    if ($date_from && $date_to) {
                        echo date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to));
                    } else {
                        echo 'All Dates';
                    }
                    ?>
                </div>
                <div class="right">
                    <strong>Total Records:</strong> <?php echo number_format($totalRecords); ?><br>
                    <strong>Filters Applied:</strong> 
                    <?php 
                    $filters = [];
                    if ($customer_id) $filters[] = 'Customer';
                    if ($payment_status) $filters[] = 'Payment Status';
                    if ($min_amount || $max_amount) $filters[] = 'Amount Range';
                    echo $filters ? implode(', ', $filters) : 'None';
                    ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-title">📊 Summary Statistics</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Total Revenue</div>
                        <div class="value">PKR <?php echo number_format($totalAmount, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Total Sales</div>
                        <div class="value"><?php echo number_format($totalRecords); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Average Sale</div>
                        <div class="value">PKR <?php echo number_format($avgAmount, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Highest Sale</div>
                        <div class="value">PKR <?php echo number_format(max(array_column($sales, 'total_price')), 2); ?></div>
                    </div>
                </div>
                
                <div class="summary-title">💳 Payment Status Breakdown</div>
                <div class="payment-status-summary">
                    <?php foreach ($paymentSummary as $status => $data): ?>
                        <div class="status-item <?php echo strtolower($status); ?>">
                            <strong><?php echo ucfirst($status); ?>:</strong><br>
                            <?php echo $data['count']; ?> sales<br>
                            PKR <?php echo number_format($data['amount'], 2); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <table class="main-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Invoice #</th>
                        <th style="width: 15%;">Customer</th>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 18%;">Item</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 12%;">Unit Price</th>
                        <th style="width: 12%;">Total</th>
                        <th style="width: 8%;">Payment</th>
                        <th style="width: 12%;">Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['invoice_number']) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= date('d-M-y', strtotime($row['sale_date'])) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td class="number"><?= number_format($row['quantity']) ?></td>
                            <td class="currency">PKR <?= number_format($row['unit_price'], 2) ?></td>
                            <td class="currency"><strong>PKR <?= number_format($row['total_price'], 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['payment_status']) ?>">
                                    <?= ucfirst($row['payment_status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['created_by_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
                        <td class="currency" style="font-size: 12px; color: #28a745;">
                            <strong>PKR <?= number_format($totalAmount, 2) ?></strong>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="footer">
                <p>This is a computer-generated report. Generated on <?php echo date('d M Y \a\t H:i:s'); ?> | Total Records: <?php echo $totalRecords; ?></p>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Generate filename with timestamp
        $filename = "Sales_Report_" . date('Y-m-d_H-i-s') . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    // EXPORT AS CSV (Enhanced)
    if ($exportFormat === 'csv') {
        $filename = "Sales_Report_" . date('Y-m-d_H-i-s') . ".csv";
        
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");

        $output = fopen("php://output", "w");
        
        // Add BOM for proper UTF-8 encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add report header information
        fputcsv($output, ["Sales Report"]);
        fputcsv($output, ["Generated on: " . date('d M Y H:i:s')]);
        fputcsv($output, ["Total records: " . $totalRecords]);
        fputcsv($output, ["Total revenue: PKR " . number_format($totalAmount, 2)]);
        fputcsv($output, []); // Empty line
        
        // Add column headers
        $headers = [
            'Invoice Number',
            'Customer Name', 
            'Sale Date',
            'Item Name',
            'Quantity',
            'Unit Price (PKR)',
            'Total Price (PKR)',
            'Payment Status',
            'Created By'
        ];
        fputcsv($output, $headers);

        // Add data rows
        foreach ($sales as $row) {
            $csvRow = [
                $row['invoice_number'],
                $row['customer_name'],
                $row['sale_date'],
                $row['item_name'],
                $row['quantity'],
                number_format($row['unit_price'], 2),
                number_format($row['total_price'], 2),
                ucfirst($row['payment_status']),
                $row['created_by_name']
            ];
            fputcsv($output, $csvRow);
        }
        
        // Add summary at the end
        fputcsv($output, []); // Empty line
        fputcsv($output, ["SUMMARY"]);
        fputcsv($output, ["Total Records", $totalRecords]);
        fputcsv($output, ["Grand Total", "PKR " . number_format($totalAmount, 2)]);
        fputcsv($output, ["Average Amount", "PKR " . number_format($avgAmount, 2)]);

        fclose($output);
        exit;
    }

    // EXPORT AS EXCEL (New feature)
    if ($exportFormat === 'excel') {
        require_once '../../vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Sales Management System")
            ->setTitle("Sales Report")
            ->setDescription("Detailed sales report with summary");
        
        // Add headers and data
        $headers = ['Invoice #', 'Customer', 'Date', 'Item', 'Qty', 'Unit Price', 'Total', 'Payment', 'Created By'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '28a745']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Add data
        $row = 2;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $sale['invoice_number']);
            $sheet->setCellValue('B' . $row, $sale['customer_name']);
            $sheet->setCellValue('C' . $row, $sale['sale_date']);
            $sheet->setCellValue('D' . $row, $sale['item_name']);
            $sheet->setCellValue('E' . $row, $sale['quantity']);
            $sheet->setCellValue('F' . $row, $sale['unit_price']);
            $sheet->setCellValue('G' . $row, $sale['total_price']);
            $sheet->setCellValue('H' . $row, ucfirst($sale['payment_status']));
            $sheet->setCellValue('I' . $row, $sale['created_by_name']);
            $row++;
        }
        
        // Auto-fit columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = "Sales_Report_" . date('Y-m-d_H-i-s') . ".xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    echo "Invalid export format. Supported formats: csv, pdf, excel";
    exit;

} catch (PDOException $e) {
    error_log("Export Sales Report Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo "Database error. Please try again later.";
    exit;
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo "An error occurred while generating the report. Please try again.";
    exit;
}
?>