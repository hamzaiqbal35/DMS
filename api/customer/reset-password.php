<?php
session_name('customer_session');
session_start();
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $token = trim($input['token'] ?? '');
    $new_password = trim($input['new_password'] ?? '');
    $confirm_password = trim($input['confirm_password'] ?? '');

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        throw new Exception("All fields are required.");
    }

    if ($new_password !== $confirm_password) {
        throw new Exception("Passwords do not match.");
    }

    if (strlen($new_password) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    // Verify token
    $stmt = $pdo->prepare("
        SELECT pr.customer_user_id, pr.expires_at, cu.email, cu.username
        FROM customer_password_resets pr
        JOIN customer_users cu ON pr.customer_user_id = cu.customer_user_id
        WHERE pr.token = ?
    ");
    $stmt->execute([$token]);
    $reset_record = $stmt->fetch();

    if (!$reset_record) {
        throw new Exception("Invalid or expired reset token.");
    }

    if (strtotime($reset_record['expires_at']) < time()) {
        throw new Exception("Reset token has expired.");
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update password
        $stmt = $pdo->prepare("
            UPDATE customer_users 
            SET password = ?, updated_at = NOW()
            WHERE customer_user_id = ?
        ");
        $stmt->execute([$hashed_password, $reset_record['customer_user_id']]);

        // Delete the used reset token
        $stmt = $pdo->prepare("
            DELETE FROM customer_password_resets 
            WHERE token = ?
        ");
        $stmt->execute([$token]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Password has been reset successfully. You can now login with your new password."
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 