<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
// Restore JWT from cookie if not set
if (!isset($_SESSION['customer_jwt_token']) && isset($_COOKIE['customer_jwt_token'])) {
    $_SESSION['customer_jwt_token'] = $_COOKIE['customer_jwt_token'];
}
// Decode JWT and set session variables
if (isset($_SESSION['customer_jwt_token'])) {
    require_once '../../inc/helpers.php';
    $decoded = decode_customer_jwt($_SESSION['customer_jwt_token']);
    if ($decoded && isset($decoded->data->customer_user_id)) {
        $_SESSION['customer_user_id'] = $decoded->data->customer_user_id;
        $_SESSION['customer_username'] = $decoded->data->username;
        $_SESSION['customer_email'] = $decoded->data->email;
        $_SESSION['customer_full_name'] = $decoded->data->full_name;
    }
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $customer_id = $_SESSION['customer_user_id'] ?? null;
    if (!$customer_id) {
        throw new Exception("Customer not logged in.");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $state = trim($input['state'] ?? '');
    $zip_code = trim($input['zip_code'] ?? '');

    if (empty($full_name) || empty($email) || empty($phone)) {
        throw new Exception("Full name, email, and phone are required.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if email already exists for another customer
    $stmt = $pdo->prepare("
        SELECT customer_user_id 
        FROM customer_users 
        WHERE email = ? AND customer_user_id != ?
    ");
    $stmt->execute([$email, $customer_id]);
    if ($stmt->fetch()) {
        throw new Exception("Email already exists. Please use a different email.");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update customer user profile
        $stmt = $pdo->prepare("
            UPDATE customer_users 
            SET full_name = ?, email = ?, phone = ?, address = ?, 
                city = ?, state = ?, zip_code = ?, updated_at = NOW()
            WHERE customer_user_id = ?
        ");

        $result = $stmt->execute([
            $full_name,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $zip_code,
            $customer_id
        ]);

        if (!$result) {
            throw new Exception("Failed to update profile.");
        }

        // Also update admin customers table if linked
        $stmt = $pdo->prepare("
            SELECT admin_customer_id 
            FROM customer_users 
            WHERE customer_user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();

        if ($customer && $customer['admin_customer_id']) {
            $stmt = $pdo->prepare("
                UPDATE customers 
                SET customer_name = ?, contact_person = ?, phone = ?, email = ?, 
                    address = ?, city = ?, state = ?, zip_code = ?, updated_at = NOW()
                WHERE customer_id = ?
            ");

            $stmt->execute([
                $full_name,
                $full_name, // contact_person same as full_name
                $phone,
                $email,
                $address,
                $city,
                $state,
                $zip_code,
                $customer['admin_customer_id']
            ]);
        }

        // Update session variables
        $_SESSION['customer_full_name'] = $full_name;
        $_SESSION['customer_email'] = $email;

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 