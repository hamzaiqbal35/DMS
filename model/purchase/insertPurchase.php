<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

// Validate incoming request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $db->beginTransaction();

    // Step 1: Collect Purchase Data
    $purchaseNumber = $_POST['purchase_number'];
    $vendorId = $_POST['vendor_id'];
    $purchaseDate = $_POST['purchase_date'];
    $expectedDelivery = $_POST['expected_delivery'];
    $notes = $_POST['notes'] ?? '';
    $createdBy = $_POST['created_by'];
    $materials = json_decode($_POST['materials'], true); // Expects array of materials

    if (!$purchaseNumber || !$vendorId || !$purchaseDate || !$createdBy || empty($materials)) {
        throw new Exception("Missing required fields");
    }

    // Step 2: Insert into purchases
    $insertPurchaseSQL = "
        INSERT INTO purchases (purchase_number, vendor_id, purchase_date, expected_delivery, notes, created_by)
        VALUES (:purchase_number, :vendor_id, :purchase_date, :expected_delivery, :notes, :created_by)
    ";
    $stmt = $db->prepare($insertPurchaseSQL);
    $stmt->execute([
        ':purchase_number'   => $purchaseNumber,
        ':vendor_id'         => $vendorId,
        ':purchase_date'     => $purchaseDate,
        ':expected_delivery' => $expectedDelivery,
        ':notes'             => $notes,
        ':created_by'        => $createdBy
    ]);

    $purchaseId = $db->lastInsertId();

    $totalAmount = 0;

    // Step 3: Insert purchase_details
    $insertDetailSQL = "
        INSERT INTO purchase_details (purchase_id, material_id, quantity, unit_price, discount, tax, total_price)
        VALUES (:purchase_id, :material_id, :quantity, :unit_price, :discount, :tax, :total_price)
    ";
    $stmtDetail = $db->prepare($insertDetailSQL);

    foreach ($materials as $material) {
        $materialId = $material['material_id'];
        $quantity = $material['quantity'];
        $unitPrice = $material['unit_price'];
        $discount = $material['discount'] ?? 0;
        $tax = $material['tax'] ?? 0;

        $gross = $quantity * $unitPrice;
        $discountAmount = ($gross * $discount) / 100;
        $taxAmount = ($gross * $tax) / 100;
        $netTotal = $gross - $discountAmount + $taxAmount;

        $stmtDetail->execute([
            ':purchase_id'  => $purchaseId,
            ':material_id'  => $materialId,
            ':quantity'     => $quantity,
            ':unit_price'   => $unitPrice,
            ':discount'     => $discount,
            ':tax'          => $tax,
            ':total_price'  => $netTotal
        ]);

        $totalAmount += $netTotal;
    }

    // Step 4: Update total in purchases
    $updateTotalSQL = "UPDATE purchases SET total_amount = :total_amount WHERE purchase_id = :purchase_id";
    $stmt = $db->prepare($updateTotalSQL);
    $stmt->execute([
        ':total_amount' => $totalAmount,
        ':purchase_id'  => $purchaseId
    ]);

    $db->commit();

    echo json_encode(['status' => 'success', 'message' => 'Purchase added successfully']);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Insert Purchase Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert purchase', 'details' => $e->getMessage()]);
}
?>
<?php
require_once '../../inc/config/database.php';

header('Content-Type: application/json');

// Validate incoming request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $db->beginTransaction();

    // Step 1: Collect Purchase Data
    $purchaseNumber = $_POST['purchase_number'];
    $vendorId = $_POST['vendor_id'];
    $purchaseDate = $_POST['purchase_date'];
    $expectedDelivery = $_POST['expected_delivery'];
    $notes = $_POST['notes'] ?? '';
    $createdBy = $_POST['created_by'];
    $materials = json_decode($_POST['materials'], true); // Expects array of materials

    if (!$purchaseNumber || !$vendorId || !$purchaseDate || !$createdBy || empty($materials)) {
        throw new Exception("Missing required fields");
    }

    // Step 2: Insert into purchases
    $insertPurchaseSQL = "
        INSERT INTO purchases (purchase_number, vendor_id, purchase_date, expected_delivery, notes, created_by)
        VALUES (:purchase_number, :vendor_id, :purchase_date, :expected_delivery, :notes, :created_by)
    ";
    $stmt = $db->prepare($insertPurchaseSQL);
    $stmt->execute([
        ':purchase_number'   => $purchaseNumber,
        ':vendor_id'         => $vendorId,
        ':purchase_date'     => $purchaseDate,
        ':expected_delivery' => $expectedDelivery,
        ':notes'             => $notes,
        ':created_by'        => $createdBy
    ]);

    $purchaseId = $db->lastInsertId();

    $totalAmount = 0;

    // Step 3: Insert purchase_details
    $insertDetailSQL = "
        INSERT INTO purchase_details (purchase_id, material_id, quantity, unit_price, discount, tax, total_price)
        VALUES (:purchase_id, :material_id, :quantity, :unit_price, :discount, :tax, :total_price)
    ";
    $stmtDetail = $db->prepare($insertDetailSQL);

    foreach ($materials as $material) {
        $materialId = $material['material_id'];
        $quantity = $material['quantity'];
        $unitPrice = $material['unit_price'];
        $discount = $material['discount'] ?? 0;
        $tax = $material['tax'] ?? 0;

        $gross = $quantity * $unitPrice;
        $discountAmount = ($gross * $discount) / 100;
        $taxAmount = ($gross * $tax) / 100;
        $netTotal = $gross - $discountAmount + $taxAmount;

        $stmtDetail->execute([
            ':purchase_id'  => $purchaseId,
            ':material_id'  => $materialId,
            ':quantity'     => $quantity,
            ':unit_price'   => $unitPrice,
            ':discount'     => $discount,
            ':tax'          => $tax,
            ':total_price'  => $netTotal
        ]);

        $totalAmount += $netTotal;
    }

    // Step 4: Update total in purchases
    $updateTotalSQL = "UPDATE purchases SET total_amount = :total_amount WHERE purchase_id = :purchase_id";
    $stmt = $db->prepare($updateTotalSQL);
    $stmt->execute([
        ':total_amount' => $totalAmount,
        ':purchase_id'  => $purchaseId
    ]);

    $db->commit();

    echo json_encode(['status' => 'success', 'message' => 'Purchase added successfully']);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Insert Purchase Error: " . $e->getMessage(), 3, '../../logs/error_log.log');
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert purchase', 'details' => $e->getMessage()]);
}
?>
