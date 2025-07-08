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
    // Profile picture upload
    if (isset($_FILES['profile_picture'])) {
        if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $error_code = $_FILES['profile_picture']['error'];
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
            ];
            $msg = $error_messages[$error_code] ?? 'Unknown upload error.';
            echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $error_code . ' - ' . $msg]);
            exit;
        }
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $target = '../../uploads/' . $filename;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
            $db_path = 'uploads/' . $filename;
            // Use PDO for DB update
            $stmt = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE user_id = ?');
            if ($stmt->execute([$db_path, $user_id])) {
                $_SESSION['profile_picture'] = $db_path;
                echo json_encode(['status' => 'success', 'message' => 'Profile picture updated!', 'profile_picture' => $db_path]);
                exit;
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $errorInfo[2]]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check permissions for /uploads/.']);
            exit;
        }
    }
    // Update profile info
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    if (!$full_name || !$email) {
        echo json_encode(['status' => 'error', 'message' => 'Name and email are required.']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?');
    if ($stmt->execute([$full_name, $email, $phone, $user_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
    }
    exit;
}
echo json_encode(['status' => 'error', 'message' => 'Invalid request.']); 