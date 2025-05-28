<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    // Get vendor ID from GET request (optional if filtering by vendor is enabled)
    $vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

    // If you want vendor-specific materials, add your JOIN logic here
    // Currently returning all raw materials (as universal for all vendors)
    $stmt = $pdo->prepare("
        SELECT 
            material_id,
            material_code,
            material_name,
            unit_of_measure,
            status
        FROM raw_materials
        WHERE status = 'active'
        ORDER BY material_name ASC
    ");
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($materials && count($materials) > 0) {
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
    error_log("fetchVendorItems.php Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error while fetching raw materials.'
    ]);
}
