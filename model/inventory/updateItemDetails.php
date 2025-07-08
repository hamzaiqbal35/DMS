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

// Sanitize & validate input
$item_id         = intval($_POST['item_id'] ?? 0);
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

if (
    $item_id <= 0 || empty($item_number) || empty($item_name) || $category_id <= 0 ||
    empty($unit_of_measure) || $unit_price <= 0 || $minimum_stock < 0 ||
    !in_array($status, ['active', 'inactive'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Update Item Error: All required fields must be filled.']);
    exit;
}

try {
    // Check if item exists
    $checkStmt = $pdo->prepare("SELECT item_id FROM inventory WHERE item_id = ?");
    $checkStmt->execute([$item_id]);
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
        exit;
    }

    // Check for duplicate item number (excluding current item)
    $duplicateCheck = $pdo->prepare("SELECT item_id FROM inventory WHERE item_number = ? AND item_id != ?");
    $duplicateCheck->execute([$item_number, $item_id]);
    if ($duplicateCheck->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Another item with this number already exists.']);
        exit;
    }

    // Update the item
    $updateStmt = $pdo->prepare("
        UPDATE inventory SET 
            item_number = :item_number,
            item_name = :item_name,
            category_id = :category_id,
            unit_of_measure = :unit_of_measure,
            unit_price = :unit_price,
            customer_price = :customer_price,
            minimum_stock = :minimum_stock,
            description = :description,
            status = :status,
            show_on_website = :show_on_website,
            is_featured = :is_featured,
            seo_title = :seo_title,
            seo_description = :seo_description
        WHERE item_id = :item_id
    ");

    $updateStmt->execute([
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
        ':seo_description' => $seo_description ?: null,
        ':item_id'         => $item_id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Item updated successfully.']);
} catch (PDOException $e) {
    error_log("Update Item Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}
