<?php
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['vendor_id']) || empty($_GET['vendor_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vendor ID is required.'
    ]);
    exit;
}

$vendor_id = $_GET['vendor_id'];

try {
    $query = "
        SELECT 
            rm.material_id,
            rm.material_name,
            rm.material_code,
            rm.unit,
            rm.current_stock
        FROM 
            raw_materials rm
        INNER JOIN 
            vendor_materials vm ON rm.material_id = vm.material_id
        WHERE 
            vm.vendor_id = :vendor_id
        ORDER BY 
            rm.material_name ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':vendor_id', $vendor_id, PDO::PARAM_INT);
    $stmt->execute();

    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $materials
    ]);

} catch (Exception $e) {
    error_log("Fetch Vendor Items Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch vendor items.',
        'details' => $e->getMessage()
    ]);
}
