<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
require_once __DIR__ . '/../../inc/config/auth.php';
require_jwt_auth();
require_once '../../inc/helpers.php';
require_once '../../inc/config/database.php'; // Ensure $pdo is available
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Use PDO
$pdo = $GLOBALS['pdo'];

if (!isset($pdo)) {
    echo json_encode(['status' => 'error', 'message' => 'PDO not set']);
    exit;
}

function getUserProfile($user_id, $role_id, $pdo) {
    $stmt = $pdo->prepare('SELECT username, full_name, email, phone, role_id, created_at, profile_picture FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return null;
    $user['role_name'] = getRoleName($user['role_id']);
    // Only use role default if no uploaded picture
    if (empty($user['profile_picture']) || strtolower($user['profile_picture']) === 'null') {
        // Use the logo as the default for all roles
        $user['profile_picture'] = '../assets/images/logo.png';
    }
    // Otherwise, use the uploaded picture as-is
    return $user;
}

function getRolePermissions($role_id) {
    $map = [
        1 => ['Full Admin Access', 'Manage Users', 'Manage Orders', 'View/Edit All Data', 'Export Data', 'System Settings'],
        2 => ['Manage Inventory', 'Manage Orders', 'View Reports', 'Export Data'],
        3 => ['View Sales', 'Manage Orders', 'Export Sales Reports'],
        4 => ['Manage Inventory', 'View Stock Alerts']
    ];
    return $map[$role_id] ?? ['Basic Access'];
}

function getAccountStats($user_id, $pdo) {
    $stmt = $pdo->prepare('SELECT last_login, total_logins, created_at FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            'last_login' => 'N/A',
            'total_logins' => 'N/A',
            'created_at' => 'N/A'
        ];
    }
    return [
        'last_login' => $row['last_login'] ?? 'N/A',
        'total_logins' => $row['total_logins'] ?? 0,
        'created_at' => $row['created_at'] ?? 'N/A'
    ];
}

function getUserSettings($user_id, $pdo) {
    // Remove language from settings
    return [
        'theme' => 'light',
        'emailNotifications' => true,
        'smsNotifications' => false,
        'pushNotifications' => true,
        'dateFormat' => 'Y-m-d',
        'timezone' => 'Asia/Karachi',
        'twoFactorAuth' => false,
        'sessionTimeout' => 30,
        'autoBackup' => false,
        'backupFrequency' => 'weekly'
    ];
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'permissions':
        echo json_encode(['status' => 'success', 'permissions' => getRolePermissions($role_id)]);
        break;
    case 'stats':
        echo json_encode(['status' => 'success', 'stats' => getAccountStats($user_id, $pdo)]);
        break;
    case 'settings':
        echo json_encode(['status' => 'success', 'settings' => getUserSettings($user_id, $pdo)]);
        break;
    default:
        $profile = getUserProfile($user_id, $role_id, $pdo);
        if ($profile) {
            echo json_encode(['status' => 'success', 'data' => $profile]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
        }
        break;
} 