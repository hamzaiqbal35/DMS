<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
        $token = trim($_GET['token']);

        // Validate token
        if (empty($token)) {
            throw new Exception("Invalid or missing reset token.");
        }

        // Check if token exists and is valid
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetData) {
            throw new Exception("Invalid or expired reset token.");
        }

        // Check if token has expired
        if (strtotime($resetData['expires_at']) < time()) {
            throw new Exception("Reset token has expired.");
        }

        // Redirect user to reset password form with the token
        header("Location: ../../views/resetPasswordForm.php?token=" . urlencode($token));
        exit;
    } else {
        throw new Exception("Invalid request.");
    }
} catch (Exception $e) {
    error_log("Process Reset Error: " . $e->getMessage(), 3, "../../error_log.log"); // Log error
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
