$(document).ready(function () {
    fetchPurchases();
    loadVendors();

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

    // Badge color helpers
    function statusBadge(status) {
        return {
            paid: 'success',
            partial: 'warning',
            pending: 'secondary'
        }[status] || 'secondary';
    }

    function deliveryBadge(status) {
        return {
            delivered: 'success',
            in_transit: 'primary',
            pending: 'secondary',
            delayed: 'danger'
        }[status] || 'secondary';
    }

    // Load vendors into dropdown
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

    // Handle Add/Update form submission
    $('#purchaseForm').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
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
                    $('#purchaseForm')[0].reset();
                    $('#purchase_id').val('');
                    fetchPurchases();
                }
            },
            error: function () {
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Load purchase into modal for editing
    $(document).on('click', '.editPurchaseBtn', function () {
        const purchase_id = $(this).data('id');

        $.ajax({
            url: '../model/purchase/getPurchaseDetails.php',
            method: 'GET',
            data: { purchase_id },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    const p = res.data;
                    $('#purchase_id').val(p.purchase_id);
                    $('#purchase_number').val(p.purchase_number);
                    $('#vendor_id').val(p.vendor_id);
                    $('#purchase_date').val(p.purchase_date);
                    $('#expected_delivery').val(p.expected_delivery);
                    $('#total_amount').val(p.total_amount);
                    $('#payment_status').val(p.payment_status);
                    $('#delivery_status').val(p.delivery_status);
                    $('#notes').val(p.notes);
                    $('#addPurchaseModal').modal('show');
                } else {
                    showMessage(res.message, 'error');
                }
            },
            error: function () {
                showMessage('Failed to load purchase details.', 'error');
            }
        });
    });

    // Handle Delete
    $(document).on('click', '.deletePurchaseBtn', function () {
        const id = $(this).data('id');
        $('#delete_purchase_id').val(id);
        $('#deletePurchaseModal').modal('show');
    });

    $('#confirmDeletePurchase').click(function () {
        const id = $('#delete_purchase_id').val();

        $.ajax({
            url: '../model/purchase/deletePurchase.php',
            method: 'POST',
            data: { purchase_id: id },
            dataType: 'json',
            success: function (res) {
                showMessage(res.message, res.status);
                if (res.status === 'success') {
                    $('#deletePurchaseModal').modal('hide');
                    fetchPurchases();
                }
            },
            error: function () {
                showMessage('Failed to delete purchase.', 'error');
            }
        });
    });

    // Flash message function
    function showMessage(message, type = 'success') {
        const alertBox = $('#purchaseMessage');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';

        alertBox.removeClass('d-none alert-success alert-danger')
                .addClass(alertClass)
                .html(message);

        setTimeout(() => {
            alertBox.addClass('d-none').html('');
        }, 4000);
    }
});
