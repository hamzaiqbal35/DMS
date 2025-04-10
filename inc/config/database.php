<?php
// Database Configuration
$host = "localhost";
$dbname = "allied_steel_dms"; // Ensure this matches your actual database name
$username = "root"; // Change if using a different database user
$password = ""; // Change if using a password

try {
    // Set up a secure PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable error reporting
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
        PDO::ATTR_EMULATE_PREPARES => false, // Disable emulation to prevent SQL injection
    ]);
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage(), 3, "../../error_log.log");
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}
?>

