<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Get and validate input
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $tax_rate = floatval($_POST['tax_rate'] ?? 0);
    $discount_rate = floatval($_POST['discount_rate'] ?? 0);

    // Validate input
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0.');
    }
    if ($unit_price <= 0) {
        throw new Exception('Unit price must be greater than 0.');
    }
    if ($tax_rate < 0 || $tax_rate > 100) {
        throw new Exception('Tax rate must be between 0 and 100.');
    }
    if ($discount_rate < 0 || $discount_rate > 100) {
        throw new Exception('Discount rate must be between 0 and 100.');
    }

    // Calculate subtotal
    $subtotal = $quantity * $unit_price;

    // Calculate tax amount
    $tax_amount = ($subtotal * $tax_rate) / 100;

    // Calculate discount amount
    $discount_amount = ($subtotal * $discount_rate) / 100;

    // Calculate total
    $total = $subtotal + $tax_amount - $discount_amount;

    // Return calculated values
    echo json_encode([
        'status' => 'success',
        'data' => [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_amount' => number_format($tax_amount, 2, '.', ''),
            'discount_amount' => number_format($discount_amount, 2, '.', ''),
            'total' => number_format($total, 2, '.', '')
        ]
    ]);

} catch (Exception $e) {
    error_log("Calculate Total Cost Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
