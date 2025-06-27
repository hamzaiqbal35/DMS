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
                            <h2><i class="fas fa-industry text-dark me-2"></i>Vendor Management</h2>
                        </div>
                        <button class="btn btn-add-vendor slide-in" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                            <i class="fas fa-industry me-2"></i> Add Vendor
                        </button>
                    </div>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search vendors by name, email, phone...">
                    </div>

                    <div id="vendorMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="vendorTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="15%">Vendor Name</th>
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
                                
                                <!-- Empty state display when no vendors -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-industry-slash"></i>
                                    <h5>No Vendors Found</h5>
                                    <p>Start by adding your first vendor or try a different search term.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                                        <i class="fas fa-industry me-1"></i> Add Vendor
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Add Vendor Modal -->
        <div class="modal fade modal-animate" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="addVendorForm">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="addVendorLabel"><i class="fas fa-industry me-2"></i>Add Vendor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label for="add_vendor_name" class="form-label"><i class="fas fa-building me-1"></i> Vendor Name</label>
                                <input type="text" name="vendor_name" id="add_vendor_name" class="form-control" required placeholder="Enter vendor name" autocomplete="organization">
                            </div>
                            <div class="col-md-6">
                                <label for="add_contact_person" class="form-label"><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" id="add_contact_person" class="form-control" placeholder="Enter contact person" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="add_phone" class="form-label"><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" id="add_phone" class="form-control" required placeholder="Enter phone number" autocomplete="tel">
                            </div>
                            <div class="col-md-6">
                                <label for="add_email" class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" id="add_email" class="form-control" placeholder="Enter email address" autocomplete="email">
                            </div>
                            <div class="col-12">
                                <label for="add_address" class="form-label"><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" id="add_address" class="form-control" rows="2" required placeholder="Enter address" autocomplete="street-address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="add_city" class="form-label"><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" id="add_city" class="form-control" required placeholder="Enter city" autocomplete="address-level2">
                            </div>
                            <div class="col-md-4">
                                <label for="add_state" class="form-label"><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" id="add_state" class="form-control" placeholder="Enter state" autocomplete="address-level1">
                            </div>
                            <div class="col-md-4">
                                <label for="add_zip_code" class="form-label"><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" id="add_zip_code" class="form-control" placeholder="Enter ZIP code" autocomplete="postal-code">
                            </div>
                            <div class="col-md-4">
                                <label for="add_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="add_status" class="form-control" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Vendor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Vendor Modal -->
        <div class="modal fade modal-animate" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="editVendorForm">
                    <input type="hidden" name="vendor_id" id="edit_vendor_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="editVendorLabel"><i class="fas fa-edit me-2"></i>Edit Vendor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label for="edit_vendor_name" class="form-label"><i class="fas fa-building me-1"></i> Vendor Name</label>
                                <input type="text" name="vendor_name" id="edit_vendor_name" class="form-control" required placeholder="Enter vendor name" autocomplete="organization">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_contact_person" class="form-label"><i class="fas fa-user me-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control" placeholder="Enter contact person" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label"><i class="fas fa-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control" required placeholder="Enter phone number" autocomplete="tel">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" placeholder="Enter email address" autocomplete="email">
                            </div>
                            <div class="col-12">
                                <label for="edit_address" class="form-label"><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2" required placeholder="Enter address" autocomplete="street-address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_city" class="form-label"><i class="fas fa-city me-1"></i> City</label>
                                <input type="text" name="city" id="edit_city" class="form-control" required placeholder="Enter city" autocomplete="address-level2">
                            </div>
                            <div class="col-md-4">
                                <label for="edit_state" class="form-label"><i class="fas fa-flag me-1"></i> State</label>
                                <input type="text" name="state" id="edit_state" class="form-control" placeholder="Enter state" autocomplete="address-level1">
                            </div>
                            <div class="col-md-4">
                                <label for="edit_zip_code" class="form-label"><i class="fas fa-map-pin me-1"></i> ZIP</label>
                                <input type="text" name="zip_code" id="edit_zip_code" class="form-control" placeholder="Enter ZIP code" autocomplete="postal-code">
                            </div>
                            <div class="col-md-4">
                                <label for="edit_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
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
                                <i class="fas fa-save me-1"></i> Update Vendor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade modal-animate" id="deleteVendorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this vendor? This action cannot be undone.</p>
                        <input type="hidden" id="delete_vendor_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete Vendor
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
        <script src="../assets/js/vendor-management.js"></script>
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">