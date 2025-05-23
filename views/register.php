<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Allied Steel Works DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">

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