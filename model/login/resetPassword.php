<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Collect input values
        $token = trim($_POST['token']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate input
        if (empty($token) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All fields are required.");
        }

        // Check if passwords match
        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Check if token exists in database and is valid
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetData) {
            throw new Exception("Invalid or expired reset token.");
        }

        // Check if token is expired
        if (strtotime($resetData['expires_at']) < time()) {
            throw new Exception("Reset token has expired.");
        }

        // Hash the new password
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in users table
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        if (!$updateStmt->execute([$hashedPassword, $resetData['email']])) {
            throw new Exception("Failed to reset password. Please try again.");
        }

        // Delete reset token after successful password reset
        $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $deleteStmt->execute([$token]);

        echo json_encode(["status" => "success", "message" => "Password reset successful! You can now log in."]);
    }
} catch (Exception $e) {
    error_log("Reset Password Error: " . $e->getMessage(), 3, "../../error_log.log"); // Log error
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

