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
$vendor_id = $_POST['vendor_id'] ?? '';
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$min_amount = $_POST['min_amount'] ?? '';
$max_amount = $_POST['max_amount'] ?? '';
$payment_status = $_POST['payment_status'] ?? '';

// Fetch filtered data from DB
try {
    $query = "
        SELECT 
            p.purchase_number,
            v.vendor_name,
            p.purchase_date,
            rm.material_name,
            pd.quantity,
            pd.unit_price,
            pd.total_price,
            p.payment_status,
            p.delivery_status
        FROM purchases p
        JOIN vendors v ON p.vendor_id = v.vendor_id
        JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
        JOIN raw_materials rm ON pd.material_id = rm.material_id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($vendor_id)) {
        $query .= " AND p.vendor_id = :vendor_id";
        $params[':vendor_id'] = $vendor_id;
    }

    if (!empty($date_from)) {
        $query .= " AND p.purchase_date >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $query .= " AND p.purchase_date <= :date_to";
        $params[':date_to'] = $date_to;
    }

    if (!empty($min_amount)) {
        $query .= " AND pd.total_price >= :min_amount";
        $params[':min_amount'] = $min_amount;
    }

    if (!empty($max_amount)) {
        $query .= " AND pd.total_price <= :max_amount";
        $params[':max_amount'] = $max_amount;
    }

    if (!empty($payment_status)) {
        $query .= " AND p.payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    $query .= " ORDER BY p.purchase_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$purchases) {
        echo "No data found for export.";
        exit;
    }

    // Calculate totals and statistics
    $totalAmount = array_sum(array_column($purchases, 'total_price'));
    $totalRecords = count($purchases);
    $avgAmount = $totalAmount / $totalRecords;
    
    // Group by payment status for summary
    $paymentSummary = [];
    foreach ($purchases as $purchase) {
        $status = $purchase['payment_status'];
        if (!isset($paymentSummary[$status])) {
            $paymentSummary[$status] = ['count' => 0, 'amount' => 0];
        }
        $paymentSummary[$status]['count']++;
        $paymentSummary[$status]['amount'] += $purchase['total_price'];
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
            <title>Purchase Report</title>
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
                    border-bottom: 3px solid #2c5aa0;
                    padding-bottom: 15px;
                }
                
                .header h1 {
                    color: #2c5aa0;
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
                    border-left: 4px solid #2c5aa0;
                }
                
                .report-meta .left, .report-meta .right {
                    width: 48%;
                }
                
                .report-meta strong {
                    color: #2c5aa0;
                }
                
                .summary-section {
                    margin-bottom: 20px;
                    background: #f1f3f4;
                    padding: 15px;
                    border-radius: 8px;
                }
                
                .summary-title {
                    color: #2c5aa0;
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
                    color: #2c5aa0;
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
                    background: linear-gradient(135deg, #2c5aa0 0%, #1e3d72 100%);
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
                    color: #2c5aa0;
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
                
                .status-delivered {
                    background-color: #d1ecf1;
                    color: #0c5460;
                }
                
                .total-row {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    font-weight: bold;
                    border-top: 2px solid #2c5aa0;
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
                <h1>Purchase Report</h1>
                <div class="company-info">
                    <strong>Allied Steel Works</strong><br>
                    Complete Purchase Analysis & Summary
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
                    if ($vendor_id) $filters[] = 'Vendor';
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
                        <div class="label">Total Amount</div>
                        <div class="value">PKR <?php echo number_format($totalAmount, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Total Records</div>
                        <div class="value"><?php echo number_format($totalRecords); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Average Amount</div>
                        <div class="value">PKR <?php echo number_format($avgAmount, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Highest Purchase</div>
                        <div class="value">PKR <?php echo number_format(max(array_column($purchases, 'total_price')), 2); ?></div>
                    </div>
                </div>
                
                <div class="summary-title">💳 Payment Status Breakdown</div>
                <div class="payment-status-summary">
                    <?php foreach ($paymentSummary as $status => $data): ?>
                        <div class="status-item <?php echo strtolower($status); ?>">
                            <strong><?php echo ucfirst($status); ?>:</strong><br>
                            <?php echo $data['count']; ?> orders<br>
                            PKR <?php echo number_format($data['amount'], 2); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <table class="main-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Purchase #</th>
                        <th style="width: 15%;">Vendor</th>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 20%;">Material</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 12%;">Unit Price</th>
                        <th style="width: 12%;">Total</th>
                        <th style="width: 8%;">Payment</th>
                        <th style="width: 8%;">Delivery</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['purchase_number']) ?></td>
                            <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                            <td><?= date('d-M-y', strtotime($row['purchase_date'])) ?></td>
                            <td><?= htmlspecialchars($row['material_name']) ?></td>
                            <td class="number"><?= number_format($row['quantity']) ?></td>
                            <td class="currency">PKR <?= number_format($row['unit_price'], 2) ?></td>
                            <td class="currency"><strong>PKR <?= number_format($row['total_price'], 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['payment_status']) ?>">
                                    <?= ucfirst($row['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['delivery_status']) ?>">
                                    <?= ucfirst($row['delivery_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
                        <td class="currency" style="font-size: 12px; color: #2c5aa0;">
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
        $filename = "Purchase_Report_" . date('Y-m-d_H-i-s') . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    // EXPORT AS CSV (Enhanced)
    if ($exportFormat === 'csv') {
        $filename = "Purchase_Report_" . date('Y-m-d_H-i-s') . ".csv";
        
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");

        $output = fopen("php://output", "w");
        
        // Add BOM for proper UTF-8 encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add report header information
        fputcsv($output, ["Purchase Report"]);
        fputcsv($output, ["Generated on: " . date('d M Y H:i:s')]);
        fputcsv($output, ["Total records: " . $totalRecords]);
        fputcsv($output, ["Total amount: PKR " . number_format($totalAmount, 2)]);
        fputcsv($output, []); // Empty line
        
        // Add column headers
        $headers = [
            'Purchase Number',
            'Vendor Name', 
            'Purchase Date',
            'Material Name',
            'Quantity',
            'Unit Price (PKR)',
            'Total Price (PKR)',
            'Payment Status',
            'Delivery Status'
        ];
        fputcsv($output, $headers);

        // Add data rows
        foreach ($purchases as $row) {
            $csvRow = [
                $row['purchase_number'],
                $row['vendor_name'],
                $row['purchase_date'],
                $row['material_name'],
                $row['quantity'],
                number_format($row['unit_price'], 2),
                number_format($row['total_price'], 2),
                ucfirst($row['payment_status']),
                ucfirst($row['delivery_status'])
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
            ->setCreator("Purchase Management System")
            ->setTitle("Purchase Report")
            ->setDescription("Detailed purchase report with summary");
        
        // Add headers and data
        $headers = ['Purchase #', 'Vendor', 'Date', 'Material', 'Qty', 'Unit Price', 'Total', 'Payment', 'Delivery'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2c5aa0']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Add data
        $row = 2;
        foreach ($purchases as $purchase) {
            $sheet->setCellValue('A' . $row, $purchase['purchase_number']);
            $sheet->setCellValue('B' . $row, $purchase['vendor_name']);
            $sheet->setCellValue('C' . $row, $purchase['purchase_date']);
            $sheet->setCellValue('D' . $row, $purchase['material_name']);
            $sheet->setCellValue('E' . $row, $purchase['quantity']);
            $sheet->setCellValue('F' . $row, $purchase['unit_price']);
            $sheet->setCellValue('G' . $row, $purchase['total_price']);
            $sheet->setCellValue('H' . $row, ucfirst($purchase['payment_status']));
            $sheet->setCellValue('I' . $row, ucfirst($purchase['delivery_status']));
            $row++;
        }
        
        // Auto-fit columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = "Purchase_Report_" . date('Y-m-d_H-i-s') . ".xlsx";
        
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
    error_log("Export Purchase Report Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo "Database error. Please try again later.";
    exit;
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo "An error occurred while generating the report. Please try again.";
    exit;
}
?>