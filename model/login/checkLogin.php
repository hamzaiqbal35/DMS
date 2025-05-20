<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_id = trim($_POST['role_id'] ?? '');

    if (empty($email) || empty($password) || empty($role_id)) {
        throw new Exception("All fields are required.");
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT user_id, username, email, password, role_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception("Invalid email or password.");
    }

    if ($user['role_id'] != $role_id) {
        throw new Exception("Incorrect role selected.");
    }

    // Create JWT payload
    $payload = [
        "user_id"  => $user['user_id'],
        "username" => $user['username'],
        "email"    => $user['email'],
        "role_id"  => $user['role_id']
    ];

    // Generate and store JWT
    $token = generate_jwt($payload);
    $_SESSION['jwt_token'] = $token;

    // Set optional session for server-side support
    $_SESSION['user_id']  = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role_id']  = $user['role_id'];

    echo json_encode([
        "status"  => "success",
        "message" => "Login successful! Redirecting...",
        "token"   => $token
    ]);

} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
