<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Reset Password - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

// Token validation logic
$token = $_GET['token'] ?? '';
$error = '';
$validToken = false;
if (empty($token)) {
    $error = "Invalid or missing reset token.";
} else {
    try {
        // Check if token exists and is valid for customer
        $stmt = $pdo->prepare("SELECT expires_at FROM customer_password_resets WHERE token = ?");
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
    }
}
?>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Reset Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <div class="mt-3">
                        <a href="<?= $base_url ?>customer.php?page=forgotPassword" class="btn btn-outline-danger">Request New Reset Link</a>
                    </div>
                </div>
            <?php elseif ($validToken): ?>
                <div id="alert-box"></div>
                <form id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="password-input-group">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="New Password" autocomplete="new-password">
                            <label for="new_password">New Password</label>
                        </div>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye" id="new_password_icon"></i>
                        </button>
                    </div>
                    <div class="password-input-group">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm New Password" autocomplete="new-password">
                            <label for="confirm_password">Confirm New Password</label>
                        </div>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </form>
            <?php endif; ?>
            <div class="login-links mt-3">
                <a href="<?= $base_url ?>customer.php?page=login">Back to Login</a>
            </div>
        </div>
    </div>
    <script>
        // Password toggle function
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength < 3) return 'weak';
            if (strength < 5) return 'medium';
            return 'strong';
        }
        
        // Form validation
        function validateForm() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            let isValid = true;
            
            // Reset validation states
            document.getElementById('new_password').classList.remove('is-valid', 'is-invalid');
            document.getElementById('confirm_password').classList.remove('is-valid', 'is-invalid');
            
            // Check password strength
            if (newPassword.length > 0) {
                const strength = checkPasswordStrength(newPassword);
                if (strength === 'weak') {
                    document.getElementById('new_password').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('new_password').classList.add('is-valid');
                }
            }
            
            // Check password confirmation
            if (confirmPassword.length > 0) {
                if (newPassword !== confirmPassword) {
                    document.getElementById('confirm_password').classList.add('is-invalid');
                    isValid = false;
                } else if (newPassword.length > 0) {
                    document.getElementById('confirm_password').classList.add('is-valid');
                }
            }
            
            return isValid;
        }
        
        $(document).ready(function() {
            // Add real-time validation
            $('#new_password').on('input', function() {
                validateForm();
            });
            
            $('#confirm_password').on('input', function() {
                validateForm();
            });
            
            $("#resetPasswordForm").submit(function(event) {
                event.preventDefault();
                
                // Validate form before submission
                if (!validateForm()) {
                    $("#alert-box").html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please fix the validation errors before submitting.</div>');
                    return;
                }
                
                $("#alert-box").empty();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Resetting...');
                $.ajax({
                    url: "<?= $base_url ?>api/customer/reset-password.php",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        token: $("input[name='token']").val(),
                        new_password: $("#new_password").val(),
                        confirm_password: $("#confirm_password").val()
                    }),
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            $("#alert-box").html('<div class="alert alert-success">' + response.message + '</div>');
                            setTimeout(function() {
                                window.location.href = "<?= $base_url ?>customer.php?page=login";
                            }, 2000);
                        } else {
                            $("#alert-box").html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while processing your request.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) errorMessage = response.message;
                        } catch (e) {}
                        $("#alert-box").html('<div class="alert alert-danger">' + errorMessage + '</div>');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalBtnText);
                    }
                });
            });
        });
    </script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 