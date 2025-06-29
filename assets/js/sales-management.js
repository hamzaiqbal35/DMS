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
                    $("#customer_id, #edit_customer_id").html(options);
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
            const totalPrice = parseFloat(sale.total_price || 0);
            const paidAmount = parseFloat(sale.paid_amount || 0);
            const unitPrice = parseFloat(sale.unit_price || 0);

            tbody.append(`
                <tr data-customer-name="${sale.customer_name}" data-payment-status="${sale.payment_status}">
                    <td>${sale.invoice_number}</td>
                    <td>${sale.customer_name}</td>
                    <td>${sale.item_name}</td>
                    <td>${sale.quantity}</td>
                    <td>PKR ${unitPrice.toFixed(2)}</td>
                    <td>PKR ${totalPrice.toFixed(2)}</td>
                    <td>PKR ${paidAmount.toFixed(2)}</td>
                    <td>${sale.sale_date}</td>
                    <td><span class="badge bg-${getStatusColor(sale.payment_status)}">${sale.payment_status}</span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item viewSaleBtn" href="#" data-id="${sale.sale_id}">
                                        <i class="fas fa-eye me-2"></i> View
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item editSaleBtn" href="#" data-id="${sale.sale_id}">
                                        <i class="fas fa-edit me-2"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger deleteSaleBtn" href="#" data-id="${sale.sale_id}">
                                        <i class="fas fa-trash-alt me-2"></i> Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `);
        });
    }

    // Get status color
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success'
        };
        return colors[status] || 'secondary';
    }

    // FIXED: Search and filter functionality
    function applyFilters() {
        const searchText = $("#searchInput").val().toLowerCase().trim();
        const customerFilter = $("#filterCustomer").val().trim();
        const statusFilter = $("#filterPaymentStatus").val().trim();

        console.log("Applying filters - Status:", statusFilter);

        let visibleCount = 0;

        $("#salesTable tbody tr").each(function() {
            const row = $(this);
            const invoiceNumber = row.find('td:eq(0)').text().toLowerCase();
            const customerName = row.find('td:eq(1)').text().toLowerCase();
            const itemName = row.find('td:eq(2)').text().toLowerCase();
            
            // FIXED: Get payment status from data attribute instead of badge text
            const paymentStatus = row.data('payment-status') || row.find('td:eq(8) .badge').text().toLowerCase();
            
            // Get the actual customer name from data attribute
            const rowCustomerName = row.data('customer-name') || row.find('td:eq(1)').text();

            // Search filter
            const matchesSearch = !searchText || 
                                invoiceNumber.includes(searchText) || 
                                customerName.includes(searchText) || 
                                itemName.includes(searchText);
            
            // Customer filter - exact match with customer name
            const matchesCustomer = !customerFilter || rowCustomerName === customerFilter;
            
            // FIXED: Status filter - compare with original status value
            const matchesStatus = !statusFilter || paymentStatus === statusFilter;

            const shouldShow = matchesSearch && matchesCustomer && matchesStatus;
            
            console.log(`Row payment status: "${paymentStatus}", Filter: "${statusFilter}", Match: ${matchesStatus}`);
            
            if (shouldShow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        // Show/hide empty state based on visible rows
        if (visibleCount === 0 && originalSalesData.length > 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Matching Sales Found');
            $("#emptyState p").text('Try adjusting your search or filter criteria.');
        } else if (originalSalesData.length === 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Sales Found');
            $("#emptyState p").text('Start by adding a sale or try searching differently.');
        } else {
            $("#emptyState").addClass('d-none');
        }
    }

    // Handle search input
    $("#searchInput").on('input', applyFilters);

    // Handle filter changes
    $("#filterCustomer").on('change', applyFilters);
    $("#filterPaymentStatus").on('change', applyFilters);

    // Handle reset filters
    $("#resetFilters").click(function() {
        $("#searchInput").val('');
        $("#filterCustomer").val('');
        $("#filterPaymentStatus").val('');
        renderSalesTable(originalSalesData);
    });

    // Handle item selection
    $(document).on('change', '#item_id, #edit_item_id', function() {
        const selectedOption = $(this).find('option:selected');
        const unitPrice = selectedOption.data('price') || 0;
        const formPrefix = $(this).attr('id').startsWith('edit_') ? 'edit_' : '';
        $(`#${formPrefix}unit_price`).val(unitPrice);
        calculateTotals(formPrefix);
    });

    // Handle quantity/price changes
    $(document).on('input', '#quantity, #unit_price, #edit_quantity, #edit_unit_price', function() {
        const formPrefix = $(this).attr('id').startsWith('edit_') ? 'edit_' : '';
        calculateTotals(formPrefix);
    });

    // Handle add sale form submission
    $("#addSaleForm").submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const totals = calculateTotals();
        
        formData.append('total_amount', totals.total);
        
        $.ajax({
            url: "../model/sale/insertSale.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    // Properly remove modal and backdrop
                    const modal = bootstrap.Modal.getInstance($("#addSaleModal"));
                    modal.hide();
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    
                    // Reset form and reload data
                    $("#addSaleForm")[0].reset();
                    loadSales();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error adding sale");
                console.error("Add sale error:", error);
            }
        });
    });

    // Handle edit sale form submission
    $("#editSaleForm").submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const totals = calculateTotals('edit_');
        
        formData.append('total_amount', totals.total);
        
        $.ajax({
            url: "../model/sale/updateSale.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === "success") {
                    // Properly remove modal and backdrop
                    const modal = bootstrap.Modal.getInstance($("#editSaleModal"));
                    modal.hide();
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    
                    loadSales();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error updating sale");
                console.error("Update sale error:", error);
            }
        });
    });

    // Handle view sale button click - UPDATED WITH MISSING FIELDS
    $(document).on('click', '.viewSaleBtn', function() {
        const saleId = $(this).data('id');
        // Clear previous sale ID and disable generate button initially
        $("#view_sale_id").val('');
        $("#generateInvoiceBtn").prop('disabled', true).text('Generate Invoice'); // Disable and reset text

        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success" && res.data) {
                const sale = res.data;
                // Set the sale ID for invoice generation ONLY if successful
                $("#view_sale_id").val(sale.sale_id);
                // Enable generate button
                $("#generateInvoiceBtn").prop('disabled', false).text('Generate Invoice');
                
                // Customer Information
                $("#view_customer_name").text(sale.customer_name);
                $("#view_customer_phone").text(sale.customer_phone || 'N/A');
                $("#view_customer_email").text(sale.customer_email || 'N/A');
                $("#view_customer_address").text(sale.customer_address || 'N/A');
                
                // Sale Information
                $("#view_invoice_number").text(sale.invoice_number);
                $("#view_sale_date").text(sale.sale_date);
                $("#view_payment_status").text(sale.payment_status);
                $("#view_created_by_name").text(sale.created_by_name);
                
                // Item Details
                $("#view_item_name").text(sale.item_name);
                $("#view_item_number").text(sale.item_number || 'N/A');
                $("#view_quantity").text(sale.quantity);
                $("#view_unit_price").text(`PKR ${parseFloat(sale.unit_price).toFixed(2)}`);
                $("#view_total_price").text(`PKR ${parseFloat(sale.total_price).toFixed(2)}`);
                
                // Notes
                $("#view_notes").text(sale.notes || 'No notes available');
                
                $("#viewSaleModal").modal("show");
            } else {
                toastr.error(res.message || "Failed to load sale details.");
                console.error("View sale error:", res);
                $("#viewSaleModal").modal("hide"); // Hide modal if details fail to load
            }
        }).fail(function(xhr, status, error) {
             toastr.error("Error fetching sale details.");
             console.error("View sale AJAX error:", error);
             $("#viewSaleModal").modal("hide"); // Hide modal on AJAX error
        });
    });

    // Handle edit sale button click
    $(document).on('click', '.editSaleBtn', function() {
        const saleId = $(this).data('id');
        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                const sale = res.data;
                $("#edit_sale_id").val(sale.sale_id);
                $("#edit_customer_id").val(sale.customer_id);
                $("#edit_item_id").val(sale.item_id);
                $("#edit_quantity").val(sale.quantity);
                $("#edit_unit_price").val(sale.unit_price);
                $("#edit_sale_date").val(sale.sale_date);
                $("#edit_payment_status").val(sale.payment_status);
                $("#edit_notes").val(sale.notes);
                calculateTotals('edit_');
                $("#editSaleModal").modal("show");
            }
        });
    });

    // Handle delete sale button click
    $(document).on('click', '.deleteSaleBtn', function() {
        const saleId = $(this).data('id');
        $("#delete_sale_id").val(saleId);
        $("#deleteSaleModal").modal("show");
    });

    // Handle delete confirmation
    $("#confirmDeleteBtn").click(function() {
        const saleId = $("#delete_sale_id").val();
        $.post("../model/sale/deleteSale.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                // Properly remove modal and backdrop
                const modal = bootstrap.Modal.getInstance($("#deleteSaleModal"));
                modal.hide();
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                
                loadSales();
                toastr.success(res.message);
            } else {
                toastr.error(res.message);
            }
        }).fail(function(xhr, status, error) {
            toastr.error("Error deleting sale");
            console.error("Delete sale error:", error);
        });
    });

    // Handle generate invoice button click
    $("#generateInvoiceBtn").on("click", function() {
        const saleId = parseInt($("#view_sale_id").val()); // Ensure it's an integer
        if (isNaN(saleId) || saleId <= 0) {
            toastr.error("Cannot generate invoice: Invalid sale ID.");
            console.error("Generate invoice clicked with invalid sale ID:", $("#view_sale_id").val());
            return;
        }

        $("#generateInvoiceBtn").prop('disabled', true).text('Generating...'); // Indicate processing

        $.ajax({
            url: "../model/sale/generateInvoice.php",
            method: "POST",
            data: {
                sale_id: saleId,
                invoice_date: new Date().toISOString().split('T')[0],
                due_date: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0]
            },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    toastr.success(res.message);
                    // Update the download link if available
                    if (res.data && res.data.invoice_file) {
                        // Note: The download link is in the View Invoice Modal, not this one.
                        // The generateInvoice function is triggered from the View Invoice modal.
                        // Let's make sure the file path is correctly handled in the View modal after generation.

                        // We need to update the href of the download link in the view modal
                        // Let's find the relevant element in the view modal. It has id 'downloadInvoice'
                         $("#viewInvoiceModal #downloadInvoice").attr("href", "../" + res.data.invoice_file);
                         // It might also be useful to update the invoice preview in the view modal
                         // This would require fetching the updated sale details after generation, or updating the view modal content directly
                         // For now, let's just ensure the download link is updated.

                    }
                     // Optionally, reload sales table to show updated status/invoice file link if table shows it
                    // loadSales(); // This might be too disruptive, let's rely on the View modal's download link

                    // Close the generate modal
                    $("#generateInvoiceModal").modal("hide");

                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error generating invoice");
                console.error("Generate invoice error:", error);
            },
            complete: function() {
                 $("#generateInvoiceBtn").prop('disabled', false).text('Generate Invoice'); // Re-enable button
            }
        });
    });

    // Add modal open handler
    $("#addSaleModal").on("show.bs.modal", function() {
        // Reload customers and items when modal opens
        loadCustomers();
        loadItems();
        // Reset form
        $("#addSaleForm")[0].reset();
        // Set today's date
        $("#sale_date").val(new Date().toISOString().split('T')[0]);
        // Reset calculations
        calculateTotals();
    });

    // Initialize date inputs with today's date
    $("#sale_date, #edit_sale_date").val(new Date().toISOString().split('T')[0]);

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initial loads
    loadCustomers();
    loadItems();
    loadSales();

    // Function to update summary cards
    function updateSummary(salesData) {
        let totalAmount = 0;
        let totalPaid = 0;
        const uniqueCustomers = new Set();

        if (salesData && salesData.length > 0) {
            salesData.forEach(sale => {
                totalAmount += parseFloat(sale.total_price || 0);
                totalPaid += parseFloat(sale.paid_amount || 0);
                uniqueCustomers.add(sale.customer_id);
            });
        }

        $("#totalRecords").text(salesData ? salesData.length : 0);
        $("#totalAmount").text(`PKR ${totalAmount.toFixed(2)}`);
        $("#pendingPayments").text(`PKR ${(totalAmount - totalPaid).toFixed(2)}`);
        $("#uniqueCustomers").text(uniqueCustomers.size);
        $("#summaryCards").show();
    }

    // Add event listeners for modal hidden events
    $('#addSaleModal, #editSaleModal, #deleteSaleModal, #viewSaleModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300);
    });

    // Add payment
    $('#addPaymentForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/sales/addPayment.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                $('#addPaymentModal').modal('hide');
                $('#addPaymentForm')[0].reset();
                showMessage(response.status, response.message);
                loadSales();
                $('.modal-backdrop').remove();
                $('body').css({
                    'overflow': '',
                    'padding-right': ''
                });
            },
            error: function () {
                showMessage('danger', 'Error adding payment.');
            }
        });
    });

    // Add event listeners for payment modal hidden events
    $('#addPaymentModal, #viewPaymentModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300);
    });
});