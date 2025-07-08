<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
require_once __DIR__ . '/../../inc/customer/customer-auth.php';
require_customer_jwt_auth();
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

    // Delete the customer
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
