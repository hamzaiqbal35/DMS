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
                        <i class="fas fa-chart-line text-dark me-2"></i>Sales Management
                        <span class="badge bg-success ms-2" id="totalSalesCount" style="display: none;">
                            <i class="fas fa-dollar-sign me-1"></i>
                            <span>0</span> Total Sales
                        </span>
                    </h2>
                </div>
                <div>
                    <button class="btn btn-add-purchase slide-in" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                        <i class="fas fa-plus me-2"></i> Direct Sale
                    </button>
                </div>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-2">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search sales...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="filterCustomer" name="filter_customer" class="form-select">
                        <option value="">Filter by Customer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterPaymentStatus" name="filter_payment_status" class="form-select">
                        <option value="">Filter by Payment Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterOrderStatus" name="filter_order_status" class="form-select">
                        <option value="">Filter by Order Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterSaleType" name="filter_sale_type" class="form-select">
                        <option value="">Filter by Sale Type</option>
                        <option value="direct">Direct Sale</option>
                        <option value="from_order">From Customer Order</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary w-100" id="resetFilters">Reset</button>
                </div>
            </div>

            <div id="saleMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesTable">
                            <thead>
                                <tr>
                                    <th width="10%">Invoice #</th>
                                    <th width="10%">Customer</th>
                                    <th width="10%">Sale Type</th>
                                    <th width="10%">Items</th>
                                    <th width="8%">Total Amount</th>
                                    <th width="9%">Paid Amount</th>
                                    <th width="9%">Pending Amount</th>
                                    <th width="10%">Sale Date</th>
                                    <th width="10%">Payment Status</th>
                                    <th width="10%">Order Status</th>
                                    <th width="14%">Tracking #</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-chart-line"></i>
                            <h5>No Sales Found</h5>
                            <p>Start by adding a sale or try searching differently.</p>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                                    <i class="fas fa-plus me-1"></i> Direct Sale
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Direct Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="addSaleForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Direct Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="item_id" class="form-label">Item</label>
                        <select name="item_id" id="item_id" class="form-select" required>
                            <option value="">Select Item</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required min="0.01" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label for="unit_price" class="form-label">Unit Price</label>
                        <input type="number" name="unit_price" id="unit_price" class="form-control" required step="0.01" min="0.01">
                    </div>
                    <div class="col-12">
                        <div class="cost-preview card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Sale Preview</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-0 fw-bold">Total:</p>
                                    </div>
                                    <div class="col-md-3">
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
                    <div class="col-md-6">
                        <label for="order_status" class="form-label">Order Status</label>
                        <select name="order_status" id="order_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
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
                        <select name="customer_id" id="edit_customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_item_id" class="form-label">Item</label>
                        <select name="item_id" id="edit_item_id" class="form-select" required>
                            <option value="">Select Item</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" required min="0.01" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label for="edit_unit_price" class="form-label">Unit Price</label>
                        <input type="number" name="unit_price" id="edit_unit_price" class="form-control" required step="0.01" min="0.01">
                    </div>
                    <div class="col-12">
                        <div class="cost-preview card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Sale Preview</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p class="mb-0 fw-bold">Total:</p>
                                    </div>
                                    <div class="col-md-3">
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
                    <div class="col-md-6">
                        <label for="edit_order_status" class="form-label">Order Status</label>
                        <select name="order_status" id="edit_order_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- View Sale Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" aria-labelledby="viewSaleLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Sale Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="view_sale_id">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="view_customer_name"></span></p>
                        <p><strong>Phone:</strong> <span id="view_customer_phone"></span></p>
                        <p><strong>Email:</strong> <span id="view_customer_email"></span></p>
                        <p><strong>Address:</strong> <span id="view_customer_address"></span></p>
                        <p><strong>City:</strong> <span id="view_customer_city"></span></p>
                        <p><strong>State:</strong> <span id="view_customer_state"></span></p>
                        <p><strong>Zip Code:</strong> <span id="view_customer_zip_code"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-invoice me-2"></i>Sale Information</h6>
                        <p><strong>Invoice:</strong> <span id="view_invoice_number"></span></p>
                        <p><strong>Sale Date:</strong> <span id="view_sale_date"></span></p>
                        <p><strong>Payment Status:</strong> <span id="view_payment_status"></span></p>
                        <p><strong>Order Status:</strong> <span id="view_order_status"></span></p>
                        <p><strong>Sale Type:</strong> <span id="view_sale_type"></span></p>
                        <p><strong>Tracking Number:</strong> <span id="view_tracking_number"></span></p>
                        <p><strong>Created By:</strong> <span id="view_created_by_name"></span></p>
                        <p><strong>Created At:</strong> <span id="view_created_at"></span></p>
                    </div>
                </div>
                
                <div id="view_order_info" class="row mt-3" style="display: none;">
                    <div class="col-12">
                        <h6><i class="fas fa-shopping-cart me-2"></i>Original Order Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Order Number:</strong> <span id="view_order_number"></span></p>
                                <p><strong>Order Date:</strong> <span id="view_order_date"></span></p>
                                <p><strong>Order Payment Method:</strong> <span id="view_order_payment_method"></span></p>
                                <p><strong>Order Payment Status:</strong> <span id="view_order_payment_status"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Shipping Address:</strong> <span id="view_shipping_address"></span></p>
                                <p><strong>Ordered By:</strong> <span id="view_customer_user_name"></span></p>
                                <p><strong>Customer Email:</strong> <span id="view_customer_user_email"></span></p>
                            </div>
                        </div>
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
                                        <th>Description</th>
                                        <th>Unit</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="view_items_tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="view_total_summary"></div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                        <p id="view_notes" class="border p-3 bg-light rounded"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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
                <input type="hidden" name="sale_id" id="delete_sale_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-1"></i> Delete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Record Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="paymentForm">
        <div class="modal-body">
          <input type="hidden" id="payment_invoice_id" name="sale_id">
          <!-- Payment summary row -->
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="card card-body bg-light text-center">
                <span class="small text-muted">Total</span>
                <span class="fw-bold h5" id="payment_total_amount">0.00</span>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card card-body bg-light text-center">
                <span class="small text-muted">Paid</span>
                <span class="fw-bold h5 text-success" id="payment_paid_amount">0.00</span>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card card-body bg-light text-center">
                <span class="small text-muted">Remaining</span>
                <span class="fw-bold h5 text-danger" id="payment_remaining_amount">0.00</span>
              </div>
            </div>
          </div>
          <!-- End payment summary row -->
          <div class="mb-3">
            <label for="payment_amount" class="form-label">Payment Amount</label>
            <input type="number" class="form-control" id="payment_amount" name="payment_amount" step="0.01" min="0.01" required>
            <div class="form-text">Enter an amount.</div>
          </div>
          <div class="mb-3">
            <label for="payment_date" class="form-label">Payment Date</label>
            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
          </div>
          <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method</label>
            <select class="form-select" id="payment_method" name="payment_method" required>
              <option value="">Select Payment Method</option>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="check">Check</option>
              <option value="credit_card">Credit Card</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="payment_notes" class="form-label">Notes (Optional)</label>
            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-credit-card me-1"></i> Record Payment
          </button>
        </div>
      </form>
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
