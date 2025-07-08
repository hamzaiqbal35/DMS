<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'Customer Dashboard - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

$customer_id = $_SESSION['customer_user_id'] ?? null;

// Fetch customer's recent orders
$recent_orders = [];
$order_stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'total_spent' => 0
];

if ($customer_id) {
    try {
        // Get recent orders
        $stmt = $pdo->prepare("
            SELECT co.*, COUNT(cod.order_detail_id) as item_count
            FROM customer_orders co
            LEFT JOIN customer_order_details cod ON co.order_id = cod.order_id
            WHERE co.customer_user_id = ?
              AND (co.is_deleted_customer = 0 OR co.is_deleted_customer IS NULL)
            GROUP BY co.order_id
            ORDER BY co.order_date DESC
            LIMIT 5
        ");
        $stmt->execute([$customer_id]);
        $recent_orders = $stmt->fetchAll();
        
        // Get order statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status IN ('pending', 'confirmed', 'processing') THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN final_amount ELSE 0 END) as total_spent
            FROM customer_orders 
            WHERE customer_user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $order_stats = $stmt->fetch();
        
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Fetch customer profile
$customer_profile = null;
$is_first_login = $_SESSION['is_first_login'] ?? false;

if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT *, last_login FROM customer_users WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        $customer_profile = $stmt->fetch();
        
        // Fallback: If session variable is not set, check the database
        if (!isset($_SESSION['is_first_login'])) {
            $is_first_login = empty($customer_profile['last_login']);
        }
        
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Get cart count
$cart_count = 0;
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch();
        $cart_count = $result['count'] ?? 0;
    } catch (Exception $e) {
        // Handle error silently
    }
}
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php if ($is_first_login): ?>
                                    Welcome, <?= htmlspecialchars($customer_profile['full_name'] ?? 'Customer') ?>!
                                <?php else: ?>
                                    Welcome back, <?= htmlspecialchars($customer_profile['full_name'] ?? 'Customer') ?>!
                                <?php endif; ?>
                            </h2>
                            <p class="mb-0">
                                <?php if ($is_first_login): ?>
                                    Welcome to Allied Steel Works! We're excited to have you here. Start exploring our products and services.
                                <?php else: ?>
                                    Manage your orders, browse products, and track your purchases.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <img src="<?= $base_url . ($customer_profile['profile_picture'] ?? 'assets/images/logo.png') ?>" 
                                 class="rounded-circle" alt="Profile" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- First Time User Welcome Section -->
    <?php if ($is_first_login): ?>
    <?php
        // Update last_login for first-time users after they see the welcome message
        if ($customer_id) {
            try {
                $stmt = $pdo->prepare("UPDATE customer_users SET last_login = NOW() WHERE customer_user_id = ?");
                $stmt->execute([$customer_id]);
                // Clear the first login session flag
                unset($_SESSION['is_first_login']);
            } catch (Exception $e) {
                // Handle error silently
            }
        }
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>Welcome to Allied Steel Works!
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">Getting Started:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Browse Products:</strong> Explore our wide range of steel products
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Add to Cart:</strong> Select items and add them to your shopping cart
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Complete Profile:</strong> Update your profile with shipping information
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Place Orders:</strong> Checkout and track your orders easily
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">Quick Tips:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Save your shipping address for faster checkout
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Check order status in real-time
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Contact support if you need assistance
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-success me-2">
                            <i class="fas fa-store me-2"></i>Start Shopping
                        </a>
                        <a href="<?= $base_url ?>customer.php?page=profile" class="btn btn-outline-success">
                            <i class="fas fa-user-edit me-2"></i>Complete Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-bag fa-2x mb-2"></i>
                    <h3 class="mb-1"><?= $order_stats['total_orders'] ?? 0 ?></h3>
                    <p class="mb-0">Total Orders</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3 class="mb-1"><?= $order_stats['pending_orders'] ?? 0 ?></h3>
                    <p class="mb-0">Pending Orders</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3 class="mb-1"><?= $order_stats['completed_orders'] ?? 0 ?></h3>
                    <p class="mb-0">Completed Orders</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                    <h3 class="mb-1">Rs. <?= number_format($order_stats['total_spent'] ?? 0, 2) ?></h3>
                    <p class="mb-0">Total Spent</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                <i class="fas fa-store fa-2x mb-2"></i>
                                <span>Browse Products</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="<?= $base_url ?>customer.php?page=cart" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 position-relative">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <span>Shopping Cart</span>
                                <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cart_count ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="<?= $base_url ?>customer.php?page=my-orders" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                <i class="fas fa-list-alt fa-2x mb-2"></i>
                                <span>My Orders</span>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="<?= $base_url ?>customer.php?page=profile" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                <i class="fas fa-user-edit fa-2x mb-2"></i>
                                <span>Edit Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Orders</h5>
                    <a href="<?= $base_url ?>customer.php?page=my-orders" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No orders yet</h5>
                        <p class="text-muted">Start shopping to see your orders here.</p>
                        <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary">
                            <i class="fas fa-store me-2"></i>Browse Products
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                    <td><?= $order['item_count'] ?> item(s)</td>
                                    <td>Rs. <?= number_format($order['final_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['order_status'] === 'delivered' ? 'success' : 
                                            ($order['order_status'] === 'cancelled' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($order['order_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= $base_url ?>customer.php?page=order-details&id=<?= $order['order_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 