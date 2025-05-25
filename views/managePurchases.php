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
                    <h2><i class="fas fa-shopping-cart text-dark me-2"></i>Purchase Management</h2>
                </div>
                <button class="btn btn-add-purchase slide-in" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                    <i class="fas fa-plus me-2"></i> Add Purchase
                </button>
            </div>
            
            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-4">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="    Search by vendor or material...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterVendor" class="form-select">
                        <option value="">Filter by Vendor</option>
                        <!-- Vendors loaded via AJAX -->
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterMaterial" class="form-select">
                        <option value="">Filter by Material</option>
                        <!-- Materials loaded via AJAX -->
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100" id="resetFilters">Reset Filters</button>
                </div>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <input type="date" id="filterStartDate" class="form-control" placeholder="Start Date">
                </div>
                <div class="col-md-4">
                    <input type="date" id="filterEndDate" class="form-control" placeholder="End Date">
                </div>
                <div class="col-md-4">
                    <input type="number" id="filterMinAmount" class="form-control" placeholder="Min Amount">
                </div>
            </div>

            <div id="purchaseMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="purchaseTable">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="15%">Vendor</th>
                                    <th width="15%">Material</th>
                                    <th width="10%">Quantity</th>
                                    <th width="10%">Unit Price</th>
                                    <th width="10%">Total Amount</th>
                                    <th width="15%">Purchase Date</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                        
                        <!-- Empty state display when no purchases -->
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-shopping-cart"></i>
                            <h5>No Purchases Found</h5>
                            <p>Start by adding your first purchase or try a different search term.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                                <i class="fas fa-plus me-1"></i> Add Purchase
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="addPurchaseForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="vendor_id" class="form-label"><i class="fas fa-truck me-1"></i> Vendor</label>
                        <select name="vendor_id" id="vendor_id" class="form-select" required>
                            <option value="">Select Vendor</option>
                            <!-- Vendors loaded via AJAX -->
                        </select>
                        <div class="invalid-feedback">Please select a vendor</div>
                    </div>
                    <div class="col-md-6">
                        <label for="material_id" class="form-label"><i class="fas fa-box me-1"></i> Material</label>
                        <select name="material_id" id="material_id" class="form-select" required>
                            <option value="">Select Material</option>
                            <!-- Materials loaded via AJAX -->
                        </select>
                        <div class="invalid-feedback">Please select a material</div>
                        <div id="materialError" class="text-danger mt-1 d-none"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="quantity" class="form-label"><i class="fas fa-cubes me-1"></i> Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                        <div class="invalid-feedback">Please enter a valid quantity</div>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_price" class="form-label"><i class="fas fa-tag me-1"></i> Unit Price</label>
                        <input type="number" name="unit_price" id="unit_price" class="form-control" min="0.01" step="0.01" required>
                        <div class="invalid-feedback">Please enter a valid unit price</div>
                    </div>
                    <div class="col-md-4">
                        <label for="purchase_date" class="form-label"><i class="fas fa-calendar me-1"></i> Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" required>
                        <div class="invalid-feedback">Please select a purchase date</div>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label"><i class="fas fa-money-bill me-1"></i> Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label"><i class="fas fa-sticky-note me-1"></i> Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Purchase
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Purchase Modal -->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="editPurchaseForm">
            <input type="hidden" name="purchase_id" id="edit_purchase_id">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="edit_vendor_id" class="form-label"><i class="fas fa-truck me-1"></i> Vendor</label>
                        <select name="vendor_id" id="edit_vendor_id" class="form-select" required>
                            <option value="">Select Vendor</option>
                            <!-- Vendors loaded via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_material_id" class="form-label"><i class="fas fa-box me-1"></i> Material</label>
                        <select name="material_id" id="edit_material_id" class="form-select" required>
                            <option value="">Select Material</option>
                            <!-- Materials loaded via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_quantity" class="form-label"><i class="fas fa-cubes me-1"></i> Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_unit_price" class="form-label"><i class="fas fa-tag me-1"></i> Unit Price</label>
                        <input type="number" name="unit_price" id="edit_unit_price" class="form-control" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_purchase_date" class="form-label"><i class="fas fa-calendar me-1"></i> Purchase Date</label>
                        <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_payment_status" class="form-label"><i class="fas fa-money-bill me-1"></i> Payment Status</label>
                        <select name="payment_status" id="edit_payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="edit_notes" class="form-label"><i class="fas fa-sticky-note me-1"></i> Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update Purchase
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Purchase Modal -->
<div class="modal fade" id="deletePurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this purchase? This action cannot be undone.</p>
                <p class="mb-0"><strong>Purchase ID: </strong><span id="delete_purchase_id"></span></p>
                <input type="hidden" id="delete_purchase_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-1"></i> Delete Purchase
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
<script src="../assets/js/purchase-management.js"></script>

<link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">
