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
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../assets/js/scripts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

<div class="container">
    <div class="forgot-container mt-5 mx-auto p-4 bg-light rounded shadow-sm" style="max-width: 400px;">
        <h3 class="text-center"><i class="fas fa-lock"></i> Forgot Password</h3>
        <p class="text-center text-muted">Enter your email to receive a password reset link.</p>
        
        <div id="alert-box"></div>

        <form id="forgotPasswordForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Send Reset Link</button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Back to Login</a>
        </div>
    </div>
</div>

<?php include_once '../inc/footer.php'; ?>

<!-- jQuery & Bootstrap JS -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        $("#forgotPasswordForm").submit(function (event) {
            event.preventDefault(); // Prevent page reload
            
            $.ajax({
                url: "../model/login/sendResetLink.php", // Make sure this file exists
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
