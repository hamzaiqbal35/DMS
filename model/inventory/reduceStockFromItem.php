<?php
require_once '../../inc/config/database.php';
require_once '../../inc/config/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['item_id']) || !isset($_POST['quantity']) || !isset($_POST['reason'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$item_id = $_POST['item_id'];
$quantity = floatval($_POST['quantity']);
$reason = $_POST['reason'];
$other_reason = isset($_POST['other_reason']) ? $_POST['other_reason'] : null;

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current stock
    $stmt = $pdo->prepare("SELECT current_stock FROM inventory WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception("Item not found");
    }

    $current_stock = floatval($item['current_stock']);

    if ($quantity > $current_stock) {
        throw new Exception("Cannot reduce more than current stock");
    }

    // Update stock
    $new_stock = $current_stock - $quantity;
    $stmt = $pdo->prepare("UPDATE inventory SET current_stock = ? WHERE item_id = ?");
    $stmt->execute([$new_stock, $item_id]);

    // Try to log the stock reduction if the table exists
    try {
        $log_reason = $reason === 'other' ? $other_reason : $reason;
        $stmt = $pdo->prepare("INSERT INTO stock_logs (item_id, quantity, type, reason, created_at) VALUES (?, ?, 'reduction', ?, NOW())");
        $stmt->execute([$item_id, $quantity, $log_reason]);
    } catch (PDOException $e) {
        // If the stock_logs table doesn't exist, we'll just continue without logging
        // This ensures the stock reduction still works even if logging fails
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Stock reduced successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close the connection
$pdo = null;
?> 