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

        .reset-card {
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

        .reset-card h2 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
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

        .btn-reset {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px;
            color: white;
            transition: transform 0.3s ease;
            margin-top: 10px;
        }

        .btn-reset:hover {
            transform: translateY(-3px);
            background-color: #2980b9;
        }

        #alert-box {
            margin-bottom: 15px;
        }

        #alert-box .alert {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
        }

        label {
            color: white;
            display: block;
            margin-bottom: 5px;
        }
    </style>
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