// customer-management.js

$(document).ready(function () {
    fetchCustomers();

    // 🔹 Search Functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterCustomers(searchTerm);
    });

    // 🔹 Add Customer
    $('#addCustomerForm').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: '../model/customer/insertCustomer.php',
            data: formData,
            success: function (response) {
                $('#addCustomerModal').modal('hide');
                $('#addCustomerForm')[0].reset();
                showMessage('Customer added successfully!', 'success');
                fetchCustomers();
            },
            error: function () {
                showMessage('Failed to add customer.', 'danger');
            }
        });
    });

    // Set values in Edit Modal
    $(document).on('click', '.editCustomer', function () {
        $('#edit_customer_id').val($(this).data('id'));
        $('#edit_customer_name').val($(this).data('name'));
        $('#edit_contact_person').val($(this).data('contact'));
        $('#edit_phone').val($(this).data('phone'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_address').val($(this).data('address'));
        $('#edit_city').val($(this).data('city'));
        $('#edit_state').val($(this).data('state'));
        $('#edit_zip_code').val($(this).data('zip'));
        $('#editCustomerModal').modal('show');
    });


   // Update Customer
    $('#editCustomerForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '../model/customer/updateCustomerDetails.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editCustomerModal').modal('hide');
                    $('#editCustomerForm')[0].reset();
                    fetchCustomers(); // Make sure this function exists and repopulates table
                }
            }
        });
    });


    // 🔹 Delete Customer
    $(document).on('click', '.deleteCustomer', function () {
        const id = $(this).data('id');
        $('#delete_customer_id').val(id);
        $('#deleteCustomerModal').modal('show');
    });
    
    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#delete_customer_id').val();
        $.ajax({
            url: '../model/customer/deleteCustomer.php',
            method: 'POST',
            data: { customer_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deleteCustomerModal').modal('hide');
                    fetchCustomers();
                }
            },
            error: function () {
                showMessage('Failed to delete customer.', 'danger');
            }
        });
    });
});

// 🔹 Function to Load All Customers
function fetchCustomers() {
    $.ajax({
        url: '../model/customer/populateCustomerDetails.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            const tbody = $('#customerTable tbody');
            tbody.empty();

            if (response.status === 'success') {
                if (response.data.length === 0) {
                    $('#emptyState').removeClass('d-none');
                    tbody.append(`<tr><td colspan="10" class="text-center">No customers found</td></tr>`);
                } else {
                    $('#emptyState').addClass('d-none');
                    
                    $.each(response.data, function (i, customer) {
                        tbody.append(`
                            <tr>
                                <td>${customer.customer_id}</td>
                                <td>${customer.customer_name}</td>
                                <td>${customer.contact_person || ''}</td>
                                <td>${customer.phone}</td>
                                <td>${customer.email || ''}</td>
                                <td>${customer.address}</td>
                                <td>${customer.city}</td>
                                <td>${customer.state || ''}</td>
                                <td>${customer.zip_code || ''}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item editCustomer" href="#" 
                                                    data-id="${customer.customer_id}"
                                                    data-name="${customer.customer_name}"
                                                    data-contact="${customer.contact_person || ''}"
                                                    data-phone="${customer.phone}"
                                                    data-email="${customer.email || ''}"
                                                    data-address="${customer.address}"
                                                    data-city="${customer.city}"
                                                    data-state="${customer.state || ''}"
                                                    data-zip="${customer.zip_code || ''}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteCustomer" href="#" 
                                                    data-id="${customer.customer_id}">
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
            } else {
                $('#emptyState').removeClass('d-none');
                tbody.append(`<tr><td colspan="10" class="text-center">${response.message}</td></tr>`);
            }
            
            const currentSearch = $('#searchInput').val().toLowerCase();
            if (currentSearch) {
                filterCustomers(currentSearch);
            }
        }
    });
}

// 🔹 Function to Filter Customers
function filterCustomers(searchTerm) {
    const rows = $('#customerTable tbody tr');
    
    if (searchTerm === '') {
        // If search is empty, show all rows
        rows.show();
        
        // Show empty state if necessary
        if (rows.length === 0 || (rows.length === 1 && rows.find('td').attr('colspan'))) {
            $('#emptyState').removeClass('d-none');
        } else {
            $('#emptyState').addClass('d-none');
        }
        return;
    }
    
    let matchFound = false;
    
    // Filter the table rows
    rows.each(function() {
        const row = $(this);
        const customerName = row.find('td:eq(1)').text().toLowerCase();
        const contactPerson = row.find('td:eq(2)').text().toLowerCase();
        const phone = row.find('td:eq(3)').text().toLowerCase();
        const email = row.find('td:eq(4)').text().toLowerCase();
        
        // Check if any of the primary fields contain the search term
        if (customerName.includes(searchTerm) || 
            contactPerson.includes(searchTerm) || 
            phone.includes(searchTerm) || 
            email.includes(searchTerm)) {
            
            row.show();
            matchFound = true;
        } else {
            row.hide();
        }
    });
    
    // Show/hide empty state based on search results
    if (!matchFound) {
        if (rows.filter(':visible').length === 0) {
            $('#emptyState').removeClass('d-none');
            if (rows.find('td[colspan="10"]').length === 0) {
                // Only append if there isn't already a message
                $('#customerTable tbody').append(`
                    <tr id="no-search-results">
                        <td colspan="10" class="text-center">
                            No customers matching "${searchTerm}" found
                        </td>
                    </tr>
                `);
            }
        }
    } else {
        $('#emptyState').addClass('d-none');
        $('#no-search-results').remove();
    }
}

// 🔹 Flash Message Handler
function showMessage(message, type = 'success') {
    $('#customerMessage').html(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
}