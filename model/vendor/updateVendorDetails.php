<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');
session_start();

try {
    // Ensure POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Sanitize and validate input
    $vendor_id      = $_POST['vendor_id'] ?? null;
    $vendor_name    = trim($_POST['vendor_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state'] ?? '');
    $zip_code       = trim($_POST['zip_code'] ?? '');

    // Required fields check
    if (
        empty($vendor_id) || empty($vendor_name) || empty($phone) ||
        empty($address) || empty($city)
    ) {
        throw new Exception("Required fields are missing.");
    }

    // Validate numeric ID
    if (!is_numeric($vendor_id)) {
        throw new Exception("Invalid vendor ID.");
    }

    // Prepare update statement
    $stmt = $pdo->prepare("
        UPDATE vendors SET
            vendor_name = ?,
            contact_person = ?,
            phone = ?,
            email = ?,
            address = ?,
            city = ?,
            state = ?,
            zip_code = ?
        WHERE vendor_id = ?
    ");

    $stmt->execute([
        $vendor_name,
        $contact_person,
        $phone,
        $email,
        $address,
        $city,
        $state,
        $zip_code,
        $vendor_id
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Vendor updated successfully."
    ]);
    
} catch (Exception $e) {
    error_log("Update Vendor Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update vendor. " . $e->getMessage()
    ]);
}
