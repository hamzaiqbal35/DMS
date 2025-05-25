$(document).ready(function () {
    // Initialize variables
    let currentPurchaseId = null;
    let materials = [];

    // Load initial data
    loadInitialData();

    // Function to load initial data
    function loadInitialData() {
        $.ajax({
            url: '../model/inventory/insertInitialData.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('Initial data loaded successfully');
                }
                // Continue with loading vendors and materials regardless of initial data status
                loadVendors();
                loadMaterials();
            },
            error: function() {
                // Continue with loading vendors and materials even if initial data fails
                loadVendors();
                loadMaterials();
            }
        });
    }

    // Load vendors for dropdowns
    function loadVendors() {
        $.ajax({
            url: '../model/vendor/getVendors.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && Array.isArray(response.data)) {
                    const vendorOptions = '<option value="">Select Vendor</option>';
                    response.data.forEach(vendor => {
                        vendorOptions += `<option value="${vendor.vendor_id}">${vendor.vendor_name}</option>`;
                    });
                    $('#vendor_id, #edit_vendor_id, #filterVendor').html(vendorOptions);
                } else {
                    console.error('Invalid vendor data format:', response);
                    showMessage('Failed to load vendors: Invalid data format', 'error');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error loading vendors';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage += ': ' + (response.message || error);
                } catch (e) {
                    errorMessage += ': ' + error;
                }
                console.error('Vendor loading error:', errorMessage);
                showMessage(errorMessage, 'error');
            }
        });
    }

    // Load materials for dropdowns
    function loadMaterials() {
        $.ajax({
            url: '../model/inventory/getItems.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    const materialOptions = '<option value="">Select Material</option>';
                    if (Array.isArray(response.data)) {
                        response.data.forEach(material => {
                            materialOptions += `<option value="${material.material_id}" 
                                data-code="${material.material_code}"
                                data-unit="${material.unit}">
                                ${material.material_name} (${material.material_code})
                            </option>`;
                        });
                        $('#material_id, #edit_material_id, #filterMaterial').html(materialOptions);
                    } else {
                        console.error('Invalid materials data format:', response);
                        showMessage('Failed to load materials: Invalid data format', 'error');
                    }
                } else {
                    console.error('Error loading materials:', response.message);
                    showMessage('Failed to load materials: ' + (response.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error loading materials';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage += ': ' + (response.message || error);
                } catch (e) {
                    errorMessage += ': ' + error;
                }
                console.error('Material loading error:', errorMessage);
                showMessage(errorMessage, 'error');
            }
        });
    }

    // Fetch all purchases
    function fetchPurchases() {
        $.ajax({
            url: '../model/purchase/getPurchases.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const tbody = $('#purchaseTable tbody');
                tbody.empty();

                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(purchase => {
                        const totalAmount = parseFloat(purchase.total_amount).toFixed(2);
                        const purchaseDate = new Date(purchase.purchase_date).toLocaleDateString();
                        const expectedDelivery = new Date(purchase.expected_delivery).toLocaleDateString();

                        tbody.append(`
                            <tr>
                                <td>${purchase.purchase_id}</td>
                                <td>${purchase.vendor_name}</td>
                                <td>${purchase.purchase_number}</td>
                                <td>${purchaseDate}</td>
                                <td>${expectedDelivery}</td>
                                <td>${totalAmount}</td>
                                <td>
                                    <span class="badge bg-${getStatusBadgeClass(purchase.payment_status)}">
                                        ${purchase.payment_status}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-${getDeliveryStatusBadgeClass(purchase.delivery_status)}">
                                        ${purchase.delivery_status}
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item viewPurchase" href="#" data-id="${purchase.purchase_id}">
                                                    <i class="fas fa-eye me-2"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item editPurchase" href="#" data-id="${purchase.purchase_id}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deletePurchase" href="#" data-id="${purchase.purchase_id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                    $('#emptyState').addClass('d-none');
                } else {
                    $('#emptyState').removeClass('d-none');
                }
            }
        });
    }

    // Helper function for payment status badge class
    function getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'paid':
                return 'success';
            case 'partial':
                return 'warning';
            case 'pending':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Helper function for delivery status badge class
    function getDeliveryStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'delivered':
                return 'success';
            case 'partial':
                return 'warning';
            case 'pending':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Add Purchase
    $('#addPurchaseForm').on('submit', function (e) {
        e.preventDefault();
        
        const materialId = $('#material_id').val();
        if (!materialId) {
            showMessage('Please select a material', 'error');
            return;
        }

        // Validate material before proceeding
        $.ajax({
            url: '../model/inventory/validateMaterial.php',
            method: 'GET',
            data: { material_id: materialId },
            dataType: 'json',
            success: function(response) {
                if (response.valid) {
                    submitPurchaseForm();
                } else {
                    showMessage(response.message || 'Invalid material selected', 'error');
                    $('#materialError').removeClass('d-none').text(response.message);
                }
            },
            error: function() {
                showMessage('Error validating material', 'error');
            }
        });
    });

    function submitPurchaseForm() {
        const formData = {
            purchase_number: generatePurchaseNumber(),
            vendor_id: $('#vendor_id').val(),
            purchase_date: $('#purchase_date').val(),
            expected_delivery: $('#expected_delivery').val(),
            notes: $('#notes').val(),
            created_by: getCurrentUserId(),
            materials: JSON.stringify(materials)
        };

        $.ajax({
            url: '../model/purchase/insertPurchase.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addPurchaseModal').modal('hide');
                    $('#addPurchaseForm')[0].reset();
                    materials = [];
                    fetchPurchases();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }
            }
        });
    }

    // Generate unique purchase number
    function generatePurchaseNumber() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `PO${year}${month}${random}`;
    }

    // Get current user ID (implement based on your authentication system)
    function getCurrentUserId() {
        // This should be implemented based on your authentication system
        return 1; // Placeholder
    }

    // View Purchase Details
    $(document).on('click', '.viewPurchase', function () {
        const purchaseId = $(this).data('id');
        $.ajax({
            url: '../model/purchase/getPurchaseDetails.php',
            method: 'POST',
            data: { purchase_id: purchaseId },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // Populate and show view modal
                    populateViewModal(response.purchase, response.items);
                    $('#viewPurchaseModal').modal('show');
                }
            }
        });
    });

    // Edit Purchase
    $(document).on('click', '.editPurchase', function () {
        const purchaseId = $(this).data('id');
        currentPurchaseId = purchaseId;

        $.ajax({
            url: '../model/purchase/getPurchaseDetails.php',
            method: 'POST',
            data: { purchase_id: purchaseId },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    populateEditForm(response.purchase, response.items);
                    $('#editPurchaseModal').modal('show');
                }
            }
        });
    });

    // Update Purchase
    $('#editPurchaseForm').on('submit', function (e) {
        e.preventDefault();
        
        const formData = {
            purchase_id: currentPurchaseId,
            vendor_id: $('#edit_vendor_id').val(),
            purchase_date: $('#edit_purchase_date').val(),
            expected_delivery: $('#edit_expected_delivery').val(),
            notes: $('#edit_notes').val(),
            items: materials
        };

        $.ajax({
            url: '../model/purchase/updatePurchase.php',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editPurchaseModal').modal('hide');
                    $('#editPurchaseForm')[0].reset();
                    materials = [];
                    fetchPurchases();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }
            }
        });
    });

    // Delete Purchase
    $(document).on('click', '.deletePurchase', function () {
        const purchaseId = $(this).data('id');
        $('#delete_purchase_id').val(purchaseId);
        $('#deletePurchaseModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const purchaseId = $('#delete_purchase_id').val();
        $.ajax({
            url: '../model/purchase/deletePurchase.php',
            method: 'POST',
            data: JSON.stringify({ purchase_id: purchaseId }),
            contentType: 'application/json',
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deletePurchaseModal').modal('hide');
                    fetchPurchases();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }
            }
        });
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPurchases(searchTerm);
    });

    // Filter by vendor
    $('#filterVendor').on('change', function() {
        applyFilters();
    });

    // Filter by material
    $('#filterMaterial').on('change', function() {
        applyFilters();
    });

    // Filter by date range
    $('#filterStartDate, #filterEndDate').on('change', function() {
        applyFilters();
    });

    // Filter by minimum amount
    $('#filterMinAmount').on('keyup', function() {
        applyFilters();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#searchInput').val('');
        $('#filterVendor').val('');
        $('#filterMaterial').val('');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#filterMinAmount').val('');
        $('#purchaseTable tbody tr').show();
    });

    // Function to filter purchases
    function filterPurchases(searchTerm) {
        const rows = $('#purchaseTable tbody tr');
        
        if (searchTerm === '') {
            rows.show();
            if (rows.length === 0 || (rows.length === 1 && rows.find('td').attr('colspan'))) {
                $('#emptyState').removeClass('d-none');
            } else {
                $('#emptyState').addClass('d-none');
            }
            return;
        }
        
        let matchFound = false;
        
        rows.each(function() {
            const row = $(this);
            const vendorName = row.find('td:eq(1)').text().toLowerCase();
            const purchaseNumber = row.find('td:eq(2)').text().toLowerCase();
            
            if (vendorName.includes(searchTerm) || purchaseNumber.includes(searchTerm)) {
                row.show();
                matchFound = true;
            } else {
                row.hide();
            }
        });
        
        if (!matchFound) {
            if (rows.filter(':visible').length === 0) {
                $('#emptyState').removeClass('d-none');
            }
        } else {
            $('#emptyState').addClass('d-none');
        }
    }

    // Function to apply all filters
    function applyFilters() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const vendorFilter = $('#filterVendor').val();
        const materialFilter = $('#filterMaterial').val();
        const startDate = $('#filterStartDate').val();
        const endDate = $('#filterEndDate').val();
        const minAmount = parseFloat($('#filterMinAmount').val()) || 0;

        const rows = $('#purchaseTable tbody tr');
        let visibleCount = 0;

        rows.each(function() {
            const row = $(this);
            const vendorName = row.find('td:eq(1)').text().toLowerCase();
            const purchaseNumber = row.find('td:eq(2)').text().toLowerCase();
            const totalAmount = parseFloat(row.find('td:eq(5)').text());
            const purchaseDate = row.find('td:eq(3)').text();

            const matchesSearch = !searchTerm || 
                                vendorName.includes(searchTerm) || 
                                purchaseNumber.includes(searchTerm);
            
            const matchesVendor = !vendorFilter || 
                                row.find('td:eq(1)').text() === $('#filterVendor option:selected').text();
            
            const matchesDateRange = (!startDate || purchaseDate >= startDate) && 
                                   (!endDate || purchaseDate <= endDate);
            
            const matchesAmount = totalAmount >= minAmount;

            if (matchesSearch && matchesVendor && matchesDateRange && matchesAmount) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        if (visibleCount === 0) {
            $('#emptyState').removeClass('d-none');
        } else {
            $('#emptyState').addClass('d-none');
        }
    }

    // Helper function to populate view modal
    function populateViewModal(purchase, items) {
        $('#view_purchase_number').text(purchase.purchase_number);
        $('#view_vendor_name').text(purchase.vendor_name);
        $('#view_purchase_date').text(new Date(purchase.purchase_date).toLocaleDateString());
        $('#view_expected_delivery').text(new Date(purchase.expected_delivery).toLocaleDateString());
        $('#view_total_amount').text(parseFloat(purchase.total_amount).toFixed(2));
        $('#view_notes').text(purchase.notes || 'No notes available');
        $('#view_created_by').text(purchase.created_by);
        $('#view_created_at').text(new Date(purchase.created_at).toLocaleString());

        // Populate items table
        const tbody = $('#view_items_table tbody');
        tbody.empty();
        items.forEach(item => {
            tbody.append(`
                <tr>
                    <td>${item.material_name}</td>
                    <td>${item.quantity}</td>
                    <td>${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>${parseFloat(item.total_price).toFixed(2)}</td>
                </tr>
            `);
        });
    }

    // Helper function to populate edit form
    function populateEditForm(purchase, items) {
        $('#edit_vendor_id').val(purchase.vendor_id);
        $('#edit_purchase_date').val(purchase.purchase_date);
        $('#edit_expected_delivery').val(purchase.expected_delivery);
        $('#edit_notes').val(purchase.notes);
        
        // Store items for update
        materials = items;
    }

    // Flash message function
    function showMessage(message, type = 'success') {
        const alertBox = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#purchaseMessage').html(alertBox);
        setTimeout(() => {
            $('#purchaseMessage .alert').alert('close');
        }, 4000);
    }
});
