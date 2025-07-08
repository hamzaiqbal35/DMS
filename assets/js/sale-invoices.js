$(document).ready(function() {
    let originalInvoiceData = [];
    
    // Load customers for filter
    function loadCustomers() {
        $.ajax({
            url: "../model/customer/getCustomers.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Filter by Customer</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.customer_name}">${c.customer_name}</option>`;
                    });
                    $("#filterCustomer").html(options);
                }
            },
            error: function(xhr, status, error) {
                console.error("Customer load error:", error);
            }
        });
    }

    // Load invoice data
    function loadInvoiceData() {
        $.get("../model/sale/fetchSaleList.php", function(res) {
            if (res.status === "success") {
                originalInvoiceData = res.data;
                renderInvoiceTable(res.data);
            } else {
                toastr.error("Failed to load invoice data");
            }
        });
    }

    // Render invoice table
    function renderInvoiceTable(data) {
        const tbody = $("#invoiceTable tbody");
        tbody.empty();

        if (data.length === 0) {
            $("#emptyState").removeClass('d-none');
            return;
        }

        $("#emptyState").addClass('d-none');
        data.forEach(sale => {
            // Determine sale type badge
            let saleTypeBadge = '';
            if (sale.sale_type === 'customer_order' || (sale.customer_order_id && sale.customer_order_id !== 'N/A')) {
                saleTypeBadge = `<span class="badge bg-info" title="From Customer Order: ${sale.order_number || 'N/A'}">
                    <i class="fas fa-shopping-cart me-1"></i>From Order
                </span>`;
            } else {
                saleTypeBadge = `<span class="badge bg-secondary">
                    <i class="fas fa-plus me-1"></i>Direct Sale
                </span>`;
            }

            // Format amounts with proper null handling
            const totalAmount = parseFloat(sale.display_total_amount || sale.order_final_amount || sale.total_amount || 0).toFixed(2);
            const paidAmount = parseFloat(sale.display_paid_amount || sale.paid_amount || 0).toFixed(2);
            const pendingAmount = parseFloat(sale.display_pending_amount || (totalAmount - paidAmount) || 0).toFixed(2);

            tbody.append(`
                <tr data-customer-name="${sale.customer_name}" data-payment-status="${sale.payment_status}" data-order-status="${sale.order_status}" data-sale-type="${sale.customer_order_id && sale.customer_order_id !== 'N/A' ? 'from_order' : 'direct'}">
                    <td>${sale.invoice_number}</td>
                    <td>${sale.customer_name}</td>
                    <td>${saleTypeBadge}</td>
                    <td>${sale.sale_date}</td>
                    <td>PKR ${totalAmount}</td>
                    <td>PKR ${paidAmount}</td>
                    <td>PKR ${pendingAmount}</td>
                    <td><span class="badge bg-${getPaymentStatusColor(sale.payment_status)}">${sale.payment_status}</span></td>
                    <td><span class="badge bg-${getOrderStatusColor(sale.order_status)}">${sale.order_status}</span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item viewInvoiceBtn" href="#" data-id="${sale.sale_id}">
                                        <i class="fas fa-eye me-2"></i> View Invoice
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item generateInvoiceBtn" href="#" data-id="${sale.sale_id}">
                                        <i class="fas fa-file-pdf me-2"></i> Generate PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
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

        const filteredData = originalInvoiceData.filter(sale => {
            const matchesSearch = !searchTerm || 
                sale.invoice_number.toLowerCase().includes(searchTerm) ||
                sale.customer_name.toLowerCase().includes(searchTerm) ||
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

        renderInvoiceTable(filteredData);
    }

    // Initialize
    loadCustomers();
    loadInvoiceData();

    // Event listeners
    $("#searchInput, #filterCustomer, #filterPaymentStatus, #filterOrderStatus, #filterSaleType").on('input change', applyFilters);

    $("#resetFilters").click(function() {
        $("#searchInput, #filterCustomer, #filterPaymentStatus, #filterOrderStatus, #filterSaleType").val('');
        applyFilters();
    });

    // View invoice
    $(document).on('click', '.viewInvoiceBtn', function() {
        const saleId = $(this).data('id');
        console.log('[DEBUG] Viewing invoice for sale ID:', saleId);
        
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
                
                // Populate invoice information
                $("#view_invoice_number").text(sale.invoice_number);
                $("#view_sale_date").text(sale.sale_date);
                $("#view_payment_status").text(sale.payment_status);
                $("#view_sale_type").text(sale.sale_type);
                $("#view_created_by_name").text(sale.created_by_name);
                
                // Hide Order Status for Direct Sales
                if (sale.sale_type === 'Direct Sale') {
                    $("#view_order_status").parent().hide();
                } else {
                    $("#view_order_status").parent().show();
                    $("#view_order_status").text(sale.order_status);
                }
                
                // Show/hide order information based on sale type
                if (sale.sale_type === 'customer_order' || (sale.customer_order_id && sale.customer_order_id !== 'N/A')) {
                    $("#view_order_info").show();
                    $("#view_order_number").text(sale.order_number);
                    $("#view_order_date").text(sale.order_date);
                    $("#view_order_payment_method").text(sale.order_payment_method);
                    $("#view_order_payment_status").text(sale.order_payment_status);
                    $("#view_shipping_address").text(sale.shipping_address);
                    $("#view_customer_user_name").text(sale.customer_user_name);
                    $("#view_customer_user_email").text(sale.customer_user_email);
                } else {
                    $("#view_order_info").hide();
                }
                
                // Populate items
                const tbody = $("#view_items_tbody");
                tbody.empty();
                let subtotal = 0;
                sale.items.forEach(item => {
                    subtotal += parseFloat(item.total_price || 0);
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
                // Render invoice summary
                let shipping, discount, tax, total;
                if (sale.sale_type === 'customer_order' || (sale.customer_order_id && sale.customer_order_id !== 'N/A')) {
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
                $("#invoiceSummary").html(summaryHtml);
                
                // Render payment history
                const paymentTbody = $("#paymentHistoryTable tbody");
                paymentTbody.empty();
                if (sale.payments && sale.payments.length > 0) {
                    sale.payments.forEach(payment => {
                        paymentTbody.append(`
                            <tr>
                                <td>${payment.payment_date}</td>
                                <td>PKR ${payment.amount}</td>
                                <td>${payment.method}</td>
                                <td>${payment.notes}</td>
                            </tr>
                        `);
                    });
                } else {
                    paymentTbody.append('<tr><td colspan="4" class="text-center">No payment history.</td></tr>');
                }

                // Show notes
                $("#view_notes").text(sale.notes && sale.notes !== 'N/A' ? sale.notes : 'No notes.');

                // Download Invoice button
                const downloadBtn = $("#downloadInvoiceBtn");
                if (sale.invoice_file && sale.invoice_file !== 'N/A') {
                    downloadBtn.off('click').on('click', function(e) {
                        e.preventDefault();
                        
                        console.log('[DEBUG] Downloading invoice:', sale.invoice_file);
                        
                        // Create hidden iframe for download without redirect
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = `../api/sale/downloadInvoice.php?filename=${encodeURIComponent(sale.invoice_file)}`;
                        document.body.appendChild(iframe);
                        
                        // Remove iframe after download starts
                        setTimeout(() => {
                            document.body.removeChild(iframe);
                        }, 3000);
                        
                        console.log('[DEBUG] Download initiated via iframe:', sale.invoice_file);
                    });
                    downloadBtn.show();
                } else {
                    downloadBtn.hide();
                }
                
                $("#viewInvoiceModal").modal('show');
            } else {
                toastr.error(res.message);
            }
        });
    });

    // Generate invoice
    $(document).on('click', '.generateInvoiceBtn', function() {
        const saleId = $(this).data('id');
        $("#generate_sale_id").val(saleId);
        $("#invoice_date").val(new Date().toISOString().split('T')[0]);
        $("#due_date").val(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]); // 30 days from now
        $("#generateInvoiceModal").modal('show');
    });

    // Generate invoice form submission
    $("#generateInvoiceForm").submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "../model/sale/generateInvoice.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message);
                    $("#generateInvoiceModal").modal('hide');
                    
                    // Refresh the invoice table to show the new invoice file
                    loadInvoiceData();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error("Error generating invoice");
            }
        });
    });

    // Robust amount parser for summary fields
    function parseAmount(val) {
        if (val === undefined || val === null) return 0;
        if (typeof val === 'string' && (val.trim() === '' || val.trim().toLowerCase() === 'n/a')) return 0;
        const num = parseFloat(val);
        return isNaN(num) ? 0 : num;
    }
});