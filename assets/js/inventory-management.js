$(document).ready(function () {
    fetchItems();
    fetchCategories();

    // Load categories into select dropdowns
    function fetchCategories() {
        $.ajax({
            url: '../model/inventory/fetchCategories.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    let options = '<option value="">Filter by Category</option>';
                    let formOptions = '<option value="">Select Category</option>';
                    response.data.forEach(category => {
                        options += `<option value="${category.category_name.toLowerCase()}">${category.category_name}</option>`;
                        formOptions += `<option value="${category.category_id}">${category.category_name}</option>`;
                    });
                    $('#filterCategory').html(options);
                    $('#category_id').html(formOptions);
                    $('#edit_category_id').html(formOptions);
                }
            }
        });
    }

    // Fetch inventory items
    function fetchItems() {
        $.ajax({
            url: '../model/inventory/fetchItemList.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const tbody = $('#inventoryTable tbody');
                tbody.empty();

                if (response.status === 'success') {
                    response.data.forEach(item => {
                        // Determine display price (customer price if set, otherwise admin price)
                        const displayPrice = item.customer_price ? item.customer_price : item.unit_price;
                        const priceLabel = item.customer_price ? 'Customer' : 'Admin';
                        
                        // Create status badges
                        const statusBadge = `<span class="badge bg-${item.status === 'active' ? 'success' : 'secondary'}">${item.status}</span>`;
                        const websiteBadge = item.show_on_website ? '<span class="badge bg-info me-1"><i class="fas fa-globe"></i> Website</span>' : '';
                        const featuredBadge = item.is_featured ? '<span class="badge bg-warning me-1"><i class="fas fa-star"></i> Featured</span>' : '';
                        
                        tbody.append(`
                            <tr 
                                data-category="${item.category_name.toLowerCase()}" 
                                data-status="${item.status.toLowerCase()}" 
                                data-price="${item.unit_price}" 
                                data-stock="${item.current_stock}"
                            >
                                <td>${item.item_id}</td>
                                <td>${item.item_number}</td>
                                <td>${item.item_name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.unit_of_measure}</td>
                                <td>PKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td>${item.customer_price ? `PKR ${parseFloat(item.customer_price).toFixed(2)}` : '<span class="text-muted">—</span>'}</td>
                                <td>${item.current_stock}</td>
                                <td>${item.minimum_stock}</td>
                                <td>
                                    ${statusBadge}
                                    ${websiteBadge}
                                    ${featuredBadge}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item editItem" href="#" 
                                                    data-id="${item.item_id}"
                                                    data-number="${item.item_number}"
                                                    data-name="${item.item_name}"
                                                    data-category="${item.category_id}"
                                                    data-unit="${item.unit_of_measure}"
                                                    data-price="${item.unit_price}"
                                                    data-customer-price="${item.customer_price || ''}"
                                                    data-min="${item.minimum_stock}"
                                                    data-desc="${item.description || ''}"
                                                    data-status="${item.status}"
                                                    data-show-website="${item.show_on_website}"
                                                    data-featured="${item.is_featured}"
                                                    data-seo-title="${item.seo_title || ''}"
                                                    data-seo-desc="${item.seo_description || ''}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item addStock" href="#" 
                                                    data-id="${item.item_id}" 
                                                    data-name="${item.item_name}">
                                                    <i class="fas fa-plus-circle me-2"></i> Add Stock
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item reduceStock" href="#" 
                                                    data-id="${item.item_id}" 
                                                    data-name="${item.item_name}"
                                                    data-current-stock="${item.current_stock}">
                                                    <i class="fas fa-minus-circle me-2"></i> Reduce Stock
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteItem" href="#" data-id="${item.item_id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.html(`<tr><td colspan="10" class="text-center">${response.message}</td></tr>`);
                }

                applyFilters();
            }
        });
    }

    // Apply filters
    function applyFilters() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const categoryFilter = $('#filterCategory').val().toLowerCase();
        const statusFilter = $('#filterStatus').val().toLowerCase();
        const websiteFilter = $('#filterWebsite').val();
        const featuredFilter = $('#filterFeatured').val();
        const minPrice = parseFloat($('#filterMinPrice').val()) || 0;
        const maxPrice = parseFloat($('#filterMaxPrice').val()) || Infinity;
        const minStock = parseFloat($('#filterMinStock').val()) || 0;

        let visibleCount = 0;

        $('#inventoryTable tbody tr').each(function() {
            const row = $(this);
            const itemName = row.find('td:eq(2)').text().toLowerCase();
            const itemNumber = row.find('td:eq(1)').text().toLowerCase();
            const category = row.data('category');
            const status = row.data('status');
            const price = row.data('price');
            const stock = row.data('stock');
            
            // Get customer panel control data
            const showWebsite = row.find('td:eq(9) .badge.bg-info').length > 0;
            const isFeatured = row.find('td:eq(9) .badge.bg-warning').length > 0;

            // Search filter
            const matchesSearch = !searchTerm || 
                itemName.includes(searchTerm) || 
                itemNumber.includes(searchTerm);
            
            // Category filter
            const matchesCategory = !categoryFilter || category === categoryFilter;
            
            // Status filter
            const matchesStatus = !statusFilter || status === statusFilter;
            
            // Website filter
            const matchesWebsite = websiteFilter === '' || 
                (websiteFilter === '1' && showWebsite) || 
                (websiteFilter === '0' && !showWebsite);
            
            // Featured filter
            const matchesFeatured = featuredFilter === '' || 
                (featuredFilter === '1' && isFeatured) || 
                (featuredFilter === '0' && !isFeatured);
            
            // Price filter
            const matchesPrice = price >= minPrice && price <= maxPrice;
            
            // Stock filter
            const matchesStock = stock >= minStock;

            const shouldShow = matchesSearch && matchesCategory && matchesStatus && 
                             matchesWebsite && matchesFeatured && matchesPrice && matchesStock;
            
            if (shouldShow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        // Show/hide empty state
        if (visibleCount === 0) {
            $('#emptyState').removeClass('d-none');
            $('#inventoryTable').addClass('d-none');
        } else {
            $('#emptyState').addClass('d-none');
            $('#inventoryTable').removeClass('d-none');
        }
    }

    $('#searchInput, #filterCategory, #filterStatus, #filterWebsite, #filterFeatured, #filterMinPrice, #filterMaxPrice, #filterMinStock').on('change keyup', applyFilters);

    // Reset filters
    $('#resetFilters').click(function() {
        $('#searchInput').val('');
        $('#filterCategory').val('');
        $('#filterStatus').val('');
        $('#filterWebsite').val('');
        $('#filterFeatured').val('');
        $('#filterMinPrice').val('');
        $('#filterMaxPrice').val('');
        $('#filterMinStock').val('');
        applyFilters();
    });

    // Add Item
    $('#addItemForm').submit(function (e) {
        e.preventDefault();
        
        // Handle checkbox values for form submission
        const formData = new FormData(this);
        formData.set('show_on_website', $('#show_on_website').is(':checked') ? '1' : '0');
        formData.set('is_featured', $('#is_featured').is(':checked') ? '1' : '0');
        
        $.ajax({
            url: '../model/inventory/insertItem.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addItemForm')[0].reset();
                    $('#addItemModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                    fetchItems();
                }
            }
        });
    });

    // Edit Item - Populate modal
    $(document).on('click', '.editItem', function () {
        $('#edit_item_id').val($(this).data('id'));
        $('#edit_item_number').val($(this).data('number'));
        $('#edit_item_name').val($(this).data('name'));
        $('#edit_category_id').val($(this).data('category'));
        $('#edit_unit_of_measure').val($(this).data('unit'));
        $('#edit_unit_price').val($(this).data('price'));
        $('#edit_customer_price').val($(this).data('customer-price'));
        $('#edit_minimum_stock').val($(this).data('min'));
        $('#edit_description').val($(this).data('desc'));
        $('#edit_status').val($(this).data('status'));
        
        // Handle checkboxes
        $('#edit_show_on_website').prop('checked', $(this).data('show-website') == 1);
        $('#edit_is_featured').prop('checked', $(this).data('featured') == 1);
        
        // Handle SEO fields
        $('#edit_seo_title').val($(this).data('seo-title'));
        $('#edit_seo_description').val($(this).data('seo-desc'));
        
        $('#editItemModal').modal('show');
    });

    // Update item
    $('#editItemForm').submit(function (e) {
        e.preventDefault();
        
        // Handle checkbox values for form submission
        const formData = new FormData(this);
        formData.set('show_on_website', $('#edit_show_on_website').is(':checked') ? '1' : '0');
        formData.set('is_featured', $('#edit_is_featured').is(':checked') ? '1' : '0');
        
        $.ajax({
            url: '../model/inventory/updateItemDetails.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editItemForm')[0].reset();
                    $('#editItemModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                    fetchItems();
                }
            }
        });
    });

    // Delete Item
    $(document).on('click', '.deleteItem', function () {
        const id = $(this).data('id');
        const name = $(this).closest('tr').find('td:eq(2)').text();
        $('#delete_item_id').val(id);
        $('#delete_item_name').text(name);
        $('#deleteConfirmModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDelete').on('click', function() {
        const id = $('#delete_item_id').val();
        $.ajax({
            url: '../model/inventory/deleteItem.php',
            method: 'POST',
            data: { item_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deleteConfirmModal').modal('hide');
                    fetchItems();
                }
            }
        });
    });

    // Add stock
    $(document).on('click', '.addStock', function () {
        const itemId = $(this).data('id');
        const itemName = $(this).data('name');

        console.log('Add Stock clicked:', { itemId, itemName });

        $('#stock_item_id').val(itemId);
        $('#stock_item_name').text(itemName);
        $('#stockQuantity').val('');
        $('#addStockModal').modal('show');
    });

    // Add stock form submission
    $('#addStockForm').submit(function (e) {
        e.preventDefault();
        console.log('Form submitted');

        const quantity = parseFloat($('#stockQuantity').val());
        const itemId = $('#stock_item_id').val();
        
        console.log('Form values:', { itemId, quantity });

        if (!itemId) {
            showMessage('Invalid item selected', 'error');
            return;
        }
        
        if (quantity <= 0) {
            showMessage('Quantity must be greater than 0', 'error');
            return;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

        // Create FormData object
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('quantity', quantity);

        $.ajax({
            url: '../model/inventory/addStockToItem.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                console.log('Server response:', response);
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    $('#addStockForm')[0].reset();
                    $('#addStockModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                    fetchItems();
                } else {
                    showMessage(response.message || 'Failed to add stock', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', {xhr, status, error});
                showMessage('Error connecting to server: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reduce stock
    $(document).on('click', '.reduceStock', function () {
        const itemId = $(this).data('id');
        const itemName = $(this).data('name');
        const currentStock = $(this).data('current-stock');

        $('#reduce_stock_item_id').val(itemId);
        $('#reduce_stock_item_name').text(itemName);
        $('#reduceStockQuantity').val('');
        $('#reduceStockQuantity').attr('max', currentStock);
        $('#current_stock_display').text(currentStock);
        $('#reason').val('');
        $('#otherReasonDiv').hide();
        $('#other_reason').val('');
        $('#reduceStockModal').modal('show');
    });

    // Handle reason dropdown change
    $('#reason').on('change', function() {
        if ($(this).val() === 'other') {
            $('#otherReasonDiv').show();
            $('#other_reason').prop('required', true);
        } else {
            $('#otherReasonDiv').hide();
            $('#other_reason').prop('required', false);
        }
    });

    // Validate reduce stock quantity
    $('#reduceStockQuantity').on('input', function() {
        const quantity = parseFloat($(this).val());
        const currentStock = parseFloat($(this).attr('max'));
        
        if (quantity > currentStock) {
            $(this).addClass('is-invalid');
            showMessage('Cannot reduce more than current stock', 'error');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    $('#reduceStockForm').submit(function (e) {
        e.preventDefault();
        const quantity = parseFloat($('#reduceStockQuantity').val());
        const currentStock = parseFloat($('#reduceStockQuantity').attr('max'));
        const reason = $('#reason').val();

        if (quantity > currentStock) {
            showMessage('Cannot reduce more than current stock', 'error');
            return;
        }

        if (reason === 'other' && !$('#other_reason').val().trim()) {
            showMessage('Please specify the other reason', 'error');
            return;
        }

        const formData = $(this).serialize();
        
        $.ajax({
            url: '../model/inventory/reduceStockFromItem.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#reduceStockForm')[0].reset();
                    $('#reduceStockModal').modal('hide');
                    fetchItems();
                }
            }
        });
    });

    
    function showMessage(msg, type = 'success') {
        // Use toastr for better looking notifications
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(msg);
        } else {
            toastr.error(msg);
        }
    }

    // Search
    $('#searchInput').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#inventoryTable tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Add event listeners for modal hidden events
    $('#addItemModal, #editItemModal, #addStockModal, #reduceStockModal, #deleteConfirmModal').on('hidden.bs.modal', function () {
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').css({
                'overflow': '',
                'padding-right': ''
            });
        }, 300);
    });
});
