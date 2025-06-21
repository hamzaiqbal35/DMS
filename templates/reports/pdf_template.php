<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= ucfirst($reportType) ?> Report</title>
<style>
@page {
    margin: 20px;
    size: A4 landscape;
}
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 11px;
    color: #333;
    margin: 0;
    padding: 0;
}
.header {
    text-align: center;
    margin-bottom: 25px;
    border-bottom: 3px solid #007bff;
    padding-bottom: 15px;
}
.header h1 {
    color: #007bff;
    font-size: 24px;
    margin: 0 0 10px 0;
    font-weight: bold;
}
.header .company-info {
    color: #666;
    font-size: 12px;
    margin: 5px 0;
}
.summary-section {
    margin-bottom: 20px;
    background: #f1f8fd;
    padding: 15px;
    border-radius: 8px;
}
.summary-title {
    color: #007bff;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 10px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
.summary-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 10px;
}
.summary-item {
    flex: 1 1 120px;
    min-width: 120px;
    text-align: center;
    background: white;
    padding: 10px 8px;
    border-radius: 5px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.summary-item .label {
    font-size: 10px;
    color: #666;
    margin-bottom: 3px;
}
.summary-item .value {
    font-size: 13px;
    font-weight: bold;
    color: #007bff;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    border-radius: 8px;
    overflow: hidden;
    table-layout: fixed;
    word-break: break-word;
}
thead { display: table-header-group; }
th {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 10px 6px;
    font-weight: bold;
    font-size: 10px;
    text-align: center;
    border: none;
    min-width: 60px;
    max-width: 120px;
    word-break: break-word;
    white-space: normal;
}
td {
    padding: 7px 5px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 10px;
    vertical-align: middle;
    min-width: 60px;
    max-width: 120px;
    word-break: break-word;
    white-space: normal;
}
tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}
tbody tr:hover {
    background-color: #e3f2fd;
}
.footer {
    margin-top: 20px;
    text-align: right;
    color: #666;
    font-size: 10px;
    border-top: 1px solid #ddd;
    padding-top: 10px;
}
.no-data {
    margin: 30px 0;
    text-align: center;
    color: #888;
    font-size: 14px;
}
</style>
</head>
<body>
<div class="header">
<h1><?= ucfirst($reportType) ?> Report</h1>
<div class="company-info">
<strong>Allied Steel Works</strong><br>
<?= date('d M Y, H:i:s') ?>
</div>
</div>

<?php if (!empty($summary) && is_array($summary)): ?>
    <div class="summary-section">
    <div class="summary-title">Summary Statistics</div>
    <div class="summary-grid">
    <?php foreach ($summary as $key => $val): ?>
        <div class="summary-item">
        <div class="label"><?= ucwords(str_replace('_', ' ', $key)) ?></div>
        <div class="value">
        <?php if (is_numeric($val)): ?>
            <?php if (stripos($key, 'amount') !== false || stripos($key, 'total') !== false || stripos($key, 'value') !== false): ?>
                PKR <?= number_format($val, 2) ?>
                <?php else: ?>
                    <?= number_format($val) ?>
                    <?php endif; ?>
                    <?php else: ?>
                        <?= htmlspecialchars($val) ?>
                        <?php endif; ?>
                        </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Set table headers and keys based on report type
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
                                                    // Fallback to dynamic keys if unknown type
                                                    if (!empty($records) && is_array($records) && count($records) > 0) {
                                                        $tableHeaders = array_map(function($col) {
                                                            return htmlspecialchars(ucwords(str_replace('_', ' ', $col)));
                                                        }, array_keys($records[0]));
                                                        $tableKeys = array_keys($records[0]);
                                                    }
                                                    break;
                                                }
                                                ?>
                                                
                                                <?php if (!empty($records) && is_array($records) && count($records) > 0): ?>
                                                    <table>
                                                    <thead>
                                                    <tr>
                                                    <?php foreach($tableHeaders as $header): ?>
                                                        <th><?= $header ?></th>
                                                        <?php endforeach; ?>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach($records as $row): ?>
                                                            <tr>
                                                            <?php foreach($tableKeys as $key): ?>
                                                                <td><?= isset($row[$key]) ? htmlspecialchars($row[$key]) : '' ?></td>
                                                                <?php endforeach; ?>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                </tbody>
                                                                </table>
                                                                <?php else: ?>
                                                                    <div class="no-data">No data available for this report.</div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="footer">
                                                                    Report generated by Allied Steel Works DMS | <?= date('d M Y, H:i:s') ?>
                                                                    </div>
                                                                    </body>
                                                                    </html> 