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
    // Sanitize input
    $sale_id        = intval($_POST['sale_id'] ?? 0);
    $payment_amount = floatval($_POST['payment_amount'] ?? 0);
    $payment_date   = trim($_POST['payment_date'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $payment_notes  = trim($_POST['payment_notes'] ?? '');
    $created_by     = $_SESSION['user_id'] ?? 1; // Assuming user_id is in session

    // Validations
    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }
    if ($payment_amount <= 0) {
        throw new Exception('Payment amount must be greater than 0.');
    }
    if (empty($payment_date)) {
        throw new Exception('Payment date is required.');
    }
    if (empty($payment_method)) {
        throw new Exception('Payment method is required.');
    }

    // Fetch current sale details to check total amount and current paid amount
    $saleStmt = $pdo->prepare("SELECT total_amount, paid_amount FROM sales WHERE sale_id = ? FOR UPDATE"); // Use FOR UPDATE to lock row
    $saleStmt->execute([$sale_id]);
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found.');
    }

    $total_amount = $sale['total_amount'];
    $current_paid_amount = $sale['paid_amount'];
    $new_paid_amount = $current_paid_amount + $payment_amount;

    // Determine new payment status
    $new_payment_status = 'pending';
    if ($new_paid_amount >= $total_amount) {
        $new_payment_status = 'paid';
    } elseif ($new_paid_amount > 0) {
        $new_payment_status = 'partial';
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert payment record
    $insertPayment = $pdo->prepare("
        INSERT INTO payments (sale_id, payment_date, amount, method, notes)
        VALUES (:sale_id, :payment_date, :amount, :method, :notes)
    ");
    $insertPayment->execute([
        'sale_id'      => $sale_id,
        'payment_date' => $payment_date,
        'amount'       => $payment_amount,
        'method'       => $payment_method,
        'notes'        => $payment_notes ?: null
    ]);

    // Update sales table with new paid amount and status
    $updateSale = $pdo->prepare("
        UPDATE sales
        SET paid_amount = :new_paid_amount,
            payment_status = :new_payment_status,
            updated_at = NOW()
        WHERE sale_id = :sale_id
    ");
    $updateSale->execute([
        'new_paid_amount'  => $new_paid_amount,
        'new_payment_status' => $new_payment_status,
        'sale_id'          => $sale_id
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment recorded and sale updated successfully.',
        'data' => [
            'new_payment_status' => $new_payment_status,
            'new_paid_amount'    => number_format($new_paid_amount, 2, '.', ''),
            'remaining_amount'   => number_format($total_amount - $new_paid_amount, 2, '.', '')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Record Payment Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Record Payment DB Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while recording payment.']);
}

?> 