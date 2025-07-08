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
                    <h2><i class="fas fa-file-invoice text-dark me-2"></i>Sales Invoices</h2>
                </div>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-2">
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
                    <select id="filterPaymentStatus" class="form-select">
                        <option value="">Filter by Payment Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterOrderStatus" class="form-select">
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
                    <select id="filterSaleType" class="form-select">
                        <option value="">Filter by Sale Type</option>
                        <option value="direct">Direct Sale</option>
                        <option value="from_order">From Customer Order</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                                    <th>Sale Type</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Pending Amount</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="10" class="text-center">Loading...</td>
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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
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
                            <p class="mb-1"><strong>Address:</strong> <span id="view_customer_address"></span></p>
                            <p class="mb-1"><strong>City:</strong> <span id="view_customer_city"></span></p>
                            <p class="mb-0"><strong>State:</strong> <span id="view_customer_state"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Invoice Information</h6>
                            <p class="mb-1"><strong>Invoice #:</strong> <span id="view_invoice_number"></span></p>
                            <p class="mb-1"><strong>Date:</strong> <span id="view_sale_date"></span></p>
                            <p class="mb-1"><strong>Payment Status:</strong> <span id="view_payment_status"></span></p>
                            <p class="mb-1"><strong>Order Status:</strong> <span id="view_order_status"></span></p>
                            <p class="mb-1"><strong>Sale Type:</strong> <span id="view_sale_type"></span></p>
                            <p class="mb-0"><strong>Created By:</strong> <span id="view_created_by_name"></span></p>
                        </div>
                    </div>
                </div>

                <div id="view_order_info" class="mb-4" style="display: none;">
                    <h6 class="text-muted mb-3">Original Order Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order Number:</strong> <span id="view_order_number"></span></p>
                            <p class="mb-1"><strong>Order Date:</strong> <span id="view_order_date"></span></p>
                            <p class="mb-1"><strong>Order Payment Method:</strong> <span id="view_order_payment_method"></span></p>
                            <p class="mb-0"><strong>Order Payment Status:</strong> <span id="view_order_payment_status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Shipping Address:</strong> <span id="view_shipping_address"></span></p>
                            <p class="mb-1"><strong>Ordered By:</strong> <span id="view_customer_user_name"></span></p>
                            <p class="mb-0"><strong>Customer Email:</strong> <span id="view_customer_user_email"></span></p>
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
                                    <th>Description</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="view_items_tbody">
                                <!-- Item rows will be rendered here by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Invoice Summary (JS will populate this) -->
                <div id="invoiceSummary" class="mt-3"></div>

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
                <a href="#" id="downloadInvoiceBtn" class="btn btn-primary" target="" style="display:none;">
                    <i class="fas fa-download me-1"></i> Download Invoice
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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
<script src="../assets/js/sale-invoices.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">

<style>
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-paid {
    background-color: #d1e7dd;
    color: #0f5132;
}

.status-pending {
    background-color: #fff3cd;
    color: #664d03;
}

.status-partial {
    background-color: #cff4fc;
    color: #055160;
}

.status-confirmed {
    background-color: #cff4fc;
    color: #055160;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
}

.status-shipped {
    background-color: #cff4fc;
    color: #055160;
}

.status-delivered {
    background-color: #d1e7dd;
    color: #0f5132;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.sale-type-badge {
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
}

.sale-type-direct {
    background-color: #e2e3e5;
    color: #383d41;
}

.sale-type-from_order {
    background-color: #d1ecf1;
    color: #0c5460;
}

.invoice-summary-table-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(44,62,80,0.08);
    padding: 18px 24px 10px 24px;
    margin-bottom: 0;
    margin-top: 10px;
    transition: box-shadow 0.2s;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
    display: block;
}
.invoice-summary-table-wrapper:hover {
    box-shadow: 0 4px 24px rgba(44,62,80,0.16);
}
.invoice-summary-table th,
.invoice-summary-table td {
    text-align: center !important;
    vertical-align: middle !important;
}
.invoice-summary-table th {
    background: #f8f9fa !important;
    color: #212529 !important;
    font-weight: 600;
    width: 50%;
    border: none;
    padding: 8px 12px;
}
.invoice-summary-table td {
    background: #fff !important;
    color: #212529 !important;
    border: none;
    padding: 8px 12px;
}
.invoice-summary-table tr.total-row th,
.invoice-summary-table tr.total-row td {
    background: #e9ecef !important;
    color: #0d6efd !important;
    font-size: 1.08em;
    font-weight: 700;
    border-top: 2px solid #dee2e6;
}
.invoice-summary-table tr.paid-row td {
    color: #198754 !important;
    font-weight: 600;
}
.invoice-summary-table tr.remaining-row td {
    color: #dc3545 !important;
    font-weight: 600;
}
.invoice-summary-table tr:hover td, .invoice-summary-table tr:hover th {
    background: #f1f3f5 !important;
}
@media (max-width: 576px) {
    .invoice-summary-table-wrapper {
        padding: 12px 4px 6px 4px;
        max-width: 98vw;
    }
}
</style>