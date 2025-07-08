<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');

    if (empty($email)) {
        throw new Exception("Email is required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT customer_user_id, username, full_name FROM customer_users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if (!$customer) {
        throw new Exception("No active account found with this email address.");
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Delete any existing reset tokens for this customer
    $stmt = $pdo->prepare("DELETE FROM customer_password_resets WHERE customer_user_id = ?");
    $stmt->execute([$customer['customer_user_id']]);

    // Insert new reset token
    $stmt = $pdo->prepare("
        INSERT INTO customer_password_resets 
        (customer_user_id, email, token, expires_at, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$customer['customer_user_id'], $email, $token, $expires_at]);

    // Send email with PHPMailer
    require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = 0; // Disable debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hamzaiqbalrajpoot35@gmail.com';
        $mail->Password = 'vvvq lojl czqz ocvz';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
        $mail->addAddress($email, $customer['full_name']);
        $mail->addReplyTo('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');

        // Content
        $mail->isHTML(false);
        $mail->Subject = 'Password Reset Request - Allied Steel Works';
        $reset_link = 'http://localhost/DMS/customer.php?page=resetPasswordForm&token=' . $token;
        $mail->Body = "Dear {$customer['full_name']},\n\nWe received a request to reset your password. Please click the link below to set a new password:\n\n$reset_link\n\nIf you did not request this, please ignore this email.\n\nRegards,\nAllied Steel Works Team";
        $mail->send();
    } catch (Exception $e) {
        // Log but do not expose to user
        error_log('PHPMailer Error: ' . $mail->ErrorInfo, 3, '../../error_log.log');
    }

    echo json_encode([
        "status" => "success",
        "message" => "Password reset instructions have been sent to your email address."
    ]);

} catch (Exception $e) {
    error_log("Customer Forgot Password Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 