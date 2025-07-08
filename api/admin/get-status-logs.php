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

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if (!$order_id) {
        throw new Exception("Invalid order ID");
    }
    
    // Get status logs with user information
    $stmt = $pdo->prepare("
        SELECT osl.*, u.full_name as changed_by_name
        FROM order_status_logs osl
        JOIN users u ON osl.changed_by = u.user_id
        WHERE osl.order_id = ?
        ORDER BY osl.created_at DESC
    ");
    $stmt->execute([$order_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $logs
    ]);
    
} catch (Exception $e) {
    error_log("Get Status Logs Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load status logs: ' . $e->getMessage()
    ]);
}
?> 