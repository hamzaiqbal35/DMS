<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    if (!$email) {
        throw new Exception("Email is required.");
    }
    // Find customer user
    $stmt = $pdo->prepare("SELECT customer_user_id, full_name, email_verified FROM customer_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception("No account found with this email.");
    }
    if ($user['email_verified']) {
        throw new Exception("This email is already verified. You can log in.");
    }
    $customer_user_id = $user['customer_user_id'];
    $full_name = $user['full_name'];
    // Check for recent token
    $stmt = $pdo->prepare("SELECT created_at FROM customer_email_verifications WHERE customer_user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$customer_user_id]);
    $existing = $stmt->fetch();
    if ($existing && strtotime($existing['created_at']) > time() - 300) {
        throw new Exception("A verification email was sent recently. Please wait a few minutes before requesting again.");
    }
    // Remove old tokens
    $stmt = $pdo->prepare("DELETE FROM customer_email_verifications WHERE customer_user_id = ?");
    $stmt->execute([$customer_user_id]);
    // Generate new token
    $verification_token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO customer_email_verifications (customer_user_id, token) VALUES (?, ?)");
    $stmt->execute([$customer_user_id, $verification_token]);
    // Send email
    $mail = new PHPMailer(true);
    try {
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
        $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
        $mail->addAddress($email, $full_name);
        $mail->addReplyTo('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
        $mail->isHTML(true);
        $mail->Subject = "Verify your email - Allied Steel Works";
        $verify_link = "http://localhost/DMS/api/customer/verify-email.php?token=$verification_token";
        $message = "<p>Dear $full_name,</p>\n<p>Please verify your email by clicking the link below:</p>\n<p><a href='$verify_link'>$verify_link</a></p>\n<p>If you did not register, please ignore this email.</p>";
        $mail->Body = $message;
        $mail->send();
    } catch (Exception $e) {
        error_log('Verification Email Error: ' . $mail->ErrorInfo, 3, '../../error_log.log');
        throw new Exception("Failed to send verification email. Please try again later.");
    }
    echo json_encode([
        "status" => "success",
        "message" => "Verification email sent! Please check your inbox."
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} 