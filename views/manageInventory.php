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
                            <h2><i class="fas fa-boxes text-dark me-2"></i>Inventory Management</h2>
                        </div>
                        <button class="btn btn-add-item slide-in" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus me-2"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="row mb-3 g-3">
                        <div class="col-md-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search items...">
                        </div>
                        <div class="col-md-3">
                            <select id="filterCategory" class="form-select">
                                <option value="">Filter by Category</option>
                                <!-- Categories loaded via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterStatus" class="form-select">
                                <option value="">Filter by Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterWebsite" class="form-select">
                                <option value="">Website Status</option>
                                <option value="1">Show on Website</option>
                                <option value="0">Hidden</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterFeatured" class="form-select">
                                <option value="">Featured Status</option>
                                <option value="1">Featured</option>
                                <option value="0">Not Featured</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3 g-3">
                        <div class="col-md-3">
                            <input type="number" id="filterMinPrice" class="form-control" placeholder="Min Admin Price">
                        </div>
                        <div class="col-md-3">
                            <input type="number" id="filterMaxPrice" class="form-control" placeholder="Max Admin Price">
                        </div>
                        <div class="col-md-3">
                            <input type="number" id="filterMinStock" class="form-control" placeholder="Min Stock">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary w-100" id="resetFilters">Reset Filters</button>
                        </div>
                    </div>

                    <div id="inventoryMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="inventoryTable">
                                    <thead>
                                        <tr>
                                            <th width="4%">ID</th>
                                            <th width="8%">Item Number</th>
                                            <th width="12%">Item Name</th>
                                            <th width="16%">Category</th>
                                            <th width="6%">Unit</th>
                                            <th width="8%">Admin Price</th>
                                            <th width="12%">Customer Price</th>
                                            <th width="8%">Current Stock</th>
                                            <th width="6%">Min Stock</th>
                                            <th width="12%">Status & Controls</th>
                                            <th width="8%">Actions</th>
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
        <div class="modal fade modal-animate" id="addItemModal" tabindex="-1" aria-labelledby="addItemLabel" aria-hidden="true">
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
                                <input type="text" name="item_number" id="item_number" class="form-control" required placeholder="Enter item number">
                            </div>
                            <div class="col-md-6">
                                <label for="item_name" class="form-label"><i class="fas fa-tag me-1"></i> Item Name</label>
                                <input type="text" name="item_name" id="item_name" class="form-control" required placeholder="Enter item name">
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label"><i class="fas fa-folder me-1"></i> Category</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="" disabled selected>Select category...</option>
                                    <!-- Categories loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                                <input type="text" name="unit_of_measure" id="unit_of_measure" class="form-control" required placeholder="Enter unit (e.g., kg, pcs)">
                            </div>
                            <div class="col-md-6">
                                <label for="unit_price" class="form-label"><i class="fas fa-rupee-sign me-1"></i> Unit Price (Admin)</label>
                                <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" min="0" required placeholder="Enter unit price">
                            </div>
                            <div class="col-md-6">
                                <label for="customer_price" class="form-label"><i class="fas fa-tag me-1"></i> Customer Price</label>
                                <input type="number" step="0.01" name="customer_price" id="customer_price" class="form-control" min="0" placeholder="Enter customer price (optional)">
                                <small class="text-muted">Leave empty to use admin price</small>
                            </div>
                            <div class="col-md-6">
                                <label for="minimum_stock" class="form-label"><i class="fas fa-level-down-alt me-1"></i> Minimum Stock</label>
                                <input type="number" step="0.01" name="minimum_stock" id="minimum_stock" class="form-control" min="0" required placeholder="Enter minimum stock level">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label"><i class="fas fa-info-circle me-1"></i> Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                            </div>
                            
                            <!-- Customer Panel Control Section -->
                            <div class="col-12">
                                <hr>
                                <h6 class="text-primary"><i class="fas fa-globe me-2"></i>Customer Panel Settings</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_on_website" id="show_on_website" value="1" checked>
                                    <label class="form-check-label" for="show_on_website">
                                        <i class="fas fa-eye me-1"></i> Show on Customer Website
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1">
                                    <label class="form-check-label" for="is_featured">
                                        <i class="fas fa-star me-1"></i> Featured Product
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="seo_title" class="form-label"><i class="fas fa-search me-1"></i> SEO Title</label>
                                <input type="text" name="seo_title" id="seo_title" class="form-control" placeholder="Enter SEO title (optional)">
                                <small class="text-muted">For search engine optimization</small>
                            </div>
                            <div class="col-md-6">
                                <label for="seo_description" class="form-label"><i class="fas fa-search me-1"></i> SEO Description</label>
                                <textarea name="seo_description" id="seo_description" class="form-control" rows="2" placeholder="Enter SEO description (optional)"></textarea>
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
        <div class="modal fade modal-animate" id="editItemModal" tabindex="-1" aria-labelledby="editItemLabel" aria-hidden="true">
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
                                <input type="text" name="item_number" id="edit_item_number" class="form-control" required placeholder="Enter item number">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_item_name" class="form-label"><i class="fas fa-tag me-1"></i> Item Name</label>
                                <input type="text" name="item_name" id="edit_item_name" class="form-control" required placeholder="Enter item name">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_category_id" class="form-label"><i class="fas fa-folder me-1"></i> Category</label>
                                <select name="category_id" id="edit_category_id" class="form-select" required>
                                    <option value="" disabled selected>Select category...</option>
                                    <!-- Categories loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                                <input type="text" name="unit_of_measure" id="edit_unit_of_measure" class="form-control" required placeholder="Enter unit (e.g., kg, pcs)">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_unit_price" class="form-label"><i class="fas fa-dollar-sign me-1"></i> Unit Price (Admin)</label>
                                <input type="number" step="0.01" name="unit_price" id="edit_unit_price" class="form-control" min="0" required placeholder="Enter unit price">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_customer_price" class="form-label"><i class="fas fa-tag me-1"></i> Customer Price</label>
                                <input type="number" step="0.01" name="customer_price" id="edit_customer_price" class="form-control" min="0" placeholder="Enter customer price (optional)">
                                <small class="text-muted">Leave empty to use admin price</small>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_minimum_stock" class="form-label"><i class="fas fa-level-down-alt me-1"></i> Minimum Stock</label>
                                <input type="number" step="0.01" name="minimum_stock" id="edit_minimum_stock" class="form-control" min="0" required placeholder="Enter minimum stock level">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="edit_description" class="form-label"><i class="fas fa-info-circle me-1"></i> Description</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                            </div>
                            
                            <!-- Customer Panel Control Section -->
                            <div class="col-12">
                                <hr>
                                <h6 class="text-primary"><i class="fas fa-globe me-2"></i>Customer Panel Settings</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_on_website" id="edit_show_on_website" value="1">
                                    <label class="form-check-label" for="edit_show_on_website">
                                        <i class="fas fa-eye me-1"></i> Show on Customer Website
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured" value="1">
                                    <label class="form-check-label" for="edit_is_featured">
                                        <i class="fas fa-star me-1"></i> Featured Product
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_seo_title" class="form-label"><i class="fas fa-search me-1"></i> SEO Title</label>
                                <input type="text" name="seo_title" id="edit_seo_title" class="form-control" placeholder="Enter SEO title (optional)">
                                <small class="text-muted">For search engine optimization</small>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_seo_description" class="form-label"><i class="fas fa-search me-1"></i> SEO Description</label>
                                <textarea name="seo_description" id="edit_seo_description" class="form-control" rows="2" placeholder="Enter SEO description (optional)"></textarea>
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
        <div class="modal fade modal-animate" id="addStockModal" tabindex="-1" aria-labelledby="addStockLabel" aria-hidden="true">
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

        <!-- Reduce Stock Modal -->
        <div class="modal fade modal-animate" id="reduceStockModal" tabindex="-1" aria-labelledby="reduceStockLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="reduceStockForm">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title"><i class="fas fa-minus-circle me-2"></i>Reduce Stock from: <span id="reduce_stock_item_name"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="item_id" id="reduce_stock_item_id">
                            <div class="mb-3">
                                <label for="reduceStockQuantity" class="form-label"><i class="fas fa-minus me-1"></i> Quantity to Reduce</label>
                                <input type="number" name="quantity" id="reduceStockQuantity" class="form-control" min="0.01" step="0.01" required>
                                <small class="text-muted">Current stock: <span id="current_stock_display"></span></small>
                            </div>
                            <div class="mb-3">
                                <label for="reason" class="form-label"><i class="fas fa-info-circle me-1"></i> Reason for Reduction</label>
                                <select name="reason" id="reason" class="form-select" required>
                                    <option value="">Select a reason</option>
                                    <option value="damaged">Damaged/Defective</option>
                                    <option value="expired">Expired</option>
                                    <option value="lost">Lost/Missing</option>
                                    <option value="adjustment">Stock Adjustment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3" id="otherReasonDiv" style="display: none;">
                                <label for="other_reason" class="form-label">Specify Other Reason</label>
                                <input type="text" name="other_reason" id="other_reason" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i> Reduce Stock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade modal-animate" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
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
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/inventory-management.js"></script>
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">