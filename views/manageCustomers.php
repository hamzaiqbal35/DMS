<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
                                            <th width="8%">Status</th>
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
                                <input type="text" name="customer_name" class="form-control" required placeholder="Enter customer name">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" placeholder="Enter contact person">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" class="form-control" required placeholder="Enter phone number">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email address">
                            </div>
                            <div class="col-12">
                                <label><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="2" required placeholder="Enter address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" class="form-control" required placeholder="Enter city">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" class="form-control" placeholder="Enter state">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" class="form-control" placeholder="Enter ZIP code">
                            </div>
                            <div class="col-md-4">
                            <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
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
                                <input type="text" name="customer_name" id="edit_customer_name" class="form-control" required placeholder="Enter customer name">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control" placeholder="Enter contact person">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control" required placeholder="Enter phone number">
                            </div>
                            <div class="col-md-6">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" placeholder="Enter email address">
                            </div>
                            <div class="col-12">
                                <label><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2" required placeholder="Enter address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" id="edit_city" class="form-control" required placeholder="Enter city">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" id="edit_state" class="form-control" placeholder="Enter state">
                            </div>
                            <div class="col-md-4">
                                <label><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" id="edit_zip_code" class="form-control" placeholder="Enter ZIP code">
                            </div>
                            <div class="col-md-4">
                            <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="edit_status" class="form-control" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
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
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/customer-management.js"></script>
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">