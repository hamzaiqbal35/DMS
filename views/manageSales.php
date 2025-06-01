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
                    <h2>
                        <i class="fas fa-chart-line text-dark me-2"></i>Sales Management
                        <span class="badge bg-success ms-2" id="totalSalesCount" style="display: none;">
                            <i class="fas fa-dollar-sign me-1"></i>
                            <span>0</span> Total Sales
                        </span>
                    </h2>
                </div>
                <button class="btn btn-add-purchase slide-in" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    <i class="fas fa-plus me-2"></i> Add Sale
                </button>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-4">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by customer or item...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterCustomer" class="form-select">
                        <option value="">Filter by Customer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterPaymentStatus" class="form-select">
                        <option value="">Filter by Payment Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100" id="resetFilters">Reset</button>
                </div>
            </div>

            <div id="saleMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-chart-line"></i>
                            <h5>No Sales Found</h5>
                            <p>Start by adding a sale or try searching differently.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                                <i class="fas fa-plus me-1"></i> Add Sale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="addSaleForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label for="item_id" class="form-label">Item</label>
                        <select name="item_id" id="item_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="add_quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="add_quantity" class="form-control" required min="0.01" step="0.01">
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
                                <h6 class="card-title">Sale Preview</h6>
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
                    <div class="col-md-6">
                        <label for="sale_date" class="form-label">Sale Date</label>
                        <input type="date" name="sale_date" id="sale_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Sale</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="editSaleForm">
            <input type="hidden" name="sale_id" id="edit_sale_id">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="edit_customer_id" class="form-label">Customer</label>
                        <select name="customer_id" id="edit_customer_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_item_id" class="form-label">Item</label>
                        <select name="item_id" id="edit_item_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="edit_quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" required min="0.01" step="0.01">
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
                                <h6 class="card-title">Sale Preview</h6>
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
                    <div class="col-md-6">
                        <label for="edit_sale_date" class="form-label">Sale Date</label>
                        <input type="date" name="sale_date" id="edit_sale_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_payment_status" class="form-label">Payment Status</label>
                        <select name="payment_status" id="edit_payment_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
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

<!-- View Sale Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" aria-labelledby="viewSaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Sale Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="view_customer_name"></span></p>
                        <p><strong>Phone:</strong> <span id="view_customer_phone"></span></p>
                        <p><strong>Email:</strong> <span id="view_customer_email"></span></p>
                        <p><strong>Address:</strong> <span id="view_customer_address"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-invoice me-2"></i>Sale Information</h6>
                        <p><strong>Invoice:</strong> <span id="view_invoice_number"></span></p>
                        <p><strong>Date:</strong> <span id="view_sale_date"></span></p>
                        <p><strong>Status:</strong> <span id="view_payment_status"></span></p>
                        <p><strong>Created By:</strong> <span id="view_created_by"></span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-box me-2"></i>Item Details</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Code</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="view_item_name"></td>
                                        <td id="view_item_code"></td>
                                        <td id="view_quantity"></td>
                                        <td id="view_unit_price"></td>
                                        <td id="view_total_price"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                        <p id="view_notes" class="border p-3 bg-light rounded"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="generateInvoiceBtn">
                    <i class="fas fa-file-pdf me-1"></i> Generate Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Sale Modal -->
<div class="modal fade" id="deleteSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this sale? This action cannot be undone.</p>
                <input type="hidden" id="delete_sale_id">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-1"></i> Delete Sale
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
<script src="../assets/js/sales-management.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">