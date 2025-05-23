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
                                <td>${item.current_stock}</td>
                                <td>${item.minimum_stock}</td>
                                <td><span class="badge bg-${item.status === 'active' ? 'success' : 'secondary'}">${item.status}</span></td>
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
                                                    data-min="${item.minimum_stock}"
                                                    data-desc="${item.description || ''}"
                                                    data-status="${item.status}">
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
        const category = $('#filterCategory').val();
        const status = $('#filterStatus').val();
        const minPrice = parseFloat($('#filterMinPrice').val()) || 0;
        const maxPrice = parseFloat($('#filterMaxPrice').val()) || Infinity;
        const minStock = parseFloat($('#filterMinStock').val()) || 0;

        $('#inventoryTable tbody tr').each(function () {
            const row = $(this);
            const rowCategory = row.data('category');
            const rowStatus = row.data('status');
            const rowPrice = parseFloat(row.data('price'));
            const rowStock = parseFloat(row.data('stock'));

            const matchCategory = !category || rowCategory === category;
            const matchStatus = !status || rowStatus === status;
            const matchPrice = rowPrice >= minPrice && rowPrice <= maxPrice;
            const matchStock = rowStock >= minStock;

            row.toggle(matchCategory && matchStatus && matchPrice && matchStock);
        });
    }

    $('#filterCategory, #filterStatus, #filterMinPrice, #filterMaxPrice, #filterMinStock').on('change keyup', applyFilters);

    $('#resetFilters').on('click', function () {
        $('#filterCategory, #filterStatus, #filterMinPrice, #filterMaxPrice, #filterMinStock').val('');
        $('#inventoryTable tbody tr').show();
    });

    // Add Item
    $('#addItemForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/inventory/insertItem.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addItemForm')[0].reset();
                    $('#addItemModal').modal('hide');
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
        $('#edit_minimum_stock').val($(this).data('min'));
        $('#edit_description').val($(this).data('desc'));
        $('#edit_status').val($(this).data('status')); // NEW
        $('#editItemModal').modal('show');
    });

    // Update item
    $('#editItemForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/inventory/updateItemDetails.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editItemForm')[0].reset();
                    $('#editItemModal').modal('hide');
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

        $('#stock_item_id').val(itemId);
        $('#stock_item_name').text(itemName);
        $('#stockQuantity').val('');
        $('#addStockModal').modal('show');
    });

    $('#addStockForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/inventory/addStockToItem.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addStockForm')[0].reset();
                    $('#addStockModal').modal('hide');
                    fetchItems();
                }
            }
        });
    });

    // Flash message
    function showMessage(message, type = 'success') {
        const msgBox = $('#inventoryMessage');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        msgBox.html(`<div class="alert ${alertClass}">${message}</div>`);
        setTimeout(() => msgBox.html(''), 4000);
    }

    // Search
    $('#searchInput').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#inventoryTable tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
