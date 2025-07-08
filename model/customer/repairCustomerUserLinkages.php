<?php
// repairCustomerUserLinkages.php
// Run this script manually to repair broken/missing admin_customer_id linkages in customer_users
require_once '../../inc/config/database.php';

function log_msg($msg) {
    echo $msg . "\n";
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM customer_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fixed = 0;
    $created = 0;
    $skipped = 0;
    foreach ($users as $user) {
        $user_id = $user['customer_user_id'];
        $email = $user['email'];
        $full_name = $user['full_name'];
        $phone = $user['phone'];
        $address = $user['address'];
        $city = $user['city'];
        $state = $user['state'];
        $zip_code = $user['zip_code'];
        $admin_customer_id = $user['admin_customer_id'];
        $needs_fix = false;
        if (!$admin_customer_id) {
            $needs_fix = true;
        } else {
            // Check if customer exists
            $stmt2 = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
            $stmt2->execute([$admin_customer_id]);
            if (!$stmt2->fetch()) {
                $needs_fix = true;
            }
        }
        if ($needs_fix) {
            // Try to find customer by email
            $stmt3 = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $stmt3->execute([$email]);
            $customer = $stmt3->fetch();
            if ($customer) {
                $new_customer_id = $customer['customer_id'];
                $stmt4 = $pdo->prepare("UPDATE customer_users SET admin_customer_id = ? WHERE customer_user_id = ?");
                $stmt4->execute([$new_customer_id, $user_id]);
                log_msg("Linked user #$user_id to existing customer #$new_customer_id by email ($email)");
                $fixed++;
            } else {
                // Create new customer record
                $stmt5 = $pdo->prepare("INSERT INTO customers (customer_name, email, phone, address, city, state, zip_code, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $status = $user['status'] ?? 'active';
                $stmt5->execute([$full_name, $email, $phone, $address, $city, $state, $zip_code, $status]);
                $new_customer_id = $pdo->lastInsertId();
                $stmt6 = $pdo->prepare("UPDATE customer_users SET admin_customer_id = ? WHERE customer_user_id = ?");
                $stmt6->execute([$new_customer_id, $user_id]);
                log_msg("Created new customer #$new_customer_id and linked user #$user_id ($email)");
                $created++;
            }
        } else {
            $skipped++;
        }
    }
    log_msg("---\nRepair complete. $fixed users linked to existing customers, $created new customers created, $skipped users already linked.");
} catch (Exception $e) {
    log_msg("Error: " . $e->getMessage());
} 