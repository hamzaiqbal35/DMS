<?php
session_name('customer_session');
session_start();
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

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'confirm_password', 'full_name'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("All required fields must be filled.");
        }
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if passwords match
    if ($input['password'] !== $input['confirm_password']) {
        throw new Exception("Passwords do not match.");
    }

    // Validate password strength
    if (strlen($input['password']) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    // Check if username already exists in customer_users
    $stmt = $pdo->prepare("SELECT customer_user_id FROM customer_users WHERE username = ?");
    $stmt->execute([$input['username']]);
    if ($stmt->fetch()) {
        throw new Exception("Username already exists. Please choose a different one.");
    }

    // Check if email already exists in customer_users
    $stmt = $pdo->prepare("SELECT customer_user_id FROM customer_users WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        throw new Exception("Email already registered. Please use a different email or login.");
    }

    // Check if email already exists in admin customers table
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        throw new Exception("Email already registered in our system. Please use a different email or contact support.");
    }

    // Hash password
    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert new customer user
        $stmt = $pdo->prepare("
            INSERT INTO customer_users 
            (username, email, password, full_name, phone, address, city, state, zip_code, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        $result = $stmt->execute([
            $input['username'],
            $input['email'],
            $hashed_password,
            $input['full_name'],
            $input['phone'] ?? null,
            $input['address'] ?? null,
            $input['city'] ?? null,
            $input['state'] ?? null,
            $input['zip_code'] ?? null
        ]);

        if (!$result) {
            throw new Exception("Failed to create customer account.");
        }

        $customer_user_id = $pdo->lastInsertId();

        // Generate email verification token
        // Check if a token already exists and is recent (within 5 minutes)
        $stmt = $pdo->prepare("SELECT created_at FROM customer_email_verifications WHERE customer_user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$customer_user_id]);
        $existing = $stmt->fetch();
        if ($existing && strtotime($existing['created_at']) > time() - 300) { // 5 minutes
            throw new Exception("A verification email was sent recently. Please wait a few minutes before requesting again.");
        }
        // Remove any old tokens
        $stmt = $pdo->prepare("DELETE FROM customer_email_verifications WHERE customer_user_id = ?");
        $stmt->execute([$customer_user_id]);
        $verification_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO customer_email_verifications (customer_user_id, token) VALUES (?, ?)");
        $stmt->execute([$customer_user_id, $verification_token]);

        // Send verification email using PHPMailer
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
            $mail->addAddress($input['email'], $input['full_name']);
            $mail->addReplyTo('hamzaiqbalrajpoot35@gmail.com', 'Allied Steel Works');
            $mail->isHTML(true);
            $mail->Subject = "Verify your email - Allied Steel Works";
            $verify_link = "http://localhost/DMS/api/customer/verify-email.php?token=$verification_token";
            $message = "<p>Dear {$input['full_name']},</p>\n<p>Thank you for registering. Please verify your email by clicking the link below:</p>\n<p><a href='$verify_link'>$verify_link</a></p>\n<p>If you did not register, please ignore this email.</p>";
            $mail->Body = $message;
            $mail->send();
        } catch (Exception $e) {
            // Log but do not block registration
            error_log('Verification Email Error: ' . $mail->ErrorInfo, 3, '../../error_log.log');
        }

        // Also create record in admin customers table
        $stmt = $pdo->prepare("
            INSERT INTO customers 
            (customer_name, contact_person, phone, email, address, city, state, zip_code, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        $result = $stmt->execute([
            $input['full_name'], // customer_name
            $input['full_name'], // contact_person (same as full name)
            $input['phone'] ?? null,
            $input['email'],
            $input['address'] ?? null,
            $input['city'] ?? null,
            $input['state'] ?? null,
            $input['zip_code'] ?? null
        ]);

        if (!$result) {
            throw new Exception("Failed to create admin customer record.");
        }

        $admin_customer_id = $pdo->lastInsertId();

        // Update customer_users table with admin_customer_id reference
        $stmt = $pdo->prepare("
            UPDATE customer_users 
            SET admin_customer_id = ? 
            WHERE customer_user_id = ?
        ");
        $stmt->execute([$admin_customer_id, $customer_user_id]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Account created successfully! Please check your email to verify your account."
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Customer Registration Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 