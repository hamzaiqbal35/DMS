<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_name('admin_session');
session_start();

// Restore JWT from cookie if not set
if (!isset($_SESSION['jwt_token']) && isset($_COOKIE['jwt_token'])) {
    $_SESSION['jwt_token'] = $_COOKIE['jwt_token'];
}
// Decode JWT and set session variables
if (isset($_SESSION['jwt_token'])) {
    require_once '../../inc/helpers.php';
    $decoded = decode_jwt($_SESSION['jwt_token']);
    if ($decoded && isset($decoded->data->user_id)) {
        $_SESSION['user_id'] = $decoded->data->user_id;
        $_SESSION['username'] = $decoded->data->username;
        $_SESSION['email'] = $decoded->data->email;
        $_SESSION['role_id'] = $decoded->data->role_id;
    }
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Please login to continue.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Get and sanitize inputs
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity_to_add = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if ($item_id <= 0 || $quantity_to_add <= 0) {
        throw new Exception("Item and quantity are required and must be valid.");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Check if item exists and get current stock
        $check = $pdo->prepare("SELECT current_stock FROM inventory WHERE item_id = ?");
        $check->execute([$item_id]);
        $item = $check->fetch();

        if (!$item) {
            throw new Exception("Item not found.");
        }

        // Update stock
        $update = $pdo->prepare("UPDATE inventory SET current_stock = current_stock + ? WHERE item_id = ?");
        $result = $update->execute([$quantity_to_add, $item_id]);

        if (!$result) {
            throw new Exception("Failed to update stock.");
        }

        // Log the stock addition
        $logStmt = $pdo->prepare("
            INSERT INTO stock_logs (
                item_id, 
                quantity, 
                type, 
                reason
            ) VALUES (?, ?, 'addition', 'Manual stock addition')
        ");

        $logResult = $logStmt->execute([
            $item_id,
            $quantity_to_add
        ]);

        if (!$logResult) {
            throw new Exception("Failed to log stock change.");
        }

        // If everything is successful, commit the transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Stock added successfully.'
        ]);
    } catch (Exception $e) {
        // If anything fails, rollback the transaction
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Add Stock Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}