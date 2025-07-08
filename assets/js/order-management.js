$(document).ready(function() {
    // Configure toastr options
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    
    let ordersData = [];
    
    // Load orders on page load
    loadOrders();
    
    // Set up periodic checking for cancellations (every 30 seconds)
    setInterval(checkForCancellations, 30000);
    
    // Refresh button
    $('#refreshOrders').click(function() {
        loadOrders();
    });
    
    // Filter functionality
    $('#searchInput, #filterStatus, #filterPaymentStatus, #filterDateFrom, #filterDateTo').on('change keyup', function() {
        applyFilters();
    });
    
    // Reset filters
    $('#resetFilters').click(function() {
        $('#searchInput').val('');
        $('#filterStatus').val('');
        $('#filterPaymentStatus').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        renderOrders(ordersData);
    });
    
    // Load orders from API
    function loadOrders() {
        showLoading($('#refreshOrders'));
        
        $.ajax({
            url: '../api/admin/get-orders.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoading($('#refreshOrders'));
                if (response.status === 'success') {
                    ordersData = response.data;
                    renderOrders(ordersData);
                    updateOrderCount(ordersData.length);
                    toastr.success('Orders loaded successfully');
                } else {
                    toastr.error('Error loading orders: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoading($('#refreshOrders'));
                console.error('Load orders error:', error);
                toastr.error('Error loading orders: ' + error);
            }
        });
    }
    
    // Render orders table
    function renderOrders(orders) {
        const tbody = $('#ordersTable tbody');
        tbody.empty();
        
        if (orders.length === 0) {
            $('#emptyState').removeClass('d-none');
            return;
        }
        
        $('#emptyState').addClass('d-none');
        
        orders.forEach(order => {
            const isCancelled = order.order_status === 'cancelled';
            const isCustomerCancelled = order.cancellation_reason && order.cancellation_reason.includes('Cancelled by customer');
            
            const row = `
                <tr class="${isCancelled ? 'table-danger' : ''}">
                    <td>
                        <strong>${order.order_number}</strong>
                        ${isCancelled ? '<br><small class="text-danger"><i class="fas fa-times-circle me-1"></i>Cancelled</small>' : ''}
                    </td>
                    <td>
                        <div>${order.customer_name}</div>
                        <small class="text-muted">${order.customer_email}</small>
                        ${isCustomerCancelled ? '<br><small class="text-warning"><i class="fas fa-user-times me-1"></i>Cancelled by Customer</small>' : ''}
                    </td>
                    <td>${formatDate(order.order_date)}</td>
                    <td><strong>Rs. ${parseFloat(order.final_amount).toFixed(2)}</strong></td>
                    <td>
                        <span class="badge bg-${getStatusColor(order.order_status)}">
                            ${capitalizeFirst(order.order_status)}
                        </span>
                        ${isCancelled && order.cancellation_date ? 
                            `<br><small class="text-muted">${formatDate(order.cancellation_date)}</small>` : ''
                        }
                    </td>
                    <td>
                        <span class="badge bg-${getPaymentStatusColor(order.payment_status)}">
                            ${capitalizeFirst(order.payment_status)}
                        </span>
                    </td>
                    <td>
                        ${order.tracking_number ? 
                            `<span class="badge bg-info">${order.tracking_number}</span>` : 
                            '<span class="text-muted">—</span>'
                        }
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item view-order" href="#" data-id="${order.order_id}">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                </li>
                                ${!isCancelled ? `
                                <li>
                                    <a class="dropdown-item update-status" href="#" data-id="${order.order_id}">
                                        <i class="fas fa-edit me-2"></i>Update Status
                                    </a>
                                </li>
                                ` : ''}
                                <li>
                                    <a class="dropdown-item view-logs" href="#" data-id="${order.order_id}">
                                        <i class="fas fa-history me-2"></i>Status History
                                    </a>
                                </li>
                                ${['delivered','cancelled'].includes(order.order_status) ? `
                                <li>
                                    <a class="dropdown-item text-danger delete-order" href="#" data-id="${order.order_id}">
                                        <i class="fas fa-trash-alt me-2"></i>Delete Order
                                    </a>
                                </li>
                                ` : ''}
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        
        // Show notification for customer cancellations
        const customerCancelledOrders = orders.filter(order => 
            order.order_status === 'cancelled' && 
            order.cancellation_reason && 
            order.cancellation_reason.includes('Cancelled by customer')
        );
        
        if (customerCancelledOrders.length > 0) {
            showCancellationNotification(customerCancelledOrders);
        }

        // Delete order click handler
        tbody.off('click', '.delete-order').on('click', '.delete-order', function(e) {
            e.preventDefault();
            const orderId = $(this).data('id');
            const orderRow = $(this).closest('tr');
            const orderNumber = orderRow.find('td:first-child strong').text();
            $('#deleteOrderNumber').text(orderNumber);
            $('#deleteOrderModal').data('order-id', orderId).modal('show');
        });
    }
    
    // Show cancellation notification
    function showCancellationNotification(cancelledOrders) {
        const count = cancelledOrders.length;
        const message = count === 1 ? 
            '1 order has been cancelled by a customer' : 
            `${count} orders have been cancelled by customers`;
            
        // Show toastr notification
        toastr.warning(`
            <div>
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Order Cancellations</strong><br>
                <small>${message}</small><br>
                <button class="btn btn-sm btn-outline-warning mt-2" onclick="viewCancelledOrders()">
                    <i class="fas fa-eye me-1"></i>View Details
                </button>
            </div>
        `, '', {
            timeOut: 0,
            extendedTimeOut: 0,
            closeButton: true,
            onclick: function() {
                viewCancelledOrders();
            }
        });
        
        // Show persistent notification
        $('#cancellationMessage').html(`
            ${message}. 
            <a href="#" onclick="viewCancelledOrders(); return false;" class="alert-link">Click here to view cancelled orders</a>
        `);
        $('#cancellationNotifications').show();
        
        // Handle notification dismissal
        $('#cancellationNotifications .btn-close').off('click').on('click', function() {
            $('#cancellationNotifications').hide();
        });
    }
    
    // View cancelled orders
    window.viewCancelledOrders = function() {
        $('#filterStatus').val('cancelled');
        applyFilters();
        toastr.info('Filtered to show cancelled orders');
    };
    
    // Check for new cancellations
    function checkForCancellations() {
        $.ajax({
            url: '../api/admin/get-orders.php',
            method: 'GET',
            data: { status: 'cancelled', limit: 10 },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const cancelledOrders = response.data.filter(order => 
                        order.order_status === 'cancelled' && 
                        order.cancellation_reason && 
                        order.cancellation_reason.includes('Cancelled by customer')
                    );
                    
                    // Check if there are recent cancellations (within last hour)
                    const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
                    const recentCancellations = cancelledOrders.filter(order => 
                        new Date(order.cancellation_date) > oneHourAgo
                    );
                    
                    if (recentCancellations.length > 0) {
                        // Only show notification if we haven't already shown it for these orders
                        const notificationKey = 'cancellation_notification_' + recentCancellations[0].order_id;
                        if (!localStorage.getItem(notificationKey)) {
                            showCancellationNotification(recentCancellations);
                            // Mark as notified (expires in 1 hour)
                            localStorage.setItem(notificationKey, Date.now());
                            setTimeout(() => localStorage.removeItem(notificationKey), 60 * 60 * 1000);
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                // Silently fail for background checks
                console.log('Background cancellation check failed:', error);
            }
        });
    }
    
    // Apply filters
    function applyFilters() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const statusFilter = $('#filterStatus').val();
        const paymentStatusFilter = $('#filterPaymentStatus').val();
        const dateFrom = $('#filterDateFrom').val();
        const dateTo = $('#filterDateTo').val();
        
        const filteredOrders = ordersData.filter(order => {
            const matchesSearch = !searchTerm || 
                order.order_number.toLowerCase().includes(searchTerm) ||
                order.customer_name.toLowerCase().includes(searchTerm) ||
                order.customer_email.toLowerCase().includes(searchTerm);
            
            const matchesStatus = !statusFilter || order.order_status === statusFilter;
            const matchesPaymentStatus = !paymentStatusFilter || order.payment_status === paymentStatusFilter;
            
            const matchesDate = !dateFrom || !dateTo || 
                (order.order_date >= dateFrom && order.order_date <= dateTo);
            
            return matchesSearch && matchesStatus && matchesPaymentStatus && matchesDate;
        });
        
        renderOrders(filteredOrders);
    }
    
    // View order details
    $(document).on('click', '.view-order', function() {
        const orderId = $(this).data('id');
        loadOrderDetails(orderId);
    });
    
    // Update order status
    $(document).on('click', '.update-status', function() {
        const orderId = $(this).data('id');
        $('#update_order_id').val(orderId);
        $('#updateStatusModal').modal('show');
    });
    
    // View status logs
    $(document).on('click', '.view-logs', function() {
        const orderId = $(this).data('id');
        loadStatusLogs(orderId);
    });
    
    // View cancellation details
    $(document).on('click', '.view-cancellation', function() {
        const orderId = $(this).data('id');
        loadCancellationDetails(orderId);
    });
    
    // Load cancellation details
    function loadCancellationDetails(orderId) {
        showLoading($('#cancellationDetailModal'));
        $.ajax({
            url: '../api/admin/get-order-details.php',
            method: 'GET',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                hideLoading($('#cancellationDetailModal'));
                if (response.status === 'success') {
                    $('#cancellationDetailModal').data('order-id', orderId);
                    $('#cancellationDetailContent').html(generateCancellationDetailHTML(response.data));
                    $('#cancellationDetailModal').modal('show');
                } else {
                    toastr.error('Error loading cancellation details: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoading($('#cancellationDetailModal'));
                toastr.error('Error loading cancellation details: ' + error);
            }
        });
    }
    
    // Generate cancellation detail HTML
    function generateCancellationDetailHTML(data) {
        const order = data.order;
        let html = `<div class="alert alert-danger">
            <h6><i class="fas fa-times-circle me-2"></i>Order Cancelled</h6>`;
        if (order.cancellation_date) {
            html += `<p><strong>Cancellation Date:</strong> ${formatDateTime(order.cancellation_date)}</p>`;
        }
        if (order.cancellation_reason) {
            html += `<p><strong>Reason for Cancellation:</strong> ${order.cancellation_reason}</p>`;
        } else {
            html += `<p class="text-muted"><em>No reason provided.</em></p>`;
        }
        html += `</div>`;
        html += `<div class="mt-3">
            <h6>Order Information</h6>
            <p><strong>Order Number:</strong> ${order.order_number}</p>
            <p><strong>Order Date:</strong> ${formatDate(order.order_date)}</p>
            <p><strong>Order Status:</strong> <span class="badge bg-${getStatusColor(order.order_status)}">${capitalizeFirst(order.order_status)}</span></p>
            <p><strong>Payment Status:</strong> <span class="badge bg-${getPaymentStatusColor(order.payment_status)}">${capitalizeFirst(order.payment_status)}</span></p>
        </div>`;
        html += `<div class="mt-3">
            <h6>Customer Information</h6>
            <p><strong>Name:</strong> ${order.customer_name}</p>
            <p><strong>Email:</strong> ${order.customer_email}</p>
            <p><strong>Phone:</strong> ${order.customer_phone}</p>
            <p><strong>Address:</strong> ${order.shipping_address}</p>
        </div>`;
        return html;
    }
    
    // Confirm status update
    $('#confirmStatusUpdate').click(function() {
        const orderId = $('#update_order_id').val();
        const newStatus = $('#update_status').val();
        const notes = $('#update_notes').val();
        
        if (!newStatus) {
            toastr.warning('Please select a status');
            return;
        }
        
        updateOrderStatus(orderId, newStatus, notes);
    });
    
    // Load order details
    function loadOrderDetails(orderId) {
        showLoading($('#viewOrderModal'));
        
        $.ajax({
            url: '../api/admin/get-order-details.php',
            method: 'GET',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                hideLoading($('#viewOrderModal'));
                if (response.status === 'success') {
                    $('#viewOrderModal').data('order-id', orderId);
                    $('#orderDetailsContent').html(generateOrderDetailsHTML(response.data));
                    $('#viewOrderModal').modal('show');
                } else {
                    toastr.error('Error loading order details: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoading($('#viewOrderModal'));
                toastr.error('Error loading order details: ' + error);
            }
        });
    }
    
    // Update order status
    function updateOrderStatus(orderId, newStatus, notes) {
        showLoading($('#confirmStatusUpdate'));
        
        $.ajax({
            url: '../api/admin/update-order-status.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                order_id: orderId,
                status: newStatus,
                notes: notes
            }),
            success: function(response) {
                hideLoading($('#confirmStatusUpdate'));
                if (response.status === 'success') {
                    toastr.success('Order status updated successfully!');
                    $('#updateStatusModal').modal('hide');
                    $('#updateStatusForm')[0].reset();
                    loadOrders(); // Refresh the list
                } else {
                    toastr.error('Error updating status: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoading($('#confirmStatusUpdate'));
                toastr.error('Error updating status: ' + error);
            }
        });
    }
    
    // Load status logs
    function loadStatusLogs(orderId) {
        showLoading($('#statusLogsModal'));
        
        $.ajax({
            url: '../api/admin/get-status-logs.php',
            method: 'GET',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                hideLoading($('#statusLogsModal'));
                if (response.status === 'success') {
                    $('#statusLogsContent').html(generateStatusLogsHTML(response.data));
                    $('#statusLogsModal').modal('show');
                } else {
                    toastr.error('Error loading status logs: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoading($('#statusLogsModal'));
                toastr.error('Error loading status logs: ' + error);
            }
        });
    }
    
    // Generate order details HTML
    function generateOrderDetailsHTML(data) {
        const order = data.order;
        const items = data.items;
        
        let itemsHTML = '';
        items.forEach(item => {
            itemsHTML += `
                <tr>
                    <td>${item.item_name}</td>
                    <td>${item.quantity}</td>
                    <td>Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>Rs. ${parseFloat(item.total_price).toFixed(2)}</td>
                </tr>
            `;
        });
        
        return `
            <div class="row">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <p><strong>Order Number:</strong> ${order.order_number}</p>
                    <p><strong>Order Date:</strong> ${formatDate(order.order_date)}</p>
                    <p><strong>Order Status:</strong> <span class="badge bg-${getStatusColor(order.order_status)}">${capitalizeFirst(order.order_status)}</span></p>
                    <p><strong>Payment Status:</strong> <span class="badge bg-${getPaymentStatusColor(order.payment_status)}">${capitalizeFirst(order.payment_status)}</span></p>
                    ${order.tracking_number ? `<p><strong>Tracking Number:</strong> ${order.tracking_number}</p>` : ''}
                    ${order.cancellation_date ? `
                        <div class="alert alert-danger mt-3">
                            <h6><i class="fas fa-times-circle me-2"></i>Order Cancelled</h6>
                            <p><strong>Cancellation Date:</strong> ${formatDateTime(order.cancellation_date)}</p>
                            ${order.cancellation_reason ? `<p><strong>Reason:</strong> ${order.cancellation_reason}</p>` : ''}
                            ${order.cancellation_reason && order.cancellation_reason.includes('Cancelled by customer') ? 
                                '<p class="text-warning mb-0"><i class="fas fa-user-times me-1"></i><strong>Cancelled by Customer</strong></p>' : ''
                            }
                        </div>
                    ` : ''}
                </div>
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <p><strong>Name:</strong> ${order.customer_name}</p>
                    <p><strong>Email:</strong> ${order.customer_email}</p>
                    <p><strong>Phone:</strong> ${order.customer_phone}</p>
                    <p><strong>Address:</strong> ${order.shipping_address}</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHTML}</tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Order Summary</h6>
                    <p><strong>Subtotal:</strong> Rs. ${parseFloat(order.total_amount).toFixed(2)}</p>
                    <p><strong>Tax:</strong> Rs. ${parseFloat(order.tax_amount).toFixed(2)}</p>
                    <p><strong>Shipping:</strong> Rs. ${parseFloat(order.shipping_amount).toFixed(2)}</p>
                    <p><strong>Discount:</strong> Rs. ${parseFloat(order.discount_amount).toFixed(2)}</p>
                    <p><strong>Final Amount:</strong> Rs. ${parseFloat(order.final_amount).toFixed(2)}</p>
                </div>
            </div>
        `;
    }
    
    // Generate status logs HTML
    function generateStatusLogsHTML(logs) {
        if (logs.length === 0) {
            return '<p class="text-center text-muted">No status changes recorded</p>';
        }
        
        let logsHTML = '';
        logs.forEach(log => {
            logsHTML += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${capitalizeFirst(log.old_status)} → ${capitalizeFirst(log.new_status)}</strong>
                            <div class="text-muted small">${formatDateTime(log.created_at)}</div>
                            ${log.notes ? `<div class="mt-1"><em>${log.notes}</em></div>` : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-secondary">${log.changed_by_name}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        return logsHTML;
    }
    
    // Utility functions
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }
    
    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }
    
    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'confirmed': 'info',
            'processing': 'primary',
            'shipped': 'info',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function getPaymentStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'processing': 'info',
            'paid': 'success',
            'failed': 'danger',
            'refunded': 'secondary'
        };
        return colors[status] || 'secondary';
    }
    
    function updateOrderCount(count) {
        $('#totalOrdersCount span').text(count);
        $('#totalOrdersCount').show();
    }
    
    function showMessage(type, message) {
        // Use toastr instead of alerts
        switch(type) {
            case 'success':
                toastr.success(message);
                break;
            case 'error':
                toastr.error(message);
                break;
            case 'warning':
                toastr.warning(message);
                break;
            case 'info':
            default:
                toastr.info(message);
                break;
        }
    }
    
    function showLoading(element) {
        if (element) {
            element.prop('disabled', true);
            const originalText = element.html();
            element.data('original-text', originalText);
            element.html('<span class="spinner-border spinner-border-sm me-2"></span>Loading...');
        }
    }
    
    function hideLoading(element) {
        if (element) {
            element.prop('disabled', false);
            const originalText = element.data('original-text');
            if (originalText) {
                element.html(originalText);
            }
        }
    }

    // Add deleteOrder function
    function deleteOrder(orderId) {
        $.ajax({
            url: '../api/admin/delete-order.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ order_id: orderId }),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success('Order deleted successfully!');
                    loadOrders();
                } else {
                    toastr.error('Error deleting order: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error deleting order: ' + error);
            }
        });
    }

    // Handle confirm delete button in modal
    $('#confirmDeleteOrderBtn').off('click').on('click', function() {
        const orderId = $('#deleteOrderModal').data('order-id');
        if (orderId) {
            deleteOrder(orderId);
            $('#deleteOrderModal').modal('hide');
        }
    });
}); 