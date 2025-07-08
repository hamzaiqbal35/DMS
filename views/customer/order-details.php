<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'Order Details - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

$customer_id = $_SESSION['customer_user_id'] ?? null;
$order_id = intval($_GET['id'] ?? 0);

// Fetch order details
$order = null;
$order_items = [];
$payments = [];

if ($customer_id && $order_id) {
    try {
        // Get order
        $stmt = $pdo->prepare("
            SELECT co.*, cu.full_name, cu.phone, cu.email
            FROM customer_orders co
            JOIN customer_users cu ON co.customer_user_id = cu.customer_user_id
            WHERE co.order_id = ? AND co.customer_user_id = ?
              AND (co.is_deleted_customer = 0 OR co.is_deleted_customer IS NULL)
        ");
        $stmt->execute([$order_id, $customer_id]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Get order items
            $stmt = $pdo->prepare("
                SELECT cod.*, i.item_name, i.item_number, m.file_path as image_path
                FROM customer_order_details cod
                JOIN inventory i ON cod.item_id = i.item_id
                LEFT JOIN media m ON i.item_id = m.item_id
                WHERE cod.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            // Get payments
            $stmt = $pdo->prepare("
                SELECT *
                FROM customer_payments
                WHERE order_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$order_id]);
            $payments = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        // Handle error silently
    }
}

// Redirect if order not found
if (!$order) {
    header("Location: " . $base_url . "customer.php?page=my-orders");
    exit();
}
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>customer.php?page=landing">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>customer.php?page=my-orders">My Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order Details</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-receipt me-2"></i>Order Details
                </h1>
                <a href="<?= $base_url ?>customer.php?page=my-orders" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                            <p><strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($order['order_date'])) ?></p>
                            <p><strong>Payment Method:</strong> Cash on Delivery</p>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <p><strong>Tracking Number:</strong> <span class="text-primary"><?= htmlspecialchars($order['tracking_number']) ?></span></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Order Status:</strong> 
                                <span class="badge bg-<?= getStatusColor($order['order_status']) ?>">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </p>
                            <p><strong>Payment Status:</strong>
                              <?php if ($order['payment_status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                              <?php elseif ($order['payment_status'] === 'partial'): ?>
                                <span class="badge bg-info">Partial</span>
                              <?php elseif ($order['payment_status'] === 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                              <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($order_items as $item): ?>
                    <div class="border-bottom p-3">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="<?= $base_url . ($item['image_path'] ?? 'assets/images/logo.png') ?>" 
                                     class="img-fluid rounded" alt="<?= htmlspecialchars($item['item_name']) ?>"
                                     style="max-height: 60px; object-fit: cover;">
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-1"><?= htmlspecialchars($item['item_name']) ?></h6>
                                <small class="text-muted">Item #<?= htmlspecialchars($item['item_number']) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="text-muted">Qty: <?= $item['quantity'] ?></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <span class="fw-bold">Rs. <?= number_format($item['total_price'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Shipping Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Shipping Address:</strong></p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Rs. <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span>Rs. <?= number_format($order['tax_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Rs. <?= number_format($order['shipping_amount'], 2) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount:</span>
                        <span class="text-success">-Rs. <?= number_format($order['discount_amount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5 mb-0">Total:</span>
                        <span class="h5 mb-0 text-primary">Rs. <?= number_format($order['final_amount'], 2) ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['notes'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($payments)): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($payments as $payment): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?= date('M d, Y H:i', strtotime($payment['created_at'])) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= getPaymentStatusColor($payment['payment_status']) ?>">
                                    <?= ucfirst($payment['payment_status']) ?>
                                </span>
                                <br>
                                <strong>Rs. <?= number_format($payment['amount'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cancel Order Button for cancellable orders -->
            <?php 
            $cancellable_statuses = ['pending', 'confirmed', 'processing'];
            if (in_array($order['order_status'], $cancellable_statuses)): 
            ?>
            <div class="card shadow-sm mt-4">
                <div class="card-body text-center">
                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="fas fa-times-circle me-2"></i>Cancel Order
                    </button>
                    <p class="text-muted mt-2 mb-0">
                        <small>You can cancel this order as it hasn't been shipped yet.</small>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cancellation Info for cancelled orders -->
            <?php if ($order['order_status'] === 'cancelled'): ?>
            <div class="card shadow-sm mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Order Cancelled</h5>
                </div>
                <div class="card-body">
                    <p><strong>Cancellation Date:</strong> <?= date('M d, Y H:i', strtotime($order['cancellation_date'])) ?></p>
                    <?php if ($order['cancellation_reason']): ?>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($order['cancellation_reason']) ?></p>
                    <?php endif; ?>
                    <?php if ($order['payment_status'] === 'refunded'): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-1"></i>
                        <strong>Payment has been refunded</strong>
                    </div>
                    <?php endif; ?>
                    <div class="stock-restored mt-3">
                        <i class="fas fa-boxes me-1"></i>
                        <strong>Stock Restored:</strong> All items from this order have been returned to inventory.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Cancel Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Are you sure you want to cancel this order?</strong>
                    <br>
                    <small>This action cannot be undone. If you've already paid, a refund will be processed.</small>
                </div>
                
                <form id="cancelOrderForm">
                    <input type="hidden" id="cancel_order_id" value="<?= $order['order_id'] ?>">
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Cancellation Reason (Optional)</label>
                        <textarea class="form-control" id="cancellation_reason" rows="3" 
                                  placeholder="Please let us know why you're cancelling this order..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Keep Order
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelOrder">
                    <i class="fas fa-times-circle me-1"></i>Cancel Order
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Processing your request...</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle order cancellation
    $('#confirmCancelOrder').click(function() {
        const orderId = $('#cancel_order_id').val();
        const cancellationReason = $('#cancellation_reason').val().trim();
        
        // Validate order ID
        if (!orderId) {
            showAlert('Invalid order ID', 'danger');
            return;
        }
        
        // Disable button to prevent double-click
        $('#confirmCancelOrder').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cancelling...');
        
        // Show loading modal
        $('#cancelOrderModal').modal('hide');
        $('#loadingModal').modal('show');
        
        // Make API call
        $.ajax({
            url: '<?= $base_url ?>api/customer/cancel-order.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                order_id: orderId,
                cancellation_reason: cancellationReason
            }),
            success: function(response) {
                $('#loadingModal').modal('hide');
                
                if (response.status === 'success') {
                    showAlert('Order cancelled successfully! Redirecting to orders page...', 'success');
                    
                    // Redirect to orders page after a short delay
                    setTimeout(function() {
                        window.location.href = '<?= $base_url ?>customer.php?page=my-orders';
                    }, 2000);
                } else {
                    showAlert('Error: ' + (response.message || 'Failed to cancel order'), 'danger');
                    $('#cancelOrderModal').modal('show');
                    // Re-enable button
                    $('#confirmCancelOrder').prop('disabled', false).html('<i class="fas fa-times-circle me-1"></i>Cancel Order');
                }
            },
            error: function(xhr, status, error) {
                $('#loadingModal').modal('hide');
                
                let errorMessage = 'An error occurred while cancelling the order';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 401) {
                    errorMessage = 'You are not authorized to perform this action. Please login again.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Order not found or access denied.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection.';
                }
                
                showAlert('Error: ' + errorMessage, 'danger');
                $('#cancelOrderModal').modal('show');
                // Re-enable button
                $('#confirmCancelOrder').prop('disabled', false).html('<i class="fas fa-times-circle me-1"></i>Cancel Order');
            }
        });
    });
    
    // Function to show Bootstrap alerts
    function showAlert(message, type = 'info') {
        // Remove any existing alerts
        $('.alert-dismissible').remove();
        // Use Toastr for all notifications
        switch(type) {
            case 'success': toastr.success(message); break;
            case 'danger': toastr.error(message); break;
            case 'warning': toastr.warning(message); break;
            default: toastr.info(message); break;
        }
    }
    
    // Handle modal close to reset form
    $('#cancelOrderModal').on('hidden.bs.modal', function() {
        $('#cancellation_reason').val('');
    });
});
</script>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'processing': return 'primary';
        case 'shipped': return 'info';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'paid': return 'success';
        case 'failed': return 'danger';
        case 'refunded': return 'secondary';
        default: return 'secondary';
    }
}
?>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 