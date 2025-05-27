<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');
session_start();

try {
    // Fetch all vendor details
    $stmt = $pdo->prepare("SELECT vendor_id, vendor_name, contact_person, phone, email, address, city, state, zip_code, status FROM vendors ORDER BY vendor_id ASC");
    $stmt->execute();
    $vendors = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $vendors
    ]);
} catch (Exception $e) {
    error_log("Fetch Vendor IDs Error: " . $e->getMessage(), 3, "../../error_log.log");

    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch vendor records"
    ]);
}
