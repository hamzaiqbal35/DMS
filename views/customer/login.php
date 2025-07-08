<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Customer Login - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 mt-5">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Customer Login
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div id="loginMessage"></div>
                    
                    <form id="customerLoginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="loginSpinner"></span>
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <!-- Resend Verification Email Section -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-2">Didn't receive the verification email?</p>
                        <div class="input-group mb-2" style="max-width: 400px; margin: 0 auto;">
                            <input type="email" id="resendEmailLogin" class="form-control" placeholder="Enter your email" required>
                            <button class="btn btn-outline-primary" id="resendBtnLogin" type="button">Resend Verification Email</button>
                        </div>
                        <div id="resendMessageLogin"></div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="<?= $base_url ?>customer.php?page=register" class="btn btn-outline-success">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="<?= $base_url ?>customer.php?page=forgotPassword" class="text-muted">
                            <i class="fas fa-key me-1"></i>Forgot Password?
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Back to Home -->
            <div class="text-center mt-4">
                <a href="<?= $base_url ?>customer.php?page=landing" class="text-muted">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('customerLoginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginMessage = document.getElementById('loginMessage');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        loginBtn.disabled = true;
        loginSpinner.classList.remove('d-none');
        loginBtn.querySelector('i').classList.add('d-none');
        
        // Clear previous messages
        loginMessage.innerHTML = '';
        
        // Get form data
        const formData = new FormData(this);
        const data = {
            email: formData.get('email'),
            password: formData.get('password'),
        };
        
        // Send login request
        fetch('<?= $base_url ?>api/customer/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Set JWT cookie (expires in 3 hours)
                document.cookie = "customer_jwt_token=" + data.token + "; path=/; max-age=" + (60 * 60 * 3) + ";";
                // Save JWT to localStorage for customer
                localStorage.setItem('customer_jwt_token', data.token);
                toastr.success(data.message);
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = '<?= $base_url ?>customer.php?page=dashboard';
                }, 1500);
            } else {
                toastr.error(data.message);
                
                // Reset form state
                loginBtn.disabled = false;
                loginSpinner.classList.add('d-none');
                loginBtn.querySelector('i').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            
            toastr.error('An error occurred. Please try again.');
            
            // Reset form state
            loginBtn.disabled = false;
            loginSpinner.classList.add('d-none');
            loginBtn.querySelector('i').classList.remove('d-none');
        });
    });
    
    // Auto-focus on email field
    document.getElementById('email').focus();

    // Resend Verification Email logic
    document.getElementById('resendBtnLogin').addEventListener('click', function() {
        const email = document.getElementById('resendEmailLogin').value.trim();
        if (!email || !email.includes('@')) {
            toastr.error('Please enter a valid email address.');
            return;
        }
        this.disabled = true;
        this.textContent = 'Sending...';
        fetch('<?= $base_url ?>api/customer/resend-verification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(() => {
            toastr.error('An error occurred. Please try again.');
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = 'Resend Verification Email';
        });
    });
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 