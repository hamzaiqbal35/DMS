<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get and sanitize input
$input = $_POST;

$material_id      = isset($input['material_id']) ? intval($input['material_id']) : 0;
$material_code    = isset($input['material_code']) ? trim($input['material_code']) : '';
$material_name    = isset($input['material_name']) ? trim($input['material_name']) : '';
$unit_of_measure  = isset($input['unit_of_measure']) ? trim($input['unit_of_measure']) : '';
$description      = isset($input['description']) ? trim($input['description']) : '';
$status           = isset($input['status']) ? trim($input['status']) : '';
$minimum_stock    = isset($input['minimum_stock']) ? floatval($input['minimum_stock']) : 0;

if (
    $material_id <= 0 || empty($material_code) || empty($material_name) || 
    empty($unit_of_measure) || !in_array($status, ['active', 'inactive'])
) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input data.'
    ]);
    exit;
}

// Validate minimum stock (optional, but good practice)
if ($minimum_stock < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Minimum stock cannot be negative.']);
    exit;
}

try {
    // Check if material exists
    $checkStmt = $pdo->prepare("SELECT * FROM raw_materials WHERE material_id = :material_id");
    $checkStmt->execute(['material_id' => $material_id]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Raw material not found.'
        ]);
        exit;
    }

    // Check for duplicate material code
    $codeCheckStmt = $pdo->prepare("SELECT * FROM raw_materials WHERE material_code = :material_code AND material_id != :material_id");
    $codeCheckStmt->execute([
        'material_code' => $material_code,
        'material_id' => $material_id
    ]);

    if ($codeCheckStmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Another material with this code already exists.'
        ]);
        exit;
    }

    // Update the raw material
    $updateStmt = $pdo->prepare("
        UPDATE raw_materials 
        SET material_code = :material_code,
            material_name = :material_name,
            unit_of_measure = :unit_of_measure,
            description = :description,
            minimum_stock = :minimum_stock,
            status = :status
        WHERE material_id = :material_id
    ");

    $updateStmt->execute([
        'material_code'     => $material_code,
        'material_name'     => $material_name,
        'unit_of_measure'   => $unit_of_measure,
        'description'       => $description,
        'minimum_stock'     => $minimum_stock,
        'status'            => $status,
        'material_id'       => $material_id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Raw material updated successfully.'
    ]);

} catch (PDOException $e) {
    error_log("Database Error in updateMaterial.php: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update raw material.'
    ]);
}
