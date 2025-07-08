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
                    <h2><i class="fas fa-chart-bar text-dark me-2"></i>Sales Reports</h2>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="exportBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-2"></i> Export Report
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportBtn">
                        <li><a class="dropdown-item" href="#" data-export-format="csv"><i class="fas fa-file-csv me-2"></i> Export as CSV</a></li>
                        <li><a class="dropdown-item" href="#" data-export-format="pdf"><i class="fas fa-file-pdf me-2"></i> Export as PDF</a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Report Filters</h5>
                </div>
                <div class="card-body">
                    <form id="reportFiltersForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select class="form-select" id="customer_id" name="customer_id">
                                <option value="">Select Customer (All)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" placeholder="Select start date">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" placeholder="Select end date">
                        </div>
                        <div class="col-md-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <option value="">Select Payment Status (All)</option>
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="order_status" class="form-label">Order Status</label>
                            <select class="form-select" id="order_status" name="order_status">
                                <option value="">Select Order Status (All)</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sale_type" class="form-label">Sale Type</label>
                            <select class="form-select" id="sale_type" name="sale_type">
                                <option value="">Select Sale Type (All)</option>
                                <option value="direct">Direct Sale</option>
                                <option value="from_order">From Customer Order</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="min_amount" class="form-label">Minimum Amount (PKR)</label>
                            <input type="number" class="form-control" id="min_amount" name="min_amount" step="0.01" min="0" placeholder="Enter minimum amount">
                        </div>
                        <div class="col-md-3">
                            <label for="max_amount" class="form-label">Maximum Amount (PKR)</label>
                            <input type="number" class="form-control" id="max_amount" name="max_amount" step="0.01" min="0" placeholder="Enter maximum amount">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                            <button type="reset" class="btn btn-secondary ms-2" id="resetBtn">
                                <i class="fas fa-undo me-2"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4" id="summaryCards" style="display: none;">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Records</h6>
                                    <h4 id="totalRecords">0</h4>
                                </div>
                                <i class="fas fa-list fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Amount</h6>
                                    <h4 id="totalAmount">PKR 0</h4>
                                </div>
                                <i class="fas fa-money-bill fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Paid</h6>
                                    <h4 id="totalPaid">PKR 0</h4>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Pending</h6>
                                    <h4 id="totalPending">PKR 0</h4>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Unique Customers</h6>
                                    <h4 id="uniqueCustomers">0</h4>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Average Sale</h6>
                                    <h4 id="averageSale">PKR 0</h4>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Results -->
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Sales Records</h5>
                    <div id="recordCount" class="text-muted"></div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reportTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Sale Type</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Pending Amount</th>
                                    <th>Payment Status</th>
                                    <th>Order Status</th>
                                    <th>Tracking #</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="empty-state d-none p-5 text-center" id="emptyState">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Sales Records Found</h5>
                            <p class="text-muted">Try adjusting your filters or select a different date range.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../inc/footer.php'; ?>

<!-- Scripts -->
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/animations.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="../assets/js/sale-reports.js"></script>

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

.summary-cards .card {
    transition: transform 0.2s ease-in-out;
}

.summary-cards .card:hover {
    transform: translateY(-2px);
}

#summaryCards .card {
    min-height: 120px; 
    height: 100%;
    display: flex;
    flex-direction: column;
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
</style>