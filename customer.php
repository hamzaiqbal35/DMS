<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
require_once 'inc/config/database.php';
require_once 'inc/helpers.php';

// Define base URL
$base_url = "http://localhost/DMS/";

// Get the requested page
$page = $_GET['page'] ?? 'dashboard';

// Define allowed pages for customer panel
$allowed_pages = [
    'landing',
    'login',
    'register',
    'dashboard',
    'catalogue',
    'product-details',
    'cart',
    'checkout',
    'my-orders',
    'order-details',
    'profile',
    'invoice',
    'forgotPassword',
    'resetPasswordForm',
    'returns-refund',
    'warranty-support',
    'faq',
    'customer-support',
    'privacy-policy',
    'terms-of-service',
    'cookie-policy',
    'sitemap'
];

// Check if customer is logged in
$customer_logged_in = isset($_SESSION['customer_user_id']);

// Set default page based on login status
if (empty($page)) {
    if ($customer_logged_in) {
        $page = 'dashboard';
    } else {
        $page = 'landing';
    }
}

// Validate page parameter
if (!in_array($page, $allowed_pages)) {
    header("Location: {$base_url}customer.php?page=login");
    exit();
}

// Check if customer is logged in for protected pages
$protected_pages = [
    'dashboard',
    'cart',
    'checkout',
    'my-orders',
    'order-details',
    'profile',
    'invoice'
];

$public_pages = [
    'landing',
    'login',
    'register',
    'forgotPassword',
    'resetPasswordForm'
];

// Check authentication for protected pages
if (in_array($page, $protected_pages) && !$customer_logged_in) {
    header("Location: {$base_url}customer.php?page=login");
    exit();
}

// Redirect logged-in customers away from login/register pages
if (in_array($page, ['login', 'register']) && $customer_logged_in) {
    header("Location: customer.php?page=dashboard");
    exit();
}

// Redirect logged-in customers away from landing page
if ($page === 'landing' && $customer_logged_in) {
    header("Location: customer.php?page=dashboard");
    exit();
}

// Include the appropriate page
$page_file = "views/customer/{$page}.php";

if (file_exists($page_file)) {
    include $page_file;
} else {
    // Fallback to appropriate default page
    if ($customer_logged_in) {
        include "views/customer/dashboard.php";
    } else {
        include "views/customer/landing.php";
    }
}
?> 