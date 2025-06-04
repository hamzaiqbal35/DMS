<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    $sale_id = intval($_GET['sale_id'] ?? 0);
    
    if ($sale_id <= 0) {
        throw new Exception('Invalid sale ID.');
    }

    $stmt = $pdo->prepare("
        SELECT 
            s.*, 
            sd.item_id, 
            sd.quantity, 
            sd.unit_price, 
            sd.total_price,
            s.paid_amount,
            i.item_name,
            i.item_number,
            c.customer_name,
            c.phone as customer_phone,
            c.email as customer_email,
            c.address as customer_address,
            u.full_name as created_by_name
        FROM sales s
        JOIN sale_details sd ON s.sale_id = sd.sale_id
        JOIN inventory i ON sd.item_id = i.item_id
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN users u ON s.created_by = u.user_id
        WHERE s.sale_id = :sale_id
    ");
    
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found.');
    }

    // Fetch all payments for this sale
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

    // Add payments and total paid amount to the sale data
    $sale['payments'] = $payments;
    // The total paid amount is already in sales.paid_amount, so we don't need to sum payments here

    echo json_encode([
        'status' => 'success',
        'data' => $sale
    ]);

} catch (Exception $e) {
    error_log("Get Sale Details Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Get Sale Details Database Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
