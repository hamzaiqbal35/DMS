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
                    <h2>
                        <i class="fas fa-shopping-cart text-dark me-2"></i>Purchase Management
                        <span class="badge bg-danger ms-2" id="delayedDeliveriesCount" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <span>0</span> Delayed
                        </span>
                    </h2>
                </div>
                <button class="btn btn-add-purchase slide-in" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                    <i class="fas fa-plus me-2"></i> Add Purchase
                </button>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-4">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by vendor or material...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterVendor" class="form-select">
                        <option value="">Filter by Vendor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterMaterial" class="form-select">
                        <option value="">Filter by Material</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100" id="resetFilters">Reset</button>
                </div>
            </div>

            <div id="purchaseMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="purchaseTable">
                            <thead>
                                <tr>
                                    <th>Purchase #</th>
                                    <th>Vendor</th>
                                    <th>Material</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-shopping-cart"></i>
                            <h5>No Purchases Found</h5>
                            <p>Start by adding a purchase or try searching differently.</p>
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
                        <label for="vendor_id" class="form-label">Vendor</label>
                        <select name="vendor_id" id="vendor_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label for="material_id" class="form-label">Material</label>
                        <select name="material_id" id="material_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="add_quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="add_quantity" class="form-control" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label for="add_unit_price" class="form-label">Unit Price</label>
                        <input type="number" name="unit_price" id="add_unit_price" class="form-control" required step="0.01" min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label for="add_tax_rate" class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="add_tax_rate" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                    <div class="col-md-4">
                        <label for="add_discount_rate" class="form-label">Discount Rate (%)</label>
                        <input type="number" name="discount_rate" id="add_discount_rate" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                    <div class="col-12">
                        <div class="cost-preview card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Cost Preview</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-1">Subtotal:</p>
                                        <p class="mb-1">Tax Amount:</p>
                                        <p class="mb-1">Discount Amount:</p>
                                        <p class="mb-0 fw-bold">Total:</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-1" id="preview_subtotal">PKR 0.00</p>
                                        <p class="mb-1" id="preview_tax">PKR 0.00</p>
                                        <p class="mb-1" id="preview_discount">PKR 0.00</p>
                                        <p class="mb-0 fw-bold" id="preview_total">PKR 0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="purchase_date" class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="expected_delivery" class="form-label">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery" id="expected_delivery" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Delivery Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Purchase</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="edit_vendor_id" class="form-label">Vendor</label>
                        <select name="vendor_id" id="edit_vendor_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_material_id" class="form-label">Material</label>
                        <select name="material_id" id="edit_material_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label for="edit_unit_price" class="form-label">Unit Price</label>
                        <input type="number" name="unit_price" id="edit_unit_price" class="form-control" required step="0.01" min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label for="edit_tax_rate" class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="edit_tax_rate" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                    <div class="col-md-4">
                        <label for="edit_discount_rate" class="form-label">Discount Rate (%)</label>
                        <input type="number" name="discount_rate" id="edit_discount_rate" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                    <div class="col-12">
                        <div class="cost-preview card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Cost Preview</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-1">Subtotal:</p>
                                        <p class="mb-1">Tax Amount:</p>
                                        <p class="mb-1">Discount Amount:</p>
                                        <p class="mb-0 fw-bold">Total:</p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-1" id="edit_preview_subtotal">PKR 0.00</p>
                                        <p class="mb-1" id="edit_preview_tax">PKR 0.00</p>
                                        <p class="mb-1" id="edit_preview_discount">PKR 0.00</p>
                                        <p class="mb-0 fw-bold" id="edit_preview_total">PKR 0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_purchase_date" class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_expected_delivery" class="form-label">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery" id="edit_expected_delivery" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="edit_payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_status" class="form-label">Delivery Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this purchase? This action cannot be undone.</p>
                <input type="hidden" id="delete_purchase_id">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="../assets/js/purchase-management.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">
