<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    // Fetch user list with role name
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id AS id,
            u.full_name,
            u.email,
            u.role_id,
            r.role_name AS role,
            u.created_at
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.created_at DESC
    ");
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);
} catch (PDOException $e) {
    error_log("Fetch User List Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch user list."
    ]);
}
