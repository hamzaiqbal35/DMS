<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    // Check if POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit;
    }

    // Sanitize Inputs
    $customer_name   = sanitize_input($_POST['customer_name'] ?? '');
    $contact_person  = sanitize_input($_POST['contact_person'] ?? '');
    $phone           = sanitize_input($_POST['phone'] ?? '');
    $email           = sanitize_input($_POST['email'] ?? '');
    $address         = sanitize_input($_POST['address'] ?? '');
    $city            = sanitize_input($_POST['city'] ?? '');
    $state           = sanitize_input($_POST['state'] ?? '');
    $zip_code        = sanitize_input($_POST['zip_code'] ?? '');

    // Basic validation
    if (empty($customer_name) || empty($phone) || empty($address) || empty($city)) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        exit;
    }

    // Prepare insert query
    $stmt = $pdo->prepare("
        INSERT INTO customers 
        (customer_name, contact_person, phone, email, address, city, state, zip_code) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $customer_name,
        $contact_person,
        $phone,
        $email,
        $address,
        $city,
        $state,
        $zip_code
    ]);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Customer added successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add customer."]);
    }

} catch (PDOException $e) {
    error_log("Insert Customer Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(["status" => "error", "message" => "Database error occurred."]);
}
