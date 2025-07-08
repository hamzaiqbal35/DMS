<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
// Start session and restore from JWT if needed
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
if (!isset($_SESSION['user_id']) && isset($_SESSION['jwt_token'])) {
    $decoded = decode_jwt($_SESSION['jwt_token']);
    if ($decoded && isset($decoded->data->user_id) && isset($decoded->data->role_id)) {
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['role_id'] = $decoded->data->role_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = intval($input['order_id'] ?? 0);
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID.');
    }
    // Check order status
    $stmt = $pdo->prepare('SELECT order_status FROM customer_orders WHERE order_id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new Exception('Order not found.');
    }
    if (!in_array($order['order_status'], ['delivered', 'cancelled'])) {
        throw new Exception('Only delivered or cancelled orders can be deleted.');
    }
    // Soft delete: just hide from admin
    $pdo->prepare('UPDATE customer_orders SET is_deleted_admin = 1 WHERE order_id = ?')->execute([$order_id]);

    // Check if both admin and customer have deleted, then hard delete
    $stmt = $pdo->prepare('SELECT is_deleted_admin, is_deleted_customer FROM customer_orders WHERE order_id = ?');
    $stmt->execute([$order_id]);
    $flags = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($flags && $flags['is_deleted_admin'] == 1 && $flags['is_deleted_customer'] == 1) {
        $pdo->prepare('DELETE FROM customer_orders WHERE order_id = ?')->execute([$order_id]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 