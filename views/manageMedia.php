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
                                    <option value="" disabled selected>Select inventory item...</option>
                                    <!-- Options loaded dynamically via JavaScript -->
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="media_file" class="form-label"><i class="fas fa-file-image me-1"></i> Choose Image</label>
                                <input type="file" name="media_file" id="media_file" class="form-control" accept="image/*" required placeholder="Choose image file">
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
        
        <link href="../assets/css/bootstrap.min.css" rel="stylesheet">        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">        
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="../assets/css/styles.css" rel="stylesheet">
        <link href="../assets/css/animations.css" rel="stylesheet">

