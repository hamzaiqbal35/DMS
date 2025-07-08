<?php
// Handle form submission
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';

$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $attachment = $_FILES['attachment'] ?? null;

    if ($name && $email && $message) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hamzaiqbalrajpoot35@gmail.com';
            $mail->Password = 'vvvq lojl czqz ocvz';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->setFrom('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
            $mail->addAddress('hamzaiqbalrajpoot35@gmail.com');
            $mail->addReplyTo($email, $name);
            $mail->Subject = 'Customer Support Request from ' . $name;
            $mail->Body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
            if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
            }
            $mail->send();
            $successMsg = 'Your message has been sent successfully! Our team will contact you soon.';
        } catch (Exception $e) {
            $errorMsg = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        $errorMsg = 'Please fill in all required fields.';
    }
}
?>
<?php include __DIR__ . '/../../inc/customer/customer-header.php'; ?>
<div class="container py-5">
    <h2 class="mb-4">Customer Support</h2>
    <?php if ($successMsg): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($successMsg) ?> </div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger"> <?= htmlspecialchars($errorMsg) ?> </div>
    <?php endif; ?>
    <p>If you have any questions, concerns, or need assistance, please contact us using the information below or fill out the support form.</p>
    <ul>
        <li><strong>Phone:</strong> <a href="tel:+923001234567">+92-300-123-4567</a></li>
        <li><strong>Email:</strong> <a href="mailto:info@alliedsteelworks.com">info@alliedsteelworks.com</a></li>
        <li><strong>Address:</strong> Lahore, Pakistan</li>
    </ul>
    <form class="mt-4" method="post" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="supportName" class="form-label">Name</label>
            <input type="text" class="form-control" id="supportName" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="supportEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="supportEmail" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="supportMessage" class="form-label">Message</label>
            <textarea class="form-control" id="supportMessage" name="message" rows="4" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="supportAttachment" class="form-label">Attach Document (optional)</label>
            <input type="file" class="form-control" id="supportAttachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt">
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>
<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 