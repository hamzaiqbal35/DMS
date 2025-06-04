<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Sanitize & validate input
    $customer_id     = intval($_POST['customer_id'] ?? 0);
    $item_id         = intval($_POST['item_id'] ?? 0);
    $quantity        = floatval($_POST['quantity'] ?? 0);
    $unit_price      = floatval($_POST['unit_price'] ?? 0);
    $sale_date       = trim($_POST['sale_date'] ?? '');
    $payment_status  = trim($_POST['payment_status'] ?? 'pending');
    $notes           = trim($_POST['notes'] ?? '');
    $created_by      = $_SESSION['user_id'] ?? 1;

    // Validations
    if ($customer_id <= 0) {
        throw new Exception('Please select a valid customer.');
    }
    if ($item_id <= 0) {
        throw new Exception('Please select a valid item.');
    }
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0.');
    }
    if ($unit_price <= 0) {
        throw new Exception('Unit price must be greater than 0.');
    }
    if (empty($sale_date)) {
        throw new Exception('Sale date is required.');
    }
    if (!in_array($payment_status, ['pending', 'partial', 'paid'])) {
        throw new Exception('Invalid payment status.');
    }

    // Check stock availability
    $stockStmt = $pdo->prepare("SELECT current_stock FROM inventory WHERE item_id = ?");
    $stockStmt->execute([$item_id]);
    $stock = $stockStmt->fetchColumn();

    if ($stock === false) {
        throw new Exception('Item not found in inventory.');
    }
    if ($stock < $quantity) {
        throw new Exception('Not enough stock available. Current stock: ' . $stock);
    }

    // Calculate total
    $total_price = $quantity * $unit_price;

    // Start transaction
    $pdo->beginTransaction();

    // Generate invoice number
    $invoice_number = 'INV-' . strtoupper(bin2hex(random_bytes(4)));

    // Insert into sales
    $saleStmt = $pdo->prepare("
        INSERT INTO sales (
            invoice_number, customer_id, sale_date, total_amount,
            payment_status, notes, created_by
        ) VALUES (
            :invoice_number, :customer_id, :sale_date, :total_amount,
            :payment_status, :notes, :created_by
        )
    ");
    $saleStmt->execute([
        'invoice_number' => $invoice_number,
        'customer_id'    => $customer_id,
        'sale_date'      => $sale_date,
        'total_amount'   => $total_price,
        'payment_status' => $payment_status,
        'notes'          => $notes ?: null,
        'created_by'     => $created_by
    ]);

    $sale_id = $pdo->lastInsertId();

    // Insert into sale_details
    $detailStmt = $pdo->prepare("
        INSERT INTO sale_details (
            sale_id, item_id, quantity, unit_price, total_price
        ) VALUES (
            :sale_id, :item_id, :quantity, :unit_price, :total_price
        )
    ");
    $detailStmt->execute([
        'sale_id'     => $sale_id,
        'item_id'     => $item_id,
        'quantity'    => $quantity,
        'unit_price'  => $unit_price,
        'total_price' => $total_price
    ]);

    // Log stock reduction
    $logStmt = $pdo->prepare("
        INSERT INTO stock_logs (item_id, quantity, type, reason)
        VALUES (:item_id, :quantity, 'reduction', :reason)
    ");
    $logStmt->execute([
        'item_id'  => $item_id,
        'quantity' => $quantity,
        'reason'   => 'Sale Transaction #' . $sale_id
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Sale recorded successfully.',
        'data' => [
            'invoice_number' => $invoice_number,
            'total_amount'   => number_format($total_price, 2, '.', '')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Sale Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Insert Sale DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
