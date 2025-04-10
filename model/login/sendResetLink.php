<?php
require __DIR__ . '/../../inc/config/database.php';  
require __DIR__ . '/../../inc/helpers.php';  
require __DIR__ . '/../../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["status" => "error", "message" => "Invalid request method."]));
}

$email = sanitize_input($_POST["email"]); // Use sanitize_input() instead of sanitizeInput()

// Check if email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(["status" => "error", "message" => "Invalid email format."]));
}

// Check if email exists in the database
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die(json_encode(["status" => "error", "message" => "Email not found."]));
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
    die(json_encode(["status" => "error", "message" => "Database error."]));
}

$reset_link = "http://localhost/DMS/views/resetPasswordForm.php?token=$token";

// Send email using PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'hamzaiqbalrajpoot35@gmail.com'; // Replace with your Gmail
    $mail->Password = 'vvvq lojl czqz ocvz ';   // Replace with your Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'DMS Support'); // Replace with your sender email
    $mail->addAddress($email);
    $mail->Subject = "Password Reset Request";
    $mail->isHTML(true);
    $mail->Body = "
        <h3>Password Reset Request</h3>
        <p>Click the link below to reset your password:</p>
        <a href='$reset_link'>$reset_link</a>
        <p>This link will expire in 1 hour.</p>
    ";

    if ($mail->send()) {
        echo json_encode(["status" => "success", "message" => "Password reset email sent."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send email."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
}
?>


