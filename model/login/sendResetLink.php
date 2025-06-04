<?php
// Start error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error_log.log');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/config/database.php';  
require_once __DIR__ . '/../../inc/helpers.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

try {
    header('Content-Type: application/json');

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    // Validate email input
    if (!isset($_POST["email"]) || empty($_POST["email"])) {
        throw new Exception("Email is required.");
    }

    $email = sanitize_input($_POST["email"]);

    // Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if email exists in the database
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Email not found in our records.");
    }

    $user_id = $user["user_id"];
    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Delete any previous reset tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Insert new reset token
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
    if (!$stmt->execute([$user_id, $email, $token, $expires_at])) {
        throw new Exception("Failed to create reset token. Please try again.");
    }

    $reset_link = "http://localhost/DMS/views/resetPasswordForm.php?token=" . urlencode($token);

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Disable debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hamzaiqbalrajpoot35@gmail.com';
        $mail->Password = 'vvvq lojl czqz ocvz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'DMS Support');
        $mail->addAddress($email);
        $mail->addReplyTo('hamzaiqbalrajpoot35@gmail.com', 'DMS Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Password Reset Request";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #333;'>Password Reset Request</h2>
                <p>You have requested to reset your password. Click the link below to proceed:</p>
                <p style='margin: 20px 0;'>
                    <a href='$reset_link' 
                       style='background-color: #4361ee; 
                              color: white; 
                              padding: 10px 20px; 
                              text-decoration: none; 
                              border-radius: 5px;
                              display: inline-block;'>
                        Reset Password
                    </a>
                </p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <p>This link will expire in 1 hour.</p>
                <hr style='border: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>
                    This is an automated message, please do not reply to this email.
                </p>
            </div>
        ";

        $mail->AltBody = "To reset your password, visit this link: $reset_link";

        if (!$mail->send()) {
            throw new Exception("Failed to send email: " . $mail->ErrorInfo);
        }

        echo json_encode([
            "status" => "success", 
            "message" => "Password reset link has been sent to your email."
        ]);

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        throw new Exception("Failed to send email. Please try again later.");
    }

} catch (Exception $e) {
    error_log("Password Reset Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}
?>


