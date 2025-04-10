<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required includes
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

// Set Content-Type to JSON
header('Content-Type: application/json');

try {
    // Query to fetch users
    $sql = "SELECT id, name, email, role_id, created_at FROM users ORDER BY created_at DESC";
    $result = $pdo->query($sql);

    $users = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id'         => $row['id'],
                'name'       => htmlspecialchars($row['name']),
                'email'      => htmlspecialchars($row['email']),
                'role_id'    => $row['role_id'],
                'created_at' => date('Y-m-d', strtotime($row['created_at']))
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $users
    ]);
} catch (Exception $e) {
    error_log("Fetch User List Error: " . $e->getMessage(), 3, "../../inc/logs/error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch user list.'
    ]);
}
?>
