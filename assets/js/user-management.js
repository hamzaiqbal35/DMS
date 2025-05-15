$(document).ready(function () {

    // Fetch all users on load
    fetchUsers();

    function fetchUsers() {
        $.ajax({
            url: '../model/user/fetchUserList.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const tbody = $('#userTable tbody');
                tbody.empty();

                if (response.status === 'success') {
                    $.each(response.data, function (i, user) {
                        tbody.append(`
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.full_name}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td>${user.created_at}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning editUser" data-id="${user.id}" data-name="${user.full_name}" data-email="${user.email}" data-role="${user.role_id}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger deleteUser" data-id="${user.id}"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append(`<tr><td colspan="6" class="text-center">${response.message}</td></tr>`);
                }
            }
        });
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
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addUserModal').modal('hide');
                    $('#addUserForm')[0].reset();
                    fetchUsers();
                }
            }
        });
    });

    // Set values in Edit Modal
    $(document).on('click', '.editUser', function () {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_full_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_role_id').val($(this).data('role'));
        $('#editUserModal').modal('show');
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
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editUserModal').modal('hide');
                    $('#editUserForm')[0].reset();
                    fetchUsers();
                }
            }
        });
    });

    // Delete User
    $(document).on('click', '.deleteUser', function () {
        if (confirm("Are you sure you want to delete this user?")) {
            const userId = $(this).data('id');
            $.ajax({
                url: '../model/user/deleteUser.php',
                method: 'POST',
                data: { id: userId },
                dataType: 'json',
                success: function (response) {
                    showMessage(response.message, response.status);
                    if (response.status === 'success') {
                        fetchUsers();
                    }
                }
            });
        }
    });

    // Flash message utility
    function showMessage(message, type = 'success') {
        const msgDiv = $('#userMessage');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        msgDiv.html(`<div class="alert ${alertClass}">${message}</div>`);
        setTimeout(() => msgDiv.html(''), 4000);
    }
});

// Add this code to your existing user-management.js file

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

// Update the fetchUsers function to reset search on data refresh
function fetchUsers() {
    $.ajax({
        url: '../model/user/fetchUserList.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            const tbody = $('#userTable tbody');
            tbody.empty();

            if (response.status === 'success' && response.data && response.data.length > 0) {
                $.each(response.data, function (i, user) {
                    tbody.append(`
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.full_name}</td>
                            <td>${user.email}</td>
                            <td>${user.role}</td>
                            <td>${user.created_at}</td>
                            <td>
                                <button class="btn btn-sm btn-warning editUser" data-id="${user.id}" data-name="${user.full_name}" data-email="${user.email}" data-role="${user.role_id}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger deleteUser" data-id="${user.id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });
                $('#userTable').show();
                $('#emptyState').addClass('d-none');
                
                // Apply current search filter if exists
                const searchText = $('#searchInput').val();
                if (searchText) {
                    $('#searchInput').trigger('keyup');
                }
            } else {
                // No users found or error
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