<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
require_once '../../inc/config/auth.php';
require_jwt_auth(); // This checks for admin JWT
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $customer_id = $_POST['customer_id'] ?? null;

    if (!$customer_id) {
        throw new Exception("Customer ID is required.");
    }

    $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    echo json_encode([
        "status" => "success",
        "message" => "Customer deleted successfully."
    ]);
} catch (Exception $e) {
    error_log("Delete Customer Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete customer. " . $e->getMessage()
    ]);
} 