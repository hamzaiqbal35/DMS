$(document).ready(function () {

    // Fetch all users on load
    fetchUsers();

    function fetchUsers() {
        $.ajax({
            url: '/DMS/model/user/fetchUserList.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const tbody = $('#userTable tbody');
                tbody.empty();

                if (response.status === 'success' && response.data && response.data.length > 0) {
                    // Sort users by ID in ascending order
                    response.data.sort((a, b) => a.id - b.id);
                    
                    $.each(response.data, function (i, user) {
                        tbody.append(`
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.full_name}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td>${user.created_at}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item editUser" href="#" 
                                                    data-id="${user.id}" 
                                                    data-name="${user.full_name}" 
                                                    data-email="${user.email}" 
                                                    data-role="${user.role_id}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteUser" href="#" 
                                                    data-id="${user.id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                    $('#userTable').show();
                    $('#emptyState').addClass('d-none');
                    
                    const searchText = $('#searchInput').val();
                    if (searchText) {
                        $('#searchInput').trigger('keyup');
                    }
                } else {
                    $('#userTable').hide();
                    $('#emptyState').removeClass('d-none');
                    $('#emptyState').find('h5').text('No Users Found');
                    $('#emptyState').find('p').text('Start by adding your first user or try a different search term.');
                }
            },
            error: function() {
                showMessage('Error fetching users. Please try again.', 'error');
            }
        });
    }

    // Function to properly close modal and cleanup
    function closeModal(modalId) {
        const modalElement = document.getElementById(modalId);
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
        
        // Additional cleanup to remove backdrop and modal-open class
        setTimeout(() => {
            // Remove any remaining backdrop
            $('.modal-backdrop').remove();
            // Remove modal-open class from body
            $('body').removeClass('modal-open');
            // Reset body style
            $('body').css('overflow', '');
            $('body').css('padding-right', '');
        }, 150);
    }

    // Add User
    $('#addUserForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/user/addUser.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                $('#addUserModal').modal('hide');
                $('#addUserForm')[0].reset();
                showMessage(response.message, response.status);
                fetchUsers();
                $('.modal-backdrop').remove();
                $('body').css({
                    'overflow': '',
                    'padding-right': ''
                });
            },
            error: function () {
                showMessage('Error adding user.', 'error');
            }
        });
    });

    // Set values in Edit Modal
    $(document).on('click', '.editUser', function () {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_full_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_role_id').val($(this).data('role'));
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editModal.show();
    });

    // Update User
    $('#editUserForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/user/updateUser.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                $('#editUserModal').modal('hide');
                $('#editUserForm')[0].reset();
                showMessage(response.message, response.status);
                fetchUsers();
                $('.modal-backdrop').remove();
                $('body').css({
                    'overflow': '',
                    'padding-right': ''
                });
            },
            error: function () {
                showMessage('Error updating user.', 'error');
            }
        });
    });

    // Delete User
    $(document).on('click', '.deleteUser', function () {
        const userId = $(this).data('id');
        $('#delete_user_id').val(userId);
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        deleteModal.show();
    });

    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#delete_user_id').val();
        $.ajax({
            url: '../model/user/deleteUser.php',
            method: 'POST',
            data: { user_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deleteUserModal').modal('hide');
                    fetchUsers();
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                }
            },
            error: function () {
                showMessage('Error deleting user.', 'error');
            }
        });
    });

    // Modal event listeners to ensure proper cleanup
    $('#addUserModal, #editUserModal, #deleteUserModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300);
    });

    // Flash message utility
    function showMessage(message, type = 'success') {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        
        // First check if we have any rows to search
        const rows = $('#userTable tbody tr');
        
        if (rows.length === 0) {
            return; // No data to search
        }
        
        let hasVisibleRows = false;
        
        // Filter the table
        rows.each(function() {
            const name = $(this).find('td:nth-child(2)').text().toLowerCase();
            const email = $(this).find('td:nth-child(3)').text().toLowerCase();
            const role = $(this).find('td:nth-child(4)').text().toLowerCase();
            
            // Check if any of the fields contain the search text
            if (name.includes(searchText) || 
                email.includes(searchText) || 
                role.includes(searchText)) {
                $(this).show();
                hasVisibleRows = true;
            } else {
                $(this).hide();
            }
        });
        
        // Show/hide empty state based on search results
        if (!hasVisibleRows) {
            // No matching rows found
            $('#emptyState').removeClass('d-none').find('p').text('No users match your search criteria.');
            $('#userTable').hide();
        } else {
            // Show table, hide empty state
            $('#emptyState').addClass('d-none');
            $('#userTable').show();
        }
    });

    // Clear search when adding a new user
    $('#addUserModal').on('shown.bs.modal', function() {
        $('#searchInput').val('');
        $('#userTable tbody tr').show();
        $('#userTable').show();
        $('#emptyState').addClass('d-none');
    });

    // Password visibility toggle
    $('#togglePassword').on('click', function() {
        const passwordInput = $('input[name="password"]');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});