<?php

require_once __DIR__ . '/config/database.php';

// Function to check if a user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Sanitize user input (compatible with PDO)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}


// Execute SQL query
function executeQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    return $result;
}


// Execute prepared statement
function executePreparedStatement($sql, $types, $params) {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Send email
function send_email($to, $subject, $message) {
    $headers = "From: no-reply@alliedsteel.com\r\n";
    $headers .= "Reply-To: support@alliedsteel.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Redirect with message
function set_flash_message($type, $message) {
    session_start();
    $_SESSION["flash_$type"] = $message;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getRoleName($role_id) {
    $roles = [
        1 => 'Admin',
        2 => 'Manager',
        3 => 'Salesperson',
        4 => 'Inventory Manager',
    ];
    return $roles[$role_id] ?? 'Unknown';
}

?>
