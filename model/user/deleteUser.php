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
    $user_id = sanitize_input($_POST['id'] ?? '');

    if (empty($user_id)) {
        throw new Exception("User ID is required.");
    }

    // Optionally: Prevent Admin from deleting themselves
    if ($_SESSION['user_id'] == $user_id) {
        throw new Exception("You cannot delete your own account.");
    }

    // Delete user from database
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $deleted = $stmt->execute([$user_id]);

    if ($deleted) {
        echo json_encode([
            "status" => "success",
            "message" => "User deleted successfully."
        ]);
    } else {
        throw new Exception("User deletion failed. Please try again.");
    }

} catch (Exception $e) {
    error_log("Delete User Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
