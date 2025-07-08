<?php
require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';

// Output HTML response
function show_message($title, $message, $success = true) {
    $color = $success ? 'success' : 'danger';
    echo "<html><head><title>$title</title><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'></head><body class='bg-light'><div class='container py-5'><div class='row justify-content-center'><div class='col-md-8'><div class='alert alert-$color shadow-lg'><h4 class='alert-heading mb-3'>$title</h4><p class='mb-0'>$message</p></div><a href='/DMS/customer.php?page=login' class='btn btn-primary mt-3'>Go to Login</a></div></div></div></body></html>";
    exit();
}

$token = $_GET['token'] ?? '';
if (!$token) {
    show_message('Invalid Link', 'No verification token provided.', false);
}

try {
    $stmt = $pdo->prepare('SELECT customer_user_id FROM customer_email_verifications WHERE token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        show_message('Invalid or Expired Link', 'This verification link is invalid or has already been used.', false);
    }
    $customer_user_id = $row['customer_user_id'];
    // Set email_verified = 1
    $stmt = $pdo->prepare('UPDATE customer_users SET email_verified = 1, status = "active" WHERE customer_user_id = ?');
    $stmt->execute([$customer_user_id]);
    // Delete the token
    $stmt = $pdo->prepare('DELETE FROM customer_email_verifications WHERE token = ?');
    $stmt->execute([$token]);
    show_message('Email Verified', 'Your email has been successfully verified! You can now log in.');
} catch (Exception $e) {
    show_message('Error', 'An error occurred during verification. Please try again later.', false);
} 