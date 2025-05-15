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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-color: #ecf0f1;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Arial', sans-serif;
            overflow: hidden; /* Prevent scrolling */
        }

        .login-wrapper {
            height: calc(100vh - 50px); /* Adjust height to leave space for footer */
            background-image: url('../assets/images/steel-background.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden; /* Prevent scrolling */
        }

        .login-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 30px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }

        .login-card h2 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .login-card p {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            margin-bottom: 15px;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }

        .btn-login {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px;
            color: white;
            transition: transform 0.3s ease;
            margin-bottom: 15px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            background-color: #2980b9;
        }

        .login-links {
            text-align: center;
            margin-top: 15px;
        }

        .login-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .login-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        #alert-box .alert {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
        }
        
        #alert-box .alert-success {
            background: rgba(40, 167, 69, 0.3);
        }
        
        #alert-box .alert-danger {
            background: rgba(220, 53, 69, 0.3);
        }
    </style>
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