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
                    showMediaMessage(response.message, 'danger');
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
                            <div class="col-md-3 mb-4">
                                <div class="card shadow-sm">
                                    <img src="../${media.file_path}" class="card-img-top" alt="Media">
                                    <div class="card-body text-center p-2 media-buttons">
                                        <button class="btn btn-sm btn-info viewDetails" data-id="${media.item_id}">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                        <button class="btn btn-sm btn-danger deleteMedia" data-id="${media.media_id}">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
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
                showMediaMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#uploadModal').modal('hide');
                    $('#uploadForm')[0].reset();
                    fetchMediaGallery();
                }
            },
            error: function (xhr) {
                showMediaMessage("Upload failed. Check file size and type.", 'danger');
            }
        });
    });

    // Delete image
    $(document).on('click', '.deleteMedia', function () {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this image?')) {
            $.ajax({
                url: '../model/inventory/deleteMedia.php',
                method: 'POST',
                data: { media_id: id },
                dataType: 'json',
                success: function (response) {
                    showMediaMessage(response.message, response.status);
                    if (response.status === 'success') {
                        fetchMediaGallery();
                    }
                }
            });
        }
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
                    showMediaMessage("Failed to load item details.", 'danger');
                }
            },
            error: function () {
                showMediaMessage("Error fetching item details.", 'danger');
            }
        });
    });

    // Flash message function
    function showMediaMessage(msg, type = 'success') {
        const alertType = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#mediaMessage').html(`<div class="alert ${alertType}">${msg}</div>`);
        setTimeout(() => $('#mediaMessage').html(''), 4000);
    }
});

