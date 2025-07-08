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
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-file-export text-primary me-2"></i>Export Data</h2>
                    <small class="text-muted">Export your business data in various formats for analysis, sharing, or backup.</small>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary ms-2" id="refreshExportBtn">
                        <i class="fas fa-sync-alt me-2"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <!-- Export Filters & Summary -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-filter me-2 text-primary"></i>Export Filters</h5>
                        </div>
                        <div class="card-body bg-light-subtle rounded-bottom">
                            <form id="exportFilters" class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label for="exportType" class="form-label">Export Type</label>
                                    <select class="form-select" id="exportType" name="export_type" data-bs-toggle="tooltip" title="Select the type of data to export">
                                        <option value="">Select Export Type (All)</option>
                                        <option value="sales">Sales</option>
                                        <option value="purchases">Purchases</option>
                                        <option value="inventory">Inventory</option>
                                        <option value="customers">Customers</option>
                                        <option value="vendors">Vendors</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="exportFormat" class="form-label">Export Format</label>
                                    <select class="form-select" id="exportFormat" name="export_format" data-bs-toggle="tooltip" title="Choose the file format for export">
                                        <option value="">Select Format</option>
                                        <option value="csv">CSV</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="dateRange" class="form-label">Date Range</label>
                                    <select class="form-select" id="dateRange" name="date_range" data-bs-toggle="tooltip" title="Choose a date range or custom period">
                                        <option value="">Select Date Range (All)</option>
                                        <option value="7">Last 7 Days</option>
                                        <option value="30" selected>Last 30 Days</option>
                                        <option value="90">Last 90 Days</option>
                                        <option value="180">Last 6 Months</option>
                                        <option value="365">Last Year</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 custom-date-range" style="display: none;">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="date_from" placeholder="Select start date">
                                </div>
                                <div class="col-md-6 col-lg-4 custom-date-range" style="display: none;">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="date_to" placeholder="Select end date">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category_id" data-bs-toggle="tooltip" title="Filter by category (for inventory)">
                                        <option value="">Select Category (All)</option>
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" data-bs-toggle="tooltip" title="Filter by status">
                                        <option value="">Select Status (All)</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="paymentStatus" class="form-label">Payment Status</label>
                                    <select class="form-select" id="paymentStatus" name="payment_status" data-bs-toggle="tooltip" title="Filter by payment status">
                                        <option value="">Select Payment Status (All)</option>
                                        <option value="paid">Paid</option>
                                        <option value="partial">Partial</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="minAmount" class="form-label">Min Amount</label>
                                    <input type="number" class="form-control" id="minAmount" name="min_amount" placeholder="Min Amount" data-bs-toggle="tooltip" title="Minimum amount for export">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="maxAmount" class="form-label">Max Amount</label>
                                    <input type="number" class="form-control" id="maxAmount" name="max_amount" placeholder="Max Amount" data-bs-toggle="tooltip" title="Maximum amount for export">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="customer" class="form-label">Customer</label>
                                    <select class="form-select" id="customer" name="customer_id" style="display:none;" data-bs-toggle="tooltip" title="Filter by customer">
                                        <option value="">All Customers</option>
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="vendor" class="form-label">Vendor</label>
                                    <select class="form-select" id="vendor" name="vendor_id" style="display:none;" data-bs-toggle="tooltip" title="Filter by vendor">
                                        <option value="">All Vendors</option>
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="stockStatus" class="form-label">Stock Status</label>
                                    <select class="form-select" id="stockStatus" name="stock_status" style="display:none;" data-bs-toggle="tooltip" title="Filter by stock status">
                                        <option value="">Select Stock Status (All)</option>
                                        <option value="sufficient">Sufficient</option>
                                        <option value="low">Low Stock</option>
                                        <option value="out">Out of Stock</option>
                                    </select>
                                </div>
                                <div class="col-12 d-flex align-items-center gap-3 mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm ms-auto">
                                        <i class="fas fa-download me-2"></i> Export
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-sm" id="resetExportBtn">
                                        <i class="fas fa-undo me-2"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Export History Table -->
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Export History</h5>
                            <div id="exportRecordCount" class="text-muted">No records found</div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="exportHistoryTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Export Type</th>
                                            <th>Format</th>
                                            <th>Date Range</th>
                                            <th>Exported By</th>
                                            <th>File Size</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Export history will be populated dynamically -->
                                    </tbody>
                                </table>
                                <div class="empty-state d-none p-5 text-center" id="emptyExportState">
                                    <i class="fas fa-file-export fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Export History</h5>
                                    <p class="text-muted">Your export history will appear here.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Help/Info Sidebar -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Export Help & Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-3">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Choose the data type and format you need.</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Apply filters for precise exports.</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Use the Export History to re-download previous files.</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>CSV is best for spreadsheets, PDF for sharing.</li>
                            </ul>
                            <div class="alert alert-info small mb-0">
                                <strong>Supported Formats:</strong> CSV, PDF<br>
                                <strong>Note:</strong> Large exports may take longer to generate.<br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Export Detail Modal -->
<div class="modal fade" id="exportDetailModal" tabindex="-1" aria-labelledby="exportDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportDetailModalLabel">Export Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="exportDetailBody">
        <!-- Details will be loaded here dynamically -->
      </div>
    </div>
  </div>
</div>

<!-- Delete Export Modal -->
<div class="modal fade" id="deleteExportModal" tabindex="-1" aria-labelledby="deleteExportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteExportModalLabel">Delete Export Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this export record? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteExport">Delete</button>
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
<script src="../assets/js/export-data.js"></script>

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
.card-body .table-responsive {
    border-radius: 0.375rem;
}
.badge {
    font-size: 0.75em;
    padding: 0.375rem 0.75rem;
}
.summary-card {
    transition: transform 0.2s ease-in-out;
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    height: 100%;
    min-height: 160px;
    display: flex;
    flex-direction: column;
}
.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
}
.summary-card .card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.summary-card h6 {
    font-size: 0.875rem;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}
.summary-card h4 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}
.btn-group .dropdown-toggle::after {
    margin-left: 0.5rem;
}
.dropdown-menu {
    min-width: 200px;
    padding: 0.5rem 0;
    margin: 0;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}
.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: background-color 0.15s ease-in-out;
}
.dropdown-item:hover {
    background-color: #f8f9fa;
}
.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}
#exportRecordCount {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}
.table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    font-size: 0.875rem;
    color: #495057;
}
.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}
.form-select:focus, .form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}
.card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.card-header {
    border-bottom: 1px solid #eee;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem 0.75rem 0 0 !important;
}
.card-title {
    font-weight: 600;
    color: rgb(50 49 57);
}
</style>