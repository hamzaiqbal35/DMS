<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define base URL (Adjust if needed)
$base_url = "http://localhost/DMS/";

// Example: Getting user info (Assuming you store user data in session)
$user_name = $_SESSION['username'] ?? 'Guest';
$role_id = $_SESSION['role_id'] ?? null;

// Manually map role_id to role names
$role_map = [
    1 => 'Admin',
    2 => 'Manager',
    3 => 'Salesperson',
    4 => 'Inventory Manager',
];

// Get the role name based on role_id
$user_role = $role_map[$role_id] ?? 'Unknown Role';

// CSRF Token Generation (Ensure it's defined in helpers.php)
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allied Steel Works - Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome (For Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/animations.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3">
    <div class="container-fluid">
        <!-- Navbar Brand -->
        <a class="navbar-brand" href="<?= $base_url ?>views/dashboard.php">
            <img src="<?= $base_url ?>assets/images/logo.png" alt="Logo" class="logo-img">
            <span>Allied Steel Works</span>
        </a>
        <!-- Current Date & Time -->
        <span id="currentTime" class="fw-semibold text-secondary mx-auto"></span>

        <!-- User Profile Dropdown -->
        <div class="dropdown ms-3">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" 
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user"></i> <?= htmlspecialchars($user_role) ?>
            </button>

            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="<?= $base_url ?>views/profile.php">Profile</a></li>
                <li><a class="dropdown-item" href="<?= $base_url ?>views/settings.php">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <!-- Logout -->
                <li>
                    <form id="logoutForm" action="<?= $base_url ?>model/login/logout.php" method="POST" style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    </form>
                    <a class="dropdown-item text-danger" href="#" onclick="document.getElementById('logoutForm').submit();">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- External JS -->
<script src="<?= $base_url ?>assets/js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>
