<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'Product Details - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

// Get product ID from URL
$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    header("Location: " . $base_url . "customer.php?page=catalogue");
    exit();
}

// Fetch product details
$product = null;
$product_images = [];
try {
    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name, c.description as category_description
        FROM inventory i 
        LEFT JOIN categories c ON i.category_id = c.category_id 
        WHERE i.item_id = ? AND i.show_on_website = 1 AND i.status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Fetch product images
        $stmt = $pdo->prepare("SELECT file_path FROM media WHERE item_id = ? ORDER BY uploaded_at ASC");
        $stmt->execute([$product_id]);
        $product_images = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Handle error silently
}

// If product not found, redirect to catalogue
if (!$product) {
    header("Location: " . $base_url . "customer.php?page=catalogue");
    exit();
}

$page_title = $product['item_name'] . ' - Allied Steel Works';
?>

<div class="container mt-5 pt-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $base_url ?>customer.php?page=landing">Home</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $base_url ?>customer.php?page=catalogue">Products</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $base_url ?>customer.php?page=catalogue&category=<?= $product['category_id'] ?>">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($product['item_name']) ?>
            </li>
        </ol>
    </nav>

    <!-- Product Details -->
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="product-gallery">
                <?php if (!empty($product_images)): ?>
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($product_images as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= $base_url . $image['file_path'] ?>" 
                                 class="d-block w-100" alt="<?= htmlspecialchars($product['item_name']) ?>"
                                 style="height: 400px; object-fit: cover;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($product_images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <img src="<?= $base_url ?>assets/images/logo.png" 
                     class="img-fluid" alt="<?= htmlspecialchars($product['item_name']) ?>"
                     style="height: 400px; object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="product-info">
                <h1 class="h2 mb-3"><?= htmlspecialchars($product['item_name']) ?></h1>
                
                <!-- Category -->
                <p class="text-muted mb-3">
                    <i class="fas fa-tag me-2"></i>
                    Category: <a href="<?= $base_url ?>customer.php?page=catalogue&category=<?= $product['category_id'] ?>" 
                                class="text-decoration-none">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                </p>

                <!-- Price -->
                <div class="product-price mb-4">
                    <span class="h3 text-primary fw-bold">
                        Rs. <?= number_format($product['customer_price'] ?? $product['unit_price'], 2) ?>
                    </span>
                    <?php if ($product['customer_price'] && $product['customer_price'] != $product['unit_price']): ?>
                    <small class="text-muted text-decoration-line-through ms-3">
                        Rs. <?= number_format($product['unit_price'], 2) ?>
                    </small>
                    <span class="badge bg-success ms-2">Special Price</span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                <div class="product-description mb-4">
                    <h5>Description</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Product Details -->
                <div class="product-details mb-4">
                    <h5>Product Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Item Number:</strong> <?= htmlspecialchars($product['item_number']) ?></p>
                            <p><strong>Unit of Measure:</strong> <?= htmlspecialchars($product['unit_of_measure']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Stock Status:</strong> 
                                <?php if ($product['current_stock'] > 0): ?>
                                <span class="badge bg-success">In Stock</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($product['current_stock'] > 0): ?>
                            <p><strong>Available Stock:</strong> <?= number_format($product['current_stock'], 2) ?> <?= htmlspecialchars($product['unit_of_measure']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Add to Cart Section -->
                <?php if ($product['current_stock'] > 0): ?>
                <div class="add-to-cart-section">
                    <h5>Add to Cart</h5>
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" value="1" min="1" 
                                   max="<?= $product['current_stock'] ?>">
                        </div>
                        <div class="col-md-8">
                            <?php if (isset($_SESSION['customer_user_id'])): ?>
                            <button class="btn btn-success btn-lg w-100" onclick="addToCart(<?= $product['item_id'] ?>)">
                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                            </button>
                            <?php else: ?>
                            <a href="<?= $base_url ?>customer.php?page=login" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Purchase
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This product is currently out of stock. Please check back later.
                </div>
                <?php endif; ?>

                <!-- Featured Badge -->
                <?php if ($product['is_featured']): ?>
                <div class="mt-3">
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-star me-1"></i>Featured Product
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-4">Related Products</h3>
            <?php
            // Fetch related products from same category
            $related_products = [];
            try {
                $stmt = $pdo->prepare("
                    SELECT i.*, c.category_name, m.file_path as image_path 
                    FROM inventory i 
                    LEFT JOIN categories c ON i.category_id = c.category_id 
                    LEFT JOIN media m ON i.item_id = m.item_id 
                    WHERE i.category_id = ? AND i.item_id != ? AND i.show_on_website = 1 AND i.status = 'active'
                    ORDER BY i.is_featured DESC, i.created_at DESC
                    LIMIT 3
                ");
                $stmt->execute([$product['category_id'], $product['item_id']]);
                $related_products = $stmt->fetchAll();
            } catch (Exception $e) {
                // Handle error silently
            }
            ?>
            
            <?php if (!empty($related_products)): ?>
            <div class="row">
                <?php foreach ($related_products as $related): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        <img src="<?= $base_url . ($related['image_path'] ?? 'assets/images/logo.png') ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($related['item_name']) ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['item_name']) ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($related['category_name']) ?>
                            </p>
                            <div class="product-price mb-3">
                                <span class="h6 text-primary fw-bold">
                                    Rs. <?= number_format($related['customer_price'] ?? $related['unit_price'], 2) ?>
                                </span>
                            </div>
                            <div class="product-actions">
                                <a href="<?= $base_url ?>customer.php?page=product-details&id=<?= $related['item_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">No related products found.</p>
                <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>Browse All Products
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add to cart function
function addToCart(itemId) {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
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

// Quantity validation
document.getElementById('quantity').addEventListener('change', function() {
    const maxStock = <?= $product['current_stock'] ?>;
    const value = parseInt(this.value) || 1;
    
    if (value < 1) {
        this.value = 1;
    } else if (value > maxStock) {
        this.value = maxStock;
        alert('Quantity cannot exceed available stock.');
    }
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 