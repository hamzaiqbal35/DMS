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
                    <h2><i class="fas fa-file-invoice text-dark me-2"></i>Sales Invoices</h2>
                </div>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-3">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search invoices...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="filterCustomer" class="form-select">
                        <option value="">Filter by Customer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select">
                        <option value="">Filter by Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                </div>
                <div class="col-md-2">
                    <input type="date" id="endDate" class="form-control" placeholder="End Date">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-secondary w-100" id="resetFilters">Reset</button>
                </div>
            </div>

            <div id="invoiceMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="invoiceTable">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-file-invoice"></i>
                            <h5>No Invoices Found</h5>
                            <p>No sales invoices available to display.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Action Menu Template -->
<template id="actionMenuTemplate">
    <div class="dropdown">
        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item view-invoice" href="#"><i class="fas fa-eye me-2"></i>View</a></li>
            <li><a class="dropdown-item generate-invoice" href="#"><i class="fas fa-file-invoice me-2"></i>Generate Invoice</a></li>
            <li><a class="dropdown-item record-payment" href="#"><i class="fas fa-credit-card me-2"></i>Record Payment</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item delete-invoice text-danger" href="#"><i class="fas fa-trash-alt me-2"></i>Delete</a></li>
        </ul>
    </div>
</template>

<!-- Generate Invoice Modal -->
<div class="modal fade" id="generateInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Generate Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="generateInvoiceForm">
                <div class="modal-body">
                    <input type="hidden" id="generate_sale_id" name="sale_id">
                    <div class="mb-3">
                        <label for="invoice_date" class="form-label">Invoice Date</label>
                        <input type="date" class="form-control" id="invoice_date" name="invoice_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>View Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="invoice-details mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <span id="view_customer_name"></span></p>
                            <p class="mb-1"><strong>Phone:</strong> <span id="view_customer_phone"></span></p>
                            <p class="mb-1"><strong>Email:</strong> <span id="view_customer_email"></span></p>
                            <p class="mb-0"><strong>Address:</strong> <span id="view_customer_address"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Invoice Information</h6>
                            <p class="mb-1"><strong>Invoice #:</strong> <span id="view_invoice_number"></span></p>
                            <p class="mb-1"><strong>Date:</strong> <span id="view_sale_date"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="view_payment_status"></span></p>
                            <p class="mb-0"><strong>Created By:</strong> <span id="view_created_by_name"></span></p>
                        </div>
                    </div>
                </div>

                <div class="invoice-items mb-4">
                    <h6 class="text-muted mb-3">Item Details</h6>
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
                                    <td id="view_item_number"></td>
                                    <td id="view_quantity"></td>
                                    <td id="view_unit_price"></td>
                                    <td id="view_total_price"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="payment-history">
                    <h6 class="text-muted mb-3">Payment History</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="paymentHistoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Payment history will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="notes-section mt-4">
                    <h6 class="text-muted mb-3">Notes</h6>
                    <div class="p-3 bg-light rounded">
                        <p id="view_notes" class="mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="#" id="downloadInvoice" class="btn btn-primary" target="_blank">
                    <i class="fas fa-download me-1"></i> Download Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Invoice Modal -->
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Delete Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
                <input type="hidden" id="delete_invoice_id">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteInvoiceBtn">
                    <i class="fas fa-trash-alt me-1"></i> Delete Invoice
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
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Payment Amount</label>
                        <input type="number" class="form-control" id="payment_amount" name="payment_amount" step="0.01" required>
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
<script src="../assets/js/sale-invoices.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">