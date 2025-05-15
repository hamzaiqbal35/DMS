<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Allied Steel Works DMS</title>
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

        .register-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 30px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            max-height: 85vh;
            overflow-y: auto;
        }

        /* Custom scrollbar for the register card */
        .register-card::-webkit-scrollbar {
            width: 6px;
        }
        
        .register-card::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .register-card::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .register-card h2 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }

        select.form-control {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        select.form-control option {
            color: #000;
            background-color: white;
        }

        .btn-register {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px;
            color: white;
            transition: transform 0.3s ease;
            margin-top: 15px;
        }

        .btn-register:hover {
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

        #error-message {
            background: rgba(220, 53, 69, 0.3);
            color: white;
            border: none;
        }

        label {
            color: white;
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="register-card">
            <h2>Create Account</h2>
            <div id="error-message" class="alert alert-danger d-none"></div>
            
            <form id="register-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" name="username" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Create password">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required placeholder="Confirm password">
                </div>
                <div class="form-group">
                    <label>Select Role</label>
                    <select class="form-control" name="role" required>
                        <option value="">-- Select Your Role --</option>
                        <option value="Admin">Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Salesperson" selected>Salesperson</option>
                        <option value="Inventory Manager">Inventory Manager</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-register w-100">Register</button>
                <div class="login-links">
                    <a href="login.php">Already have an account? Login</a>
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
    $(document).ready(function() {
        $("#register-form").submit(function(event) {
            event.preventDefault();
            $.ajax({
                url: "../model/login/register.php",
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        window.location.href = "login.php";
                    } else {
                        $("#error-message").removeClass("d-none").text(response.message);
                    }
                },
                error: function() {
                    $("#error-message").removeClass("d-none")
                        .text("An error occurred. Please try again later.");
                }
            });
        });
    });
    </script>
</body>
</html>