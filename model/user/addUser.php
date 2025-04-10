<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

function generateUniqueUsername($base, $pdo) {
    $base = strtolower(preg_replace('/\s+/', '', $base)); // "Hamza Iqbal" => "hamzaiqbal"
    $username = $base;
    $i = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);

        $count = $stmt->fetchColumn();
        if ($count == 0) {
            return $username;
        }

        $username = $base . $i;
        $i++;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }


    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);

    if (empty($full_name) || empty($email) || empty($password) || empty($role_id)) {
        throw new Exception("All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Email is already in use.");
    }

    // Generate username
    $username = generateUniqueUsername($full_name, $pdo);
    error_log("Generated username: $username", 3, "../../error_log.log");

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $email, $full_name, $role_id]);

    echo json_encode(["status" => "success", "message" => "User added successfully."]);
    exit();
} catch (Exception $e) {
    error_log("Add User Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(["status" => "error", "message" => "Add User Error: " . $e->getMessage()]);
}