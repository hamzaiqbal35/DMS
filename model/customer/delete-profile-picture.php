<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

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
    $decoded = decode_customer_jwt($_SESSION['customer_jwt_token']);
    if ($decoded && isset($decoded->data->customer_user_id)) {
        $_SESSION['customer_user_id'] = $decoded->data->customer_user_id;
    }
}

$customer_id = $_SESSION['customer_user_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// Get current profile picture
$stmt = $pdo->prepare('SELECT profile_picture FROM customer_users WHERE customer_user_id = ?');
$stmt->execute([$customer_id]);
$old = $stmt->fetchColumn();
if ($old && file_exists('../../' . ltrim($old, '/'))) {
    @unlink('../../' . ltrim($old, '/'));
}
// Update DB
$stmt = $pdo->prepare('UPDATE customer_users SET profile_picture = NULL WHERE customer_user_id = ?');
$stmt->execute([$customer_id]);
echo json_encode([
    'status' => 'success',
    'message' => 'Profile picture deleted!'
]); 