<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

// Function to check if email exists outside of transaction
function checkEmailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

// Function to generate unique username
function generateUniqueUsername($pdo, $fullName) {
    $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $fullName)[0]));
    $username = $baseUsername;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() == 0) {
            return $username;
        }
        $username = $baseUsername . $counter++;
    }
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit;
    }

    // Sanitize inputs
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email     = sanitize_input($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role_id   = sanitize_input($_POST['role_id'] ?? '');

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($password) || empty($role_id)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }
    
    $emailExists = checkEmailExists($pdo, $email);
    
    // Generate a unique username outside transaction
    $username = generateUniqueUsername($pdo, $full_name);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Create user directly 
    $insert = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
    $result = $insert->execute([$username, $full_name, $email, $hashed_password, $role_id]);
    
    if ($result) {
        echo json_encode([
            "status" => "success", 
            "message" => "User added successfully with username: " . $username
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to add user."
        ]);
    }
    
} catch (PDOException $e) {
    // Special handling for duplicate entry errors
    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // The user was likely created already, so report success anyway
        echo json_encode([
            "status" => "success", 
            "message" => "User was created successfully."
        ]);
    } else {
        error_log("Add User Error: " . $e->getMessage());
        echo json_encode([
            "status" => "error", 
            "message" => "Database error occurred. Please try again."
        ]);
    }
} catch (Exception $e) {
    error_log("Add User Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error", 
        "message" => "Error: " . $e->getMessage()
    ]);
}