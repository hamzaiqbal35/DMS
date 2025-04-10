<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "http://localhost/DMS/";
$role_id = $_SESSION['role_id'] ?? null;
$email = $_SESSION['email'] ?? 'Unknown User';
?>

<!-- Sidebar Navigation -->
<link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $base_url ?>assets/css/styles.css">
<link rel="stylesheet" href="<?= $base_url ?>assets/css/animations.css">

<nav id="sidebar" class="bg-dark">
    <ul class="nav flex-column py-3 px-2">
        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/manageCustomers.php">
                <i class="fas fa-users me-2"></i> Customers
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/manageVendors.php">
                <i class="fas fa-industry me-2"></i> Vendors
            </a>
        </li>

        <!-- Inventory Dropdown -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#inventoryMenu" role="button">
                <i class="fas fa-boxes me-2"></i> Inventory <i class="fas fa-chevron-down float-end"></i>
            </a>
            <div class="collapse" id="inventoryMenu">
                <a class="nav-link" href="<?= $base_url ?>views/manageInventory.php">Manage Inventory</a>
                <a class="nav-link" href="#">Stock Alerts</a>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/manageSales.php">
                <i class="fas fa-shopping-cart me-2"></i> Sales
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/managePurchases.php">
                <i class="fas fa-truck-loading me-2"></i> Purchases
            </a>
        </li>

        <?php if ($role_id == 1): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $base_url ?>views/manageUsers.php">
                <i class="fas fa-user-shield me-2"></i> User Management
            </a>
        </li>
        <?php endif; ?>

        <!-- Reports Dropdown -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#reportMenu" role="button">
                <i class="fas fa-chart-line me-2"></i> Reports <i class="fas fa-chevron-down float-end"></i>
            </a>
            <div class="collapse" id="reportMenu">
                <a class="nav-link" href="<?= $base_url ?>views/reports.php">General Reports</a>
                <a class="nav-link" href="#">Export Data</a>
            </div>
        </li>
    </ul>

    <!-- User Email Footer -->
    <div class="sidebar-footer text-center text-white">
        <small><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($email) ?></small>
    </div>

</nav>

<!-- Scripts -->
<script src="<?= $base_url ?>assets/js/jquery.min.js"></script>
<script src="<?= $base_url ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_url ?>assets/js/scripts.js"></script>
