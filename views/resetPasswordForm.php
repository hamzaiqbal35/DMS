<?php
require_once '../inc/config/database.php';
require_once '../inc/helpers.php';

// Check if token is provided in URL
$token = isset($_GET['token']) ? trim($_GET['token']) : null;
if (!$token) {
    die("Invalid or missing reset token.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Allied Steel Works DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">

</head>
<body>
    <div class="login-wrapper">
        <div class="reset-card">
            <h2>Reset Password</h2>
            <div id="alert-box"></div>

            <form action="../model/login/resetPassword.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required placeholder="Enter new password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                </div>

                <button type="submit" class="btn btn-reset w-100">Reset Password</button>
            </form>
        </div>
    </div>

    <footer class="footer" style="height: 50px; background: rgba(44, 62, 80, 0.8); color: white; display: flex; align-items: center; justify-content: center; position: fixed; bottom: 0; width: 100%;">
        <?php include_once '../inc/footer.php'; ?>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $("form").submit(function() {
                const newPassword = $("#new_password").val();
                const confirmPassword = $("#confirm_password").val();
                
                if (newPassword !== confirmPassword) {
                    $("#alert-box").html('<div class="alert alert-danger">Passwords do not match!</div>');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    $("#alert-box").html('<div class="alert alert-danger">Password must be at least 6 characters long!</div>');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>