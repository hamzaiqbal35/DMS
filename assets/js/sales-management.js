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
        const taxRate = parseFloat($(`#${formPrefix}tax_rate`).val()) || 0;
        const discountRate = parseFloat($(`#${formPrefix}discount_rate`).val()) || 0;

        const subtotal = quantity * unitPrice;
        const taxAmount = (subtotal * taxRate) / 100;
        const discountAmount = (subtotal * discountRate) / 100;
        const total = subtotal + taxAmount - discountAmount;

        $(`#${formPrefix}preview_subtotal`).text(`PKR ${subtotal.toFixed(2)}`);
        $(`#${formPrefix}preview_tax`).text(`PKR ${taxAmount.toFixed(2)}`);
        $(`#${formPrefix}preview_discount`).text(`PKR ${discountAmount.toFixed(2)}`);
        $(`#${formPrefix}preview_total`).text(`PKR ${total.toFixed(2)}`);

        return { subtotal, taxAmount, discountAmount, total };
    }

    // Load sales data
    function loadSales() {
        $.get("../model/sale/fetchSaleList.php", function (res) {
            if (res.status === "success") {
                originalSalesData = res.data;
                renderSalesTable(res.data);
                $("#totalSalesCount span").text(res.data.length);
                $("#totalSalesCount").show();
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
            tbody.append(`
                <tr data-customer-name="${sale.customer_name}">
                    <td>${sale.invoice_number}</td>
                    <td>${sale.customer_name}</td>
                    <td>${sale.item_name}</td>
                    <td>${sale.quantity}</td>
                    <td>PKR ${parseFloat(sale.unit_price).toFixed(2)}</td>
                    <td>PKR ${parseFloat(sale.total_price).toFixed(2)}</td>
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

    // Search and filter functionality
    function applyFilters() {
        const searchText = $("#searchInput").val().toLowerCase().trim();
        const customerFilter = $("#filterCustomer").val().trim();
        const statusFilter = $("#filterPaymentStatus").val().trim();

        let visibleCount = 0;

        $("#salesTable tbody tr").each(function() {
            const row = $(this);
            const invoiceNumber = row.find('td:eq(0)').text().toLowerCase();
            const customerName = row.find('td:eq(1)').text().toLowerCase();
            const itemName = row.find('td:eq(2)').text().toLowerCase();
            const paymentStatus = row.find('td:eq(7) .badge').text().toLowerCase();
            
            // Get the actual customer name from data attribute
            const rowCustomerName = row.data('customer-name') || row.find('td:eq(1)').text();

            // Search filter
            const matchesSearch = !searchText || 
                                invoiceNumber.includes(searchText) || 
                                customerName.includes(searchText) || 
                                itemName.includes(searchText);
            
            // Customer filter - exact match with customer name
            const matchesCustomer = !customerFilter || rowCustomerName === customerFilter;
            
            // Status filter
            const matchesStatus = !statusFilter || paymentStatus === statusFilter.toLowerCase();

            const shouldShow = matchesSearch && matchesCustomer && matchesStatus;
            
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
    $("#filterCustomer, #filterPaymentStatus").on('change', applyFilters);

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
    $(document).on('input', '#quantity, #unit_price, #tax_rate, #discount_rate, #edit_quantity, #edit_unit_price, #edit_tax_rate, #edit_discount_rate', function() {
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
        $.get("../model/sale/getSaleDetails.php", { sale_id: saleId }, function(res) {
            if (res.status === "success") {
                const sale = res.data;
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
            }
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
    $("#generateInvoiceBtn").click(function() {
        const saleId = $("#view_sale_id").val();
        window.open(`../model/sale/generateInvoice.php?sale_id=${saleId}`, '_blank');
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
});