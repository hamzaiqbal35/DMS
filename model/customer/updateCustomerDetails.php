<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');
session_start();

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit;
    }

    // Sanitize and get input values
    $customer_id     = sanitize_input($_POST['customer_id'] ?? '');
    $customer_name   = sanitize_input($_POST['customer_name'] ?? '');
    $contact_person  = sanitize_input($_POST['contact_person'] ?? '');
    $phone           = sanitize_input($_POST['phone'] ?? '');
    $email           = sanitize_input($_POST['email'] ?? '');
    $address         = sanitize_input($_POST['address'] ?? '');
    $city            = sanitize_input($_POST['city'] ?? '');
    $state           = sanitize_input($_POST['state'] ?? '');
    $zip_code        = sanitize_input($_POST['zip_code'] ?? '');
    $status          = sanitize_input($_POST['status'] ?? 'active');

    // Validate required fields
    if (empty($customer_id) || empty($customer_name) || empty($phone) || empty($address) || empty($city)) {
        echo json_encode(["status" => "error", "message" => "Please fill all required fields."]);
        exit;
    }

    // Update query
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET 
            customer_name = ?, 
            contact_person = ?, 
            phone = ?, 
            email = ?, 
            address = ?, 
            city = ?, 
            state = ?, 
            zip_code = ?,
            status = ?
        WHERE customer_id = ?
    ");

    $result = $stmt->execute([
        $customer_name,
        $contact_person,
        $phone,
        $email,
        $address,
        $city,
        $state,
        $zip_code,
        $status,
        $customer_id
    ]);

    // Also update status in customer_users table for all users linked to this customer
    $stmt2 = $pdo->prepare("UPDATE customer_users SET status = ? WHERE admin_customer_id = ?");
    $stmt2->execute([$status, $customer_id]);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Customer updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update customer."]);
    }

} catch (PDOException $e) {
    error_log("Update Customer Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode(["status" => "error", "message" => "Database error occurred."]);
}
