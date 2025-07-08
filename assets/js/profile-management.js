// Profile Management JS
$(document).ready(function() {
    // Fetch and display user profile info
    fetchUserProfile();
    fetchRolePermissions();
    fetchAccountStats();

    // Handle profile update
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../model/user/updateUserProfile.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || 'Profile updated successfully!');
                    fetchUserProfile();
                } else {
                    toastr.error(res.message || 'Failed to update profile.');
                }
            },
            error: function() {
                toastr.error('Error updating profile.');
            }
        });
    });

    // Handle password change
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../model/user/changePassword.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || 'Password changed successfully!');
                    $('#changePasswordForm')[0].reset();
                } else {
                    toastr.error(res.message || 'Failed to change password.');
                }
            },
            error: function() {
                toastr.error('Error changing password.');
            }
        });
    });

    // Handle profile picture upload
    $('#profilePictureForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: '../model/user/updateUserProfile.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || 'Profile picture updated!');
                    var imgPath = res.profile_picture;
                    if (!imgPath) {
                        imgPath = '../assets/images/logo.png';
                    } else if (!imgPath.startsWith('http') && !imgPath.startsWith('/') && !imgPath.startsWith('../')) {
                        // If the path is just 'uploads/...' and we're in /views/, prepend '../'
                        imgPath = '../' + imgPath;
                    }
                    $('#profilePicture').attr('src', imgPath);
                    fetchUserProfile();
                } else {
                    toastr.error(res.message || 'Failed to update picture.');
                }
            },
            error: function() {
                toastr.error('Error uploading picture.');
            }
        });
    });

    // Add instant preview for profile picture
    $('#profile_picture').on('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#profilePicture').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Fetch user profile info
    function fetchUserProfile() {
        $.ajax({
            url: '../model/user/getUserProfile.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.data) {
                    $('#profileName').text(res.data.full_name);
                    $('#profileRole').text('Role: ' + res.data.role_name);
                    $('#profileEmail').text('Email: ' + res.data.email);
                    $('#full_name').val(res.data.full_name);
                    $('#email').val(res.data.email);
                    $('#phone').val(res.data.phone || '');
                    var imgPath = res.data.profile_picture;
                    if (!imgPath) {
                        imgPath = '../assets/images/logo.png';
                    } else if (!imgPath.startsWith('http') && !imgPath.startsWith('/') && !imgPath.startsWith('../')) {
                        // If the path is just 'uploads/...' and we're in /views/, prepend '../'
                        imgPath = '../' + imgPath;
                    }
                    $('#profilePicture').attr('src', imgPath);
                } else {
                    $('#profileName').text('Error loading profile');
                    $('#profilePicture').attr('src', '../assets/images/logo.png');
                }
            },
            error: function() {
                $('#profileName').text('Error loading profile');
                $('#profilePicture').attr('src', '../assets/images/logo.png');
            }
        });
    }

    // Fetch role permissions
    function fetchRolePermissions() {
        $.ajax({
            url: '../model/user/getUserProfile.php?action=permissions',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.permissions) {
                    var html = '';
                    // Group and abstract permissions for a friendlier UI
                    var groups = {
                        'Data Access': ['View/Edit All Data', 'View Reports', 'View Sales', 'Manage Orders', 'Manage Inventory', 'View Stock Alerts', 'Manage Users'],
                        'Export & Reports': ['Export Data', 'Export Sales Reports', 'Sale Reports', 'Purchase Reports', 'Analytics'],
                        'Management': ['Manage Users', 'Manage Orders', 'Manage Inventory', 'Manage Vendors', 'Manage Customers', 'Manage Categories', 'User Management', 'System Settings'],
                        'Other': ['System Settings', 'Full Admin Access', 'Basic Access']
                    };
                    var shown = [];
                    res.permissions.forEach(function(perm) {
                        for (var group in groups) {
                            if (groups[group].includes(perm) && !shown.includes(group)) {
                                html += '<li class="list-group-item"><strong>' + group + ':</strong></li>';
                                shown.push(group);
                                break;
                            }
                        }
                        html += '<li class="list-group-item">' + perm + '</li>';
                    });
                    $('#rolePermissions').html(html);
                }
            }
        });
    }

    // Fetch account stats
    function fetchAccountStats() {
        $.ajax({
            url: '../model/user/getUserProfile.php?action=stats',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.stats) {
                    $('#lastLogin').text(res.stats.last_login || 'N/A');
                    $('#totalLogins').text(res.stats.total_logins || 'N/A');
                    $('#createdAt').text(res.stats.created_at || 'N/A');
                }
            }
        });
    }
}); 