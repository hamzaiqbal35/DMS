$(document).ready(function () {
    fetchInventoryItems();
    fetchMediaGallery();

    // Populate item select dropdown
    function fetchInventoryItems() {
        $.ajax({
            url: '../model/inventory/showItemNames.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const itemSelect = $('#item_id');
                itemSelect.empty().append('<option value="">-- Select Item --</option>');
                if (response.status === 'success') {
                    response.data.forEach(item => {
                        itemSelect.append(`<option value="${item.item_id}">${item.item_name}</option>`);
                    });
                } else {
                    showMessage(response.message, 'danger');
                }
            }
        });
    }

    // Load media gallery
    function fetchMediaGallery() {
        $.ajax({
            url: '../model/inventory/fetchMedia.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const gallery = $('#mediaGallery');
                gallery.empty();

                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(media => {
                        gallery.append(`
                            <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                                <div class="card shadow-sm">
                                    <img src="../${media.file_path}" class="card-img-top preview-image" alt="Media" style="cursor: pointer;" data-image="../${media.file_path}">
                                    <div class="card-body text-center p-2">
                                        <hr class="divider">
                                        <div class="dropdown">
                                            <button class="btn btn-link text-dark" type="button" id="dropdownMenu${media.media_id}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu${media.media_id}">
                                                <li>
                                                    <a class="dropdown-item viewDetails" href="#" data-id="${media.item_id}">
                                                        <i class="fas fa-info-circle me-2"></i>Details
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger deleteMedia" href="#" data-id="${media.media_id}">
                                                        <i class="fas fa-trash-alt me-2"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    gallery.html('<div class="col-12 text-center text-muted">No images uploaded yet.</div>');
                }
            }
        });
    }

    // Upload form submit
    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: '../model/inventory/uploadImage.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#uploadModal').modal('hide');
                    $('#uploadForm')[0].reset();
                    fetchMediaGallery();
                    
                    // Remove modal backdrop and restore body overflow
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                }
            },
            error: function (xhr) {
                showMessage("Upload failed. Check file size and type.", 'danger');
            }
        });
    });

    // Delete image
    $(document).on('click', '.deleteMedia', function () {
        const id = $(this).data('id');
        $('#delete_media_id').val(id);
        $('#deleteMediaModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#delete_media_id').val();
        $.ajax({
            url: '../model/inventory/deleteMedia.php',
            method: 'POST',
            data: { media_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deleteMediaModal').modal('hide');
                    fetchMediaGallery();
                }
            }
        });
    });

    // View product details
    $(document).on('click', '.viewDetails', function () {
        const itemId = $(this).data('id');
        $.ajax({
            url: '../model/inventory/getItemDetails.php',
            method: 'GET',
            data: { item_id: itemId },
            dataType: 'json',
            success: function (response) {
                const body = $('#itemDetailsBody');
                if (response.status === 'success') {
                    const item = response.data;
                    body.html(`
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Item Number:</strong> ${item.item_number}<br>
                                <strong>Item Name:</strong> ${item.item_name}<br>
                                <strong>Category:</strong> ${item.category_name}<br>
                                <strong>Price:</strong> PKR ${item.unit_price}<br>
                            </div>
                            <div class="col-md-6">
                                <strong>Current Stock:</strong> ${item.current_stock}<br>
                                <strong>Minimum Stock:</strong> ${item.minimum_stock}<br>
                                <strong>Status:</strong> ${item.status}<br>
                            </div>
                            <div class="col-12 mt-3">
                                <strong>Description:</strong><br>
                                ${item.description || 'N/A'}
                            </div>
                        </div>
                    `);
                    $('#itemDetailModal').modal('show');
                } else {
                    showMessage("Failed to load item details.", 'danger');
                }
            },
            error: function () {
                showMessage("Error fetching item details.", 'danger');
            }
        });
    });

    // Event listener to ensure modal backdrop is removed after hiding
    $('#uploadModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove(); // Remove all modal backdrops
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300); // Increased delay for robustness
    });

    $('#deleteMediaModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove(); // Remove all modal backdrops
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300); // Increased delay for robustness
    });

    $('#itemDetailModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove(); // Remove all modal backdrops
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300); // Increased delay for robustness
    });

    function showMessage(msg, type = 'success') {
        // Use toastr for better looking notifications
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(msg);
        } else {
            toastr.error(msg);
        }
    }
    
    // Image Preview Functionality
    $(document).on('click', '.preview-image', function() {
        const imageUrl = $(this).data('image');
        const previewModal = `
            <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable custom-scrollable-modal custom-modal-position">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-3">
                            <img src="${imageUrl}" class="img-fluid preview-img" alt="Preview" style="max-height: 70vh; width: auto; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing preview modal
        $('#imagePreviewModal').remove();
        
        // Add new preview modal to body
        $('body').append(previewModal);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        modal.show();
        
        // Remove modal from DOM after it's hidden
        $('#imagePreviewModal').on('hidden.bs.modal', function () {
            $(this).remove();
        });
    });
});

$(document).ready(function() {
    // $('#item_id').select2({
    //     dropdownParent: $('#uploadModal'),
    //     width: '100%',
    //     dropdownCssClass: 'select-custom-height'
    // });
});