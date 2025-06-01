<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve inputs
    $material_code   = trim($_POST['material_code'] ?? '');
    $material_name   = trim($_POST['material_name'] ?? '');
    $unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
    $status          = strtolower(trim($_POST['status'] ?? 'active'));
    $description     = trim($_POST['description'] ?? '');
    $minimum_stock   = floatval($_POST['minimum_stock'] ?? 0);

    // Validate required inputs
    if (empty($material_code) || empty($material_name) || empty($unit_of_measure)) {
        echo json_encode(['status' => 'error', 'message' => 'Material code, name, and unit of measure are required.']);
        exit;
    }

    if (!in_array($status, ['active', 'inactive'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status provided.']);
        exit;
    }

    // Validate minimum stock (optional, but good practice)
    if ($minimum_stock < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Minimum stock cannot be negative.']);
        exit;
    }

    try {
        // Check for duplicate material code
        $checkQuery = "SELECT material_id FROM raw_materials WHERE material_code = ?";
        $checkStmt  = $pdo->prepare($checkQuery);
        $checkStmt->execute([$material_code]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Material code already exists.']);
            exit;
        }

        // Insert new material
        $insertQuery = "INSERT INTO raw_materials (material_code, material_name, description, unit_of_measure, minimum_stock, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$material_code, $material_name, $description, $unit_of_measure, $minimum_stock, $status]);

        echo json_encode(['status' => 'success', 'message' => 'Raw material added successfully.']);
    } catch (PDOException $e) {
        error_log("Database Error in insertMaterial.php: " . $e->getMessage(), 3, '../../error_log.log');
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again later.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
