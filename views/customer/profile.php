<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'My Profile - Allied Steel Works'; 
include __DIR__ . '/../../inc/customer/customer-header.php'; 

$customer_id = $_SESSION['customer_user_id'] ?? null;

// Fetch customer profile
$customer_data = null;
$order_stats = [];

if ($customer_id) {
    try {
        // Get customer data
        $stmt = $pdo->prepare("SELECT * FROM customer_users WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        $customer_data = $stmt->fetch();

        // Get order statistics
        // Total orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM customer_orders 
            WHERE customer_user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $order_stats['total_orders'] = $stmt->fetch()['total'];

        // Pending orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending FROM customer_orders 
            WHERE customer_user_id = ? AND order_status IN ('pending', 'confirmed', 'processing')
        ");
        $stmt->execute([$customer_id]);
        $order_stats['pending_orders'] = $stmt->fetch()['pending'];

        // Completed orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed FROM customer_orders 
            WHERE customer_user_id = ? AND order_status = 'delivered'
        ");
        $stmt->execute([$customer_id]);
        $order_stats['completed_orders'] = $stmt->fetch()['completed'];

        // Total spent
        $stmt = $pdo->prepare("
            SELECT SUM(final_amount) as total_spent FROM customer_orders 
            WHERE customer_user_id = ? AND order_status = 'delivered'
        ");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch();
        $order_stats['total_spent'] = $result['total_spent'] ?? 0;
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Fetch customer status from customers table
$customer_status = null;
if ($customer_data && $customer_data['admin_customer_id']) {
    $stmt = $pdo->prepare("SELECT status FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_data['admin_customer_id']]);
    $row = $stmt->fetch();
    $customer_status = $row ? $row['status'] : null;
}

// Redirect if not logged in
if (!$customer_data) {
    header("Location: " . $base_url . "customer.php?page=login");
    exit();
}
?>

<div class="container">
    <div class="row">
        <!-- Profile Picture Section -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-image me-2"></i>Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <img id="customerProfilePicture" src="<?= $customer_data['profile_picture'] ? $base_url . $customer_data['profile_picture'] : $base_url . 'assets/images/logo.png' ?>" alt="Profile Picture" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #e9ecef;">
                    <form id="customerProfilePictureForm" enctype="multipart/form-data">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="form-control mb-2">
                        <button type="submit" class="btn btn-primary btn-sm mb-2 w-100"><i class="fas fa-upload me-1"></i>Upload New</button>
                    </form>
                    <?php if ($customer_data['profile_picture']): ?>
                    <button id="deleteProfilePictureBtn" class="btn btn-danger btn-sm w-100"><i class="fas fa-trash me-1"></i>Delete Picture</button>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Profile Summary Card  -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Account Summary
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Account Statistics -->
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-primary mb-1"><?= $order_stats['total_orders'] ?? 0 ?></h4>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-warning mb-1"><?= $order_stats['pending_orders'] ?? 0 ?></h4>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success mb-1"><?= $order_stats['completed_orders'] ?? 0 ?></h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="text-center mb-4">
                        <h5 class="text-success">Rs. <?= number_format($order_stats['total_spent'] ?? 0, 2) ?></h5>
                        <small class="text-muted">Total Spent</small>
                    </div>
                    <hr>
                    <!-- Account Details -->
                    <div class="mb-3">
                        <small class="text-muted">Member since:</small><br>
                        <strong><?= date('M d, Y', strtotime($customer_data['created_at'])) ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Last login:</small><br>
                        <strong><?= $customer_data['last_login'] ? date('M d, Y H:i', strtotime($customer_data['last_login'])) : 'Never' ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Status:</small><br>
                        <span class="badge bg-<?= $customer_status === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst($customer_status ?? $customer_data['status']) ?>
                        </span>
                    </div>
                    <?php if ($customer_status === 'inactive'): ?>
                        <div class="alert alert-warning mt-3" role="alert">
                            <strong>Your account is currently <span class="text-danger">inactive</span>.</strong><br>
                            You cannot place orders. Please contact <strong>Customer Support</strong> to reactivate your account.
                        </div>
                    <?php elseif ($customer_data && !$customer_data['admin_customer_id']): ?>
                        <div class="alert alert-danger mt-3" role="alert">
                            <strong>Account linkage error:</strong> Your account is not properly linked to a customer record. Please contact <strong>Customer Support</strong>.
                        </div>
                    <?php endif; ?>
                    <!-- Quick Actions -->
                    <hr>
                    <h6 class="mb-3">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= $base_url ?>customer.php?page=my-orders" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-outline-success btn-sm<?= ($customer_data['status'] === 'inactive' ? ' disabled' : '') ?>" tabindex="<?= ($customer_data['status'] === 'inactive' ? '-1' : '0') ?>">
                            <i class="fas fa-store me-2"></i>Browse Products
                        </a>
                        <a href="<?= $base_url ?>customer.php?page=cart" class="btn btn-outline-warning btn-sm<?= ($customer_data['status'] === 'inactive' ? ' disabled' : '') ?>" tabindex="<?= ($customer_data['status'] === 'inactive' ? '-1' : '0') ?>">
                            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </h4>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fullName" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="fullName" name="full_name" 
                                       value="<?= htmlspecialchars($customer_data['full_name'] ?? '') ?>" required autocomplete="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($customer_data['email'] ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($customer_data['phone'] ?? '') ?>" required autocomplete="tel">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($customer_data['username'] ?? '') ?>" readonly autocomplete="username">
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" autocomplete="street-address"><?= htmlspecialchars($customer_data['address'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= htmlspecialchars($customer_data['city'] ?? '') ?>" autocomplete="address-level2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?= htmlspecialchars($customer_data['state'] ?? '') ?>" autocomplete="address-level1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="<?= htmlspecialchars($customer_data['zip_code'] ?? '') ?>" autocomplete="postal-code">
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="updateProfileBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="profileSpinner"></span>
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                    <div id="profileMessage"></div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </h4>
                </div>
                <div class="card-body">
                    <form id="passwordForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="passwordSpinner"></span>
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                    <div id="passwordMessage"></div>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-trash-alt me-2"></i>Delete Account
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Once you delete your account, all your data including orders, payments, and personal information will be permanently removed.
                    </div>
                    
                    <div class="mb-3">
                        <h6>Before deleting your account, please note:</h6>
                        <ul class="text-muted">
                            <li>All your orders and payment history will be permanently deleted</li>
                            <li>Your personal information will be completely removed</li>
                            <li>You cannot recover your account after deletion</li>
                            <li>You must complete or cancel any active orders before deletion</li>
                        </ul>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-danger" id="deleteAccountBtn" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-trash-alt me-2"></i>Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Account Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>This action is irreversible!</strong> Once you delete your account, all your data will be permanently lost.
                </div>
                
                <form id="deleteAccountForm">
                    <div class="mb-3">
                        <label for="deletePassword" class="form-label">Enter your password to confirm *</label>
                        <input type="password" class="form-control" id="deletePassword" name="password" required>
                        <div class="form-text">This is required to verify your identity.</div>
                    </div>
                    <div class="mb-3">
                        <label for="deleteReason" class="form-label">Reason for deletion (optional)</label>
                        <textarea class="form-control" id="deleteReason" name="reason" rows="3" placeholder="Please let us know why you're leaving..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            I understand that this action cannot be undone and all my data will be permanently deleted.
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="deleteSpinner"></span>
                    <i class="fas fa-trash-alt me-2"></i>Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const updateProfileBtn = document.getElementById('updateProfileBtn');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const profileSpinner = document.getElementById('profileSpinner');
    const passwordSpinner = document.getElementById('passwordSpinner');
    const profileMessage = document.getElementById('profileMessage');
    const passwordMessage = document.getElementById('passwordMessage');

    // Delete account elements
    const deleteAccountForm = document.getElementById('deleteAccountForm');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteSpinner = document.getElementById('deleteSpinner');
    const deletePassword = document.getElementById('deletePassword');
    const deleteReason = document.getElementById('deleteReason');
    const confirmDeleteCheckbox = document.getElementById('confirmDelete');
    const deleteAccountModal = document.getElementById('deleteAccountModal');

    // Enable/disable delete button based on form validation
    function updateDeleteButtonState() {
        const password = deletePassword.value.trim();
        const isConfirmed = confirmDeleteCheckbox.checked;
        confirmDeleteBtn.disabled = !(password && isConfirmed);
    }

    // Add event listeners for delete account form validation
    deletePassword.addEventListener('input', updateDeleteButtonState);
    confirmDeleteCheckbox.addEventListener('change', updateDeleteButtonState);

    // Delete account form submission
    confirmDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const password = deletePassword.value.trim();
        const reason = deleteReason.value.trim();
        
        if (!password) {
            alert('Please enter your password to confirm account deletion.');
            return;
        }
        
        if (!confirmDeleteCheckbox.checked) {
            alert('Please confirm that you understand this action cannot be undone.');
            return;
        }
        
        // Final confirmation
        if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
            return;
        }
        
        // Show loading state
        confirmDeleteBtn.disabled = true;
        deleteSpinner.classList.remove('d-none');
        confirmDeleteBtn.querySelector('i').classList.add('d-none');
        
        // Prepare data
        const data = {
            password: password,
            reason: reason
        };
        
        // Send delete request
        fetch('<?= $base_url ?>model/customer/delete-account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show success message
                alert(data.message);
                // Redirect to home page after a short delay
                setTimeout(() => {
                    window.location.href = '<?= $base_url ?>customer.php';
                }, 2000);
            } else {
                // Show error message
                alert('Error: ' + data.message);
                // Reset form state
                confirmDeleteBtn.disabled = false;
                deleteSpinner.classList.add('d-none');
                confirmDeleteBtn.querySelector('i').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Delete account error:', error);
            alert('An error occurred while deleting your account. Please try again.');
            // Reset form state
            confirmDeleteBtn.disabled = false;
            deleteSpinner.classList.add('d-none');
            confirmDeleteBtn.querySelector('i').classList.remove('d-none');
        });
    });

    // Reset delete form when modal is closed
    deleteAccountModal.addEventListener('hidden.bs.modal', function() {
        deleteAccountForm.reset();
        confirmDeleteBtn.disabled = true;
        deleteSpinner.classList.add('d-none');
        confirmDeleteBtn.querySelector('i').classList.remove('d-none');
    });

    // Profile form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        updateProfileBtn.disabled = true;
        profileSpinner.classList.remove('d-none');
        updateProfileBtn.querySelector('i').classList.add('d-none');
        
        // Clear previous messages
        profileMessage.innerHTML = '';
        
        // Get form data
        const formData = new FormData(this);
        const data = {
            full_name: formData.get('full_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zip_code: formData.get('zip_code')
        };
        
        // Send update request
        fetch('<?= $base_url ?>model/customer/update-profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show success message
                profileMessage.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            } else {
                // Show error message
                profileMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Profile update error:', error);
            // Show error message
            profileMessage.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        })
        .finally(() => {
            // Reset form state
            updateProfileBtn.disabled = false;
            profileSpinner.classList.add('d-none');
            updateProfileBtn.querySelector('i').classList.remove('d-none');
        });
    });

    // Password form submission
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validate passwords match
        if (newPassword !== confirmPassword) {
            passwordMessage.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>New passwords do not match.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            return;
        }
        
        // Show loading state
        changePasswordBtn.disabled = true;
        passwordSpinner.classList.remove('d-none');
        changePasswordBtn.querySelector('i').classList.add('d-none');
        
        // Clear previous messages
        passwordMessage.innerHTML = '';
        
        // Get form data
        const formData = new FormData(this);
        const data = {
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            confirm_password: formData.get('confirm_password')
        };
        
        // Send password change request
        fetch('<?= $base_url ?>model/customer/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server returned invalid JSON. Raw response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            if (data.status === 'success') {
                // Show success message
                passwordMessage.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                // Clear form
                passwordForm.reset();
            } else {
                // Show error message
                passwordMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Password change error:', error);
            // Show detailed error message
            passwordMessage.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Error: ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        })
        .finally(() => {
            // Reset form state
            changePasswordBtn.disabled = false;
            passwordSpinner.classList.add('d-none');
            changePasswordBtn.querySelector('i').classList.remove('d-none');
        });
    });

    // Instant preview for profile picture
    $('#profile_picture').on('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#customerProfilePicture').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Handle profile picture upload
    $('#customerProfilePictureForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'model/customer/upload-profile-picture.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || 'Profile picture updated!');
                    location.reload(); // Fast page refresh
                } else {
                    toastr.error(res.message || 'Failed to update picture.');
                }
            },
            error: function() {
                toastr.error('Error uploading picture.');
            }
        });
    });

    // Handle profile picture delete
    $('#deleteProfilePictureBtn').on('click', function() {
        if (!confirm('Are you sure you want to delete your profile picture?')) return;
        $.ajax({
            url: 'model/customer/delete-profile-picture.php',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || 'Profile picture deleted!');
                    $('#customerProfilePicture').attr('src', '<?= $base_url ?>assets/images/logo.png');
                    $('#deleteProfilePictureBtn').remove();
                } else {
                    toastr.error(res.message || 'Failed to delete picture.');
                }
            },
            error: function() {
                toastr.error('Error deleting picture.');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 