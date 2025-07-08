<?php
header('Content-Type: application/json');
require_once '../../inc/helpers.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$subject || !$message) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required.'
    ]);
    exit;
}

$mail = new PHPMailer(true);
try {
    // SMTP settings (from sendResetLink.php)
    $mail->SMTPDebug = 0;
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
    $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
    $mail->addAddress('hamzaiqbalrajpoot35@gmail.com'); // Support email
    $mail->addReplyTo($email, $name);
    // Content
    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subject;
    $mail->Body = '<b>Name:</b> ' . htmlspecialchars($name) . '<br>' .
                  '<b>Email:</b> ' . htmlspecialchars($email) . '<br>' .
                  '<b>Message:</b><br>' . nl2br(htmlspecialchars($message));
    $mail->AltBody = "Name: $name\nEmail: $email\nMessage:\n$message";
    $mail->send();
    echo json_encode([
        'status' => 'success',
        'message' => 'Your message has been sent successfully!'
    ]);
} catch (Exception $e) {
    error_log('Contact Form Email Error: ' . $mail->ErrorInfo);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send your message. Please try again later.'
    ]);
} 