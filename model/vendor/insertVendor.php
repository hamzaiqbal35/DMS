<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Sanitize and fetch data
    $vendor_name     = sanitize_input($_POST['vendor_name'] ?? '');
    $contact_person  = sanitize_input($_POST['contact_person'] ?? '');
    $phone           = sanitize_input($_POST['phone'] ?? '');
    $email           = sanitize_input($_POST['email'] ?? '');
    $address         = sanitize_input($_POST['address'] ?? '');
    $city            = sanitize_input($_POST['city'] ?? '');
    $state           = sanitize_input($_POST['state'] ?? '');
    $zip_code        = sanitize_input($_POST['zip_code'] ?? '');
    $status          = sanitize_input($_POST['status'] ?? 'active');

    // Required field validation
    if (empty($vendor_name) || empty($phone) || empty($address) || empty($city)) {
        throw new Exception("Please fill in all required fields: Vendor Name, Phone, Address, and City.");
    }

    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO vendors 
        (vendor_name, contact_person, phone, email, address, city, state, zip_code, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $vendor_name,
        $contact_person ?: null,
        $phone,
        $email ?: null,
        $address,
        $city,
        $state ?: null,
        $zip_code ?: null,
        $status
    ]);

    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Vendor added successfully."
        ]);
    } else {
        throw new Exception("Failed to insert vendor.");
    }

} catch (Exception $e) {
    error_log("Insert Vendor Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Insert failed: " . $e->getMessage()
    ]);
}
