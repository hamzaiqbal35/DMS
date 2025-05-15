<?php
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }

        select.form-control {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        select.form-control option {
            color: #000;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
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
            margin: 0 10px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .login-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        #alertBox {
            margin-bottom: 15px;
        }

        /* Password toggle style */
        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(27, 27, 27, 0.7);
            z-index: 10;
        }

        .password-toggle:hover {
            color: black;
        }

        @media (max-width: 768px) {
            .login-card {
                margin: 0 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Allied Steel Works</h2>
            <div id="alertBox" class="alert d-none"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                </div>
                <div class="mb-3 password-container">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Password">
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
                    <a href="register.php">Create Account</a>
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

            // Login form submission
            $("#loginForm").submit(function (e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "../model/login/checkLogin.php",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
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