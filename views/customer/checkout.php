<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'Checkout - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

$customer_id = $_SESSION['customer_user_id'] ?? null;

// Fetch cart items for checkout
$cart_items = [];
$cart_total = 0;

if ($customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, i.item_name, i.customer_price, i.unit_price
            FROM cart c
            JOIN inventory i ON c.item_id = i.item_id
            WHERE c.customer_user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $cart_items = $stmt->fetchAll();
        
        foreach ($cart_items as $item) {
            $cart_total += $item['total_price'];
        }
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Redirect if cart is empty
if (empty($cart_items)) {
    header("Location: " . $base_url . "customer.php?page=cart");
    exit();
}

// Fetch customer profile for pre-filling
$customer_profile = null;
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customer_users WHERE customer_user_id = ?");
        $stmt->execute([$customer_id]);
        $customer_profile = $stmt->fetch();
    } catch (Exception $e) {
        // Handle error silently
    }
}

$shipping_amount = 500.00; // Default shipping amount
$order_total = $cart_total + $shipping_amount;

// In backend logic, force payment_method = 'cod'
$payment_method = 'cod';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>customer.php?page=landing">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>customer.php?page=cart">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
            
            <h1 class="mb-4">
                <i class="fas fa-credit-card me-2"></i>Checkout
            </h1>
        </div>
    </div>

    <form id="checkoutForm">
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?= htmlspecialchars($customer_profile['full_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($customer_profile['phone'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?= htmlspecialchars($customer_profile['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?= htmlspecialchars($customer_profile['city'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">State/Province *</label>
                                    <input type="text" class="form-control" id="state" name="state" 
                                           value="<?= htmlspecialchars($customer_profile['state'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="zip_code" class="form-label">ZIP/Postal Code *</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                           value="<?= htmlspecialchars($customer_profile['zip_code'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-comment me-2"></i>Order Notes (Optional)
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="order_notes" name="order_notes" rows="3" 
                                  placeholder="Any special instructions or notes for your order..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= htmlspecialchars($item['item_name']) ?> x <?= $item['quantity'] ?></span>
                            <span>Rs. <?= number_format($item['total_price'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>Rs. <?= number_format($cart_total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Rs. <?= number_format($shipping_amount, 2) ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="h5 mb-0">Total:</span>
                            <span class="h5 mb-0 text-primary">Rs. <?= number_format($order_total, 2) ?></span>
                        </div>
                        
                        <div id="checkoutMessage"></div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="placeOrderBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="orderSpinner"></span>
                                <i class="fas fa-check me-2"></i>Place Order
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="<?= $base_url ?>customer.php?page=cart" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const orderSpinner = document.getElementById('orderSpinner');
    const checkoutMessage = document.getElementById('checkoutMessage');
    
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        placeOrderBtn.disabled = true;
        orderSpinner.classList.remove('d-none');
        placeOrderBtn.querySelector('i').classList.add('d-none');
        
        // Clear previous messages
        checkoutMessage.innerHTML = '';
        
        // Get form data
        const formData = new FormData(this);
        const data = {
            full_name: formData.get('full_name'),
            phone: formData.get('phone'),
            shipping_address: formData.get('shipping_address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zip_code: formData.get('zip_code'),
            payment_method: '<?= $payment_method ?>',
            order_notes: formData.get('order_notes')
        };
        
        // Send checkout request
        fetch('<?= $base_url ?>api/customer/checkout.php', {
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
                    window.location.href = '<?= $base_url ?>customer.php?page=order-details&id=' + data.order_id;
                }, 2000);
            } else {
                toastr.error(data.message);
                placeOrderBtn.disabled = false;
                orderSpinner.classList.add('d-none');
                placeOrderBtn.querySelector('i').classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Checkout error:', error);
            toastr.error('An error occurred. Please try again.');
            placeOrderBtn.disabled = false;
            orderSpinner.classList.add('d-none');
            placeOrderBtn.querySelector('i').classList.remove('d-none');
        });
    });
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 