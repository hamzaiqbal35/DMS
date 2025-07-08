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
    
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $remember_me = $input['remember_me'] ?? false;

    if (empty($email) || empty($password)) {
        throw new Exception("Email and password are required.");
    }

    // Fetch customer user
    $stmt = $pdo->prepare("SELECT customer_user_id, username, email, password, full_name, status, last_login, email_verified FROM customer_users WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer || !password_verify($password, $customer['password'])) {
        throw new Exception("Invalid email or password.");
    }

    if ($customer['status'] !== 'active') {
        throw new Exception("Account is not active. Please contact support.");
    }

    if ($customer['email_verified'] != 1) {
        throw new Exception("Please verify your email before logging in. Check your inbox for the verification link.");
    }

    // Check if this is the first login ever (last_login is NULL)
    $is_first_login = empty($customer['last_login']);

    // Update last_login only if it's not the first login
    if (!$is_first_login) {
        $updateStmt = $pdo->prepare("UPDATE customer_users SET last_login = NOW() WHERE customer_user_id = ?");
        $updateStmt->execute([$customer['customer_user_id']]);
    }

    // Set session variables and JWT for customer
    $payload = [
        "customer_user_id" => $customer['customer_user_id'],
        "username" => $customer['username'],
        "email" => $customer['email'],
        "full_name" => $customer['full_name']
    ];
    $token = generate_customer_jwt($payload);
    $_SESSION['customer_jwt_token'] = $token;
    $_SESSION['customer_user_id'] = $customer['customer_user_id'];
    $_SESSION['customer_username'] = $customer['username'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_full_name'] = $customer['full_name'];
    $_SESSION['is_first_login'] = $is_first_login;

    // Set remember me cookie if requested
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        setcookie('customer_remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
        
        // Store token in database (you might want to add a remember_token column to customer_users)
    }

    echo json_encode([
        "status" => "success",
        "message" => "Login successful!",
        "token" => $token
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 