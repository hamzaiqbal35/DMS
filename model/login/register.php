<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        // Default role assignment (Modify if needed)
        $selected_role = trim($_POST['role']) ?? 'Salesperson'; 

        // Check if fields are empty
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            throw new Exception("All fields are required.");
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check password match
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email is already registered.");
        }

        // Ensure the selected role exists in the database
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
        $stmt->execute([$selected_role]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$role) {
            throw new Exception("Selected role not found. Please check the roles table.");
        }
        $role_id = $role['role_id']; // Assign correct role_id

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$username, $email, $hashedPassword, $role_id])) {
            throw new Exception("Database insert failed.");
        }

        echo json_encode(["status" => "success", "message" => "Registration successful! Redirecting..."]);
    } catch (Exception $e) {
        error_log("Register Error: " . $e->getMessage(), 3, "../../error_log.log");
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
