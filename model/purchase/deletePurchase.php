<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

// Validate request type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Parse and sanitize input
$input = json_decode(file_get_contents("php://input"), true);
$purchase_id = intval($input['purchase_id'] ?? 0);

// Validate ID
if ($purchase_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid purchase ID.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Optional: check if purchase exists
    $check = $pdo->prepare("SELECT * FROM purchases WHERE purchase_id = ?");
    $check->execute([$purchase_id]);
    if ($check->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Purchase not found.']);
        $pdo->rollBack();
        exit;
    }

    // Delete purchase_details (handled by ON DELETE CASCADE, but good to be explicit)
    $pdo->prepare("DELETE FROM purchase_details WHERE purchase_id = ?")->execute([$purchase_id]);

    // Delete main purchase record
    $pdo->prepare("DELETE FROM purchases WHERE purchase_id = ?")->execute([$purchase_id]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Purchase deleted successfully.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete Purchase Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error during deletion.']);
}
