<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start(); // Ensure session is started

$base_url = "http://localhost/DMS/";

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $selected_role = trim($_POST['role_id'] ?? ''); // Get selected role

    if (empty($email) || empty($password) || empty($selected_role)) {
        throw new Exception("All fields are required.");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT user_id, username, email, password, role_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Invalid email or password.");
    }

    // Check if selected role matches the user's role
    if ($user['role_id'] != $selected_role) {
        throw new Exception("Incorrect role selection.");
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception("Invalid email or password.");
    }

    // Store user session data
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];

    echo json_encode(["status" => "success", "message" => "Login successful! Redirecting..."]);
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
