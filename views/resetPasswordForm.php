<?php
session_name('admin_session');
session_start();
require_once '../inc/config/database.php';
require_once '../inc/helpers.php';

$token = $_GET['token'] ?? '';
$error = '';
$validToken = false;

if (empty($token)) {
    $error = "Invalid or missing reset token.";
} else {
    try {
        // Check if token exists and is valid
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetData) {
            $error = "Invalid or expired reset token.";
        } else if (strtotime($resetData['expires_at']) < time()) {
            $error = "Reset token has expired.";
        } else {
            $validToken = true;
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again.";
        error_log("Reset Password Form Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Allied Steel Works DMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <div class="mt-3">
                        <a href="forgotPassword.php" class="btn btn-outline-danger">Request New Reset Link</a>
                    </div>
                </div>
            <?php elseif ($validToken): ?>
                <div id="alert-box"></div>
                <form id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <input type="password" class="form-control" id="new_password" name="new_password" required 
                               placeholder="New Password" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm New Password" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-login w-100">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="login-links mt-3">
                <a href="login.php">Back to Login</a>
            </div>
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
            $("#resetPasswordForm").submit(function(event) {
                event.preventDefault();
                
                // Clear previous alerts
                $("#alert-box").empty();
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Resetting...');
                
                $.ajax({
                    url: "../model/login/resetPassword.php",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            $("#alert-box").html(
                                '<div class="alert alert-success">' + 
                                '<i class="fas fa-check-circle"></i> ' + 
                                response.message + 
                                '</div>'
                            );
                            // Redirect to login after success
                            setTimeout(function() {
                                window.location.href = "login.php";
                            }, 2000);
                        } else {
                            $("#alert-box").html(
                                '<div class="alert alert-danger">' + 
                                '<i class="fas fa-exclamation-circle"></i> ' + 
                                response.message + 
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
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