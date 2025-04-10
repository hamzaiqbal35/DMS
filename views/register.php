<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Allied Steel DMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../assets/js/scripts.js"></script>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h3 class="text-center">Register</h3>
                    <div id="error-message" class="alert alert-danger d-none"></div>
                    
                    <form id="register-form">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <label>Select Role</label>
                            <select class="form-control" name="role" required>
                                <option value="Admin">Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Salesperson" selected>Salesperson</option>
                                <option value="Inventory Manager">Inventory Manager</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark btn-block">Register</button>
                    </form>

                    <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../inc/footer.php'; ?>

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
            }
        });
    });
});
</script>

</body>
</html>
