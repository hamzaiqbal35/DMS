$(document).ready(function () {

    // Load category data
    function loadCategories() {
        $.ajax({
            url: '../model/category/fetchCategories.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                let rows = '';
                if (data.status === 'success' && Array.isArray(data.data)) {
                    data.data.forEach(function (item, index) {
                        rows += `
                            <tr>
                                <td>${item.category_id}</td>
                                <td>${item.category_name}</td>
                                <td>${item.description}</td>
                                <td>${item.status}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item editCategoryBtn" href="#" 
                                                    data-id="${item.category_id}" 
                                                    data-name="${item.category_name}" 
                                                    data-description="${item.description}"
                                                    data-status="${item.status}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteCategoryBtn" href="#" 
                                                    data-id="${item.category_id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    rows = `<tr><td colspan="5" class="text-center">No categories found.</td></tr>`;
                }
                $('#categoryTable tbody').html(rows);
                
                // Apply current search filter after reloading
                const currentSearch = $('#searchInput').val().toLowerCase();
                if (currentSearch) {
                    filterCategories(currentSearch);
                }
            },
            error: function () {
                $('#categoryTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load categories.</td></tr>');
            }
        });
    }

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterCategories(searchTerm);
    });

    // Function to filter categories
    function filterCategories(searchTerm) {
        const rows = $('#categoryTable tbody tr');
        
        if (searchTerm === '') {
            rows.show();
            if (rows.length === 0 || (rows.length === 1 && rows.find('td').attr('colspan'))) {
                $('#emptyState').removeClass('d-none');
            } else {
                $('#emptyState').addClass('d-none');
            }
            return;
        }
        
        let matchFound = false;
        
        rows.each(function() {
            const row = $(this);
            const categoryName = row.find('td:eq(1)').text().toLowerCase();
            const description = row.find('td:eq(2)').text().toLowerCase();
            
            if (categoryName.includes(searchTerm) || description.includes(searchTerm)) {
                row.show();
                matchFound = true;
            } else {
                row.hide();
            }
        });
        
        if (!matchFound) {
            if (rows.filter(':visible').length === 0) {
                $('#emptyState').removeClass('d-none');
                if (rows.find('td[colspan="5"]').length === 0) {
                    $('#categoryTable tbody').append(`
                        <tr id="no-search-results">
                            <td colspan="5" class="text-center">
                                No categories matching "${searchTerm}" found
                            </td>
                        </tr>
                    `);
                }
            }
        } else {
            $('#emptyState').addClass('d-none');
            $('#no-search-results').remove();
        }
    }

    loadCategories(); // Initial load
    
    // Add category
    $('#addCategoryForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/category/addCategory.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                const modal = $('#addCategoryModal');
                modal.modal('hide');
                $('#addCategoryForm')[0].reset();
                showMessage(response.status, response.message);
                loadCategories();
                $('.modal-backdrop').remove();
                $('body').css({
                    'overflow': '',
                    'padding-right': ''
                });
            },
            error: function () {
                showMessage('danger', 'Error adding category.');
            }
        });
    });

    // Show data in edit modal
    $(document).on('click', '.editCategoryBtn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        const status = $(this).data('status');

        $('#edit_category_id').val(id);
        $('#edit_category_name').val(name);
        $('#edit_description').val(description);
        $('#edit_status').val(status);

        $('#editCategoryModal').modal('show');
    });

    // Update category
    $('#editCategoryForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/category/updateCategory.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                $('#editCategoryModal').modal('hide');
                $('#editCategoryForm')[0].reset();
                showMessage(response.status, response.message);
                loadCategories();
                $('.modal-backdrop').remove();
                $('body').css({
                    'overflow': '',
                    'padding-right': ''
                });
            },
            error: function () {
                showMessage('danger', 'Error updating category.');
            }
        });
    });

    // Delete category
    $(document).on('click', '.deleteCategoryBtn', function () {
        const id = $(this).data('id');
        const name = $(this).closest('tr').find('td:eq(1)').text();
        $('#delete_category_id').val(id);
        $('#deleteCategoryModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#delete_category_id').val();
        $.ajax({
            url: '../model/category/deleteCategory.php',
            method: 'POST',
            data: { category_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.status, response.message);
                if (response.status === 'success') {
                    $('#deleteCategoryModal').modal('hide');
                    loadCategories();
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                }
            },
            error: function () {
                showMessage('danger', 'Error deleting category.');
            }
        });
    });

    // Add event listeners for modal hidden events
    $('#addCategoryModal, #editCategoryModal, #deleteCategoryModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300);
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

});
