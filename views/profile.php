<?php
session_name('admin_session');
session_start();
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
require_once "../inc/helpers.php"; // For getRoleName
$role_id = $_SESSION['role_id'] ?? null;
$role_name = getRoleName($role_id);
$email = $_SESSION['email'] ?? '';
?>

<div class="page-wrapper profile-bg-gradient">
    <main class="main-content">
        <div class="container-fluid fade-in">
            <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-user-circle text-primary me-2"></i>My Profile</h2>
                    <span class="badge rounded-pill bg-primary-soft text-primary px-3 py-2" id="profileRole">Role: <?= htmlspecialchars($role_name) ?></span>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="text-muted"><i class="fas fa-envelope me-1"></i><span id="profileEmail">Email: <?= htmlspecialchars($email) ?></span></span>
                </div>
            </div>
            <div class="row g-4">
                <!-- Profile Sidebar -->
                <div class="col-lg-4">
                    <div class="card shadow-lg border-0 mb-4 profile-card">
                        <div class="card-body text-center p-4">
                            <div class="position-relative d-inline-block mb-3">
                                <img src="../assets/images/logo.png" alt="Profile Picture" class="rounded-circle border border-3 border-primary shadow profile-avatar" id="profilePicture" style="width: 130px; height: 130px; object-fit: cover;">
                                <form id="profilePictureForm" enctype="multipart/form-data" class="mt-2" autocomplete="off">
                                    <label for="profile_picture" class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="fas fa-camera"></i> Change</label>
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="d-none" autocomplete="photo">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill mt-2 w-100">Upload</button>
                                </form>
                            </div>
                            <h4 id="profileName" class="fw-semibold mb-0">Loading...</h4>
                        </div>
                    </div>
                    <!-- Account Statistics -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-light border-0 pb-2">
                            <h6 class="mb-0 text-primary"><i class="fas fa-chart-bar me-2"></i>Account Statistics</h6>
                        </div>
                        <div class="card-body pt-2 pb-3">
                            <ul class="list-group list-group-flush" id="accountStats">
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">Last Login: <span id="lastLogin" class="fw-semibold text-dark">...</span></li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">Total Logins: <span id="totalLogins" class="fw-semibold text-dark">...</span></li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">Account Created: <span id="createdAt" class="fw-semibold text-dark">...</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Profile Details & Edit Form -->
                <div class="col-lg-8">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-primary-soft border-0">
                            <h6 class="mb-0 text-primary"><i class="fas fa-user-edit me-2"></i>Edit Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <form id="profileForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="<?= htmlspecialchars($profile['full_name'] ?? 'Enter your full name') ?>" value="" autocomplete="name">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required placeholder="<?= htmlspecialchars($profile['email'] ?? 'Enter your email') ?>" value="" autocomplete="email">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" placeholder="<?= htmlspecialchars($profile['phone'] ?? 'Enter your phone') ?>" value="" autocomplete="tel">
                                    </div>
                                </div>
                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-success rounded-pill px-4">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Change Password -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-warning-soft border-0">
                            <h6 class="mb-0 text-warning"><i class="fas fa-key me-2"></i>Change Password</h6>
                        </div>
                        <div class="card-body">
                            <form id="changePasswordForm">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Current password" value="" autocomplete="current-password">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="New password" value="" autocomplete="new-password">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm new password" value="" autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-warning rounded-pill px-4">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Permissions -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-success-soft border-0">
                            <h6 class="mb-0 text-success"><i class="fas fa-user-shield me-2"></i>Role & Permissions</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0" style="min-width: 350px;">
                                    <thead>
                                        <tr>
                                            <th>Module</th>
                                            <th>Access</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $modules = [
                                            'Dashboard' => [1,2,3,4],
                                            'Customers' => [1,2,3],
                                            'Vendors' => [1,2,4],
                                            'Inventory' => [1,2,4],
                                            'Stock Alerts' => [1,2,4],
                                            'Media Catalog' => [1,2,4],
                                            'Categories' => [1,2,4],
                                            'Sales' => [1,2,3],
                                            'Manage Orders' => [1,2,3],
                                            'Sale Reports' => [1,2,3],
                                            'Sale Invoices' => [1,2,3],
                                            'Purchases' => [1,2,4],
                                            'Purchase Reports' => [1,2,4],
                                            'Purchase Invoices' => [1,2,4],
                                            'Purchase Analytics' => [1,2,4],
                                            'Raw Materials' => [1,2,4],
                                            'User Management' => [1],
                                            'Reports' => [1,2,3,4],
                                            'Export Data' => [1,2,3,4],
                                        ];
                                        foreach ($modules as $module => $roles) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($module) . '</td>';
                                            echo '<td class="text-center">' . (in_array($role_id, $roles) ? '<span class="text-success fw-bold">&#10003;</span>' : '<span class="text-muted">&mdash;</span>') . '</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../inc/footer.php'; ?>

<!-- Scripts -->
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/animations.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="../assets/js/profile-management.js"></script>

<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">

<style>
    .profile-bg-gradient {
        background: linear-gradient(135deg, #f8fafc 0%, #e3e9f7 100%);
        min-height: 100vh;
    }
    .profile-card {
        background: linear-gradient(135deg, #fff 60%, #f0f4fa 100%);
    }
    .profile-avatar {
        box-shadow: 0 4px 16px rgba(67,97,238,0.08);
        border: 4px solid #fff;
    }
    .bg-primary-soft { background: #e9f1ff !important; }
    .bg-warning-soft { background: #fff7e6 !important; }
    .bg-info-soft { background: #e6f7fa !important; }
    .bg-success-soft { background: #e6faed !important; }
    .card-header { border-radius: 0.5rem 0.5rem 0 0; }
    .rounded-pill { border-radius: 50rem !important; }
    .fw-semibold { font-weight: 600; }
    .text-primary-soft { color: #4361ee !important; }
    .text-warning-soft { color: #f9c74f !important; }
    .text-info-soft { color: #4cc9f0 !important; }
    .text-success-soft { color: #43aa8b !important; }
    @media (max-width: 991.98px) {
        .profile-avatar { width: 100px !important; height: 100px !important; }
    }
</style>
