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
                                    <button class="btn btn-sm btn-warning editCategoryBtn" 
                                        data-id="${item.category_id}" 
                                        data-name="${item.category_name}" 
                                        data-description="${item.description}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger deleteCategoryBtn" 
                                        data-id="${item.category_id}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    rows = `<tr><td colspan="5" class="text-center">No categories found.</td></tr>`;
                }
                $('#categoryTable tbody').html(rows);
            },
            error: function () {
                $('#categoryTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load categories.</td></tr>');
            }
        });
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
                $('#addCategoryModal').modal('hide');
                $('#addCategoryForm')[0].reset();
                showMessage(response.status, response.message);
                loadCategories();
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

        $('#edit_category_id').val(id);
        $('#edit_category_name').val(name);
        $('#edit_description').val(description);

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
            },
            error: function () {
                showMessage('danger', 'Error updating category.');
            }
        });
    });

    // Delete category
    $(document).on('click', '.deleteCategoryBtn', function () {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this category?')) {
            $.ajax({
                url: '../model/category/deleteCategory.php',
                method: 'POST',
                data: { category_id: id },
                dataType: 'json',
                success: function (response) {
                    showMessage(response.status, response.message);
                    loadCategories();
                },
                error: function () {
                    showMessage('danger', 'Error deleting category.');
                }
            });
        }
    });

    // Flash message function
    function showMessage(type, message) {
        const alertBox = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#categoryMessage').html(alertBox);
        setTimeout(() => {
            $('#categoryMessage .alert').alert('close');
        }, 4000);
    }

});
