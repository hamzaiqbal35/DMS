<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
// Include database connection and helpers
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers.php';

// Define base URL
$base_url = "http://localhost/DMS/";

// Get customer info if logged in
$customer_name = $_SESSION['customer_full_name'] ?? 'Guest';
$customer_email = $_SESSION['customer_email'] ?? '';
$customer_id = $_SESSION['customer_user_id'] ?? null;

// Get cart count for logged-in customers
$cart_count = 0;
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch();
        $cart_count = $result['count'] ?? 0;
    } catch (Exception $e) {
        // Silently handle error
    }
}

// Restore JWT from cookie to session if not already set
if (!isset($_SESSION['customer_jwt_token']) && isset($_COOKIE['customer_jwt_token'])) {
    $_SESSION['customer_jwt_token'] = $_COOKIE['customer_jwt_token'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Allied Steel Works - Customer Portal' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Custom Customer CSS -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/customer/css/customer-styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/customer/css/responsive.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="customer-body" <?= $customer_id ? 'data-customer-logged-in="true"' : '' ?>>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_url ?>customer.php<?= $customer_id ? '?page=dashboard' : '?page=landing' ?>">
            <img src="<?= $base_url ?>assets/images/logo.png" alt="Allied Steel Works" height="40">
            <span class="ms-2">Allied Steel Works</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="customerNavbar">
            <ul class="navbar-nav me-auto">
                <?php if ($customer_id): ?>
                <!-- Logged-in customer navigation -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=dashboard">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=catalogue">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=my-orders">My Orders</a>
                </li>
                <?php else: ?>
                <!-- Guest navigation -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=landing">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=catalogue">Products</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if ($customer_id): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= $base_url ?>customer.php?page=cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $cart_count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($customer_name) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $base_url ?>customer.php?page=profile">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item customer-logout-link" href="#">Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=login">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>customer.php?page=register">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Container -->
<div class="customer-content">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['customer_message'])): ?>
    <div class="alert alert-<?= $_SESSION['customer_message_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['customer_message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
        unset($_SESSION['customer_message']);
        unset($_SESSION['customer_message_type']);
    endif; 
    ?>