<?php
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <!-- Dashboard Title -->
            <div class="col-12">
                <h2 class="dashboard-title">Dashboard</h2>
            </div>
        </div>

        <!-- Dashboard Stats Cards -->
        <div class="row">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manageVendors.php" class="text-decoration-none">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5>Total Vendors</h5>
                            <h2 id="totalVendors">Loading...</h2>
                            <p>Active Vendors</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manageInventory.php" class="text-decoration-none">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5>Total Inventory</h5>
                            <h2 id="totalInventory">Loading...</h2>
                            <p>Total Stock Quantity</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="managePurchases.php" class="text-decoration-none">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5>Purchases</h5>
                            <h2 id="totalPurchases">Loading...</h2>
                            <p>Last 30 days</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manageCustomers.php" class="text-decoration-none">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5>Customers</h5>
                            <h2 id="totalCustomers">Loading...</h2>
                            <p>Active Customers</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="inventoryChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once "../inc/footer.php"; ?> 

    <!-- Scripts -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/charts.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">