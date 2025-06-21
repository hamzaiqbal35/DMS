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
            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-chart-line text-dark me-2"></i>Reports</h2>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="exportBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-2"></i> Export Report
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportBtn">
                        <li><a class="dropdown-item export-action" href="#" data-export-format="pdf"><i class="fas fa-file-pdf me-2"></i> Export as PDF</a></li>
                        <li><a class="dropdown-item export-action" href="#" data-export-format="csv"><i class="fas fa-file-csv me-2"></i> Export as CSV</a></li>
                    </ul>
                    <button type="button" class="btn btn-secondary ms-2" id="refreshBtn">
                        <i class="fas fa-sync-alt me-2"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Report Filters</h5>
                </div>
                <div class="card-body">
                    <form id="reportFilters" class="row g-3">
                        <div class="col-md-4">
                            <label for="reportType" class="form-label">Report Type</label>
                            <select class="form-select" id="reportType" name="type">
                                <option value="">Select Report Type (All)</option>
                                <option value="sales">Sales</option>
                                <option value="purchases">Purchases</option>
                                <option value="inventory">Inventory</option>
                                <option value="customers">Customers</option>
                                <option value="vendors">Vendors</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="dateRange" class="form-label">Date Range</label>
                            <select class="form-select" id="dateRange" name="period">
                                <option value="">Select Date Range (All)</option>
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="180">Last 6 Months</option>
                                <option value="365">Last Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category_id">
                                <option value="">Select Category (All)</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="col-md-4 custom-date-range" style="display: none;">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="startDate" placeholder="Select start date">
                        </div>
                        <div class="col-md-4 custom-date-range" style="display: none;">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="endDate" placeholder="Select end date">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Select Status (All)</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="paymentStatus" class="form-label">Payment Status</label>
                            <select class="form-select" id="paymentStatus" name="payment_status">
                                <option value="">Select Payment Status (All)</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                            <button type="reset" class="btn btn-secondary btn-sm ms-2" id="resetBtn">
                                <i class="fas fa-undo me-2"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4" id="summaryCards">
                <div class="col-md-6">
                    <div class="card bg-primary text-white summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Profit Margin</h6>
                                    <div class="d-flex flex-column">
                                        <h4 id="profitMargin">0%</h4>
                                        <small class="text-white-50" id="profitValue">PKR 0</small>
                                    </div>
                                    <small class="text-white-50" id="profitMarginPeriod">Last 30 Days</small>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Inventory Value</h6>
                                    <h4 id="inventoryValue">PKR 0</h4>
                                    <small class="text-white-50" id="inventoryValueCategory">All Categories</small>
                                </div>
                                <i class="fas fa-boxes fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Sales vs Purchases Trend</h5>
                            <div class="chart-filters">
                                <select class="form-select form-select-sm" id="chartDateRange">
                                    <option value="7">Last 7 Days</option>
                                    <option value="30" selected>Last 30 Days</option>
                                    <option value="90">Last 90 Days</option>
                                    <option value="180">Last 6 Months</option>
                                    <option value="365">Last Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="salesPurchasesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Category Distribution</h5>
                            <div class="chart-filters">
                                <select class="form-select form-select-sm" id="categoryChartType">
                                    <option value="count">By Count</option>
                                    <option value="stock">By Stock</option>
                                    <option value="value">By Value</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Charts -->
            <div class="row mb-4">
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Stock Levels</h5>
                            <div class="chart-filters">
                                <select class="form-select form-select-sm" id="stockChartFilter">
                                    <option value="all">All Items</option>
                                    <option value="low">Low Stock</option>
                                    <option value="out">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-bar">
                                <canvas id="stockChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Payment Status</h5>
                            <div class="chart-filters d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm me-2" role="group" aria-label="Payment Status Source">
                                    <input type="radio" class="btn-check" name="paymentStatusSource" id="paymentStatusCombined" value="combined" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="paymentStatusCombined">Combined</label>
                                    <input type="radio" class="btn-check" name="paymentStatusSource" id="paymentStatusSales" value="sales" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="paymentStatusSales">Sales</label>
                                    <input type="radio" class="btn-check" name="paymentStatusSource" id="paymentStatusPurchases" value="purchases" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="paymentStatusPurchases">Purchases</label>
                                </div>
                                <select class="form-select form-select-sm" id="paymentChartPeriod">
                                    <option value="7">Last 7 Days</option>
                                    <option value="30" selected>Last 30 Days</option>
                                    <option value="90">Last 90 Days</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie">
                                <canvas id="paymentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detailed Report Data</h5>
                    <div id="recordCount" class="text-muted">No records found</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reportTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                        <div class="empty-state d-none p-5 text-center" id="emptyState">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Report Data Found</h5>
                            <p class="text-muted">Try adjusting your filters or select a different date range.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Record Detail Modal -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="recordDetailModalLabel">Record Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="recordDetailBody">
        <!-- Details will be loaded here dynamically -->
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
<script src="../assets/js/report-charts.js"></script>
<script src="../assets/js/reports.js"></script>

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

#recordCount {
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

.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.15s ease-in-out;
    color:rgb(168, 170, 172);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
    transform: translateY(-1px);
    color: #ffffff;
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
    border: none;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #545b62 0%, #3d4449 100%);
    transform: translateY(-1px);
}

.page-header h2 {
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chart-area, .chart-pie, .chart-bar {
    position: relative;
    height: 300px;
}

.modal-content {
    border-radius: 0.75rem;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #eee;
    border-radius: 0.75rem 0.75rem 0 0;
}

.modal-footer {
    border-top: 1px solid #eee;
    border-radius: 0 0 0.75rem 0.75rem;
}

.btn-close {
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
}

/* Custom date range styling */
.custom-date-range {
    transition: all 0.3s ease-in-out;
}

.custom-date-range.show {
    display: block !important;
    animation: slideIn 0.3s ease-in-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .summary-card {
        margin-bottom: 1rem;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

.chart-filters {
    min-width: 150px;
}

.chart-filters .form-select {
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
    padding: 0.25rem 2rem 0.25rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    appearance: none;
}

.chart-filters .form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.chart-area, .chart-pie, .chart-bar {
    position: relative;
    height: 300px;
    margin: 0 auto;
}
</style>

<script>
// Chart filter event listeners
document.getElementById('chartDateRange').addEventListener('change', function() {
    ReportCharts.loadSalesPurchasesData();
});

document.getElementById('categoryChartType').addEventListener('change', function() {
    ReportCharts.loadCategoryData();
});

document.getElementById('stockChartFilter').addEventListener('change', function() {
    ReportCharts.loadStockData();
});

document.getElementById('paymentChartPeriod').addEventListener('change', function() {
    ReportCharts.loadPaymentData();
});
</script>