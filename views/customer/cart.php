<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('customer_session');
    session_start();
}

$page_title = 'Shopping Cart - Allied Steel Works';
include __DIR__ . '/../../inc/customer/customer-header.php';

$customer_id = $_SESSION['customer_user_id'] ?? null;
$cart_items = [];
$cart_total = 0;

if ($customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, i.item_name, i.customer_price, i.unit_price, m.file_path as image_path
            FROM cart c
            JOIN inventory i ON c.item_id = i.item_id
            LEFT JOIN media m ON i.item_id = m.item_id
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
?>

<div class="container">
    <h1>Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
    <div class="text-center py-5">
        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
        <h3>Your cart is empty</h3>
        <a href="<?= $base_url ?>customer.php?page=catalogue" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-lg-8">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td>
                            <input type="number" min="1" class="form-control cart-qty-input" value="<?= $item['quantity'] ?>" data-id="<?= $item['item_id'] ?>" data-cart-id="<?= $item['cart_id'] ?>" style="width:80px;">
                        </td>
                        <td>Rs. <?= number_format($item['customer_price'] ?? $item['unit_price'], 2) ?></td>
                        <td>Rs. <?= number_format($item['total_price'], 2) ?></td>
                        <td>
                            <button class="btn btn-danger btn-sm cart-delete-btn" data-id="<?= $item['item_id'] ?>" data-cart-id="<?= $item['cart_id'] ?>" title="Remove"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>Order Summary</h5>
                    <p>Total: Rs. <?= number_format($cart_total, 2) ?></p>
                    <a href="<?= $base_url ?>customer.php?page=checkout" class="btn btn-success">Checkout</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCartItemModal" tabindex="-1" aria-labelledby="deleteCartItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteCartItemModalLabel"><i class="fas fa-trash-alt me-2"></i>Remove Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove this item from your cart?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteCartItemBtn">Remove</button>
      </div>
    </div>
  </div>
</div>

<script>
let cartIdToDelete = null;

$(document).ready(function() {
    // Update cart quantity
    $('.cart-qty-input').on('change', function() {
        const cartId = $(this).data('cart-id');
        const quantity = parseInt($(this).val());
        if (quantity < 1) {
            toastr.error('Quantity must be at least 1.');
            $(this).val(1);
            return;
        }
        $.ajax({
            url: '<?= $base_url ?>api/customer/update-cart.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ cart_id: cartId, quantity }),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success('Cart updated successfully!');
                    setTimeout(() => location.reload(), 1000); // Or update DOM directly
                } else {
                    toastr.error(response.message || 'Failed to update cart.');
                }
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // Delete cart item (show modal)
    $('.cart-delete-btn').on('click', function() {
        cartIdToDelete = $(this).data('cart-id');
        $('#deleteCartItemModal').modal('show');
    });

    // Confirm delete in modal
    $('#confirmDeleteCartItemBtn').on('click', function() {
        if (!cartIdToDelete) return;
        $.ajax({
            url: '<?= $base_url ?>api/customer/remove-from-cart.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ cart_id: cartIdToDelete }),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success('Item removed from cart.');
                    // Remove the row from the table
                    $(`button[data-cart-id='${cartIdToDelete}']`).closest('tr').remove();
                    // Auto-refresh the page after a short delay
                    setTimeout(() => location.reload(), 500);
                } else {
                    toastr.error(response.message || 'Failed to remove item.');
                }
                $('#deleteCartItemModal').modal('hide');
                cartIdToDelete = null;
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
                $('#deleteCartItemModal').modal('hide');
                cartIdToDelete = null;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../inc/customer/customer-footer.php'; ?> 