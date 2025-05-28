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
                    <h2><i class="fas fa-file-invoice text-dark me-2"></i>Purchase Invoices</h2>
                </div>
            </div>

            <div class="row mb-3 g-3 filter-container">
                <div class="col-md-4">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by purchase number or vendor...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterVendor" class="form-select">
                        <option value="">Filter by Vendor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterStatus" class="form-select">
                        <option value="">Filter by Status</option>
                        <option value="has_invoice">Has Invoice</option>
                        <option value="no_invoice">No Invoice</option>
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
                                    <th>Purchase #</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Invoice Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-file-invoice"></i>
                            <h5>No Purchases Found</h5>
                            <p>No purchases available to display.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Upload Invoice Modal -->
<div class="modal fade" id="uploadInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadInvoiceForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="upload_purchase_id" name="purchase_id">
                    <div class="mb-3">
                        <label for="invoice_file" class="form-label">Select Invoice File</label>
                        <input type="file" class="form-control" id="invoice_file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Allowed formats: PDF, JPEG, PNG (Max size: 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Upload
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
            <div class="modal-body text-center">
                <div id="invoicePreview" class="mb-3">
                    <!-- Invoice preview will be loaded here -->
                </div>
            </div>
            <div class="modal-footer-download">
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
                <input type="hidden" id="delete_invoice_purchase_id">
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

<?php include_once '../inc/footer.php'; ?>

<!-- Scripts -->
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/animations.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="../assets/js/purchase-invoices.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">
