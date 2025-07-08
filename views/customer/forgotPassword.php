<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Forgot Password - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';
?>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Forgot Password</h2>
            <p>Enter your email to receive a password reset link</p>
            <div id="alert-box"></div>
            <form id="forgotPasswordForm">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                    <label for="email">Email Address</label>
                </div>
                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
                <div class="login-links">
                    <a href="<?= $base_url ?>customer.php?page=login">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $("#forgotPasswordForm").submit(function (event) {
                event.preventDefault();
                $("#alert-box").empty();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Sending...');
                $.ajax({
                    url: "<?= $base_url ?>api/customer/forgot-password.php",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ email: $("#email").val().trim() }),
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
                            $("#alert-box").html('<div class="alert alert-success">' + response.message + '</div>');
                            $("#forgotPasswordForm")[0].reset();
                        } else {
                            $("#alert-box").html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function (xhr) {
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