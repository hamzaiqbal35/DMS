<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../inc/config/database.php';
header('Content-Type: application/json');

try {
    $purchase_id = intval($_POST['purchase_id'] ?? 0);
    if ($purchase_id <= 0) throw new Exception('Invalid purchase ID.');

    // Get current invoice file
    $stmt = $pdo->prepare("SELECT invoice_file FROM purchases WHERE purchase_id = ?");
    $stmt->execute([$purchase_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['invoice_file']) throw new Exception('No invoice attached.');

    $file_path = '../../' . $row['invoice_file'];
    if (file_exists($file_path)) unlink($file_path);

    // Remove reference from DB
    $update = $pdo->prepare("UPDATE purchases SET invoice_file = NULL WHERE purchase_id = ?");
    $update->execute([$purchase_id]);

    echo json_encode(['status' => 'success', 'message' => 'Invoice deleted.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}