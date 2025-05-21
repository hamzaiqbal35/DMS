<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stock Alerts</title>

        <!-- Bootstrap CSS -->
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Custom CSS -->
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">
        
        <style>
            :root {
                --primary-color: #4361ee;
                --secondary-color: #3f37c9;
                --success-color: #4cc9f0;
                --warning-color: #f72585;
                --danger-color: #ef233c;
                --light-color: #f8f9fa;
                --dark-color: #212529;
                --border-radius: 0.5rem;
                --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                --transition: all 0.3s ease;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f5f7fa;
            }
            
            .page-wrapper {
                min-height: 100vh;
            }
            
            .main-content {
                padding: 2rem;
            }
            
            .page-header {
                margin-bottom: 2rem;
                position: relative;
            }
            
            .page-header h2 {
                font-weight: 600;
                color: var(--dark-color);
                margin-bottom: 0.5rem;
            }
            
            .page-header p {
                color: #6c757d;
                margin-bottom: 0;
            }
            
            .card {
                border: none;
                border-radius: var(--border-radius);
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                transition: var(--transition);
            }
            
            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            .table th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.8rem;
                letter-spacing: 0.5px;
                background-color: var(--danger-color);
                color: white;
                border: none;
            }
            
            .table td {
                vertical-align: middle;
                padding: 0.75rem;
                border-color: #e9ecef;
            }
            
            .table-hover tbody tr:hover {
                background-color: rgba(239, 35, 60, 0.05);
            }
            
            .btn {
                border-radius: var(--border-radius);
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: var(--transition);
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }
            
            .btn-primary:hover {
                background-color: var(--secondary-color);
                border-color: var(--secondary-color);
            }
            
            .btn-success {
                background-color: var(--success-color);
                border-color: var(--success-color);
                color: var(--dark-color);
            }
            
            .btn-success:hover {
                background-color: #2ec4b6;
                border-color: #2ec4b6;
            }
            
            .btn-danger {
                background-color: var(--danger-color);
                border-color: var(--danger-color);
            }
            
            .search-container {
                position: relative;
                margin-bottom: 1.5rem;
            }
            
            .search-container .search-icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: #6c757d;
            }
            
            .search-container input {
                padding-left: 2.5rem;
                border-radius: 2rem;
                border: 1px solid #ced4da;
                box-shadow: var(--box-shadow);
            }
            
            .search-container input:focus {
                border-color: var(--primary-color);
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .slide-in {
                animation: slideIn 0.3s ease-in-out;
            }
            
            @keyframes slideIn {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .badge {
                font-weight: 500;
                padding: 0.35em 0.65em;
                border-radius: 0.25rem;
            }
            
            .empty-state {
                text-align: center;
                padding: 3rem 0;
            }
            
            .empty-state i {
                font-size: 3rem;
                color: #d1d5db;
                margin-bottom: 1rem;
            }
            
            .empty-state p {
                color: #6c757d;
                margin-bottom: 1.5rem;
            }
            
            .alert-icon {
                color: var(--danger-color);
                font-size: 1.5rem;
                margin-right: 0.5rem;
            }
            
            .stock-low {
                background-color: rgba(239, 35, 60, 0.15);
            }
            
            .refresh-btn {
                background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
                border: none;
                color: white;
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(67, 97, 238, 0.3);
                transition: all 0.3s ease;
            }
            
            .refresh-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 8px rgba(67, 97, 238, 0.4);
                color: white;
            }
        </style>
    </head>
    <body>
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
        
        <script>
        $(document).ready(function () {
            fetchStockAlerts();
            
            // Refresh stock alerts
            $('#refreshStockAlerts').on('click', function() {
                const btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Refreshing...');
                btn.prop('disabled', true);
                
                fetchStockAlerts();
                
                setTimeout(function() {
                    btn.html('<i class="fas fa-sync-alt me-2"></i> Refresh Alerts');
                    btn.prop('disabled', false);
                }, 1000);
            });

            function fetchStockAlerts() {
                $.ajax({
                    url: '../model/inventory/fetchStockAlerts.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function (res) {
                        const tbody = $('#stockAlertTable tbody');
                        tbody.empty();

                        if (res.status === 'success') {
                            if (res.data.length > 0) {
                                $('#emptyState').addClass('d-none');
                                
                                res.data.forEach(item => {
                                    const rowClass = item.current_stock < item.minimum_stock ? 'stock-low' : '';
                                    const stockStatus = item.current_stock < item.minimum_stock ? 
                                        `<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> Low Stock</span>` : 
                                        `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Adequate</span>`;
                                    
                                    tbody.append(`
                                        <tr class="${rowClass}">
                                            <td><span class="badge badge-id">${item.item_number}</span></td>
                                            <td><strong>${item.item_name}</strong></td>
                                            <td>${item.category_name}</td>
                                            <td>${item.unit_of_measure}</td>
                                            <td>₨ ${parseFloat(item.unit_price).toLocaleString()}</td>
                                            <td><strong class="${item.current_stock < item.minimum_stock ? 'text-danger' : ''}">${item.current_stock}</strong></td>
                                            <td>${item.minimum_stock}</td>
                                            <td>
                                                ${stockStatus}
                                                <span class="badge bg-${item.status === 'active' ? 'info' : 'secondary'} ms-1">${item.status}</span>
                                            </td>
                                        </tr>
                                    `);
                                });
                            } else {
                                $('#emptyState').removeClass('d-none');
                            }
                        } else if (res.status === 'empty') {
                            $('#emptyState').removeClass('d-none');
                        } else {
                            $('#stockAlertMessage').html(`
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>${res.message}
                                </div>
                            `);
                        }
                    },
                    error: function () {
                        $('#stockAlertMessage').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>Failed to load stock alert data. Please try again.
                            </div>
                        `);
                    }
                });
            }
        });
        </script>
    </body>
</html>