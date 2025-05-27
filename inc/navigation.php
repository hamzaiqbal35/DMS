<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "http://localhost/DMS/";
$role_id = $_SESSION['role_id'] ?? null;
$email = $_SESSION['email'] ?? 'Unknown User';

// Get current page for active link detection
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $base_url ?>assets/css/styles.css">
<link rel="stylesheet" href="<?= $base_url ?>assets/css/animations.css">

<nav id="sidebar" class="bg-dark">
    <div class="nav-wrapper">
        <ul class="nav flex-column py-3 px-2">
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'dashboard.php') !== false ? 'active' : '' ?>" 
                   href="<?= $base_url ?>views/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'manageCustomers.php') !== false ? 'active' : '' ?>" 
                   href="<?= $base_url ?>views/manageCustomers.php">
                    <i class="fas fa-users"></i> Customers
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'manageVendors.php') !== false ? 'active' : '' ?>" 
                   href="<?= $base_url ?>views/manageVendors.php">
                    <i class="fas fa-industry"></i> Vendors
                </a>
            </li>

            <!-- Inventory Dropdown -->
            <?php
            $inventoryActive = in_array($current_page, [
                'manageInventory.php', 'manageStockalerts.php', 
                'manageMedia.php', 'manageCategories.php'
            ]);
            ?>
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center <?= $inventoryActive ? 'menu-expanded' : '' ?>" 
                   data-bs-toggle="collapse" 
                   href="#inventoryMenu" 
                   role="button" 
                   aria-expanded="<?= $inventoryActive ? 'true' : 'false' ?>" 
                   aria-controls="inventoryMenu">
                    <span><i class="fas fa-boxes"></i> Inventory</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="collapse <?= $inventoryActive ? 'show' : '' ?>" id="inventoryMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'manageInventory.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/manageInventory.php">
                                <i class="fas fa-warehouse"></i> Manage Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'manageStockalerts.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/manageStockalerts.php">
                                <i class="fas fa-exclamation-triangle"></i> Stock Alerts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'manageMedia.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/manageMedia.php">
                                <i class="fas fa-images"></i> Catalog
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'manageCategories.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/manageCategories.php">
                                <i class="fas fa-tags"></i> Category
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'manageSales.php') !== false ? 'active' : '' ?>" 
                   href="<?= $base_url ?>views/manageSales.php">
                    <i class="fas fa-shopping-cart"></i> Sales
                </a>
            </li>

            <!-- Purchases Dropdown -->
            <?php
            $purchasesActive = in_array($current_page, [
                'managePurchases.php', 'purchaseReports.php', 
                'purchaseInvoices.php', 'purchaseAnalytics.php',
                'manageRawMaterials.php'
            ]);
            ?>
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center <?= $purchasesActive ? 'menu-expanded' : '' ?>" 
                   data-bs-toggle="collapse" 
                   href="#purchasesMenu" 
                   role="button" 
                   aria-expanded="<?= $purchasesActive ? 'true' : 'false' ?>" 
                   aria-controls="purchasesMenu">
                    <span><i class="fas fa-file-invoice-dollar"></i> Purchases</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="collapse <?= $purchasesActive ? 'show' : '' ?>" id="purchasesMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'managePurchases.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/managePurchases.php">
                                <i class="fas fa-shopping-basket"></i> Manage Purchases
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'purchaseReports.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/purchaseReports.php">
                                <i class="fas fa-file-alt"></i> Purchase Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'purchaseInvoices.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/purchaseInvoices.php">
                                <i class="fas fa-file-invoice"></i> Attached Invoices
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'purchaseAnalytics.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/purchaseAnalytics.php">
                                <i class="fas fa-chart-pie"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'manageRawMaterials.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/manageRawMaterials.php">
                                <i class="fas fa-drum-steelpan"></i> Raw Materials
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <?php if ($role_id == 1): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'manageUsers.php') !== false ? 'active' : '' ?>" 
                   href="<?= $base_url ?>views/manageUsers.php">
                    <i class="fas fa-user-shield"></i> User Management
                </a>
            </li>
            <?php endif; ?>

            <!-- Reports Dropdown -->
            <?php
            $reportsActive = in_array($current_page, ['reports.php']);
            ?>
            <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center <?= $reportsActive ? 'menu-expanded' : '' ?>" 
                   data-bs-toggle="collapse" 
                   href="#reportMenu" 
                   role="button" 
                   aria-expanded="<?= $reportsActive ? 'true' : 'false' ?>" 
                   aria-controls="reportMenu">
                    <span><i class="fas fa-chart-line"></i> Reports</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="collapse <?= $reportsActive ? 'show' : '' ?>" id="reportMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, 'reports.php') !== false ? 'active' : '' ?>" 
                               href="<?= $base_url ?>views/reports.php">
                                <i class="fas fa-file-contract"></i> General Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-file-export"></i> Export Data
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- User Email Footer -->
    <div class="sidebar-footer">
        <small><i class="fas fa-user-circle"></i><?= htmlspecialchars($email) ?></small>
    </div>
</nav>

<!-- Scripts -->
<script src="<?= $base_url ?>assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= $base_url ?>assets/js/scripts.js"></script>
<script src="<?= $base_url ?>assets/js/animations.js"></script>