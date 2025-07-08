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
.company-info { 
    font-size: 13px; 
    color: #fff; 
}
.company-info h1 { 
    margin: 0 0 2px 0; 
    font-size: 22px; 
    color: #fff; 
    letter-spacing: 1px; 
    font-weight: 700; 
}
.company-info p { 
    margin: 0; 
    color: #e3e3e3; 
    font-size: 12px; 
}
.report-title { 
    text-align: right; 
    font-size: 20px; 
    color: #fff; 
    font-weight: 700; 
    margin-left: auto; 
}
.report-info {
    margin-bottom: 16px;
    font-size: 12px;
    color: #222;
    background: #e3f2fd;
    padding: 10px 14px;
    border-radius: 6px;
    border-left: 4px solid #1976d2;
}
.summary-section {
    margin: 16px 0;
    padding: 10px 14px;
    background: #e3f2fd;
    border-radius: 6px;
    font-size: 12px;
    border-left: 4px solid #1976d2;
    color: #222;
}
.summary-title {
    margin: 0 0 6px 0; 
    font-size: 15px; 
    color: #1976d2; 
    font-weight: 700;
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
    color: #1976d2;
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-bottom: 18px; 
    background: #fff; 
    border-radius: 8px; 
    overflow: hidden; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
thead { 
    display: table-header-group; 
}
th { 
    background: #1976d2; 
    color: #fff; 
    font-weight: 700; 
    font-size: 11px; 
    text-transform: uppercase; 
    padding: 10px 6px; 
    text-align: center; 
    border: none; 
    min-width: 60px; 
    max-width: 120px; 
    word-break: break-word; 
    white-space: normal;
}
td { 
    border: 1px solid #e0e0e0; 
    padding: 7px 5px; 
    text-align: left; 
    font-size: 10px; 
    vertical-align: middle; 
    min-width: 60px; 
    max-width: 120px; 
    word-break: break-word; 
    white-space: normal;
}
tbody tr:nth-child(even) { 
    background: #f6f8fa; 
}
tbody tr:nth-child(odd) { 
    background: #fff; 
}
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
.status-completed { background: #27ae60; }
.status-active { background: #27ae60; }
.status-inactive { background: #95a5a6; }
.status-low_stock { background: #f39c12; }
.status-out_of_stock { background: #e74c3c; }
.status-sufficient { background: #27ae60; }
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
.amount-cell { 
    font-weight: 600; 
    color: #2c3e50; 
}
.footer { 
    text-align: center; 
    font-size: 11px; 
    color: #7f8c8d; 
    margin-top: 20px; 
    padding-top: 10px; 
    border-top: 1px solid #ecf0f1; 
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
<?php
// Get logo data
$logo_path = __DIR__ . '/../../assets/images/logo.png';
$logo_data = '';
if (file_exists($logo_path)) {
    $logo_data = base64_encode(file_get_contents($logo_path));
}

// Set timezone to Pakistan
date_default_timezone_set('Asia/Karachi');
$generated_date = date('F j, Y \a\t g:i A');
$company_name = 'Allied Steel Works';
$company_address = 'Allied Steel Works (Pvt) Ltd., Service Road, Bhamma, Lahore, Pakistan';
$company_email = 'info@alliedsteelworks.pk';
$company_phone = '+92-300-1234567';
?>

<div class="header">
<?php if ($logo_data): ?>
    <img src="data:image/png;base64,<?= $logo_data ?>" class="logo" alt="Allied Steel Works Logo">
<?php endif; ?>
<div class="company-info">
    <h1><?= htmlspecialchars($company_name) ?></h1>
    <p><?= htmlspecialchars($company_address) ?></p>
    <p><?= htmlspecialchars($company_email) ?> | <?= htmlspecialchars($company_phone) ?></p>
</div>
<div class="report-title"><?= ucfirst($reportType) ?> Report</div>
</div>

<div class="report-info">
<span><strong>Generated on:</strong> <?= $generated_date ?></span>
<?php if (!empty($date_from) || !empty($date_to)): ?>
    <br><strong>Date Range:</strong> <?= $date_from ?: 'Start' ?> to <?= $date_to ?: 'End' ?>
<?php endif; ?>
<?php if (!empty($payment_status)): ?>
    <br><strong>Payment Status:</strong> <?= ucfirst($payment_status) ?>
<?php endif; ?>
<?php if (!empty($order_status)): ?>
    <br><strong>Order Status:</strong> <?= ucfirst($order_status) ?>
<?php endif; ?>
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
            <?php 
            // Determine if this is a count field or monetary field
            $isCountField = in_array($key, [
                'total_records', 'total_orders', 'total_purchases', 'items_count', 
                'unique_customers', 'unique_vendors', 'current_stock', 'minimum_stock'
            ]);
            
            if ($isCountField) {
                // For count fields, show as whole numbers without PKR
                echo number_format($val, 0);
            } elseif (stripos($key, 'amount') !== false || stripos($key, 'total') !== false || stripos($key, 'value') !== false || stripos($key, 'spent') !== false || stripos($key, 'price') !== false) {
                // For monetary fields, show with PKR and 2 decimal places
                echo 'PKR ' . number_format($val, 2);
            } else {
                // For other numeric fields, show as whole numbers
                echo number_format($val, 0);
            }
            ?>
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
                        $tableHeaders = ['Invoice #', 'Customer', 'Sale Date', 'Sale Type', 'Total Amount', 'Paid Amount', 'Pending Amount', 'Payment Status', 'Order Status', 'Items Count', 'Created By'];
                        $tableKeys = ['invoice_number', 'customer_name', 'sale_date', 'sale_type', 'total_amount', 'paid_amount', 'pending_amount', 'payment_status', 'order_status', 'items_count', 'created_by_name'];
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
                                                        <td>
                                                        <?php 
                                                        if (isset($row[$key])) {
                                                            $value = $row[$key];
                                                            
                                                            // Handle special formatting for different fields
                                                            if ($key === 'sale_type') {
                                                                $sale_type_class = ($value === 'Direct Sale') ? 'sale-type-direct' : 'sale-type-order';
                                                                echo '<span class="sale-type-badge ' . $sale_type_class . '">' . htmlspecialchars($value) . '</span>';
                                                            } elseif ($key === 'payment_status' || $key === 'order_status' || $key === 'delivery_status' || $key === 'status' || $key === 'stock_status') {
                                                                $status_class = 'status-' . strtolower(str_replace(' ', '_', $value));
                                                                echo '<span class="status-badge ' . $status_class . '">' . ucfirst(htmlspecialchars($value)) . '</span>';
                                                            } elseif ($key === 'total_orders' || $key === 'total_purchases' || $key === 'items_count') {
                                                                // For count fields, show as whole numbers without PKR
                                                                echo number_format($value, 0);
                                                            } elseif (is_numeric($value)) {
                                                                if (stripos($key, 'amount') !== false || stripos($key, 'price') !== false || stripos($key, 'total') !== false || stripos($key, 'value') !== false || stripos($key, 'spent') !== false) {
                                                                    echo '<span class="amount-cell">PKR ' . number_format($value, 2) . '</span>';
                                                                } else {
                                                                    echo number_format($value, 0);
                                                                }
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                        } else {
                                                            echo '';
                                                        }
                                                        ?>
                                                        </td>
                                                        <?php endforeach; ?>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        </tbody>
                                                        </table>
                                                        <?php else: ?>
                                                            <div class="no-data">No data available for this report.</div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="footer">
                                                            Report generated by Allied Steel Works DMS | <?= $generated_date ?>
                                                            </div>
                                                            </body>
                                                            </html> 