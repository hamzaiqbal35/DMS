<?php
session_name('admin_session');
session_start();
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
                event.preventDefault();
                
                // Clear previous alerts
                $("#alert-box").empty();
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Sending...');
                
                $.ajax({
                    url: "../model/login/sendResetLink.php",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        console.log("Response:", response); // Debug log
                        if (response.status === "success") {
                            $("#alert-box").html(
                                '<div class="alert alert-success">' + 
                                '<i class="fas fa-check-circle"></i> ' + 
                                response.message + 
                                '</div>'
                            );
                            // Clear the form
                            $("#forgotPasswordForm")[0].reset();
                        } else {
                            $("#alert-box").html(
                                '<div class="alert alert-danger">' + 
                                '<i class="fas fa-exclamation-circle"></i> ' + 
                                response.message + 
                                '</div>'
                            );
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("XHR Status:", status);
                        console.error("Error:", error);
                        console.error("Response Text:", xhr.responseText);
                        
                        let errorMessage = 'An error occurred while processing your request.';
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error("Error parsing response:", e);
                        }
                        
                        $("#alert-box").html(
                            '<div class="alert alert-danger">' + 
                            '<i class="fas fa-exclamation-circle"></i> ' + 
                            errorMessage + 
                            '</div>'
                        );
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).text(originalBtnText);
                    }
                });
            });
        });
    </script>
</body>
</html>