<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Sanitize and fetch form data
$item_number     = trim($_POST['item_number'] ?? '');
$item_name       = trim($_POST['item_name'] ?? '');
$category_id     = intval($_POST['category_id'] ?? 0);
$unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
$unit_price      = floatval($_POST['unit_price'] ?? 0);
$customer_price  = !empty($_POST['customer_price']) ? floatval($_POST['customer_price']) : null;
$minimum_stock   = floatval($_POST['minimum_stock'] ?? 0);
$description     = trim($_POST['description'] ?? '');
$status          = strtolower(trim($_POST['status'] ?? 'active'));

// Customer panel control fields
$show_on_website = intval($_POST['show_on_website'] ?? 1);
$is_featured     = intval($_POST['is_featured'] ?? 0);
$seo_title       = trim($_POST['seo_title'] ?? '');
$seo_description = trim($_POST['seo_description'] ?? '');

// Validation
if (
    empty($item_number) || empty($item_name) || $category_id <= 0 ||
    empty($unit_of_measure) || $unit_price <= 0 || $minimum_stock < 0 ||
    !in_array($status, ['active', 'inactive'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Insert Item Error: All required fields must be filled.']);
    exit;
}

try {
    // Check for duplicate item number
    $checkQuery = "SELECT item_id FROM inventory WHERE item_number = ?";
    $checkStmt  = $pdo->prepare($checkQuery);
    $checkStmt->execute([$item_number]);

    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Item number already exists.']);
        exit;
    }

    // Insert item
    $insertQuery = "
        INSERT INTO inventory (
            item_number, item_name, category_id, unit_of_measure,
            unit_price, customer_price, minimum_stock, description, status,
            show_on_website, is_featured, seo_title, seo_description
        ) VALUES (
            :item_number, :item_name, :category_id, :unit_of_measure,
            :unit_price, :customer_price, :minimum_stock, :description, :status,
            :show_on_website, :is_featured, :seo_title, :seo_description
        )
    ";
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        ':item_number'     => $item_number,
        ':item_name'       => $item_name,
        ':category_id'     => $category_id,
        ':unit_of_measure' => $unit_of_measure,
        ':unit_price'      => $unit_price,
        ':customer_price'  => $customer_price,
        ':minimum_stock'   => $minimum_stock,
        ':description'     => $description ?: null,
        ':status'          => $status,
        ':show_on_website' => $show_on_website,
        ':is_featured'     => $is_featured,
        ':seo_title'       => $seo_title ?: null,
        ':seo_description' => $seo_description ?: null
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Item added successfully.']);
} catch (PDOException $e) {
    error_log("Insert Item Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
