<?php
date_default_timezone_set('Asia/Karachi');
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
        
        // Add logo as base64 (same as sales report)
        $company_name = 'Allied Steel Works';
        $company_address = 'Allied Steel Works (Pvt) Ltd., Service Road, Bhamma, Lahore, Pakistan';
        $company_email = 'info@alliedsteelworks.pk';
        $company_phone = '+92-300-1234567';
        $logo_path = __DIR__ . '/../../assets/images/logo.png';
        $logo_data = '';
        if (file_exists($logo_path)) {
            $logo_data = base64_encode(file_get_contents($logo_path));
        }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Purchase Report</title>
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
                    background: #388e3c;
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
                    border-left: 4px solid #388e3c;
                }
                .summary {
                    margin: 16px 0;
                    padding: 10px 14px;
                    background: #e3f2fd;
                    border-radius: 6px;
                    font-size: 12px;
                    border-left: 4px solid #388e3c;
                    color: #222;
                }
                .summary h3 { margin: 0 0 6px 0; font-size: 15px; color: #388e3c; }
                .summary p { margin: 4px 0; color: #222; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 18px; background: #fff; border-radius: 8px; overflow: hidden; }
                th, td { border: 1px solid #e0e0e0; padding: 7px 5px; text-align: left; font-size: 10px; }
                th { background: #388e3c; color: #fff; font-weight: 700; font-size: 11px; text-transform: uppercase; }
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
        <body>
            <div class="header">
                <?php if ($logo_data): ?>
                    <img src="data:image/png;base64,<?php echo $logo_data; ?>" class="logo" alt="Allied Steel Works Logo">
                <?php endif; ?>
                <div class="company-info">
                    <h1><?php echo htmlspecialchars($company_name); ?></h1>
                    <p><?php echo htmlspecialchars($company_address); ?></p>
                    <p><?php echo htmlspecialchars($company_email); ?> | <?php echo htmlspecialchars($company_phone); ?></p>
                </div>
                <div class="report-title">Purchase Report</div>
            </div>

            <div class="report-info">
                <span><strong>Report Generated:</strong> <?php echo date('d M Y, H:i:s'); ?></span><br>
                <strong>Date Range:</strong> 
                <?php 
                if ($date_from && $date_to) {
                    echo date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to));
                } else {
                    echo 'All Dates';
                }
                ?>
                <br><strong>Total Records:</strong> <?php echo number_format($totalRecords); ?><br>
                <strong>Filters Applied:</strong> 
                <?php 
                $filters = [];
                if ($vendor_id) $filters[] = 'Vendor';
                if ($payment_status) $filters[] = 'Payment Status';
                if ($min_amount || $max_amount) $filters[] = 'Amount Range';
                echo $filters ? implode(', ', $filters) : 'None';
                ?>
            </div>

            <div class="summary">
                <h3>Summary Statistics</h3>
                <p><strong>Total Amount:</strong> PKR <?php echo number_format($totalAmount, 2); ?></p>
                <p><strong>Total Records:</strong> <?php echo number_format($totalRecords); ?></p>
                <p><strong>Average Amount:</strong> PKR <?php echo number_format($avgAmount, 2); ?></p>
                <p><strong>Highest Purchase:</strong> PKR <?php echo number_format(max(array_column($purchases, 'total_price')), 2); ?></p>
                <h3>Payment Status Breakdown</h3>
                <?php foreach ($paymentSummary as $status => $data): ?>
                    <p><strong><?php echo ucfirst($status); ?>:</strong> <?php echo $data['count']; ?> orders, PKR <?php echo number_format($data['amount'], 2); ?></p>
                <?php endforeach; ?>
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