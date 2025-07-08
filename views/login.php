<?php
session_name('admin_session');
session_start();
$base_url = "http://localhost/DMS/"; 

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: " . $base_url . "views/dashboard.php"); 
    exit();
}

// Role options 
$roles = [
    "1" => "Admin",
    "2" => "Manager",
    "3" => "Salesperson",
    "4" => "Inventory Manager"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Allied Steel DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/styles.css" rel="stylesheet">

</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Allied Steel Works</h2>
            <div id="alertBox" class="alert d-none"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address" autocomplete="email">
                </div>
                <div class="mb-3 password-container">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Password" autocomplete="current-password">
                    <span class="password-toggle" id="togglePassword">
                        <i class="fa-solid fa-eye"></i>
                    </span>
                </div>
                <div class="mb-3">
                    <select class="form-control" id="role" name="role_id" required>
                        <option value="">Select Your Role</option>
                        <?php foreach ($roles as $role_id => $role_name): ?>
                            <option value="<?= $role_id ?>"><?= $role_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-login w-100">Sign In</button>
                <div class="login-links">
                    <a href="forgotPassword.php">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer" style="height: 50px; background: rgba(44, 62, 80, 0.8); color: white; display: flex; align-items: center; justify-content: center; position: fixed; bottom: 0; width: 100%;">
        <?php include_once '../inc/footer.php'; ?>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>

    <script>
        $(document).ready(function () {
            // Password toggle functionality
            $("#togglePassword").on("click", function() {
                const passwordField = $("#password");
                const icon = $(this).find("i");
                
                // Toggle the password field type
                if (passwordField.attr("type") === "password") {
                    passwordField.attr("type", "text");
                    icon.removeClass("fa-eye").addClass("fa-eye-slash");
                } else {
                    passwordField.attr("type", "password");
                    icon.removeClass("fa-eye-slash").addClass("fa-eye");
                }
            });

            // Login form submission - FIXED
            $("#loginForm").submit(function (e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "../model/login/checkLogin.php",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
                            // Store JWT token in localStorage 
                            localStorage.setItem("jwt_token", response.token);
                            // Also set as a cookie for PHP session restoration
                            document.cookie = "jwt_token=" + response.token + "; path=/; max-age=" + (60 * 60 * 3) + ";";
                            
                            $("#alertBox")
                                .removeClass("d-none alert-danger")
                                .addClass("alert-success")
                                .text(response.message);
                            setTimeout(() => { 
                                window.location.href = "../views/dashboard.php"; 
                            }, 2000);
                        } else {
                            $("#alertBox")
                                .removeClass("d-none alert-success")
                                .addClass("alert-danger")
                                .text(response.message);
                        }
                    },
                    error: function() {
                        $("#alertBox")
                            .removeClass("d-none alert-success")
                            .addClass("alert-danger")
                            .text("An error occurred. Please try again.");
                    }
                });
            });
        });
    </script>
</body>
</html>