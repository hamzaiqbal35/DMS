<?php
require_once '../inc/config/database.php';
require_once '../inc/helpers.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Allied Steel Works DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">

</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Forgot Password</h2>
            <p>Enter your email to receive a password reset link</p>
            
            <div id="alert-box"></div>

            <form id="forgotPasswordForm">
                <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                <button type="submit" class="btn btn-login w-100">Send Reset Link</button>
                <div class="login-links">
                    <a href="login.php">Back to Login</a>
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
    
    <script>
        $(document).ready(function () {
            $("#forgotPasswordForm").submit(function (event) {
                event.preventDefault(); // Prevent page reload
                
                $.ajax({
                    url: "../model/login/sendResetLink.php",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
                            $("#alert-box").html('<div class="alert alert-success">' + response.message + '</div>');
                        } else {
                            $("#alert-box").html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function () {
                        $("#alert-box").html('<div class="alert alert-danger">Something went wrong. Try again.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>