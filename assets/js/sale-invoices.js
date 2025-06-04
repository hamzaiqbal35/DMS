$(document).ready(function() {
    // Store original data for filtering
    let originalSalesData = [];

    // Load customers into filter dropdown
    function loadCustomers() {
        $.ajax({
            url: "../model/customer/getCustomers.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Filter by Customer</option>';
                    res.data.forEach(customer => {
                        options += `<option value="${customer.customer_id}">${customer.customer_name}</option>`;
                    });
                    $("#filterCustomer").html(options);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading customers:", error);
                showMessage("Error loading customers", "error");
            }
        });
    }

    // Load invoices with filters
    function loadInvoices() {
        const filters = {
            customer_id: $("#filterCustomer").val(),
            payment_status: $("#filterStatus").val(),
            date_from: $("#startDate").val(),
            date_to: $("#endDate").val()
        };

        // Show loading state
        const tbody = $("#invoiceTable tbody");
        tbody.html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.ajax({
            url: "../model/sale/fetchSaleList.php",
            method: "GET",
            data: filters,
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    originalSalesData = res.data; // Store original data
                    renderInvoices(res.data);
                } else {
                    tbody.empty();
                    $("#emptyState").removeClass('d-none');
                }
            },
            error: function(xhr, status, error) {
                tbody.html('<tr><td colspan="6" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error loading invoices</td></tr>');
                showMessage("Error loading invoices: " + error, "error");
            }
        });
    }

    // Render invoices with search/filter
    function renderInvoices(sales) {
        const tbody = $("#invoiceTable tbody");
        tbody.empty();

        if (sales.length === 0) {
            $("#emptyState").removeClass('d-none');
            return;
        }

        $("#emptyState").addClass('d-none');
        sales.forEach(sale => {
            const actions = `
                <div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#" onclick="viewInvoice(${sale.sale_id})">
                                <i class="fas fa-eye me-2"></i> View
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="generateInvoice(${sale.sale_id})">
                                <i class="fas fa-file-pdf me-2"></i> Generate Invoice
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="recordPayment(${sale.sale_id})">
                                <i class="fas fa-credit-card me-2"></i> Record Payment
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteInvoice(${sale.sale_id})">
                                <i class="fas fa-trash-alt me-2"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            `;

            tbody.append(`
                <tr data-customer-name="${sale.customer_name}" data-payment-status="${sale.payment_status}">
                    <td>${sale.invoice_number || 'N/A'}</td>
                    <td>${sale.customer_name || 'N/A'}</td>
                    <td>${sale.sale_date || 'N/A'}</td>
                    <td>PKR ${parseFloat(sale.total_price || 0).toFixed(2)}</td>
                    <td><span class="badge bg-${getStatusColor(sale.payment_status)}">${sale.payment_status || 'Unknown'}</span></td>
                    <td>${actions}</td>
                </tr>
            `);
        });
    }

    // Get status color for badges
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success'
        };
        return colors[status] || 'secondary';
    }

    // Apply filters including search
    function applyFilters() {
        const searchText = $("#searchInput").val().toLowerCase().trim();
        const customerFilter = $("#filterCustomer").val().trim();
        const statusFilter = $("#filterStatus").val().trim();
        const dateFrom = $("#startDate").val();
        const dateTo = $("#endDate").val();

        let filteredData = originalSalesData.filter(sale => {
            const matchesSearch = !searchText || 
                                sale.invoice_number.toLowerCase().includes(searchText) || 
                                sale.customer_name.toLowerCase().includes(searchText);
            
            const matchesCustomer = !customerFilter || sale.customer_id.toString() === customerFilter;
            const matchesStatus = !statusFilter || sale.payment_status === statusFilter;
            
            const saleDate = new Date(sale.sale_date);
            const matchesDateFrom = !dateFrom || saleDate >= new Date(dateFrom);
            const matchesDateTo = !dateTo || saleDate <= new Date(dateTo);

            return matchesSearch && matchesCustomer && matchesStatus && matchesDateFrom && matchesDateTo;
        });

        renderInvoices(filteredData);
    }

    // Handle search input
    $("#searchInput").on('input', applyFilters);

    // Handle filter changes
    $("#filterCustomer, #filterStatus, #startDate, #endDate").change(function() {
        applyFilters();
    });

    // Handle reset filters
    $("#resetFilters").click(function() {
        $("#searchInput").val('');
        $("#filterCustomer").val('');
        $("#filterStatus").val('');
        $("#startDate").val('');
        $("#endDate").val('');
        renderInvoices(originalSalesData);
    });

    // View invoice
    window.viewInvoice = function(saleId) {
        $.ajax({
            url: "../model/sale/getSaleDetails.php",
            method: "GET",
            data: { sale_id: saleId },
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    const sale = res.data;
                    
                    // Customer Information
                    $("#view_customer_name").text(sale.customer_name);
                    $("#view_customer_phone").text(sale.customer_phone || 'N/A');
                    $("#view_customer_email").text(sale.customer_email || 'N/A');
                    $("#view_customer_address").text(sale.customer_address || 'N/A');
                    
                    // Invoice Information
                    $("#view_invoice_number").text(sale.invoice_number);
                    $("#view_sale_date").text(sale.sale_date);
                    $("#view_payment_status").html(`<span class="badge bg-${getStatusColor(sale.payment_status)}">${sale.payment_status}</span>`);
                    $("#view_created_by_name").text(sale.created_by_name);
                    
                    // Item Details
                    $("#view_item_name").text(sale.item_name);
                    $("#view_item_number").text(sale.item_number || 'N/A');
                    $("#view_quantity").text(sale.quantity);
                    $("#view_unit_price").text(`PKR ${parseFloat(sale.unit_price).toFixed(2)}`);
                    $("#view_total_price").text(`PKR ${parseFloat(sale.total_price).toFixed(2)}`);
                    
                    // Notes
                    $("#view_notes").text(sale.notes || 'No notes available');

                    // Payment History
                    const paymentHistoryTable = $("#paymentHistoryTable tbody");
                    paymentHistoryTable.empty();

                    if (sale.payments && sale.payments.length > 0) {
                        sale.payments.forEach(payment => {
                            paymentHistoryTable.append(`
                                <tr>
                                    <td>${payment.payment_date}</td>
                                    <td>PKR ${parseFloat(payment.amount).toFixed(2)}</td>
                                    <td><span class="badge bg-info">${payment.method}</span></td>
                                    <td>${payment.notes || '-'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        paymentHistoryTable.append(`
                            <tr>
                                <td colspan="4" class="text-center text-muted">No payment history available</td>
                            </tr>
                        `);
                    }

                    // Update download link
                    $("#downloadInvoice").attr("href", "../" + sale.invoice_file);
                    
                    // Show modal
                    $("#viewInvoiceModal").modal("show");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading invoice details:", error);
                showMessage("Error loading invoice details", "error");
            }
        });
    };

    // Generate invoice
    window.generateInvoice = function(saleId) {
        $("#generate_sale_id").val(saleId);
        $("#invoice_date").val(new Date().toISOString().split('T')[0]);
        $("#due_date").val(new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0]);
        $("#generateInvoiceModal").modal("show");
    };

    // Record payment
    window.recordPayment = function(saleId) {
        // First fetch sale details to show remaining amount
        $.ajax({
            url: "../model/sale/getSaleDetails.php",
            method: "GET",
            data: { sale_id: saleId },
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    const sale = res.data;
                    const totalAmount = parseFloat(sale.total_price);
                    const paidAmount = parseFloat(sale.paid_amount || 0);
                    const remainingAmount = totalAmount - paidAmount;

                    // Update payment modal with sale details
                    $("#payment_invoice_id").val(saleId);
                    $("#payment_date").val(new Date().toISOString().split('T')[0]);
                    
                    // Add remaining amount info
                    let paymentInfoHtml = `
                        <div class="alert alert-info mb-3">
                            <p class="mb-1"><strong>Total Amount:</strong> PKR ${totalAmount.toFixed(2)}</p>
                            <p class="mb-1"><strong>Paid Amount:</strong> PKR ${paidAmount.toFixed(2)}</p>
                            <p class="mb-0"><strong>Remaining Amount:</strong> PKR ${remainingAmount.toFixed(2)}</p>
                        </div>`;
                    
                    // Insert the payment info before the payment amount input
                    $("#payment_amount").closest('.mb-3').before(paymentInfoHtml);
                    
                    // Set max amount for payment
                    $("#payment_amount").attr('max', remainingAmount);
                    
                    $("#paymentModal").modal("show");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading sale details:", error);
                showMessage("Error loading sale details", "error");
            }
        });
    };

    // Delete invoice
    window.deleteInvoice = function(saleId) {
        $("#delete_invoice_id").val(saleId);
        $("#deleteInvoiceModal").modal("show");
    };

    // Handle generate invoice form submission
    $("#generateInvoiceForm").submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        // UI feedback: disable button and show spinner
        const $btn = $("#generateInvoiceForm button[type=submit]");
        const originalText = $btn.html();
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');

        // Optionally, close the modal immediately for instant feel
        $("#generateInvoiceModal").modal("hide");
        toastr.info("Generating invoice...");

        $.ajax({
            url: "../model/sale/generateInvoice.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    toastr.success("Invoice generated successfully");
                    // Optionally reload invoices or update UI
                    loadInvoices();
                } else {
                    toastr.error(res.message || "Error generating invoice");
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error generating invoice");
            },
            complete: function() {
                // Re-enable button and restore text
                $btn.prop("disabled", false).html(originalText);
            }
        });
    });

    // Handle payment form submission
    $("#paymentForm").submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const paymentAmount = parseFloat($("#payment_amount").val());
        const maxAmount = parseFloat($("#payment_amount").attr('max'));

        if (paymentAmount > maxAmount) {
            showMessage("Payment amount cannot exceed remaining amount", "error");
            return;
        }

        $.ajax({
            url: "../model/sale/recordPayment.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    showMessage("Payment recorded successfully");
                    $("#paymentModal").modal("hide");
                    loadInvoices();
                } else {
                    showMessage(res.message || "Error recording payment", "error");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error recording payment:", error);
                showMessage("Error recording payment", "error");
            }
        });
    });

    // Clear payment info when modal is hidden
    $("#paymentModal").on('hidden.bs.modal', function() {
        $(".alert-info").remove();
        $("#paymentForm")[0].reset();
    });

    // Handle delete invoice confirmation
    $("#confirmDeleteInvoiceBtn").click(function() {
        const saleId = $("#delete_invoice_id").val();

        $.ajax({
            url: "../model/sale/deleteSale.php",
            method: "POST",
            data: { sale_id: saleId },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    showMessage("Invoice deleted successfully");
                    $("#deleteInvoiceModal").modal("hide");
                    loadInvoices();
                } else {
                    showMessage(res.message || "Error deleting invoice", "error");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error deleting invoice:", error);
                showMessage("Error deleting invoice", "error");
            }
        });
    });

    // Handle export
    $('.dropdown-menu a[data-export-format]').click(function(e) {
        e.preventDefault();
        const format = $(this).data('export-format');
        const filters = {
            customer_id: $("#filterCustomer").val(),
            payment_status: $("#filterStatus").val(),
            date_from: $("#startDate").val(),
            date_to: $("#endDate").val()
        };

        $.ajax({
            url: "../model/sale/exportSales.php",
            method: "POST",
            data: { ...filters, export_format: format },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response, status, xhr) {
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = "sales_report." + format;
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = decodeURIComponent(matches[1].replace(/['"]/g, ''));
                    }
                }

                const blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                showMessage("Report exported successfully");
            },
            error: function(xhr, status, error) {
                console.error("Error exporting report:", error);
                showMessage("Error exporting report", "error");
            }
        });
    });

    // Show message using toastr
    function showMessage(message, type = 'success') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000
        };
        toastr[type](message);
    }

    // Initialize
    loadCustomers();
    loadInvoices();
});