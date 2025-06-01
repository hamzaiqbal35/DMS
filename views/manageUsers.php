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
                            <h2><i class="fas fa-user-shield text-dark me-2"></i>User Management</h2>
                        </div>
                        <button class="btn btn-add-user slide-in" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i> Add User
                        </button>
                    </div>
                    
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search users by name, email, role...">
                    </div>

                    <div id="userMessage"></div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover" id="userTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="25%">Full Name</th>
                                            <th width="25%">Email</th>
                                            <th width="15%">Role</th>
                                            <th width="20%">Created At</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data populated via AJAX -->
                                    </tbody>
                                </table>
                                
                                <!-- Empty state display when no users -->
                                <div class="empty-state d-none" id="emptyState">
                                    <i class="fas fa-user-slash"></i>
                                    <h5>No Users Found</h5>
                                    <p>Start by adding your first user or try a different search term.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fas fa-user-plus me-1"></i> Add User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="addUserForm">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-12">
                                <label for="full_name" class="form-label"><i class="fas fa-user me-1"></i> Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Enter full name" autocomplete="name">
                            </div>
                            <div class="col-md-12">
                                <label for="email" class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" id="email" name="email" class="form-control" required placeholder="Enter email address" autocomplete="email">
                            </div>
                            <div class="col-md-12">
                                <label for="password" class="form-label"><i class="fas fa-lock me-1"></i> Password</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="role_id" class="form-label"><i class="fas fa-user-tag me-1"></i> Role</label>
                                <select id="role_id" name="role_id" class="form-select" required>
                                    <option value="" disabled selected>Select role...</option>
                                    <option value="1">Admin</option>
                                    <option value="2">Manager</option>
                                    <option value="3">Salesperson</option>
                                    <option value="4">Inventory Manager</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                <form id="editUserForm">
                    <input type="hidden" name="id" id="edit_user_id">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-12">
                                <label for="edit_full_name" class="form-label"><i class="fas fa-user me-1"></i> Full Name</label>
                                <input type="text" id="edit_full_name" name="full_name" class="form-control" required placeholder="Enter full name" autocomplete="name">
                            </div>
                            <div class="col-md-12">
                                <label for="edit_email" class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" id="edit_email" name="email" class="form-control" required placeholder="Enter email address" autocomplete="email">
                            </div>
                            <div class="col-md-12">
                                <label for="edit_role_id" class="form-label"><i class="fas fa-user-tag me-1"></i> Role</label>
                                <select id="edit_role_id" name="role_id" class="form-select" required>
                                    <option value="" disabled selected>Select role...</option>
                                    <option value="1">Admin</option>
                                    <option value="2">Manager</option>
                                    <option value="3">Salesperson</option>
                                    <option value="4">Inventory Manager</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i> Update User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                        <input type="hidden" id="delete_user_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once '../inc/footer.php'; ?>

        <!-- Scripts -->
        <script src="/DMS/assets/js/jquery.min.js"></script>
        <script src="/DMS/assets/js/scripts.js"></script>
        <script src="/DMS/assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="/DMS/assets/js/user-management.js"></script>
        
        <link href="/DMS/assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="/DMS/assets/css/styles.css" rel="stylesheet">
        <link href="/DMS/assets/css/animations.css" rel="stylesheet">