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
                            <h2><i class="fas fa-tags text-dark me-2"></i>Category Management</h2>
                        </div>
                        <button class="btn btn-add-category slide-in" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i> Add Category
                        </button>
                    </div>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search categories...">
                    </div>

                    <div id="categoryMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="categoryTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">ID</th>
                                            <th width="25%">Category Name</th>
                                            <th width="45%">Description</th>
                                            <th width="10%">Status</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data populated via AJAX -->
                                    </tbody>
                                </table>
                                
                                <!-- Empty state display when no categories -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-tags"></i>
                                    <h5>No Categories Found</h5>
                                    <p>Start by adding your first category or try a different search term.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus me-1"></i> Add Category
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Add Category Modal -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-position">
                <form id="addCategoryForm">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="addCategoryLabel"><i class="fas fa-plus-circle me-2"></i>Add Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-12">
                                <label for="add_category_name" class="form-label"><i class="fas fa-tag me-1"></i> Category Name</label>
                                <input type="text" name="category_name" id="add_category_name" class="form-control" required placeholder="Enter category name">
                            </div>
                            <div class="col-12">
                                <label for="add_description" class="form-label"><i class="fas fa-align-left me-1"></i> Description</label>
                                <textarea name="description" id="add_description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="add_status" class="form-label"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                <select name="status" id="add_status" class="form-select" required>
                                    <option value="" disabled selected>Select status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-position">
                <form id="editCategoryForm">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="editCategoryLabel"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-12">
                                <label for="edit_category_name" class="form-label"><i class="fas fa-tag me-1"></i> Category Name</label>
                                <input type="text" name="category_name" id="edit_category_name" class="form-control" required placeholder="Enter category name">
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
                                <i class="fas fa-save me-1"></i> Update Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Category Modal -->
        <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                        <input type="hidden" id="delete_category_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete Category
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
        <script src="../assets/js/category-management.js"></script>
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">