$(document).ready(function () {
    // Store original data for filtering
    let originalPurchaseData = [];
    
    // Load all vendors and materials into dropdowns
    function loadDropdowns() {
        $.get("../model/vendor/getVendors.php", function (res) {
            if (res.status === "success") {
                let options = '<option value="">Select Vendor</option>';
                let filterOptions = '<option value="">Filter by Vendor</option>';
                res.data.forEach(v => {
                    options += `<option value="${v.vendor_id}">${v.vendor_name}</option>`;
                    filterOptions += `<option value="${v.vendor_name}">${v.vendor_name}</option>`;
                });
                $("#vendor_id, #edit_vendor_id").html(options);
                $("#filterVendor").html(filterOptions);
            }
        });

        $.get("../model/purchase/fetchVendorItems.php", function (res) {
            if (res.status === "success") {
                let options = '<option value="">Select Material</option>';
                let filterOptions = '<option value="">Filter by Material</option>';
                res.data.forEach(m => {
                    options += `<option value="${m.material_id}">${m.material_name}</option>`;
                    filterOptions += `<option value="${m.material_name}">${m.material_name}</option>`;
                });
                $("#material_id, #edit_material_id").html(options);
                $("#filterMaterial").html(filterOptions);
            }
        });
    }

    function loadPurchases() {
        $.get("../model/purchase/fetchPurchaseList.php", function (res) {
            let rows = "";
            const tableBody = $("#purchaseTable tbody");
            const emptyState = $("#emptyState");
            
            if (res.status === "success" && res.data && res.data.length > 0) {
                // Store original data for filtering
                originalPurchaseData = res.data;
                
                res.data.forEach(row => {
                    rows += `
                        <tr data-vendor-name="${row.vendor_name}" data-material-name="${row.material_name}" data-vendor-id="${row.vendor_id}" data-material-id="${row.material_id}">
                            <td>${row.purchase_number}</td>
                            <td>${row.vendor_name}</td>
                            <td>${row.material_name}</td>
                            <td>${row.quantity}</td>
                            <td>PKR ${parseFloat(row.unit_price).toFixed(2)}</td>
                            <td>PKR ${parseFloat(row.total_price).toFixed(2)}</td>
                            <td>${row.purchase_date}</td>
                            <td><span class="badge bg-${getStatusColor(row.delivery_status)}">${row.delivery_status}</span></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item editBtn" href="#" data-id="${row.purchase_id}" data-vendor-id="${row.vendor_id}">
                                                <i class="fas fa-edit me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger deleteBtn" href="#" data-id="${row.purchase_id}">
                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tableBody.html(rows);
                emptyState.addClass('d-none');
            } else {
                originalPurchaseData = [];
                tableBody.html('');
                emptyState.removeClass('d-none');
            }
        }).fail(function(xhr, status, error) {
            console.error("Error loading purchases:", error);
            originalPurchaseData = [];
            $("#purchaseTable tbody").html('<tr><td colspan="9" class="text-center text-danger">Error loading purchases. Please try again.</td></tr>');
            $("#emptyState").addClass('d-none');
        });
    }

    // Helper function to get status color
    function getStatusColor(status) {
        switch(status.toLowerCase()) {
            case 'pending':
                return 'warning';
            case 'in_transit':
                return 'info';
            case 'delivered':
                return 'success';
            case 'delayed':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Search and Filter functionality
    function applyFilters() {
        const searchText = $("#searchInput").val().toLowerCase().trim();
        const vendorFilter = $("#filterVendor").val().trim();
        const materialFilter = $("#filterMaterial").val().trim();

        let visibleCount = 0;

        $("#purchaseTable tbody tr").each(function() {
            const row = $(this);
            const purchaseNumber = row.find('td:eq(0)').text().toLowerCase();
            const vendorName = row.find('td:eq(1)').text().toLowerCase();
            const materialName = row.find('td:eq(2)').text().toLowerCase();
            
            // Get the actual vendor and material names from data attributes
            const rowVendorName = row.data('vendor-name') || row.find('td:eq(1)').text();
            const rowMaterialName = row.data('material-name') || row.find('td:eq(2)').text();

            // Search filter - check if search text is in purchase number, vendor name, or material name
            const matchesSearch = !searchText || 
                                purchaseNumber.includes(searchText) || 
                                vendorName.includes(searchText) || 
                                materialName.includes(searchText);
            
            // Vendor filter - exact match with vendor name
            const matchesVendor = !vendorFilter || rowVendorName === vendorFilter;
            
            // Material filter - exact match with material name
            const matchesMaterial = !materialFilter || rowMaterialName === materialFilter;

            const shouldShow = matchesSearch && matchesVendor && matchesMaterial;
            
            if (shouldShow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        // Show/hide empty state based on visible rows
        if (visibleCount === 0 && originalPurchaseData.length > 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Matching Purchases Found');
            $("#emptyState p").text('Try adjusting your search or filter criteria.');
        } else if (originalPurchaseData.length === 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Purchases Found');
            $("#emptyState p").text('Start by adding a purchase or try searching differently.');
        } else {
            $("#emptyState").addClass('d-none');
        }
    }

    // Handle search input with debounce
    let searchTimeout;
    $("#searchInput").on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });

    // Handle filter changes
    $("#filterVendor, #filterMaterial").on('change', function() {
        applyFilters();
    });

    // Handle reset filters
    $("#resetFilters").click(function () {
        $("#searchInput").val("");
        $("#filterVendor").val("");
        $("#filterMaterial").val("");
        applyFilters();
    });

    // Add Purchase
    $("#addPurchaseForm").submit(function (e) {
        e.preventDefault();
        
        // Validate form
        const quantity = parseFloat($("#quantity").val());
        const unitPrice = parseFloat($("#unit_price").val());
        
        if (quantity <= 0 || unitPrice <= 0) {
            showMessage("Quantity and unit price must be greater than 0", "error");
            return;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: "../model/purchase/insertPurchase.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    $("#addPurchaseModal").modal("hide");
                    $("#addPurchaseForm")[0].reset();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    loadPurchases();
                    showMessage(res.message, "success");
                } else {
                    showMessage(res.message || "Failed to add purchase", "error");
                }
            },
            error: function (xhr, status, error) {
                console.error("Error:", error);
                showMessage("Failed to add purchase. Please try again.", "error");
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Load purchase data into edit modal
    $(document).on("click", ".editBtn", function () {
        const id = $(this).data("id");
        $.get("../model/purchase/getPurchaseDetails.php", { purchase_id: id }, function (res) {
            if (res.status === "success") {
                const d = res.data;
                $("#edit_purchase_id").val(d.purchase_id);
                $("#edit_vendor_id").val(d.vendor_id);
                $("#edit_material_id").val(d.material_id);
                $("#edit_quantity").val(d.quantity);
                $("#edit_unit_price").val(d.unit_price);
                $("#edit_purchase_date").val(d.purchase_date);
                $("#edit_payment_status").val(d.payment_status);
                $("#edit_status").val(d.status);
                $("#edit_notes").val(d.notes);
                $("#editPurchaseModal").modal("show");
            } else {
                showMessage("Failed to load purchase data.", "error");
            }
        });
    });

    // Update purchase
    $("#editPurchaseForm").submit(function (e) {
        e.preventDefault();
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: "../model/purchase/updatePurchase.php",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    $("#editPurchaseModal").modal("hide");
                    $("#editPurchaseForm")[0].reset();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    loadPurchases();
                    showMessage(res.message, "success");
                } else {
                    showMessage(res.message, "error");
                }
            },
            error: function () {
                showMessage("Failed to update purchase.", "error");
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Prepare delete
    $(document).on("click", ".deleteBtn", function () {
        const id = $(this).data("id");
        $("#delete_purchase_id").val(id);
        $("#deletePurchaseModal").modal("show");
    });

    // Confirm delete
    $("#confirmDeleteBtn").click(function () {
        const id = $("#delete_purchase_id").val();
        
        // Show loading state
        const deleteBtn = $(this);
        const originalText = deleteBtn.html();
        deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        
        $.ajax({
            url: "../model/purchase/deletePurchase.php",
            method: "POST",
            data: { purchase_id: id },
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    $("#deletePurchaseModal").modal("hide");
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    loadPurchases();
                    showMessage(res.message, "success");
                } else {
                    showMessage(res.message, "error");
                }
            },
            error: function () {
                showMessage("Failed to delete purchase.", "error");
            },
            complete: function() {
                deleteBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Show flash message
    function showMessage(message, type = 'success') {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    // Initial loads
    loadDropdowns();
    loadPurchases();
});