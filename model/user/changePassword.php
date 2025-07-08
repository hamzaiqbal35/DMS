<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
// Restore JWT from cookie if not set
if (!isset($_SESSION['jwt_token']) && isset($_COOKIE['jwt_token'])) {
    $_SESSION['jwt_token'] = $_COOKIE['jwt_token'];
}
// Restore session variables from JWT if not set
if (isset($_SESSION['jwt_token']) && !isset($_SESSION['user_id'])) {
    require_once '../../inc/helpers.php';
    $decoded = decode_jwt($_SESSION['jwt_token']);
    if ($decoded && isset($decoded->data->user_id)) {
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
        $_SESSION['role_id'] = $decoded->data->role_id;
    }
}
require_once '../../inc/helpers.php';
require_once '../../inc/config/database.php'; // Ensure $pdo is available
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = $GLOBALS['pdo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!$current_password || !$new_password || !$confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
        exit;
    }
    // Fetch current hash
    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        exit;
    }
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    if ($stmt->execute([$new_hash, $user_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to change password.']);
    }
    exit;
}
echo json_encode(['status' => 'error', 'message' => 'Invalid request.']); 