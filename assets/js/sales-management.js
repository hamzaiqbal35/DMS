$(document).ready(function () {
    // Store original data for filtering
    let originalSalesData = [];
    
    // Load customers into dropdowns
    function loadCustomers() {
        $.ajax({
            url: "../model/customer/getCustomers.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Select Customer</option>';
                    let filterOptions = '<option value="">Filter by Customer</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.customer_id}">${c.customer_name}</option>`;
                        filterOptions += `<option value="${c.customer_name}">${c.customer_name}</option>`;
                    });
                    $("#customer_id, #edit_customer_id, #order_customer_id").html(options);
                    $("#filterCustomer").html(filterOptions);
                } else {
                    toastr.error("Failed to load customers");
                    console.error("Customer load error:", res);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading customers");
                console.error("Customer load error:", error);
            }
        });
    }

    // Load items into dropdowns
    function loadItems() {
        $.ajax({
            url: "../model/inventory/getItems.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Select Item</option>';
                    res.data.forEach(i => {
                        options += `<option value="${i.item_id}" data-price="${i.unit_price}">${i.item_name}</option>`;
                    });
                    $("#item_id, #edit_item_id").html(options);
                } else {
                    toastr.error("Failed to load items");
                    console.error("Item load error:", res);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading items");
                console.error("Item load error:", error);
            }
        });
    }

    // Load customer orders for sale from order
    function loadCustomerOrders(customerId) {
        if (!customerId) {
            $("#customer_order_id").html('<option value="">Select Order</option>');
            return;
        }
        
        $.ajax({
            url: "../api/admin/get-orders.php",
            method: "GET",
            data: { customer_id: customerId },
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Select Order</option>';
                    res.data.forEach(order => {
                        // Only show orders not pending/cancelled
                        if (!['pending', 'cancelled'].includes(order.order_status)) {
                            options += `<option value="${order.order_id}" data-order='${JSON.stringify(order)}'>${order.order_number} - ${order.order_date}</option>`;
                        }
                    });
                    $("#customer_order_id").html(options);
                } else {
                    $("#customer_order_id").html('<option value="">No orders found</option>');
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading customer orders");
                console.error("Order load error:", error);
            }
        });
    }

    // Calculate totals
    function calculateTotals(formPrefix = '') {
        const quantity = parseFloat($(`#${formPrefix}quantity`).val()) || 0;
        const unitPrice = parseFloat($(`#${formPrefix}unit_price`).val()) || 0;
        const total = quantity * unitPrice;

        $(`#${formPrefix}preview_total`).text(`PKR ${total.toFixed(2)}`);

        return { total };
    }

    // Load sales data
    function loadSales() {
        $.get("../model/sale/fetchSaleList.php", function (res) {
            if (res.status === "success") {
                originalSalesData = res.data;
                renderSalesTable(res.data);
                $("#totalSalesCount span").text(res.data.length);
                $("#totalSalesCount").show();
                updateSummary(res.data);
            }
        });
    }

    // Render sales table
    function renderSalesTable(sales) {
        const tbody = $("#salesTable tbody");
        tbody.empty();

        if (sales.length === 0) {
            $("#emptyState").removeClass('d-none');
            return;
        }

        $("#emptyState").addClass('d-none');
        sales.forEach(sale => {
            // Determine sale type badge
            let saleTypeBadge = '';
            if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                saleTypeBadge = `<span class="badge bg-info" title="From Customer Order: ${sale.order_number || 'N/A'}">
                    <i class="fas fa-shopping-cart me-1"></i>From Order
                </span>`;
            } else {
                saleTypeBadge = `<span class="badge bg-secondary">
                    <i class="fas fa-plus me-1"></i>Direct Sale
                </span>`;
            }

            // Format items display
            let itemsDisplay = sale.total_items > 1 ? `${sale.total_items} items` : sale.items_details || 'N/A';
            
            // Format amounts with proper null handling
            const totalAmount = parseFloat(sale.display_total_amount || 0).toFixed(2);
            const paidAmount = parseFloat(sale.display_paid_amount || 0).toFixed(2);
            const pendingAmount = parseFloat(sale.display_pending_amount || 0).toFixed(2);

            // Build action menu, hiding Edit for customer order sales
            let actionMenu = `<div class="dropdown">
                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item viewSaleBtn" href="#" data-id="${sale.sale_id}">
                            <i class="fas fa-eye me-2"></i> View
                        </a>
                    </li>`;
            if (!sale.customer_order_id || sale.customer_order_id === 'N/A') {
                actionMenu += `<li>
                    <a class="dropdown-item editSaleBtn" href="#" data-id="${sale.sale_id}">
                        <i class="fas fa-edit me-2"></i> Edit
                    </a>
                </li>`;
            }
            actionMenu += `<li>
                    <a class="dropdown-item recordPaymentBtn" href="#" data-id="${sale.sale_id}">
                        <i class="fas fa-credit-card me-2"></i> Record Payment
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger deleteSaleBtn" href="#" data-id="${sale.sale_id}">
                        <i class="fas fa-trash-alt me-2"></i> Delete
                    </a>
                </li>
            </ul>
        </div>`;

            tbody.append(`
                <tr data-customer-name="${sale.customer_name}" data-payment-status="${sale.payment_status}" data-order-status="${sale.order_status}" data-sale-type="${sale.customer_order_id && sale.customer_order_id !== 'N/A' ? 'from_order' : 'direct'}">
                    <td>${sale.invoice_number}</td>
                    <td>${sale.customer_name}</td>
                    <td>${saleTypeBadge}</td>
                    <td>${itemsDisplay}</td>
                    <td>PKR ${totalAmount}</td>
                    <td>PKR ${paidAmount}</td>
                    <td>PKR ${pendingAmount}</td>
                    <td>${sale.sale_date}</td>
                    <td><span class="badge bg-${getPaymentStatusColor(sale.payment_status)}">${sale.payment_status}</span></td>
                    <td><span class="badge bg-${getOrderStatusColor(sale.order_status)}">${sale.order_status}</span></td>
                    <td>${sale.tracking_number && sale.tracking_number !== 'N/A' ? sale.tracking_number : '_'}</td>
                    <td>
                        ${actionMenu}
                    </td>
                </tr>
            `);
        });
    }

    // Get payment status color
    function getPaymentStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success'
        };
        return colors[status] || 'secondary';
    }

    // Get order status color
    function getOrderStatusColor(status) {
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

    // Apply filters
    function applyFilters() {
        const searchTerm = $("#searchInput").val().toLowerCase();
        const customerFilter = $("#filterCustomer").val().toLowerCase();
        const paymentStatusFilter = $("#filterPaymentStatus").val();
        const orderStatusFilter = $("#filterOrderStatus").val();
        const saleTypeFilter = $("#filterSaleType").val();

        const filteredData = originalSalesData.filter(sale => {
            const matchesSearch = !searchTerm || 
                sale.invoice_number.toLowerCase().includes(searchTerm) ||
                sale.customer_name.toLowerCase().includes(searchTerm) ||
                sale.items_details.toLowerCase().includes(searchTerm) ||
                sale.tracking_number.toLowerCase().includes(searchTerm);

            const matchesCustomer = !customerFilter || 
                sale.customer_name.toLowerCase().includes(customerFilter);

            const matchesPaymentStatus = !paymentStatusFilter || 
                sale.payment_status === paymentStatusFilter;

            const matchesOrderStatus = !orderStatusFilter || (sale.order_status === orderStatusFilter);

            const matchesSaleType = !saleTypeFilter || 
                (saleTypeFilter === 'direct' && (!sale.customer_order_id || sale.customer_order_id === 'N/A')) ||
                (saleTypeFilter === 'from_order' && sale.customer_order_id && sale.customer_order_id !== 'N/A');

            return matchesSearch && matchesCustomer && matchesPaymentStatus && matchesOrderStatus && matchesSaleType;
        });

        renderSalesTable(filteredData);
        updateSummary(filteredData);
    }

    // Update summary
    function updateSummary(salesData) {
        const totalSales = salesData.length;
        const totalAmount = salesData.reduce((sum, sale) => sum + parseFloat(sale.display_total_amount || 0), 0);
        const totalPaid = salesData.reduce((sum, sale) => sum + parseFloat(sale.display_paid_amount || 0), 0);
        const totalPending = salesData.reduce((sum, sale) => sum + parseFloat(sale.display_pending_amount || 0), 0);
        const uniqueCustomers = new Set(salesData.map(sale => sale.customer_name)).size;
        const averageSale = totalSales > 0 ? totalAmount / totalSales : 0;

        $("#totalSalesCount span").text(totalSales);
        $("#totalAmount").text(`PKR ${totalAmount.toFixed(2)}`);
        $("#totalPaid").text(`PKR ${totalPaid.toFixed(2)}`);
        $("#totalPending").text(`PKR ${totalPending.toFixed(2)}`);
        $("#uniqueCustomers").text(uniqueCustomers);
        $("#averageSale").text(`PKR ${averageSale.toFixed(2)}`);
    }

    // Initialize
    loadCustomers();
    loadItems();
    loadSales();

    // Set default dates
    $("#sale_date, #order_sale_date").val(new Date().toISOString().split('T')[0]);

    // Event listeners
    $("#searchInput, #filterCustomer, #filterPaymentStatus, #filterOrderStatus, #filterSaleType").on('input change', applyFilters);

    $("#resetFilters").click(function() {
        $("#searchInput, #filterCustomer, #filterPaymentStatus, #filterOrderStatus, #filterSaleType").val('');
        applyFilters();
    });

    // Customer order selection
    $("#order_customer_id").change(function() {
        const customerId = $(this).val();
        loadCustomerOrders(customerId);
    });

    $("#customer_order_id").change(function() {
        const selectedOption = $(this).find('option:selected');
        const orderData = selectedOption.data('order');
        
        if (orderData) {
            // Populate order details preview
            let previewHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Order Number:</strong> ${orderData.order_number}</p>
                        <p><strong>Order Date:</strong> ${orderData.order_date}</p>
                        <p><strong>Total Amount:</strong> PKR ${parseFloat(orderData.final_amount).toFixed(2)}</p>
                        <p><strong>Payment Method:</strong> ${orderData.payment_method}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Items:</strong></p>
                        <ul>`;
            
            if (orderData.items && orderData.items.length > 0) {
                orderData.items.forEach(item => {
                    previewHtml += `<li>${item.item_name} - ${item.quantity} x PKR ${parseFloat(item.unit_price).toFixed(2)}</li>`;
                });
            }
            
            previewHtml += `</ul></div></div>`;
            
            $("#orderDetailsContent").html(previewHtml);
            $("#orderDetailsPreview").show();
            
            // Auto-populate tracking number if available
            if (orderData.tracking_number) {
                $("#order_tracking_number").val(orderData.tracking_number);
            }
        } else {
            $("#orderDetailsPreview").hide();
        }
    });

    // Calculate totals on input change
    $("#quantity, #unit_price").on('input', function() {
        calculateTotals();
    });

    $("#edit_quantity, #edit_unit_price").on('input', function() {
        calculateTotals('edit_');
    });

    // Item selection - auto-populate price
    $("#item_id").change(function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        if (price) {
            $("#unit_price").val(price);
            calculateTotals();
        }
    });

    $("#edit_item_id").change(function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        if (price) {
            $("#edit_unit_price").val(price);
            calculateTotals('edit_');
        }
    });

    // Add direct sale form submission
    $("#addSaleForm").submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "../model/sale/insertSale.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message);
                    $("#addSaleModal").modal('hide');
                    $("#addSaleForm")[0].reset();
                    loadSales();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error("Error adding sale");
            }
        });
    });

    // View sale details
    $(document).on('click', '.viewSaleBtn', function() {
        const saleId = $(this).data('id');
        console.log('[DEBUG] Viewing sale ID:', saleId);
        
        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                const sale = res.data;
                
                // Populate customer information
                $("#view_customer_name").text(sale.customer_name);
                $("#view_customer_phone").text(sale.customer_phone);
                $("#view_customer_email").text(sale.customer_email);
                $("#view_customer_address").text(sale.customer_address);
                $("#view_customer_city").text(sale.customer_city);
                $("#view_customer_state").text(sale.customer_state);
                $("#view_customer_zip_code").text(sale.customer_zip_code);
                
                // Populate sale information
                $("#view_invoice_number").text(sale.invoice_number);
                $("#view_sale_date").text(sale.sale_date);
                $("#view_payment_status").text(sale.payment_status);
                $("#view_sale_type").text(sale.sale_type);
                $("#view_tracking_number").text(sale.tracking_number && sale.tracking_number !== 'N/A' ? sale.tracking_number : '-');
                $("#view_created_by_name").text(sale.created_by_name);
                $("#view_created_at").text(sale.created_at);
                
                // Hide Order Status for Direct Sales
                if (sale.sale_type === 'Direct Sale') {
                    $("#view_order_status").parent().hide();
                } else {
                    $("#view_order_status").parent().show();
                    $("#view_order_status").text(sale.order_status);
                }
                
                // Show/hide order information based on sale type
                if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                    $("#view_order_info").show();
                    $("#view_order_number").text(sale.order_number);
                    $("#view_order_date").text(sale.order_date);
                    $("#view_order_payment_method").text(sale.order_payment_method);
                    $("#view_order_payment_status").text(sale.order_payment_status);
                    $("#view_shipping_address").text(sale.shipping_address);
                    $("#view_billing_address").text(sale.billing_address);
                    $("#view_customer_user_name").text(sale.customer_user_name);
                    $("#view_customer_user_email").text(sale.customer_user_email);
                } else {
                    $("#view_order_info").hide();
                }
                
                // Populate items
                const tbody = $("#view_items_tbody");
                tbody.empty();
                sale.items.forEach(item => {
                    tbody.append(`
                        <tr>
                            <td>${item.item_name}</td>
                            <td>${item.item_number}</td>
                            <td>${item.description || 'N/A'}</td>
                            <td>${item.unit_of_measure}</td>
                            <td>${item.quantity}</td>
                            <td>PKR ${item.unit_price}</td>
                            <td>PKR ${item.total_price}</td>
                        </tr>
                    `);
                });

                // --- Invoice-style total summary (matches sale-invoices.js) ---
                let subtotal = 0;
                sale.items.forEach(item => {
                    subtotal += parseFloat(item.total_price || 0);
                });
                function parseAmount(val) {
                    let n = parseFloat(val);
                    return isNaN(n) ? 0 : n;
                }
                let shipping, discount, tax, total;
                if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                    shipping = parseAmount(sale.order_shipping_amount);
                    discount = parseAmount(sale.order_discount_amount);
                    tax = parseAmount(sale.order_tax_amount);
                    total = parseAmount(sale.order_final_amount);
                } else {
                    shipping = parseAmount(sale.shipping_amount);
                    discount = parseAmount(sale.discount_amount);
                    tax = parseAmount(sale.tax_amount);
                    total = subtotal + shipping + tax - discount;
                }
                const paid = parseAmount(sale.paid_amount);
                const pending = parseAmount(sale.pending_amount) || (total - paid);
                let summaryHtml = `
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <table class="table table-sm invoice-summary-table mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-end">Subtotal:</th>
                                        <td class="text-end">PKR ${subtotal.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Shipping:</th>
                                        <td class="text-end">PKR ${shipping.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Tax:</th>
                                        <td class="text-end">PKR ${tax.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Discount:</th>
                                        <td class="text-end">- PKR ${discount.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Total:</th>
                                        <td class="text-end fw-bold">PKR ${total.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Paid:</th>
                                        <td class="text-end text-success">PKR ${paid.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-end">Remaining:</th>
                                        <td class="text-end text-danger">PKR ${pending.toFixed(2)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                $("#view_total_summary").html(summaryHtml);

                // Populate notes
                $("#view_notes").text(sale.notes);
                
                // Show correct totals
                const displayTotal = sale.display_total_amount || sale.order_final_amount || sale.total_amount;
                const displayPaid = sale.display_paid_amount || sale.paid_amount;
                const displayPending = sale.display_pending_amount || (displayTotal - displayPaid);
                $("#view_total_amount").text(displayTotal);
                $("#view_paid_amount").text(displayPaid);
                $("#view_pending_amount").text(displayPending);
                
                $("#viewSaleModal").modal('show');
            } else {
                toastr.error(res.message);
            }
        });
    });

    // Edit sale
    $(document).on('click', '.editSaleBtn', function() {
        const saleId = $(this).data('id');
        
        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                const sale = res.data;
                // Populate form fields
                $("#edit_sale_id").val(sale.sale_id);
                $("#edit_customer_id").val(sale.customer_id);
                $("#edit_item_id").val(sale.items[0].item_id);
                $("#edit_quantity").val(sale.items[0].quantity);
                $("#edit_unit_price").val(sale.items[0].unit_price);
                $("#edit_sale_date").val(sale.sale_date);
                $("#edit_payment_status").val(sale.payment_status);
                var $editOrderStatus = $('#edit_order_status');
                $editOrderStatus.prop('disabled', false).prop('readonly', false);
                $editOrderStatus.val(sale.order_status);
                $("#edit_tracking_number").val(sale.tracking_number);
                $("#edit_notes").val(sale.notes);
                
                // Disable editing for customer order sales
                if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                    $('#editSaleForm input, #editSaleForm select, #editSaleForm textarea').prop('disabled', true);
                    $('#editSaleForm button[type="submit"]').hide();
                    if ($('#editSaleForm .alert-customer-order').length === 0) {
                        $('#editSaleForm').prepend('<div class="alert alert-info alert-customer-order">Customer order sales cannot be edited.</div>');
                    }
                } else {
                    $('#editSaleForm input, #editSaleForm select, #editSaleForm textarea').prop('disabled', false);
                    $('#editSaleForm button[type="submit"]').show();
                    $('#editSaleForm .alert-customer-order').remove();
                }
                calculateTotals('edit_');
                $("#editSaleModal").modal('show');
            } else {
                toastr.error(res.message);
            }
        });
    });

    // Update sale form submission
    $("#editSaleForm").submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('sale_id', $("#edit_sale_id").val());
        
        $.ajax({
            url: "../model/sale/updateSale.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message);
                    $("#editSaleModal").modal('hide');
                    loadSales();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error("Error updating sale");
            }
        });
    });

    // Delete sale
    $(document).on('click', '.deleteSaleBtn', function() {
        const saleId = $(this).data('id');
        $("#delete_sale_id").val(saleId);
        $("#deleteSaleModal").modal('show');
    });

    $("#confirmDeleteBtn").click(function() {
        const saleId = $("#delete_sale_id").val();
        
        $.post("../model/sale/deleteSale.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                toastr.success(res.message);
                $("#deleteSaleModal").modal('hide');
                loadSales();
            } else {
                toastr.error(res.message);
            }
        });
    });

    // Record payment
    $(document).on('click', '.recordPaymentBtn', function() {
        const saleId = $(this).data('id');
        // Fetch sale details to get amounts
        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                const sale = res.data;
                const total = parseFloat(sale.display_total_amount || sale.order_final_amount || sale.total_amount || 0);
                const paid = parseFloat(sale.display_paid_amount || sale.paid_amount || 0);
                const remaining = (total - paid).toFixed(2);
                $("#payment_total_amount").text(total.toFixed(2));
                $("#payment_paid_amount").text(paid.toFixed(2));
                $("#payment_remaining_amount").text(remaining);
                
                // Set max value for payment amount input
                const paymentAmountInput = $("#payment_amount");
                paymentAmountInput.attr('max', remaining);
                paymentAmountInput.attr('placeholder', `Max: PKR ${remaining}`);
                
                // Add validation on input change
                paymentAmountInput.off('input.paymentValidation').on('input.paymentValidation', function() {
                    const inputAmount = parseFloat($(this).val()) || 0;
                    const remainingAmount = parseFloat(remaining);
                    
                    if (inputAmount > remainingAmount) {
                        $(this).addClass('is-invalid');
                        if ($(this).next('.invalid-feedback').length === 0) {
                            $(this).after('<div class="invalid-feedback">Payment amount cannot exceed remaining amount (PKR ' + remaining + ')</div>');
                        }
                    } else {
                        $(this).removeClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                    }
                });
            } else {
                $("#payment_total_amount, #payment_paid_amount, #payment_remaining_amount").text('0.00');
            }
            $("#payment_invoice_id").val(saleId);
            $("#payment_date").val(new Date().toISOString().split('T')[0]);
            $("#paymentModal").modal('show');
        });
    });

    $("#paymentForm").submit(function(e) {
        e.preventDefault();
        
        // Get payment amount and remaining amount
        const paymentAmount = parseFloat($("#payment_amount").val()) || 0;
        const remainingAmount = parseFloat($("#payment_remaining_amount").text()) || 0;
        
        // Validate payment amount
        if (paymentAmount <= 0) {
            toastr.error("Payment amount must be greater than 0");
            return;
        }
        
        if (paymentAmount > remainingAmount) {
            toastr.error("Payment amount cannot exceed remaining amount (PKR " + remainingAmount.toFixed(2) + ")");
            return;
        }
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "../model/sale/recordPayment.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message);
                    $("#paymentModal").modal('hide');
                    $("#paymentForm")[0].reset();
                    loadSales();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error("Error recording payment");
            }
        });
    });

    // Add sale modal show event
    $('#addSaleModal').on('show.bs.modal', function() {
        // If direct sale (no customer order selected), set order status to 'delivered' only if not already set
        var $orderStatus = $('#order_status');
        if ((!$('#customer_order_id').val() || $('#customer_order_id').val() === 'N/A') && ($orderStatus.val() === '' || $orderStatus.val() === 'pending')) {
            $orderStatus.val('delivered');
        }
    });

    // Clear payment validation when modal is closed
    $('#paymentModal').on('hidden.bs.modal', function() {
        const paymentAmountInput = $("#payment_amount");
        paymentAmountInput.removeClass('is-invalid');
        paymentAmountInput.next('.invalid-feedback').remove();
        paymentAmountInput.off('input.paymentValidation');
    });
});