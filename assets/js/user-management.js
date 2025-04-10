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
