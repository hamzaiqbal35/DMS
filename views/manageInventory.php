<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../inc/config/auth.php';
include_once '../inc/header.php';
include_once '../inc/navigation.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory Management</title>

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
            
            .table-responsive {
                overflow-x: auto;
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
            
            .form-select {
                border-radius: var(--border-radius);
                padding: 0.625rem 1rem;
                border: 1px solid #ced4da;
                transition: var(--transition);
            }
            
            .form-select:focus {
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
                padding: 1rem 1rem;
            }
            
            .modal-header.bg-primary {
                background-color: var(--primary-color) !important;
            }
            
            .modal-header.bg-warning {
                background-color: #f9c74f !important;
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
            
            .btn-add-item {
                background: linear-gradient(45deg, var(--success-color), #2ec4b6);
                border: none;
                color: var(--dark-color);
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(76, 201, 240, 0.3);
                transition: all 0.3s ease;
            }
            
            .btn-add-item:hover {
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
            
            .filter-container {
                margin-bottom: 1.5rem;
            }
            
            /* Limit modal height and enable vertical scroll inside modal body */
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

            /* Ensure the modal header colors are preserved */
            #addItemModal .modal-header {
                background-color: var(--primary-color) !important;
            }

            #editItemModal .modal-header {
                background-color: #f9c74f !important;
            }

            #addStockModal .modal-header {
                background-color: var(--success-color) !important;
            }

            /* Fix for the close button to be visible on colored headers */
            .modal-header .btn-close {
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                padding: 0.5rem;
                margin: -0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <main class="main-content">
                <div class="container-fluid fade-in">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-boxes text-dark me-2"></i>Inventory Management</h2>
                        </div>
                        <button class="btn btn-add-item slide-in" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus me-2"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="row mb-3 g-3 filter-container">
                        <div class="col-md-4">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search Product Name or Item Number ...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="filterCategory" class="form-select">
                                <option value="">Filter by Category</option>
                                <!-- Categories loaded via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatus" class="form-select">
                                <option value="">Filter by Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-secondary w-100" id="resetFilters">Reset Filters</button>
                        </div>
                    </div>
                    
                    <div class="row mb-3 g-3">
                        <div class="col-md-4">
                            <input type="number" id="filterMinPrice" class="form-control" placeholder="Min Price">
                        </div>
                        <div class="col-md-4">
                            <input type="number" id="filterMaxPrice" class="form-control" placeholder="Max Price">
                        </div>
                        <div class="col-md-4">
                            <input type="number" id="filterMinStock" class="form-control" placeholder="Min Stock">
                        </div>
                    </div>

                    <div id="inventoryMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="inventoryTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="10%">Item Number</th>
                                            <th width="15%">Item Name</th>
                                            <th width="10%">Category</th>
                                            <th width="8%">Unit</th>
                                            <th width="10%">Unit Price</th>
                                            <th width="10%">Current Stock</th>
                                            <th width="8%">Min Stock</th>
                                            <th width="10%">Status</th>
                                            <th width="14%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data populated via AJAX -->
                                    </tbody>
                                </table>
                                
                                <!-- Empty state display when no items -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-box-open"></i>
                                    <h5>No Inventory Items Found</h5>
                                    <p>Start by adding your first inventory item or try a different search term.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                        <i class="fas fa-plus me-1"></i> Add Item
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Add Item Modal -->
        <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="addItemForm">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label for="item_number" class="form-label"><i class="fas fa-hashtag me-1"></i> Item Number</label>
                                <input type="text" name="item_number" id="item_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="item_name" class="form-label"><i class="fas fa-tag me-1"></i> Item Name</label>
                                <input type="text" name="item_name" id="item_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label"><i class="fas fa-folder me-1"></i> Category</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <!-- Categories loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                                <input type="text" name="unit_of_measure" id="unit_of_measure" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="unit_price" class="form-label"><i class="fas fa-rupee-sign me-1"></i> Unit Price</label>
                                <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="minimum_stock" class="form-label"><i class="fas fa-level-down-alt me-1"></i> Minimum Stock</label>
                                <input type="number" step="0.01" name="minimum_stock" id="minimum_stock" class="form-control" min="0" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label"><i class="fas fa-info-circle me-1"></i> Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="editItemForm">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label for="edit_item_number" class="form-label"><i class="fas fa-hashtag me-1"></i> Item Number</label>
                                <input type="text" name="item_number" id="edit_item_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_item_name" class="form-label"><i class="fas fa-tag me-1"></i> Item Name</label>
                                <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_category_id" class="form-label"><i class="fas fa-folder me-1"></i> Category</label>
                                <select name="category_id" id="edit_category_id" class="form-select" required>
                                    <!-- Categories loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                                <input type="text" name="unit_of_measure" id="edit_unit_of_measure" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_unit_price" class="form-label"><i class="fas fa-dollar-sign me-1"></i> Unit Price</label>
                                <input type="number" step="0.01" name="unit_price" id="edit_unit_price" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_minimum_stock" class="form-label"><i class="fas fa-level-down-alt me-1"></i> Minimum Stock</label>
                                <input type="number" step="0.01" name="minimum_stock" id="edit_minimum_stock" class="form-control" min="0" required>
                            </div>
                            <div class="col-12">
                                <label for="edit_description" class="form-label"><i class="fas fa-info-circle me-1"></i> Description</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i> Update Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Stock Modal -->
        <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="addStockForm">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-dark">
                            <h5 class="modal-title"><i class="fas fa-cubes me-2"></i>Add Stock to: <span id="stock_item_name"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="item_id" id="stock_item_id">
                            <div class="mb-3">
                                <label for="stockQuantity" class="form-label"><i class="fas fa-plus me-1"></i> Quantity to Add</label>
                                <input type="number" name="quantity" id="stockQuantity" class="form-control" min="0.01" step="0.01" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Add Stock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this inventory item? This action cannot be undone.</p>
                        <p class="mb-0"><strong>Item: </strong><span id="delete_item_name"></span></p>
                        <input type="hidden" id="delete_item_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete">
                            <i class="fas fa-trash-alt me-1"></i> Delete Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once '../inc/footer.php'; ?>

        <!-- Scripts -->
        <script src="../assets/js/jquery.min.js"></script>
        <script src="../assets/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/inventory-management.js"></script>
        
    </body>
</html>