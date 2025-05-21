<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Management</title>

        <!-- Bootstrap CSS -->
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Custom CSS -->
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">
        
        <style>
            :root {
                --primary-color: #4361ee;
                --secondary-color: #3f37c9;
                --success-color: #4cc9f0;
                --warning-color: #f72585;
                --light-color: #f8f9fa;
                --dark-color: #212529;
                --border-radius: 0.5rem;
                --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                --transition: all 0.3s ease;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f5f7fa;
            }
            
            .page-wrapper {
                min-height: 100vh;
            }
            
            .main-content {
                padding: 2rem;
            }
            
            .page-header {
                margin-bottom: 2rem;
                position: relative;
            }
            
            .page-header h2 {
                font-weight: 600;
                color: var(--dark-color);
                margin-bottom: 0.5rem;
            }
            
            .page-header p {
                color: #6c757d;
                margin-bottom: 0;
            }
            
            .card {
                border: none;
                border-radius: var(--border-radius);
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                transition: var(--transition);
            }
            
            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            .table th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.8rem;
                letter-spacing: 0.5px;
                background-color: var(--dark-color);
                color: white;
                border: none;
            }
            
            .table td {
                vertical-align: middle;
                padding: 0.75rem;
                border-color: #e9ecef;
            }
            
            .table-hover tbody tr:hover {
                background-color: rgba(67, 97, 238, 0.05);
            }
            
            .btn {
                border-radius: var(--border-radius);
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: var(--transition);
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }
            
            .btn-primary:hover {
                background-color: var(--secondary-color);
                border-color: var(--secondary-color);
            }
            
            .btn-success {
                background-color: var(--success-color);
                border-color: var(--success-color);
                color: var(--dark-color);
            }
            
            .btn-success:hover {
                background-color: #2ec4b6;
                border-color: #2ec4b6;
            }
            
            .btn-warning {
                background-color: #f9c74f;
                border-color: #f9c74f;
                color: var(--dark-color);
            }
            
            .btn-danger {
                background-color: var(--warning-color);
                border-color: var(--warning-color);
            }
            
            .btn-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .form-control {
                border-radius: var(--border-radius);
                padding: 0.625rem 1rem;
                border: 1px solid #ced4da;
                transition: var(--transition);
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
            }
            
            .search-container {
                position: relative;
                margin-bottom: 1.5rem;
            }
            
            .search-container .search-icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: #6c757d;
            }
            
            .search-container input {
                padding-left: 2.5rem;
                border-radius: 2rem;
                border: 1px solid #ced4da;
                box-shadow: var(--box-shadow);
            }
            
            .search-container input:focus {
                border-color: var(--primary-color);
            }
            
            .modal-content {
                border-radius: var(--border-radius);
                border: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            .modal-header {
                border-top-left-radius: calc(var(--border-radius) - 1px);
                border-top-right-radius: calc(var(--border-radius) - 1px);
                padding: 1rem 1.5rem;
            }
            
            .modal-header.bg-primary {
                background-color: var(--primary-color) !important;
            }
            
            .modal-header.bg-warning {
                background-color: #f9c74f !important;
            }
            
            .modal-header.bg-success {
                background-color: var(--success-color) !important;
            }
            
            .modal-header.bg-danger {
                background-color: var(--warning-color) !important;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .slide-in {
                animation: slideIn 0.3s ease-in-out;
            }
            
            @keyframes slideIn {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .btn-add-purchase {
                background: linear-gradient(45deg, var(--success-color), #2ec4b6);
                border: none;
                color: var(--dark-color);
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(76, 201, 240, 0.3);
                transition: all 0.3s ease;
            }
            
            .btn-add-purchase:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 8px rgba(76, 201, 240, 0.4);
                color: var(--dark-color);
            }
            
            .action-buttons .btn {
                margin-right: 0.25rem;
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .badge {
                font-weight: 500;
                padding: 0.35em 0.65em;
                border-radius: 0.25rem;
            }
            
            .badge-id {
                background-color: #e9ecef;
                color: #495057;
            }
            
            .empty-state {
                text-align: center;
                padding: 3rem 0;
            }
            
            .empty-state i {
                font-size: 3rem;
                color: #d1d5db;
                margin-bottom: 1rem;
            }
            
            .empty-state p {
                color: #6c757d;
                margin-bottom: 1.5rem;
            }
            
            .modal-dialog-scrollable .modal-content {
                max-height: 78vh;
                overflow: hidden;
            }

            .modal-dialog-scrollable .modal-header {
                position: sticky;
                top: 0;
                z-index: 1050;
                background-color: inherit;
            }

            .modal-dialog-scrollable .modal-footer {
                position: sticky;
                bottom: 0;
                z-index: 1050;
                background-color: #fff;
            }

            /* Ensure the modal body has proper padding and doesn't overlap */
            .modal-dialog-scrollable .modal-body {
                padding-top: 1rem;
                padding-bottom: 1rem;
                overflow-y: auto;
            }

            /* custom modal positioning - adjust these values as needed */
            .custom-modal-position {
                margin-top: 40px;
                margin-left: 270px; 
            }

            /* Fix for the close button to be visible on colored headers */
            .modal-header .btn-close {
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                padding: 0.5rem;
                margin: -0.5rem;
            }
            
            /* Status badges */
            .badge-pending {
                background-color: #f0ad4e;
                color: #fff;
            }
            
            .badge-partial {
                background-color: #5bc0de;
                color: #fff;
            }
            
            .badge-paid {
                background-color: #5cb85c;
                color: #fff;
            }
            
            .badge-in-transit {
                background-color: #0275d8;
                color: #fff;
            }
            
            .badge-delivered {
                background-color: #5cb85c;
                color: #fff;
            }
            
            .badge-delayed {
                background-color: #d9534f;
                color: #fff;
            }
            
            /* Button styling for reports */
            .btn-info {
                background-color: #17a2b8;
                border-color: #17a2b8;
                color: #fff;
            }
            
            .btn-info:hover {
                background-color: #138496;
                border-color: #117a8b;
                color: #fff;
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <main class="main-content">
                <div class="container-fluid fade-in">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-shopping-cart text-dark me-2"></i>Purchase Management</h2>
                        </div>
                        <div>
                            <a href="purchaseReports.php" class="btn btn-info me-2 slide-in">
                                <i class="fas fa-chart-bar me-1"></i> Reports
                            </a>
                            <button class="btn btn-add-purchase slide-in" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
                                <i class="fas fa-plus me-2"></i> Add Purchase
                            </button>
                        </div>
                    </div>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search purchases by number, vendor, date...">
                    </div>

                    <div id="purchaseMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="purchaseTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">Purchase #</th>
                                            <th width="20%">Vendor</th>
                                            <th width="15%">Purchase Date</th>
                                            <th width="15%">Total Amount</th>
                                            <th width="10%">Payment</th>
                                            <th width="10%">Delivery</th>
                                            <th width="20%">Actions</th>
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
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="purchaseForm">
                    <input type="hidden" name="purchase_id" id="purchase_id">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label><i class="fas fa-hashtag me-1"></i> Purchase Number</label>
                                    <input type="text" name="purchase_number" id="purchase_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-building me-1"></i> Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="form-select" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-calendar me-1"></i> Purchase Date</label>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-truck me-1"></i> Expected Delivery</label>
                                    <input type="date" name="expected_delivery" id="expected_delivery" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-dollar-sign me-1"></i> Total Amount</label>
                                    <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-money-bill-wave me-1"></i> Payment Status</label>
                                    <select name="payment_status" id="payment_status" class="form-select" required>
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-shipping-fast me-1"></i> Delivery Status</label>
                                    <select name="delivery_status" id="delivery_status" class="form-select" required>
                                        <option value="pending">Pending</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="delayed">Delayed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-file-invoice me-1"></i> Attach Invoice</label>
                                    <input type="file" name="invoice_file" id="invoice_file" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label><i class="fas fa-sticky-note me-1"></i> Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3"><i class="fas fa-list me-2"></i>Purchase Items</h5>
                            
                            <!-- Item Entry Section -->
                            <div class="row g-2 mb-3 border p-2 rounded">
                                <div class="col-md-3">
                                    <select id="item_id" class="form-select">
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="item_quantity" class="form-control" placeholder="Qty" min="1">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="item_price" class="form-control" placeholder="Price" min="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="item_discount" class="form-control" placeholder="Discount" min="0" value="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="item_tax" class="form-control" placeholder="Tax" min="0" value="0">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="addItemToPurchase" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <!-- Purchase Items Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Tax</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseItemsContainer">
                                        <tr><td colspan="7" class="text-center">No items added.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Save Purchase
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Purchase Modal -->
        <div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="editPurchaseForm">
                    <input type="hidden" name="edit_purchase_id" id="edit_purchase_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label><i class="fas fa-hashtag me-1"></i> Purchase Number</label>
                                    <input type="text" name="purchase_number" id="edit_purchase_number" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-building me-1"></i> Vendor</label>
                                    <select name="vendor_id" id="edit_vendor_id" class="form-select" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-calendar me-1"></i> Purchase Date</label>
                                    <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-truck me-1"></i> Expected Delivery</label>
                                    <input type="date" name="expected_delivery" id="edit_expected_delivery" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-dollar-sign me-1"></i> Total Amount</label>
                                    <input type="number" step="0.01" name="total_amount" id="edit_total_amount" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-money-bill-wave me-1"></i> Payment Status</label>
                                    <select name="payment_status" id="edit_payment_status" class="form-select" required>
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-shipping-fast me-1"></i> Delivery Status</label>
                                    <select name="delivery_status" id="edit_delivery_status" class="form-select" required>
                                        <option value="pending">Pending</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="delayed">Delayed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-file-invoice me-1"></i> Attach Invoice</label>
                                    <input type="file" name="invoice_file" id="edit_invoice_file" class="form-control">
                                    <small class="text-muted">Leave empty to keep current file</small>
                                </div>
                                <div class="col-12">
                                    <label><i class="fas fa-sticky-note me-1"></i> Notes</label>
                                    <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3"><i class="fas fa-list me-2"></i>Purchase Items</h5>
                            
                            <!-- Item Entry Section for Edit Modal -->
                            <div class="row g-2 mb-3 border p-2 rounded">
                                <div class="col-md-3">
                                    <select id="edit_item_id" class="form-select">
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="edit_item_quantity" class="form-control" placeholder="Qty" min="1">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="edit_item_price" class="form-control" placeholder="Price" min="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="edit_item_discount" class="form-control" placeholder="Discount" min="0" value="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" id="edit_item_tax" class="form-control" placeholder="Tax" min="0" value="0">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="addEditItemToPurchase" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>

                            <!-- Purchase Items Table for Edit Modal -->
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Tax</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editPurchaseItemsContainer">
                                        <tr><td colspan="7" class="text-center">No items added.</td></tr>
                                    </tbody>
                                </table>
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

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deletePurchaseModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this purchase? This action cannot be undone.</p>
                        <input type="hidden" id="delete_purchase_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeletePurchase" class="btn btn-danger">
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
        
    </body>
</html>