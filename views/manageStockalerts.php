<?php
session_name('admin_session');
session_start();
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
?>
        
        <div class="page-wrapper">
            <main class="main-content">
                <div class="container-fluid fade-in">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-exclamation-triangle text-danger me-2"></i>Stock Alerts</h2>
                            <p>Monitor inventory items that are running low</p>
                        </div>
                        <button class="btn refresh-btn slide-in" id="refreshStockAlerts">
                            <i class="fas fa-sync-alt me-2"></i> Refresh Alerts
                        </button>
                    </div>
                    
                    <div id="stockAlertMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="stockAlertTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">Item No</th>
                                            <th width="15%">Item Name</th>
                                            <th width="15%">Category</th>
                                            <th width="10%">Unit</th>
                                            <th width="15%">Unit Price (PKR)</th>
                                            <th width="10%">Current Stock</th>
                                            <th width="10%">Minimum Stock</th>
                                            <th width="15%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data populated via AJAX -->
                                    </tbody>
                                </table>
                                
                                <!-- Empty state display when no alerts -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <h5>No Stock Alerts</h5>
                                    <p>All inventory items are currently above their minimum stock levels.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <?php include_once '../inc/footer.php'; ?>

    <!-- Scripts -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="../assets/js/stock-alerts.js"></script>
        
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">