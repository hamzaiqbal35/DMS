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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_picture'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type.']);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
    echo json_encode(['status' => 'error', 'message' => 'File too large (max 2MB).']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $customer_id . '_' . time() . '.' . $ext;
$upload_dir = '../../uploads/';
$upload_path = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload file.']);
    exit;
}

// Remove old profile picture if exists
$stmt = $pdo->prepare('SELECT profile_picture FROM customer_users WHERE customer_user_id = ?');
$stmt->execute([$customer_id]);
$old = $stmt->fetchColumn();
if ($old && file_exists('../../' . ltrim($old, '/'))) {
    @unlink('../../' . ltrim($old, '/'));
}

// Update DB
$relative_path = 'uploads/' . $filename;
$stmt = $pdo->prepare('UPDATE customer_users SET profile_picture = ? WHERE customer_user_id = ?');
$stmt->execute([$relative_path, $customer_id]);

// Return new image URL
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) . '/';
$profile_picture_url = $base_url . $relative_path;
echo json_encode([
    'status' => 'success',
    'message' => 'Profile picture updated!',
    'profile_picture_url' => $profile_picture_url
]); 