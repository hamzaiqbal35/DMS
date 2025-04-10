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
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../assets/js/scripts.js"></script>
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
            <h3 class="text-center mb-4">Login</h3>
            <div id="alertBox" class="alert d-none"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Select Role</label>
                    <select class="form-control" id="role" name="role_id" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role_id => $role_name): ?>
                            <option value="<?= $role_id ?>"><?= $role_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-dark w-100">Login</button>
                <p class="text-center mt-3">
                    <a href="register.php">Create an Account</a> | <a href="forgotPassword.php">Forgot Password?</a>
                </p>
            </form>
        </div>
    </div>

    <?php include_once '../inc/footer.php'; ?>
    
    <script>
        $(document).ready(function () {
            $("#loginForm").submit(function (e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "../model/login/checkLogin.php",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
                            $("#alertBox").removeClass("d-none alert-danger").addClass("alert-success").text(response.message);
                            setTimeout(() => { window.location.href = "../views/dashboard.php"; }, 2000);
                        } else {
                            $("#alertBox").removeClass("d-none alert-success").addClass("alert-danger").text(response.message);
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
