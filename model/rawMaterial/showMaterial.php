<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            material_id, 
            material_code, 
            material_name, 
            description, 
            unit_of_measure, 
            current_stock, 
            minimum_stock, 
            status, 
            created_at 
        FROM raw_materials 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($materials) > 0) {
        echo json_encode([
            'status' => 'success',
            'data' => $materials
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'No raw materials found.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error in showMaterial.php: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch raw materials.'
    ]);
}
