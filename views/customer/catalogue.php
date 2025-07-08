<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}
$page_title = 'Product Catalogue - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Build the query
$query = "
    SELECT i.*, c.category_name, m.file_path as image_path 
    FROM inventory i 
    LEFT JOIN categories c ON i.category_id = c.category_id 
    LEFT JOIN media m ON i.item_id = m.item_id 
    WHERE i.show_on_website = 1 AND i.status = 'active'
";

$params = [];

// Add search filter
if (!empty($search)) {
    $query .= " AND (i.item_name LIKE ? OR i.description LIKE ? OR c.category_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Add category filter
if (!empty($category)) {
    $query .= " AND i.category_id = ?";
    $params[] = $category;
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY i.customer_price ASC, i.unit_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY i.customer_price DESC, i.unit_price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY i.item_name DESC";
        break;
    default:
        $query .= " ORDER BY i.item_name ASC";
}

// Fetch products
$products = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Fetch categories for filter
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}
?>

<div class="container mt-5 pt-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold text-dark mb-3">Product Catalogue</h1>
            <p class="lead text-muted">Discover our comprehensive range of quality steel products</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="page" value="catalogue">
                        
                        <!-- Search -->
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Products</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort -->
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                            </select>
                        </div>
                        
                        <!-- Filter Button -->
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Count -->
    <div class="row mb-3">
        <div class="col-12">
            <p class="text-muted">
                Showing <?= count($products) ?> product<?= count($products) != 1 ? 's' : '' ?>
                <?php if (!empty($search) || !empty($category)): ?>
                for your search criteria
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if (!empty($products)): ?>
    <div class="row">
        <?php foreach ($products as $product): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card product-card h-100 shadow-sm">
                <div class="position-relative">
                    <img src="<?= $base_url . ($product['image_path'] ?? 'assets/images/logo.png') ?>" 
                         class="card-img-top" alt="<?= htmlspecialchars($product['item_name']) ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php if ($product['is_featured']): ?>
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-star me-1"></i>Featured
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= htmlspecialchars($product['item_name']) ?></h5>
                    <p class="card-text text-muted small mb-2">
                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                    </p>
                    <p class="card-text flex-grow-1">
                        <?= htmlspecialchars(substr($product['description'] ?? 'Quality steel product', 0, 100)) ?>
                        <?php if (strlen($product['description'] ?? '') > 100): ?>...<?php endif; ?>
                    </p>
                    <div class="product-price mb-3">
                        <span class="h5 text-primary fw-bold">
                            Rs. <?= number_format($product['customer_price'] ?? $product['unit_price'], 2) ?>
                        </span>
                        <?php if ($product['customer_price'] && $product['customer_price'] != $product['unit_price']): ?>
                        <small class="text-muted text-decoration-line-through ms-2">
                            Rs. <?= number_format($product['unit_price'], 2) ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions mt-auto">
                        <div class="row g-2">
                            <div class="col-8">
                                <a href="<?= $base_url ?>customer.php?page=product-details&id=<?= $product['item_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                            <div class="col-4">
                                <?php if (isset($_SESSION['customer_user_id'])): ?>
                                <button class="btn btn-success btn-sm w-100" onclick="addToCart(<?= $product['item_id'] ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                                <?php else: ?>
                                <a href="<?= $base_url ?>customer.php?page=login" class="btn btn-success btn-sm w-100">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- No Products Found -->
    <div class="row">
        <div class="col-12 text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-4"></i>
            <h3 class="text-muted">No Products Found</h3>
            <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
            <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary">
                <i class="fas fa-list me-2"></i>View All Products
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

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

// Auto-submit form when sort or category changes
document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 