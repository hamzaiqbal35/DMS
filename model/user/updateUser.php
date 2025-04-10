<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required includes
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

// Set Content-Type
header('Content-Type: application/json');

// Ensure all required fields are provided
if (!isset($_POST['id'], $_POST['name'], $_POST['email'], $_POST['role_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Sanitize input
$id      = intval($_POST['id']);
$name    = sanitize_input($_POST['name']);
$email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$role_id = intval($_POST['role_id']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    // Prepare the statement
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $name, $email, $role_id, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user.']);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Update User Error: " . $e->getMessage(), 3, "../../inc/logs/error_log.log");
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>
