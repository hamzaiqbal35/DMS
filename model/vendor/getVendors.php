<?php
// Prevent any output before JSON response
ob_start();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include database configuration
require_once '../../inc/config/database.php';

try {
    // Log the start of the query
    error_log("Starting vendor query execution", 3, '../../logs/error_log.log');

    // First, check if the vendors table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'vendors'");
    if ($checkTable->rowCount() == 0) {
        throw new Exception("Vendors table does not exist");
    }

    // Check if there are any vendors
    $countQuery = "SELECT COUNT(*) as count FROM vendors";
    $countStmt = $pdo->query($countQuery);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    error_log("Found {$count} vendors in database", 3, '../../logs/error_log.log');

    $query = "
        SELECT 
            vendor_id,
            vendor_name,
            contact_person,
            phone,
            email,
            address,
            city,
            state,
            zip_code,
            status
        FROM 
            vendors
        ORDER BY 
            vendor_name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the query results
    error_log("Query executed successfully. Found " . count($vendors) . " vendors", 3, '../../logs/error_log.log');

    // Clear any previous output
    ob_clean();
    
    // Ensure we have a valid array, even if empty
    if ($vendors === false) {
        $vendors = [];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $vendors
    ]);
} catch (PDOException $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("Database Error in getVendors: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while fetching vendors',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    error_log("General Error in getVendors: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch vendor list',
        'details' => $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();
