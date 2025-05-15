<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

try {
    // ✅ Fetch customers in ascending order by customer_id
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY customer_id ASC");
    $customers = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $customers
    ]);
} catch (PDOException $e) {
    error_log("Fetch Customers Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch customers"
    ]);
}
catch (Exception $e) {
    error_log("General Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}
catch (Error $e) {
    error_log("Fatal Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "A fatal error occurred."
    ]);
}
catch (TypeError $e) {
    error_log("Type Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "A type error occurred."
    ]);
}
catch (Throwable $e) {
    error_log("Throwable Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}
catch (Exception $e) {
    error_log("Exception Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}
catch (Error $e) {
    error_log("Error Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}
catch (TypeError $e) {
    error_log("Type Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}
catch (Throwable $e) {
    error_log("Throwable Error: " . $e->getMessage(), 3, "../../error_log.log");
    echo json_encode([
        "status" => "error",
        "message" => "An unexpected error occurred."
    ]);
}