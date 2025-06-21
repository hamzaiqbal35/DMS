<?php
require_once '../inc/config/database.php';
require_once '../vendor/autoload.php';
require_once 'saveExportHistory.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$exportFormat = $_POST['export_format'] ?? 'pdf';
$reportType = $_POST['report_type'] ?? $_POST['export_type'] ?? 'all';

// Only allow 'all' if explicitly selected
if (!in_array($reportType, ['sales','purchases','inventory','customers','vendors','all'])) {
    $reportType = 'all';
}

// Get common filters
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$min_amount = $_POST['min_amount'] ?? '';
$max_amount = $_POST['max_amount'] ?? '';

// Get specific filters
$customer_id = $_POST['customer_id'] ?? '';
$vendor_id = $_POST['vendor_id'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$payment_status = $_POST['payment_status'] ?? '';
$delivery_status = $_POST['delivery_status'] ?? '';

try {
    $response = ['status' => 'success', 'data' => []];
    $params = [];

    // Build base query based on report type
    switch ($reportType) {
        case 'sales':
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
                    u.full_name as created_by_name,
                    'sale' as record_type
                FROM sales s
                JOIN customers c ON s.customer_id = c.customer_id
                JOIN sale_details sd ON s.sale_id = sd.sale_id
                JOIN inventory i ON sd.item_id = i.item_id
                JOIN users u ON s.created_by = u.user_id
                WHERE 1=1
            ";
            break;

        case 'purchases':
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
                    p.delivery_status,
                    'purchase' as record_type
                FROM purchases p
                JOIN vendors v ON p.vendor_id = v.vendor_id
                JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
                JOIN raw_materials rm ON pd.material_id = rm.material_id
                WHERE 1=1
            ";
            break;

        case 'inventory':
            $query = "
                SELECT 
                    i.item_number,
                    i.item_name,
                    c.category_name,
                    i.current_stock,
                    i.minimum_stock,
                    i.unit_price,
                    (i.current_stock * i.unit_price) as total_value,
                    CASE 
                        WHEN i.current_stock = 0 THEN 'out_of_stock'
                        WHEN i.current_stock <= i.minimum_stock THEN 'low_stock'
                        ELSE 'sufficient'
                    END as stock_status,
                    'inventory' as record_type
                FROM inventory i
                JOIN categories c ON i.category_id = c.category_id
                WHERE 1=1
            ";
            break;

        case 'customers':
            $query = "
                SELECT 
                    c.customer_id,
                    c.customer_name,
                    c.phone,
                    c.email,
                    c.address,
                    c.city,
                    c.state,
                    c.zip_code,
                    c.status,
                    c.created_at,
                    COUNT(s.sale_id) as total_orders,
                    COALESCE(SUM(s.total_amount), 0) as total_spent
                FROM customers c
                LEFT JOIN sales s ON c.customer_id = s.customer_id
                WHERE 1=1
            ";
            break;

        case 'vendors':
            $query = "
                SELECT 
                    v.vendor_id,
                    v.vendor_name,
                    v.contact_person,
                    v.phone,
                    v.email,
                    v.address,
                    v.city,
                    v.state,
                    v.zip_code,
                    v.status,
                    v.created_at,
                    COUNT(DISTINCT p.purchase_id) as total_purchases,
                    COALESCE(SUM(p.total_amount), 0) as total_spent
                FROM vendors v
                LEFT JOIN purchases p ON v.vendor_id = p.vendor_id
                WHERE 1=1
            ";
            break;

        case 'all':
            $query = "
                (SELECT 
                    s.invoice_number as reference_number,
                    c.customer_name as entity_name,
                    s.sale_date as transaction_date,
                    i.item_name as item_name,
                    sd.quantity,
                    sd.unit_price,
                    sd.total_price,
                    s.payment_status,
                    'sale' as record_type
                FROM sales s
                JOIN customers c ON s.customer_id = c.customer_id
                JOIN sale_details sd ON s.sale_id = sd.sale_id
                JOIN inventory i ON sd.item_id = i.item_id)
                UNION ALL
                (SELECT 
                    p.purchase_number as reference_number,
                    v.vendor_name as entity_name,
                    p.purchase_date as transaction_date,
                    rm.material_name as item_name,
                    pd.quantity,
                    pd.unit_price,
                    pd.total_price,
                    p.payment_status,
                    'purchase' as record_type
                FROM purchases p
                JOIN vendors v ON p.vendor_id = v.vendor_id
                JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
                JOIN raw_materials rm ON pd.material_id = rm.material_id)
            ";
            break;
    }

    // Apply date filters using the correct column for each report type
    $dateColumn = '';
    switch ($reportType) {
        case 'sales':
            $dateColumn = 's.sale_date';
            break;
        case 'purchases':
            $dateColumn = 'p.purchase_date';
            break;
        case 'inventory':
            // Optionally use 'i.updated_at' or 'i.created_at' if you want date filtering
            // $dateColumn = 'i.updated_at';
            break;
        case 'all':
            $dateColumn = 'transaction_date';
            break;
    }

    if (!empty($date_from) && $dateColumn) {
        $query .= " AND $dateColumn >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if (!empty($date_to) && $dateColumn) {
        $query .= " AND $dateColumn <= :date_to";
        $params[':date_to'] = $date_to;
    }

    if (!empty($min_amount)) {
        $query .= " AND total_price >= :min_amount";
        $params[':min_amount'] = $min_amount;
    }

    if (!empty($max_amount)) {
        $query .= " AND total_price <= :max_amount";
        $params[':max_amount'] = $max_amount;
    }

    // Apply specific filters
    if (!empty($customer_id) && $reportType === 'sales') {
        $query .= " AND s.customer_id = :customer_id";
        $params[':customer_id'] = $customer_id;
    }

    if (!empty($vendor_id) && $reportType === 'purchases') {
        $query .= " AND p.vendor_id = :vendor_id";
        $params[':vendor_id'] = $vendor_id;
    }

    if (!empty($category_id) && $reportType === 'inventory') {
        $query .= " AND i.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    if (!empty($payment_status)) {
        $query .= " AND payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }

    if (!empty($delivery_status) && $reportType === 'purchases') {
        $query .= " AND delivery_status = :delivery_status";
        $params[':delivery_status'] = $delivery_status;
    }

    // Use the correct order column for each report type
    $orderColumn = '';
    switch ($reportType) {
        case 'sales':
            $orderColumn = 's.sale_date';
            break;
        case 'purchases':
            $orderColumn = 'p.purchase_date';
            break;
        case 'inventory':
            $orderColumn = 'i.item_name';
            break;
        case 'customers':
            $orderColumn = 'c.customer_name';
            break;
        case 'vendors':
            $orderColumn = 'v.vendor_name';
            break;
        case 'all':
            $orderColumn = 'transaction_date';
            break;
    }

    // For customers and vendors, add GROUP BY before ORDER BY
    if ($reportType === 'customers') {
        $query .= " GROUP BY c.customer_id, c.customer_name, c.phone, c.email, c.address, c.city, c.state, c.zip_code, c.status, c.created_at";
    }
    if ($reportType === 'vendors') {
        $query .= " GROUP BY v.vendor_id, v.vendor_name, v.contact_person, v.phone, v.email, v.address, v.city, v.state, v.zip_code, v.status, v.created_at";
    }

    if (!empty($orderColumn)) {
        if ($reportType === 'all') {
            $query = "SELECT * FROM ($query) as combined_data ORDER BY $orderColumn DESC";
        } else {
            $query .= " ORDER BY $orderColumn DESC";
        }
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$records) {
        echo "No data found for export.";
        exit;
    }

    // Calculate summary statistics
    $summary = [
        'total_records' => count($records),
        'total_amount' => array_sum(array_column($records, 'total_price')),
        'average_amount' => count($records) > 0 ? array_sum(array_column($records, 'total_price')) / count($records) : 0
    ];

    // Group by record type for combined reports
    if ($reportType === 'all') {
        $typeSummary = [];
        foreach ($records as $record) {
            $type = $record['record_type'];
            if (!isset($typeSummary[$type])) {
                $typeSummary[$type] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $typeSummary[$type]['count']++;
            $typeSummary[$type]['amount'] += $record['total_price'];
        }
        $summary['type_breakdown'] = $typeSummary;
    }

    // Set table headers and keys for PDF export
    $tableHeaders = [];
    $tableKeys = [];
    switch ($reportType) {
        case 'sales':
            $tableHeaders = ['Invoice #', 'Customer', 'Date', 'Item', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Created By', 'Type'];
            $tableKeys = ['invoice_number', 'customer_name', 'sale_date', 'item_name', 'quantity', 'unit_price', 'total_price', 'payment_status', 'created_by_name', 'record_type'];
            break;
        case 'purchases':
            $tableHeaders = ['Purchase #', 'Vendor', 'Date', 'Material', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Delivery Status', 'Type'];
            $tableKeys = ['purchase_number', 'vendor_name', 'purchase_date', 'material_name', 'quantity', 'unit_price', 'total_price', 'payment_status', 'delivery_status', 'record_type'];
            break;
        case 'inventory':
            $tableHeaders = ['Item #', 'Item Name', 'Category', 'Current Stock', 'Min Stock', 'Unit Price', 'Total Value', 'Stock Status', 'Type'];
            $tableKeys = ['item_number', 'item_name', 'category_name', 'current_stock', 'minimum_stock', 'unit_price', 'total_value', 'stock_status', 'record_type'];
            break;
        case 'customers':
            $tableHeaders = ['Customer ID', 'Customer Name', 'Phone', 'Email', 'Address', 'City', 'State', 'Zip Code', 'Status', 'Created At', 'Total Orders', 'Total Spent'];
            $tableKeys = ['customer_id', 'customer_name', 'phone', 'email', 'address', 'city', 'state', 'zip_code', 'status', 'created_at', 'total_orders', 'total_spent'];
            break;
        case 'vendors':
            $tableHeaders = ['Vendor ID', 'Vendor Name', 'Contact Person', 'Phone', 'Email', 'Address', 'City', 'State', 'Zip Code', 'Status', 'Created At', 'Total Purchases', 'Total Spent'];
            $tableKeys = ['vendor_id', 'vendor_name', 'contact_person', 'phone', 'email', 'address', 'city', 'state', 'zip_code', 'status', 'created_at', 'total_purchases', 'total_spent'];
            break;
        case 'all':
            $tableHeaders = ['Reference #', 'Entity', 'Date', 'Item', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Type'];
            $tableKeys = ['reference_number', 'entity_name', 'transaction_date', 'item_name', 'quantity', 'unit_price', 'total_price', 'payment_status', 'record_type'];
            break;
        default:
            if (!empty($records) && is_array($records) && count($records) > 0) {
                $tableHeaders = array_map(function($col) {
                    return htmlspecialchars(ucwords(str_replace('_', ' ', $col)));
                }, array_keys($records[0]));
                $tableKeys = array_keys($records[0]);
            }
            break;
    }

    // Ensure export directory exists for all formats
    if (!is_dir('../uploads/exports')) {
        mkdir('../uploads/exports', 0775, true);
    }

    // EXPORT AS PDF
    if ($exportFormat === 'pdf') {
        $filename = ucfirst($reportType) . "_Report_" . date('Y-m-d_H-i-s') . ".pdf";
        $filePath = '../uploads/exports/' . $filename;
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        ob_start();
        include '../templates/reports/pdf_template.php';
        $html = ob_get_clean();
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        // Save to file
        file_put_contents($filePath, $dompdf->output());
        // Check if file exists and is not empty
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            error_log("PDF export failed: file not created or empty: $filePath");
            http_response_code(500);
            echo "Failed to generate PDF file. Please try again.";
            exit;
        }
        // Save export history with file size and path
        saveExportHistory([
            'export_type' => $reportType,
            'export_format' => $exportFormat,
            'date_range' => $_POST['date_range_label'] ?? ($date_from && $date_to ? "$date_from to $date_to" : 'All Time'),
            'filters' => $_POST,
            'file_name' => $filename,
            'file_size' => filesize($filePath),
            'file_path' => $filePath
        ]);
        // Stream to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        exit;
    }

    // EXPORT AS CSV
    if ($exportFormat === 'csv') {
        $filename = ucfirst($reportType) . "_Report_" . date('Y-m-d_H-i-s') . ".csv";
        $filePath = '../uploads/exports/' . $filename;
        $output = fopen($filePath, 'w');
        // Add report header
        fputcsv($output, [ucfirst($reportType) . " Report"]);
        fputcsv($output, ["Generated on: " . date('d M Y H:i:s')]);
        fputcsv($output, ["Total records: " . $summary['total_records']]);
        fputcsv($output, ["Total amount: PKR " . number_format($summary['total_amount'], 2)]);
        fputcsv($output, []);

        // Add column headers based on report type
        $headers = [];
        switch ($reportType) {
            case 'sales':
                $headers = ['Invoice #', 'Customer', 'Date', 'Item', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Created By'];
                break;
            case 'purchases':
                $headers = ['Purchase #', 'Vendor', 'Date', 'Material', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Delivery Status'];
                break;
            case 'inventory':
                $headers = ['Item #', 'Item Name', 'Category', 'Current Stock', 'Min Stock', 'Unit Price', 'Total Value', 'Stock Status'];
                break;
            case 'customers':
                $headers = [
                    'Customer ID', 'Customer Name', 'Phone', 'Email', 'Address', 'City', 'State', 'Zip Code', 'Status', 'Created At', 'Total Orders', 'Total Spent'
                ];
                break;
            case 'vendors':
                $headers = [
                    'Vendor ID', 'Vendor Name', 'Contact Person', 'Phone', 'Email', 'Address', 'City', 'State', 'Zip Code', 'Status', 'Created At', 'Total Purchases', 'Total Spent'
                ];
                break;
            case 'all':
                $headers = ['Reference #', 'Entity', 'Date', 'Item', 'Qty', 'Unit Price', 'Total', 'Payment Status', 'Type'];
                break;
        }
        fputcsv($output, $headers);

        // Add data rows
        foreach ($records as $row) {
            $csvRow = [];
            switch ($reportType) {
                case 'sales':
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
                    break;
                case 'purchases':
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
                    break;
                case 'inventory':
                    $csvRow = [
                        $row['item_number'],
                        $row['item_name'],
                        $row['category_name'],
                        $row['current_stock'],
                        $row['minimum_stock'],
                        number_format($row['unit_price'], 2),
                        number_format($row['total_value'], 2),
                        ucfirst($row['stock_status'])
                    ];
                    break;
                case 'customers':
                    $csvRow = [
                        $row['customer_id'],
                        $row['customer_name'],
                        $row['phone'],
                        $row['email'],
                        $row['address'],
                        $row['city'],
                        $row['state'],
                        $row['zip_code'],
                        ucfirst($row['status']),
                        $row['created_at'],
                        $row['total_orders'],
                        number_format($row['total_spent'], 2)
                    ];
                    break;
                case 'vendors':
                    $csvRow = [
                        $row['vendor_id'],
                        $row['vendor_name'],
                        $row['contact_person'],
                        $row['phone'],
                        $row['email'],
                        $row['address'],
                        $row['city'],
                        $row['state'],
                        $row['zip_code'],
                        ucfirst($row['status']),
                        $row['created_at'],
                        $row['total_purchases'],
                        number_format($row['total_spent'], 2)
                    ];
                    break;
                case 'all':
                    $csvRow = [
                        $row['reference_number'],
                        $row['entity_name'],
                        $row['transaction_date'],
                        $row['item_name'],
                        $row['quantity'],
                        number_format($row['unit_price'], 2),
                        number_format($row['total_price'], 2),
                        ucfirst($row['payment_status']),
                        ucfirst($row['record_type'])
                    ];
                    break;
            }
            fputcsv($output, $csvRow);
        }

        // Add summary
        fputcsv($output, []);
        fputcsv($output, ["SUMMARY"]);
        fputcsv($output, ["Total Records", $summary['total_records']]);
        fputcsv($output, ["Total Amount", "PKR " . number_format($summary['total_amount'], 2)]);
        fputcsv($output, ["Average Amount", "PKR " . number_format($summary['average_amount'], 2)]);

        if ($reportType === 'all' && isset($summary['type_breakdown'])) {
            fputcsv($output, []);
            fputcsv($output, ["TYPE BREAKDOWN"]);
            foreach ($summary['type_breakdown'] as $type => $data) {
                fputcsv($output, [
                    ucfirst($type),
                    $data['count'] . " records",
                    "PKR " . number_format($data['amount'], 2)
                ]);
            }
        }

        fclose($output);
        // Check if file exists and is not empty
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            error_log("CSV export failed: file not created or empty: $filePath");
            http_response_code(500);
            echo "Failed to generate CSV file. Please try again.";
            exit;
        }
        // Save export history with file size and path
        saveExportHistory([
            'export_type' => $reportType,
            'export_format' => $exportFormat,
            'date_range' => $_POST['date_range_label'] ?? ($date_from && $date_to ? "$date_from to $date_to" : 'All Time'),
            'filters' => $_POST,
            'file_name' => $filename,
            'file_size' => filesize($filePath),
            'file_path' => $filePath
        ]);
        // Stream to browser
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=" . $filename);
        readfile($filePath);
        exit;
    }

    echo "Invalid export format. Supported formats: csv, pdf";
    exit;

} catch (PDOException $e) {
    error_log("Export Report Error: " . $e->getMessage(), 3, "../error_log.log");
    echo "Database error. Please try again later.";
    exit;
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage(), 3, "../error_log.log");
    echo "An error occurred while generating the report. Please try again.";
    exit;
}
