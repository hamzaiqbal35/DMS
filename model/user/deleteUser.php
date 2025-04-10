<?php
// Include database connection
include_once "../../inc/config/database.php";
include_once "../../inc/helpers.php";

// Check if user is logged in and has proper role (admin)
session_start();

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    die("Access denied. Admins only.");
}

// Check if user_id is provided and is a valid integer
if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $user_id = sanitize_input($_GET['user_id']); // Sanitize user_id

    // SQL query to check if the user exists
    $checkQuery = "SELECT id FROM users WHERE id = ?";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        // Proceed to delete user
        $deleteQuery = "DELETE FROM users WHERE id = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);

        if ($deleteStmt->execute([$user_id])) {
            $_SESSION['message'] = "User deleted successfully!";
        } else {
            $_SESSION['message'] = "Error deleting user. Please try again.";
        }
    } else {
        $_SESSION['message'] = "User not found.";
    }
} else {
    $_SESSION['message'] = "Invalid user ID.";
}

// Redirect to manage users page after deletion
header("Location: ../views/manageUsers.php");
exit();
?>
