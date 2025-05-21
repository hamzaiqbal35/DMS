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
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5>Total Sales</h5>
                        <h2>$12,340</h2>
                        <p>Last 30 days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5>Total Inventory</h5>
                        <h2>1,240 Items</h2>
                        <p>Available Stock</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5>Purchases</h5>
                        <h2>$5,680</h2>
                        <p>Last 30 days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5>Customers</h5>
                        <h2>320</h2>
                        <p>Active Customers</p>
                    </div>
                </div>
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

        <!-- Recent Activities Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025-04-02</td>
                                    <td>Added new Inventory Item</td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>2025-04-01</td>
                                    <td>Updated Sales Report</td>
                                    <td>Staff</td>
                                </tr>
                                <tr>
                                    <td>2025-03-31</td>
                                    <td>Vendor Payment Processed</td>
                                    <td>Admin</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once "../inc/footer.php"; ?> 
