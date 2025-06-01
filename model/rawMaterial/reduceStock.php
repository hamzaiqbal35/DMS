<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php'; // Assuming helpers.php has necessary functions

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // Sanitize and validate input
    $material_id = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
    $quantity    = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
    $reason      = trim($_POST['reason'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $created_by  = $_SESSION['user_id'] ?? null; // Get logged-in user ID

    // Basic validation
    if (!$material_id || $material_id <= 0) {
        throw new Exception('Invalid material ID provided.');
    }
    if (!$quantity || $quantity <= 0) {
        throw new Exception('Quantity must be a positive number.');
    }
    if (empty($reason)) {
        throw new Exception('Reason for reduction is required.');
    }

    // Check if material exists and get current stock
    $stmt = $pdo->prepare("SELECT current_stock FROM raw_materials WHERE material_id = ?");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        throw new Exception('Raw material not found.');
    }

    $current_stock = floatval($material['current_stock']);

    // Check if enough stock is available for reduction
    if ($quantity > $current_stock) {
        throw new Exception('Insufficient stock. Current stock is ' . number_format($current_stock, 2) . '.');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update raw material stock
    $updateStmt = $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE material_id = ?");
    $updateStmt->execute([$quantity, $material_id]);

    // Log the stock reduction
    $logStmt = $pdo->prepare("
        INSERT INTO raw_material_stock_logs (material_id, quantity, type, reason, notes, created_by)
        VALUES (?, ?, 'reduction', ?, ?, ?)
    ");
    $logStmt->execute([$material_id, $quantity, $reason, $notes ?: null, $created_by]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Stock reduced successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reduce Stock Error: " . $e->getMessage() . "\n" . print_r($_POST, true), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reduce Stock Database Error: " . $e->getMessage() . "\n" . print_r($_POST, true), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
} 