<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../inc/config/auth.php';
include_once '../inc/header.php';
include_once '../inc/navigation.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer Management</title>

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
                background-color: var(--dark-color);
                color: white;
                border: none;
            }
            
            .table td {
                vertical-align: middle;
                padding: 0.75rem;
                border-color: #e9ecef;
            }
            
            .table-hover tbody tr:hover {
                background-color: rgba(67, 97, 238, 0.05);
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
            
            .btn-warning {
                background-color: #f9c74f;
                border-color: #f9c74f;
                color: var(--dark-color);
            }
            
            .btn-danger {
                background-color: var(--warning-color);
                border-color: var(--warning-color);
            }
            
            .btn-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .form-control {
                border-radius: var(--border-radius);
                padding: 0.625rem 1rem;
                border: 1px solid #ced4da;
                transition: var(--transition);
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
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
            
            .modal-content {
                border-radius: var(--border-radius);
                border: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            .modal-header {
                border-top-left-radius: calc(var(--border-radius) - 1px);
                border-top-right-radius: calc(var(--border-radius) - 1px);
                padding: 1rem 1.5rem;
            }
            
            .modal-header.bg-primary {
                background-color: var(--primary-color) !important;
            }
            
            .modal-header.bg-warning {
                background-color: #f9c74f !important;
            }
            
            .modal-body {
                padding: 1.5rem;
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
            
            .btn-add-customer {
                background: linear-gradient(45deg, var(--success-color), #2ec4b6);
                border: none;
                color: var(--dark-color);
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(76, 201, 240, 0.3);
                transition: all 0.3s ease;
            }
            
            .btn-add-customer:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 8px rgba(76, 201, 240, 0.4);
                color: var(--dark-color);
            }
            
            .action-buttons .btn {
                margin-right: 0.25rem;
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .badge {
                font-weight: 500;
                padding: 0.35em 0.65em;
                border-radius: 0.25rem;
            }
            
            .badge-id {
                background-color: #e9ecef;
                color: #495057;
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
            .modal-dialog-scrollable .modal-content {
                max-height: 78vh;
                overflow: hidden;
            }

            .modal-dialog-scrollable .modal-header {
                position: sticky;
                top: 0;
                z-index: 1050;
                background-color: inherit;
            }

            .modal-dialog-scrollable .modal-footer {
                position: sticky;
                bottom: 0;
                z-index: 1050;
                background-color: #fff;
            }

            /* Ensure the modal body has proper padding and doesn't overlap */
            .modal-dialog-scrollable .modal-body {
                padding-top: 1rem;
                padding-bottom: 1rem;
                overflow-y: auto;
            }

            /* custom modal positioning - adjust these values as needed */
            .custom-modal-position {
                margin-top: 40px;
                margin-left: 270px; 
            }

            /* Ensure the modal header colors are preserved */
            #addCustomerForm .modal-header {
                background-color: var(--primary-color) !important;
            }

            #editCustomerForm .modal-header {
                background-color: #f9c74f !important;
            }
            
            /* Fix for the close button to be visible on colored headers */
            .modal-header .btn-close {
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                padding: 0.5rem;
                margin: -0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <main class="main-content">
                <div class="container-fluid fade-in">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-users text-dark me-2"></i>Customer Management</h2>
                        </div>
                        <button class="btn btn-add-customer slide-in" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="fas fa-user-plus me-2"></i> Add Customer
                        </button>
                    </div>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search customers by name, email, phone...">
                    </div>

                    <div id="customerMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="customerTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="15%">Customer Name</th>
                                            <th width="15%">Contact Person</th>
                                            <th width="10%">Phone</th>
                                            <th width="15%">Email</th>
                                            <th width="15%">Address</th>
                                            <th width="7%">City</th>
                                            <th width="5%">State</th>
                                            <th width="5%">ZIP</th>
                                            <th width="8%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data populated via AJAX -->
                                    </tbody>
                                </table>
                                
                                <!-- Empty state display when no customers -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-users-slash"></i>
                                    <h5>No Customers Found</h5>
                                    <p>Start by adding your first customer or try a different search term.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                        <i class="fas fa-user-plus me-1"></i> Add Customer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Add Customer Modal -->
        <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="addCustomerForm">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label><i class="fas fa-building me-1"></i> Customer Name</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-12">
                                <label><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Customer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="editCustomerForm">
                    <input type="hidden" name="customer_id" id="edit_customer_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label><i class="fas fa-building me-1"></i> Customer Name</label>
                                <input type="text" name="customer_name" id="edit_customer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                            <div class="col-12">
                                <label><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" id="edit_city" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" id="edit_state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" id="edit_zip_code" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i> Update Customer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this customer? This action cannot be undone.</p>
                        <input type="hidden" id="delete_customer_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once '../inc/footer.php'; ?>

        <!-- Scripts -->
        <script src="../assets/js/jquery.min.js"></script>
        <script src="../assets/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/customer-management.js"></script>
        
    </body>
</html>