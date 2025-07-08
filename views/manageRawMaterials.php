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
                    <h2><i class="fas fa-cubes text-dark me-2"></i>Raw Material Management</h2>
                </div>
                <button class="btn btn-add-category slide-in" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                    <i class="fas fa-plus me-2"></i> Add Material
                </button>
            </div>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Search materials...">
            </div>

            <div id="materialMessage"></div>

            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="materialTable">
                            <thead>
                                <tr>
                                    <th width="10%">ID</th>
                                    <th width="15%">Code</th>
                                    <th width="20%">Name</th>
                                    <th width="15%">Unit</th>
                                    <th width="15%">Current Stock</th>
                                    <th width="15%">Status</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>

                        <!-- Empty state -->
                        <div class="empty-state d-none" id="emptyState">
                            <i class="fas fa-cubes"></i>
                            <h5>No Raw Materials Found</h5>
                            <p>Start by adding your first raw material or try a different search term.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                                <i class="fas fa-plus me-1"></i> Add Material
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="addMaterialForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addMaterialLabel"><i class="fas fa-plus-circle me-2"></i>Add Raw Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label for="material_code" class="form-label"><i class="fas fa-hashtag me-1"></i> Material Code</label>
                        <input type="text" name="material_code" id="material_code" class="form-control" required placeholder="Enter material code">
                    </div>
                    <div class="col-12">
                        <label for="material_name" class="form-label"><i class="fas fa-cube me-1"></i> Material Name</label>
                        <input type="text" name="material_name" id="material_name" class="form-control" required placeholder="Enter material name">
                    </div>
                    <div class="col-12">
                        <label for="unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                        <input type="text" name="unit_of_measure" id="unit_of_measure" class="form-control" required placeholder="Enter unit (e.g., kg, pcs)">
                    </div>
                    <div class="col-12">
                        <label for="minimum_stock" class="form-label"><i class="fas fa-box me-1"></i> Minimum Stock Level</label>
                        <input type="number" name="minimum_stock" id="minimum_stock" class="form-control" step="0.01" min="0" value="0" placeholder="Enter minimum stock level">
                        <small class="text-muted">This will be used to show warning when stock falls below this level</small>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label"><i class="fas fa-align-left me-1"></i> Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                    </div>
                    <div class="col-12">
                        <label for="status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="" disabled selected>Select status...</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Material
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-position">
        <form id="editMaterialForm">
            <input type="hidden" name="material_id" id="edit_material_id">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editMaterialLabel"><i class="fas fa-edit me-2"></i>Edit Raw Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label for="edit_material_code" class="form-label"><i class="fas fa-hashtag me-1"></i> Material Code</label>
                        <input type="text" name="material_code" id="edit_material_code" class="form-control" required placeholder="Enter material code">
                    </div>
                    <div class="col-12">
                        <label for="edit_material_name" class="form-label"><i class="fas fa-cube me-1"></i> Material Name</label>
                        <input type="text" name="material_name" id="edit_material_name" class="form-control" required placeholder="Enter material name">
                    </div>
                    <div class="col-12">
                        <label for="edit_unit_of_measure" class="form-label"><i class="fas fa-ruler me-1"></i> Unit of Measure</label>
                        <input type="text" name="unit_of_measure" id="edit_unit_of_measure" class="form-control" required placeholder="Enter unit (e.g., kg, pcs)">
                    </div>
                    <div class="col-12">
                        <label for="edit_min_stock" class="form-label"><i class="fas fa-box me-1"></i> Minimum Stock Level</label>
                        <input type="number" name="minimum_stock" id="edit_min_stock" class="form-control" step="0.01" min="0" value="0" placeholder="Enter minimum stock level">
                        <small class="text-muted">This will be used to show warning when stock falls below this level</small>
                    </div>
                    <div class="col-12">
                        <label for="edit_description" class="form-label"><i class="fas fa-align-left me-1"></i> Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                    </div>
                    <div class="col-12">
                        <label for="edit_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="" disabled selected>Select status...</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update Material
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Material Modal -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMaterialLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this raw material? This action cannot be undone.</p>
                <input type="hidden" name="material_id" id="delete_material_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i> Delete Material
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stock History Modal -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1" aria-labelledby="stockHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="stockHistoryLabel">
                    <i class="fas fa-chart-line me-2"></i>
                    Stock History: <span id="stockHistoryMaterialName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Current Stock</h6>
                                <h3 id="currentStockValue" class="mb-0">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Minimum Stock Level</h6>
                                <h3 id="minimumStockValue" class="mb-0">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody id="stockHistoryTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reduce Stock Modal -->
<div class="modal fade" id="reduceStockModal" tabindex="-1" aria-labelledby="reduceStockLabel" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
        <form id="reduceStockForm">
            <input type="hidden" name="material_id" id="reduce_material_id">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="reduceStockLabel"><i class="fas fa-minus-circle me-2"></i>Reduce Stock: <span id="reduceStockMaterialName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-12">
                        <label for="reduce_quantity" class="form-label">Reduce Quantity</label>
                        <input type="number" name="quantity" id="reduce_quantity" class="form-control" required min="0.01" step="0.01">
                    </div>
                     <div class="col-12">
                        <label for="reduction_reason" class="form-label">Reason for Reduction</label>
                        <input type="text" name="reason" id="reduction_reason" class="form-control" required placeholder="e.g., Used in production, Damaged, Lost">
                    </div>
                    <div class="col-12">
                        <label for="reduce_notes" class="form-label">Notes (Optional)</label>
                        <textarea name="notes" id="reduce_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-minus-circle me-1"></i> Reduce Stock</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include_once '../inc/footer.php'; ?>

        <!-- Scripts -->
        <script src="../assets/js/jquery.min.js"></script>
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/raw-material-management.js"></script>
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">
