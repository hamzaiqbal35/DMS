    </div> <!-- End of customer-content -->
    
    <!-- Enhanced Footer -->
    <footer class="customer-footer bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- Company Information -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= $base_url ?>assets/images/logo.png" alt="Allied Steel Works" height="40" class="me-2">
                        <h5 class="mb-0">Allied Steel Works</h5>
                    </div>
                    <p class="text-muted mb-3">Your trusted partner for quality steel solutions and construction materials. We provide premium steel products for all your construction and industrial needs.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3" title="Facebook"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light me-3" title="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-light me-3" title="LinkedIn"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-light me-3" title="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light" title="YouTube"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <?php if ($customer_id): ?>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=dashboard" class="text-light text-decoration-none footer-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=catalogue" class="text-light text-decoration-none footer-link"><i class="fas fa-store me-2"></i>Browse Products</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=my-orders" class="text-light text-decoration-none footer-link"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=cart" class="text-light text-decoration-none footer-link"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=profile" class="text-light text-decoration-none footer-link"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <?php else: ?>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=landing" class="text-light text-decoration-none footer-link"><i class="fas fa-home me-2"></i>Home</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=catalogue" class="text-light text-decoration-none footer-link"><i class="fas fa-store me-2"></i>Products</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=login" class="text-light text-decoration-none footer-link"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=register" class="text-light text-decoration-none footer-link"><i class="fas fa-user-plus me-2"></i>Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Customer Services -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Customer Services</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=returns-refund" class="text-light text-decoration-none footer-link"><i class="fas fa-undo me-2"></i>Returns & Refunds</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=warranty-support" class="text-light text-decoration-none footer-link"><i class="fas fa-shield-alt me-2"></i>Warranty & Support</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=faq" class="text-light text-decoration-none footer-link"><i class="fas fa-question-circle me-2"></i>FAQ</a></li>
                        <li class="mb-2"><a href="<?= $base_url ?>customer.php?page=customer-support" class="text-light text-decoration-none footer-link"><i class="fas fa-headset me-2"></i>Customer Support</a></li>
                    </ul>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Contact Information</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            <a href="tel:+923001234567" class="text-light text-decoration-none footer-link">+92-300-123-4567</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <a href="mailto:info@alliedsteelworks.com" class="text-light text-decoration-none footer-link">info@alliedsteelworks.com</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            <span class="text-light">Lahore, Pakistan</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2 text-primary"></i>
                            <span class="text-light">Mon - Fri: 9:00 AM - 6:00 PM</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-calendar me-2 text-primary"></i>
                            <span class="text-light">Sat: 9:00 AM - 2:00 PM</span>
                        </li>
                    </ul>
                    
                    <!-- Newsletter Subscription -->
                    <div class="mt-3">
                        <h6 class="mb-2">Newsletter</h6>
                        <p class="text-muted small mb-2">Subscribe for updates and special offers</p>
                        <div class="input-group">
                            <input type="email" class="form-control form-control-sm" placeholder="Your email" id="newsletterEmail">
                            <button class="btn btn-primary btn-sm" type="button" onclick="subscribeNewsletter()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Links Row -->
            <div class="row mt-4">
                <div class="col-12">
                    <hr class="border-secondary">
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
                    <p class="text-muted mb-0">
                        <i class="fas fa-copyright me-1"></i>
                        2024 Allied Steel Works. All rights reserved.
                    </p>
                </div>
                <div class="col-lg-4 col-md-6 mb-3 mb-md-0 text-center">
                    <!-- Payment methods removed: Only Cash on Delivery supported -->
                </div>
                <div class="col-lg-4 col-md-12 text-lg-end">
                    <div class="footer-links">
                        <a href="<?= $base_url ?>customer.php?page=privacy-policy" class="text-light text-decoration-none me-3 footer-link">Privacy Policy</a>
                        <a href="<?= $base_url ?>customer.php?page=terms-of-service" class="text-light text-decoration-none me-3 footer-link">Terms of Service</a>
                        <a href="<?= $base_url ?>customer.php?page=cookie-policy" class="text-light text-decoration-none me-3 footer-link">Cookie Policy</a>
                    </div>
                </div>
            </div>

            <!-- Back to Top Button -->
            <div class="text-center mt-4">
                <button class="btn btn-outline-light btn-sm" onclick="scrollToTop()" id="backToTop" style="display: none;">
                    <i class="fas fa-arrow-up me-1"></i>Back to Top
                </button>
            </div>
        </div>
    </footer>

    <!-- Customer JavaScript -->
    <script src="<?= $base_url ?>assets/customer/js/customer-main.js"></script>
    
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    // Toastr default options (customize as needed)
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "4000"
    };
    
    // Newsletter subscription
    function subscribeNewsletter() {
        const email = document.getElementById('newsletterEmail').value;
        if (!email || !email.includes('@')) {
            alert('Please enter a valid email address.');
            return;
        }
        
        // Simulate newsletter subscription
        alert('Thank you for subscribing to our newsletter!');
        document.getElementById('newsletterEmail').value = '';
    }
    
    // Back to top functionality
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // Show/hide back to top button
    window.addEventListener('scroll', function() {
        const backToTopBtn = document.getElementById('backToTop');
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
    </script>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="logoutModalLabel"><i class="fas fa-sign-out-alt me-2"></i>Confirm Logout</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to log out from your account?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmLogoutBtn">Logout</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    $(document).ready(function() {
        // Intercept logout link/button
        $(document).on('click', '.customer-logout-link', function(e) {
            e.preventDefault();
            $('#logoutModal').modal('show');
        });
        // Confirm logout in modal
        $('#confirmLogoutBtn').on('click', function() {
            $.ajax({
                url: '<?= $base_url ?>api/customer/logout.php',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success('Logged out successfully!');
                        setTimeout(function() {
                            window.location.href = '<?= $base_url ?>customer.php?page=login';
                        }, 1000);
                    } else {
                        toastr.error(response.message || 'Logout failed.');
                    }
                    $('#logoutModal').modal('hide');
                },
                error: function(xhr) {
                    toastr.error('Logout failed. Please try again.');
                    $('#logoutModal').modal('hide');
                }
            });
        });
    });
    </script>
</body>
</html> 