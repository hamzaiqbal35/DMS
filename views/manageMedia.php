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
        <title>Inventory Media Management</title>

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
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            
            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                transform: translateY(-3px);
            }
            
            .card-img-top {
                object-fit: cover;
                height: 200px;
                width: 100%;
                border-top-left-radius: var(--border-radius);
                border-top-right-radius: var(--border-radius);
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
            
            .modal-header.bg-info {
                background-color: var(--success-color) !important;
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
            
            .btn-upload-media {
                background: linear-gradient(45deg, var(--success-color), #2ec4b6);
                border: none;
                color: var(--dark-color);
                font-weight: 600;
                padding: 0.625rem 1.25rem;
                border-radius: 2rem;
                box-shadow: 0 4px 6px rgba(76, 201, 240, 0.3);
                transition: all 0.3s ease;
            }
            
            .btn-upload-media:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 8px rgba(76, 201, 240, 0.4);
                color: var(--dark-color);
            }
            
            .action-buttons .btn {
                margin-right: 0.25rem;
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .media-card {
                transition: all 0.3s ease;
                margin-bottom: 1.5rem;
            }
            
            .media-card .card-body {
                padding: 1rem;
            }
            
            .media-card .card-title {
                font-size: 1rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: var(--dark-color);
            }
            
            .media-card .card-text {
                color: #6c757d;
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .media-buttons {
                display: flex;
                justify-content: space-between;
                margin-top: 0.5rem;
            }
            
            .media-buttons .btn {
                margin: 0 4px;
                flex: 1;
            }
            
            .empty-gallery {
                text-align: center;
                padding: 3rem 0;
            }
            
            .empty-gallery i {
                font-size: 3rem;
                color: #d1d5db;
                margin-bottom: 1rem;
            }
            
            .empty-gallery p {
                color: #6c757d;
                margin-bottom: 1.5rem;
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
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <main class="main-content">
                <div class="container-fluid fade-in">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="fas fa-images text-dark me-2"></i>Inventory Media</h2>
                        </div>
                        <button class="btn btn-upload-media slide-in" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-2"></i> Upload Image
                        </button>
                    </div>

                    <div id="mediaMessage"></div>

                    <div class="row g-4" id="mediaGallery">
                        <!-- Images will be loaded via AJAX -->
                        
                        <!-- Empty state display when no media -->
                        <div class="col-12 empty-gallery d-none" id="emptyGallery">
                            <i class="fas fa-photo-video"></i>
                            <h5>No Media Found</h5>
                            <p>Start by uploading your first image for an inventory item.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-upload me-1"></i> Upload Image
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-position">
                <form id="uploadForm" enctype="multipart/form-data" method="POST">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="uploadModalLabel"><i class="fas fa-upload me-2"></i>Upload Inventory Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-12">
                                <label for="item_id" class="form-label"><i class="fas fa-box me-1"></i> Select Inventory Item</label>
                                <select name="item_id" id="item_id" class="form-select" required size="5">
                                    <option value="">-- Select Item --</option>
                                    <!-- Options loaded dynamically via JavaScript -->
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="media_file" class="form-label"><i class="fas fa-file-image me-1"></i> Choose Image</label>
                                <input type="file" name="media_file" id="media_file" class="form-control" accept="image/*" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Upload Image
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Detail Modal -->
        <div class="modal fade" id="itemDetailModal" tabindex="-1" aria-labelledby="itemDetailLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-modal-position">
                <div class="modal-content">
                    <div class="modal-header bg-info text-dark">
                        <h5 class="modal-title" id="itemDetailLabel"><i class="fas fa-info-circle me-2"></i>Product Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="itemDetailsBody">
                        <!-- Product details loaded via JS -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteMediaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered custom-modal-position">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this image? This action cannot be undone.</p>
                        <input type="hidden" id="delete_media_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete Image
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
        <script src="../assets/js/media-management.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#item_id').select2({
                    dropdownParent: $('#uploadModal'),
                    width: '100%',
                    dropdownCssClass: 'select-custom-height'
                });
            });
        </script>
    </body>
</html>