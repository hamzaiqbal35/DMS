<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Allied Steel Works - Your Trusted Steel Solutions Partner';
include __DIR__ . '/../../inc/customer/customer-header.php';

// Fetch featured products for landing page
$featured_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT i.*, m.file_path as image_path
        FROM inventory i
        LEFT JOIN media m ON i.item_id = m.item_id
        WHERE i.show_on_website = 1 AND i.status = 'active' AND i.is_featured = 1
        GROUP BY i.item_id
        ORDER BY i.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (Exception $e) {
    $featured_products = [];
}
?>
<div class="landing-bg-parallax">

<!-- Hero Section (Slider) -->
<section class="hero-section p-0">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <!-- Slide 1: Welcome -->
            <div class="carousel-item active">
                <div class="hero-slide-bg" style="background-image: url('<?= $base_url ?>assets/images/Slide1.jpg');">
                    <div class="hero-overlay"></div>
                    <div class="container hero-content text-center">
                        <h1 class="display-4 fw-bold mb-3">Welcome to Allied Steel Works</h1>
                        <p class="lead mb-4">Your trusted partner for premium steel products and solutions.</p>
                        <a href="<?= $base_url ?>views/customer/register.php" class="btn btn-primary btn-lg btn-animate me-2">+ Register</a>
                        <a href="<?= $base_url ?>views/customer/login.php" class="btn btn-outline-light btn-lg btn-animate">Login</a>
                    </div>
                </div>
            </div>
            <!-- Slide 2: Featured Products -->
            <div class="carousel-item">
                <div class="hero-slide-bg" style="background-image: url('<?= $base_url ?>assets/images/Slide2.jpg');">
                    <div class="hero-overlay"></div>
                    <div class="container hero-content text-center">
                        <h1 class="display-4 fw-bold mb-3">Featured Steel Products</h1>
                        <p class="lead mb-4">Explore our wide range of high-quality steel items for every need.</p>
                        <a href="<?= $base_url ?>views/customer/catalogue.php" class="btn btn-success btn-lg btn-animate">View Catalogue</a>
                    </div>
                </div>
            </div>
            <!-- Slide 3: Customer Service -->
            <div class="carousel-item">
                <div class="hero-slide-bg" style="background-image: url('<?= $base_url ?>assets/images/Slide3.jpeg');">
                    <div class="hero-overlay"></div>
                    <div class="container hero-content text-center">
                        <h1 class="display-4 fw-bold mb-3">24/7 Customer Support</h1>
                        <p class="lead mb-4">We are here to help you anytime, anywhere. Contact us for assistance.</p>
                        <a href="<?= $base_url ?>views/customer/customer-support.php" class="btn btn-info btn-lg btn-animate">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<!-- About Section -->
<section class="about-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="about-content">
                    <h2>About Allied Steel Works</h2>
                    <p><?= htmlspecialchars($company_info['description'] ?? 'Leading provider of quality steel products and solutions for all your construction and industrial needs. We pride ourselves on delivering exceptional products and outstanding customer service.') ?></p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-medal fa-3x text-primary mb-3"></i>
                                <h5>Quality Assured</h5>
                                <p class="text-muted">Premium steel products meeting international standards</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                                <h5>Fast Delivery</h5>
                                <p class="text-muted">Quick and reliable delivery across Pakistan</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                <h5>24/7 Support</h5>
                                <p class="text-muted">Round-the-clock customer support and assistance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featured_products)): ?>
<section class="featured-section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Products</h2>
            <p>Discover our premium selection of steel products</p>
        </div>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="product-card">
                    <img src="<?= $base_url . ($product['image_path'] ?? 'assets/images/logo.png') ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($product['item_name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['item_name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($product['description'] ?? 'Quality steel product') ?></p>
                        <div class="product-price">
                            Rs. <?= number_format($product['customer_price'] ?? $product['unit_price'], 2) ?>
                        </div>
                        <div class="product-actions">
                            <a href="<?= $base_url ?>customer.php?page=product-details&id=<?= $product['item_id'] ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <?php if (isset($_SESSION['customer_user_id'])): ?>
                            <button class="btn btn-success btn-sm" onclick="addToCart(<?= $product['item_id'] ?>)">
                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                            </button>
                            <?php else: ?>
                            <a href="<?= $base_url ?>customer.php?page=login" class="btn btn-success btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Login to Buy
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary btn-lg">
                View All Products
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <div class="section-title">
            <h2>Our Services</h2>
            <p>Comprehensive steel solutions for all your needs</p>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-industry"></i>
                    <h4>Industrial Steel</h4>
                    <p>High-quality steel products for industrial applications and manufacturing processes.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-building"></i>
                    <h4>Construction Materials</h4>
                    <p>Reliable steel materials for construction projects of all sizes and complexities.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-tools"></i>
                    <h4>Custom Solutions</h4>
                    <p>Tailored steel solutions designed to meet your specific requirements and specifications.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-truck"></i>
                    <h4>Delivery Service</h4>
                    <p>Fast and reliable delivery service across Pakistan with real-time tracking.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-certificate"></i>
                    <h4>Quality Assurance</h4>
                    <p>All products undergo rigorous quality testing to ensure they meet international standards.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="service-card">
                    <i class="fas fa-users"></i>
                    <h4>Expert Support</h4>
                    <p>Professional technical support and consultation from our experienced team.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <p><i class="fas fa-phone me-2"></i> <?= htmlspecialchars($company_info['contact_phone'] ?? '+92-XXX-XXXXXXX') ?></p>
                    <p><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($company_info['contact_email'] ?? 'info@alliedsteelworks.com') ?></p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> <?= htmlspecialchars($company_info['address'] ?? 'Lahore, Pakistan') ?></p>
                    <div class="mt-4">
                        <h5>Business Hours</h5>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p>Saturday: 9:00 AM - 2:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    <form id="contactForm">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required id="contact_name" name="contact_name">
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required id="contact_email" name="contact_email">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Subject" required id="contact_subject" name="contact_subject">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="4" placeholder="Your Message" required id="contact_message" name="contact_message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Add to cart function
function addToCart(itemId, quantity = 1) {
    fetch('<?= $base_url ?>api/customer/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message || 'Product added to cart successfully!');
            setTimeout(() => location.reload(), 500);
        } else {
            toastr.error(data.message || 'Failed to add product to cart');
        }
    })
    .catch(() => {
        toastr.error('An error occurred. Please try again.');
    });
}

// Contact form submission
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

    const formData = {
        name: document.getElementById('contact_name').value.trim(),
        email: document.getElementById('contact_email').value.trim(),
        subject: document.getElementById('contact_subject').value.trim(),
        message: document.getElementById('contact_message').value.trim()
    };
    fetch('<?= $base_url ?>api/customer/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            toastr.success(data.message || 'Thank you for your message! We will get back to you soon.');
            document.getElementById('contactForm').reset();
        } else {
            toastr.error(data.message || 'Failed to send your message. Please try again.');
        }
    })
    .catch(() => {
        toastr.error('An error occurred. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
    });
});
</script>

</div>
<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 