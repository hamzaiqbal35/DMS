$(document).ready(function () {
    fetchPurchases();
    loadVendors();
    loadItems(); // inventory item dropdown

    // Store selected purchase items
    let purchaseItems = [];

    // Fetch all purchase records
    function fetchPurchases() {
        $.ajax({
            url: '../model/purchase/fetchPurchaseList.php',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                const tbody = $('#purchaseTable tbody');
                tbody.empty();

                if (res.status === 'success') {
                    res.data.forEach(purchase => {
                        tbody.append(`
                            <tr>
                                <td>${purchase.purchase_number}</td>
                                <td>${purchase.vendor_name}</td>
                                <td>${purchase.purchase_date}</td>
                                <td>PKR ${parseFloat(purchase.total_amount).toFixed(2)}</td>
                                <td><span class="badge bg-${statusBadge(purchase.payment_status)}">${purchase.payment_status}</span></td>
                                <td><span class="badge bg-${deliveryBadge(purchase.delivery_status)}">${purchase.delivery_status}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning editPurchaseBtn" data-id="${purchase.purchase_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger deletePurchaseBtn" data-id="${purchase.purchase_id}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.html(`<tr><td colspan="7" class="text-center">${res.message}</td></tr>`);
                }
            },
            error: () => {
                $('#purchaseTable tbody').html(`<tr><td colspan="7" class="text-center text-danger">Failed to load purchases.</td></tr>`);
            }
        });
    }

    // Load vendors
    function loadVendors() {
        $.ajax({
            url: '../model/vendor/showVendorIDs.php',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                let options = '<option value="">Select Vendor</option>';
                if (res.status === 'success') {
                    res.data.forEach(v => {
                        options += `<option value="${v.vendor_id}">${v.vendor_name}</option>`;
                    });
                }
                $('#vendor_id').html(options);
            }
        });
    }

    // Load inventory items for dropdown
    function loadItems() {
        $.ajax({
            url: '../model/inventory/showItemNames.php',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                let options = '<option value="">Select Item</option>';
                if (res.status === 'success') {
                    res.data.forEach(i => {
                        options += `<option value="${i.item_id}" data-name="${i.item_name}">${i.item_name}</option>`;
                    });
                }
                $('#item_id').html(options);
            }
        });
    }

    // Add item to purchaseItems list
    $('#addItemToPurchase').click(function () {
        const item_id = $('#item_id').val();
        const item_name = $('#item_id option:selected').text();
        const quantity = parseFloat($('#item_quantity').val()) || 0;
        const unit_price = parseFloat($('#item_price').val()) || 0;
        const discount = parseFloat($('#item_discount').val()) || 0;
        const tax = parseFloat($('#item_tax').val()) || 0;

        if (!item_id || quantity <= 0 || unit_price <= 0) {
            alert("Please fill valid item, quantity, and unit price.");
            return;
        }

        const total_price = (quantity * unit_price) - discount + tax;

        purchaseItems.push({
            item_id,
            quantity,
            unit_price,
            discount,
            tax,
            total_price
        });

        renderItems();
        $('#item_id, #item_quantity, #item_price, #item_discount, #item_tax').val('');
    });

    function renderItems() {
        const container = $('#purchaseItemsContainer');
        container.empty();
        if (purchaseItems.length === 0) {
            container.html('<tr><td colspan="6" class="text-center">No items added.</td></tr>');
            return;
        }

        purchaseItems.forEach((item, index) => {
            container.append(`
                <tr>
                    <td>${$('#item_id option[value="' + item.item_id + '"]').text()}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit_price}</td>
                    <td>${item.discount}</td>
                    <td>${item.tax}</td>
                    <td>${item.total_price.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger removeItemBtn" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Remove item
    $(document).on('click', '.removeItemBtn', function () {
        const index = $(this).data('index');
        purchaseItems.splice(index, 1);
        renderItems();
    });

    // Handle form submit
    $('#purchaseForm').on('submit', function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        if (purchaseItems.length === 0) {
            showMessage('Add at least one item.', 'error');
            return;
        }

        purchaseItems.forEach((item, i) => {
            for (const key in item) {
                formData.append(`items[${i}][${key}]`, item[key]);
            }
        });

        const url = $('#purchase_id').val()
            ? '../model/purchase/updatePurchase.php'
            : '../model/purchase/insertPurchase.php';

        $.ajax({
            url,
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                showMessage(res.message, res.status);
                if (res.status === 'success') {
                    $('#addPurchaseModal').modal('hide');
                    form.reset();
                    purchaseItems = [];
                    renderItems();
                    $('#purchase_id').val('');
                    fetchPurchases();
                }
            },
            error: function () {
                showMessage('An error occurred while saving.', 'error');
            }
        });
    });

    // Flash message
    function showMessage(message, type = 'success') {
        const box = $('#purchaseMessage');
        const alert = type === 'success' ? 'alert-success' : 'alert-danger';

        box.removeClass('d-none alert-success alert-danger')
            .addClass(alert)
            .html(message);

        setTimeout(() => {
            box.addClass('d-none').html('');
        }, 4000);
    }

    // Badge helpers
    function statusBadge(status) {
        return { paid: 'success', partial: 'warning', pending: 'secondary' }[status] || 'secondary';
    }

    function deliveryBadge(status) {
        return {
            delivered: 'success',
            in_transit: 'primary',
            pending: 'secondary',
            delayed: 'danger'
        }[status] || 'secondary';
    }
});
