<?php
require_once '../../inc/config/database.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;

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

    // EXPORT AS PDF
    if ($exportFormat === 'pdf') {
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Purchase Report</title>
            <style>
                @media print {
                    body { margin: 0; font-family: Arial, sans-serif; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #000; padding: 6px; text-align: left; }
                    th { background-color: #f0f0f0; font-weight: bold; }
                    .currency { text-align: right; }
                    .number { text-align: right; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h2 { margin: 0; }
                    .header p { margin: 5px 0; }
                    .total-row { font-weight: bold; background-color: #f8f9fa; }
                }
                body { margin: 20px; font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 6px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .currency { text-align: right; }
                .number { text-align: right; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h2 { margin: 0; }
                .header p { margin: 5px 0; }
                .total-row { font-weight: bold; background-color: #f8f9fa; }
            </style>
            <script>
                window.onload = function() { window.print(); }
            </script>
        </head>
        <body>
            <div class="header">
                <h2>Purchase Report</h2>
                <p>Generated on: <?php echo date('d M Y H:i:s'); ?></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Purchase #</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['purchase_number']) ?></td>
                            <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                            <td><?= htmlspecialchars($row['purchase_date']) ?></td>
                            <td><?= htmlspecialchars($row['material_name']) ?></td>
                            <td><?= number_format($row['quantity']) ?></td>
                            <td>PKR <?= number_format($row['unit_price'], 2) ?></td>
                            <td><strong>PKR <?= number_format($row['total_price'], 2) ?></strong></td>
                            <td><?= ucfirst($row['payment_status']) ?></td>
                            <td><?= ucfirst($row['delivery_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("Purchase_Report.pdf", ["Attachment" => true]);
        exit;
    }

    // EXPORT AS CSV
    if ($exportFormat === 'csv') {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=Purchase_Report.csv");

        $output = fopen("php://output", "w");
        fputcsv($output, array_keys($purchases[0]));

        foreach ($purchases as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    echo "Invalid export format.";
    exit;

} catch (PDOException $e) {
    error_log("Export Purchase Report Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo "Database error. Please try again later.";
    exit;
}
?>
<?php
// End of exportPurchases.php