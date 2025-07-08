<?php
require_once '../inc/config/database.php';
require_once '../inc/helpers.php';

header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Find sales records linked to cancelled or pending orders
    $stmt = $pdo->prepare("
        SELECT s.sale_id, s.invoice_number, co.order_id, co.order_number, co.order_status
        FROM sales s
        JOIN customer_orders co ON s.customer_order_id = co.order_id
        WHERE co.order_status IN ('cancelled', 'pending')
    ");
    $stmt->execute();
    $salesToRemove = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $removedCount = 0;
    $errors = [];

    foreach ($salesToRemove as $sale) {
        try {
            // Delete sale details first (due to foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM sale_details WHERE sale_id = ?");
            $stmt->execute([$sale['sale_id']]);
            
            // Delete payments
            $stmt = $pdo->prepare("DELETE FROM payments WHERE sale_id = ?");
            $stmt->execute([$sale['sale_id']]);
            
            // Delete sales record
            $stmt = $pdo->prepare("DELETE FROM sales WHERE sale_id = ?");
            $stmt->execute([$sale['sale_id']]);
            
            $removedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Error removing sale {$sale['invoice_number']}: " . $e->getMessage();
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "Cleanup completed successfully. Removed {$removedCount} sales records.",
        'data' => [
            'removed_count' => $removedCount,
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order Sales Cleanup Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 