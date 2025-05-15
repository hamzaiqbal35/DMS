<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Sanitize input
    $user_id    = sanitize_input($_POST['id'] ?? '');
    $full_name  = sanitize_input($_POST['full_name'] ?? '');
    $email      = sanitize_input($_POST['email'] ?? '');
    $role_id    = sanitize_input($_POST['role_id'] ?? '');

    // Basic validation
    if (empty($user_id) || empty($full_name) || empty($email) || empty($role_id)) {
        throw new Exception("All fields are required.");
    }

    // Check if the email is already taken by another user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    $emailExists = $stmt->fetchColumn();

    if ($emailExists) {
        throw new Exception("Email is already in use by another user.");
    }

    // Update query
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, updated_at = NOW() WHERE user_id = ?");
    $updated = $stmt->execute([$full_name, $email, $role_id, $user_id]);

    if ($updated) {
        echo json_encode([
            "status" => "success",
            "message" => "User updated successfully!"
        ]);
    } else {
        throw new Exception("Update failed. Please try again.");
    }

} catch (Exception $e) {
    error_log("Update User Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
