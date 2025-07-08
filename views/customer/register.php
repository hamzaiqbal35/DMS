<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Customer Registration - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 mt-5">
                <div class="card-header bg-success text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div id="registerMessage"></div>
                    
                    <form id="customerRegisterForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="username" name="username" required placeholder="Username">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="Full Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" autocomplete="tel">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
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
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" autocomplete="street-address"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" autocomplete="address-level2">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state" autocomplete="address-level1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" autocomplete="postal-code">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="<?= $base_url ?>customer.php?page=terms-of-service" class="text-primary">Terms of Service</a> and <a href="<?= $base_url ?>customer.php?page=privacy-policy" class="text-primary">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="registerBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="registerSpinner"></span>
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    
                    <!-- Resend Verification Email Section -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-2">Didn't receive the verification email?</p>
                        <div class="input-group mb-2" style="max-width: 400px; margin: 0 auto;">
                            <input type="email" id="resendEmailRegister" class="form-control" placeholder="Enter your email" required>
                            <button class="btn btn-outline-primary" id="resendBtnRegister" type="button">Resend Verification Email</button>
                        </div>
                        <div id="resendMessageRegister"></div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Already have an account?</p>
                        <a href="<?= $base_url ?>customer.php?page=login" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
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
    const registerForm = document.getElementById('customerRegisterForm');
    const registerBtn = document.getElementById('registerBtn');
    const registerSpinner = document.getElementById('registerSpinner');
    const registerMessage = document.getElementById('registerMessage');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Password strength validation
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
    });
    
    // Confirm password validation
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Handle form submission
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Show loading state
        registerBtn.disabled = true;
        registerSpinner.classList.remove('d-none');
        registerBtn.querySelector('i').classList.add('d-none');
        // Clear previous messages
        registerMessage.innerHTML = '';
        // Get form data
        const formData = new FormData(this);
        const data = {
            username: formData.get('username'),
            full_name: formData.get('full_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password'),
            address: formData.get('address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zip_code: formData.get('zip_code'),
            agree_terms: formData.get('agree_terms') ? true : false
        };
        // Send registration request
        fetch('<?= $base_url ?>api/customer/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                toastr.success(data.message);
                setTimeout(() => {
                    window.location.href = '<?= $base_url ?>customer.php?page=login';
                }, 2000);
            } else {
                toastr.error(data.message);
                registerBtn.disabled = false;
                registerSpinner.classList.add('d-none');
                registerBtn.querySelector('i').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Register error:', error);
            toastr.error('An error occurred. Please try again.');
            registerBtn.disabled = false;
            registerSpinner.classList.add('d-none');
            registerBtn.querySelector('i').classList.remove('d-none');
        });
    });
    
    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }
    
    // Update password strength indicator
    function updatePasswordStrengthIndicator(strength) {
        // You can add a visual strength indicator here if needed
        // For now, we'll just use the built-in browser validation
    }
    
    // Auto-focus on username field
    document.getElementById('username').focus();
    
    // Resend Verification Email logic
    document.getElementById('resendBtnRegister').addEventListener('click', function() {
        const email = document.getElementById('resendEmailRegister').value.trim();
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