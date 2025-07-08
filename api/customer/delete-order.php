<?php
// Ensure customer session is started and restored from cookie if needed
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
// Restore JWT from cookie if not set
if (!isset($_SESSION['customer_jwt_token']) && isset($_COOKIE['customer_jwt_token'])) {
    $_SESSION['customer_jwt_token'] = $_COOKIE['customer_jwt_token'];
}
// Decode JWT and set session variables
if (isset($_SESSION['customer_jwt_token'])) {
    require_once '../../inc/helpers.php';
    $decoded = decode_customer_jwt($_SESSION['customer_jwt_token']);
    if ($decoded && isset($decoded->data->customer_user_id)) {
        $_SESSION['customer_user_id'] = $decoded->data->customer_user_id;
        $_SESSION['customer_username'] = $decoded->data->username;
        $_SESSION['customer_email'] = $decoded->data->email;
        $_SESSION['customer_full_name'] = $decoded->data->full_name;
    }
}
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    $customer_id = $_SESSION['customer_user_id'] ?? null;
    if (!$customer_id) {
        throw new Exception('Not logged in.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID.');
    }
    // Verify order belongs to customer
    $stmt = $pdo->prepare('SELECT * FROM customer_orders WHERE order_id = ? AND customer_user_id = ?');
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new Exception('Order not found.');
    }
    // Only allow deletion if order_status is cancelled or delivered
    $deletable_statuses = ['cancelled', 'delivered'];
    if (!in_array($order['order_status'], $deletable_statuses)) {
        throw new Exception('Order can only be deleted if it is cancelled or delivered.');
    }
    // Soft delete: just hide from customer
    $stmt = $pdo->prepare('UPDATE customer_orders SET is_deleted_customer = 1 WHERE order_id = ?');
    $stmt->execute([$order_id]);

    // Check if both admin and customer have deleted, then hard delete
    $stmt2 = $pdo->prepare('SELECT is_deleted_admin, is_deleted_customer FROM customer_orders WHERE order_id = ?');
    $stmt2->execute([$order_id]);
    $flags = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($flags && $flags['is_deleted_admin'] == 1 && $flags['is_deleted_customer'] == 1) {
        $pdo->prepare('DELETE FROM customer_orders WHERE order_id = ?')->execute([$order_id]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 