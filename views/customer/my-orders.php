<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'My Orders - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

$customer_id = $_SESSION['customer_user_id'] ?? null;

// Fetch customer orders with admin status
$orders = [];
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT co.*, COUNT(cod.order_detail_id) as item_count
            FROM customer_orders co
            LEFT JOIN customer_order_details cod ON co.order_id = cod.order_id
            WHERE co.customer_user_id = ?
              AND (co.is_deleted_customer = 0 OR co.is_deleted_customer IS NULL)
            GROUP BY co.order_id
            ORDER BY co.order_date DESC
        ");
        $stmt->execute([$customer_id]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        // Handle error silently
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>customer.php?page=landing">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Orders</li>
                </ol>
            </nav>
            
            <h1 class="mb-4">
                <i class="fas fa-shopping-bag me-2"></i>My Orders
            </h1>
        </div>
    </div>

    <?php if (empty($orders)): ?>
    <!-- No Orders -->
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">No orders yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your order history here.</p>
                <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Orders List -->
    <div class="row">
        <div class="col-12">
            <?php foreach ($orders as $order): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="mb-1">Order #<?= htmlspecialchars($order['order_number']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('M d, Y', strtotime($order['order_date'])) ?>
                            </small>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-<?= getStatusColor($order['order_status']) ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted"><?= $order['item_count'] ?> items</small>
                        </div>
                        <div class="col-md-2">
                            <span class="fw-bold">Rs. <?= number_format($order['final_amount'], 2) ?></span>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?= $base_url ?>customer.php?page=order-details&id=<?= $order['order_id'] ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <?php if (in_array($order['order_status'], ['pending', 'cancelled', 'delivered'])): ?>
                                <button class="btn btn-danger btn-sm delete-order-btn" data-order-id="<?= $order['order_id'] ?>">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Order Confirmation Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteOrderModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this order? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteOrderBtn"><i class="fas fa-trash-alt me-1"></i>Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
let orderIdToDelete = null;
$(document).on('click', '.delete-order-btn', function() {
    orderIdToDelete = $(this).data('order-id');
    $('#deleteOrderModal').modal('show');
});

$('#confirmDeleteOrderBtn').on('click', function() {
    if (!orderIdToDelete) return;
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting...');
    $.ajax({
        url: '<?= $base_url ?>api/customer/delete-order.php',
        method: 'POST',
        data: { order_id: orderIdToDelete },
        success: function(res) {
            $('#confirmDeleteOrderBtn').prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Delete');
            $('#deleteOrderModal').modal('hide');
            if (res.status === 'success') {
                toastr.success('Order deleted successfully.');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(res.message || 'Failed to delete order.');
            }
        },
        error: function() {
            $('#confirmDeleteOrderBtn').prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Delete');
            $('#deleteOrderModal').modal('hide');
            toastr.error('Failed to delete order.');
        }
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
?>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 