<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1); // Log errors instead

// Prevent any output before JSON response
ob_start();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include database configuration
require_once '../../inc/config/database.php';

try {
    // Log the start of the query
    error_log("Starting materials query execution", 3, '../../logs/error_log.log');

    // First, check if the raw_materials table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'raw_materials'");
    if ($checkTable->rowCount() == 0) {
        throw new Exception("Raw materials table does not exist");
    }

    // Check if there are any materials
    $countQuery = "SELECT COUNT(*) as count FROM raw_materials";
    $countStmt = $pdo->query($countQuery);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    error_log("Found {$count} materials in database", 3, '../../logs/error_log.log');

    $query = "
        SELECT 
            material_id,
            material_code,
            material_name,
            description,
            unit_of_measure as unit,
            status
        FROM 
            raw_materials
        ORDER BY 
            material_name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the query results
    error_log("Query executed successfully. Found " . count($materials) . " materials", 3, '../../logs/error_log.log');

    // Clear any previous output
    ob_clean();
    
    // Ensure we have a valid array, even if empty
    if ($materials === false) {
        $materials = [];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $materials
    ]);
} catch (PDOException $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("Database Error in getItems: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while fetching materials',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("General Error in getItems: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch materials list',
        'details' => $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();
