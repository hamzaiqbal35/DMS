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
        <title>User Management</title>

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
            
            .btn-add-user {
                background: linear-gradient(45deg, var(--success-color), #2ec4b6);
                border: none;
                color: var(--dark-color);
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(76, 201, 240, 0.3);
                transition: all 0.3s ease;
            }
            
            .btn-add-user:hover {
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

            .modal-dialog-scrollable .modal-body {
                padding-top: 1rem;
                padding-bottom: 1rem;
                overflow-y: auto;
            }

            .custom-modal-position {
                margin-top: 40px;
                margin-left: 500px; 
            }

            #addUserForm .modal-header {
                background-color: var(--primary-color) !important;
            }

            #editUserForm .modal-header {
                background-color: #f9c74f !important;
            }
            
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
                                <label><i class="fas fa-user me-1"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label><i class="fas fa-lock me-1"></i> Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label><i class="fas fa-user-tag me-1"></i> Role</label>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role</option>
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
                                <label><i class="fas fa-user me-1"></i> Full Name</label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label><i class="fas fa-user-tag me-1"></i> Role</label>
                                <select name="role_id" id="edit_role_id" class="form-select" required>
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
        <script src="../assets/js/jquery.min.js"></script>
        <script src="../assets/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/scripts.js"></script>
        <script src="../assets/js/animations.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../assets/js/user-management.js"></script>
        
    </body>
</html>