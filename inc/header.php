<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
// Restore JWT from cookie to session if not already set (for admin)
if (!isset($_SESSION['jwt_token']) && isset($_COOKIE['jwt_token'])) {
    $_SESSION['jwt_token'] = $_COOKIE['jwt_token'];
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

// Get profile picture path from database for the current user
$profile_picture = $base_url . 'assets/images/logo.png'; // Default

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/database.php';
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['profile_picture'])) {
        // If not an absolute URL, prepend base_url
        if (!preg_match('/^https?:\\/\\//', $row['profile_picture']) && strpos($row['profile_picture'], '/') !== 0) {
            $profile_picture = $base_url . ltrim($row['profile_picture'], '/');
        } else {
            $profile_picture = $row['profile_picture'];
        }
    }
}

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/animations.css">

    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        <div class="dropdown ms-3 d-flex align-items-center">
            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;border:2px solid #e9ecef;box-shadow:0 2px 6px rgba(0,0,0,0.07);">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" 
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user"></i> <?= htmlspecialchars($user_role) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="<?= $base_url ?>views/profile.php">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <!-- Logout -->
                <li>
                    <form id="logoutForm" action="<?= $base_url ?>model/login/logout.php" method="POST" style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    </form>
                    <!-- Logout button in navigation -->
                    <a href="#" onclick="logout(); return false;" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
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
