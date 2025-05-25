$(document).ready(function () {
    // Global variable to store all vendors
    let allVendors = [];

    // Fetch Vendors on page load
    fetchVendors();

    function fetchVendors() {
        $.ajax({
            url: '../model/vendor/showVendorIDs.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                const tbody = $('#vendorTable tbody');
                tbody.empty();

                if (response.status === 'success') {
                    // Store all vendors in global variable
                    allVendors = response.data;
                    
                    // Display all vendors
                    displayVendors(allVendors);
                } else {
                    tbody.append(`<tr><td colspan="10" class="text-center text-danger">${response.message}</td></tr>`);
                    $('#emptyState').removeClass('d-none');
                }
            }
        });
    }
    
    // Function to display vendors in the table
    function displayVendors(vendors) {
        const tbody = $('#vendorTable tbody');
        tbody.empty();
        
        if (vendors.length === 0) {
            tbody.append(`<tr><td colspan="10" class="text-center text-danger">No vendors found.</td></tr>`);
            $('#emptyState').removeClass('d-none');
        } else {
            $('#emptyState').addClass('d-none');
            
            vendors.forEach(vendor => {
                tbody.append(`
                    <tr>
                        <td>${vendor.vendor_id}</td>
                        <td>${vendor.vendor_name}</td>
                        <td>${vendor.contact_person || ''}</td>
                        <td>${vendor.phone}</td>
                        <td>${vendor.email || ''}</td>
                        <td>${vendor.address}</td>
                        <td>${vendor.city}</td>
                        <td>${vendor.state || ''}</td>
                        <td>${vendor.zip_code || ''}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item editVendor" href="#" 
                                            data-id="${vendor.vendor_id}"
                                            data-name="${vendor.vendor_name}"
                                            data-contact="${vendor.contact_person || ''}"
                                            data-phone="${vendor.phone}"
                                            data-email="${vendor.email || ''}"
                                            data-address="${vendor.address}"
                                            data-city="${vendor.city}"
                                            data-state="${vendor.state || ''}"
                                            data-zip="${vendor.zip_code || ''}">
                                            <i class="fas fa-edit me-2"></i> Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger deleteVendor" href="#" 
                                            data-id="${vendor.vendor_id}">
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
    }

    // Add Vendor
    $('#addVendorForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/vendor/insertVendor.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#addVendorForm')[0].reset();
                    $('#addVendorModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    fetchVendors();
                }
            }
        });
    });

    // Fill Edit Form
    $(document).on('click', '.editVendor', function () {
        $('#edit_vendor_id').val($(this).data('id'));
        $('#edit_vendor_name').val($(this).data('name'));
        $('#edit_contact_person').val($(this).data('contact'));
        $('#edit_phone').val($(this).data('phone'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_address').val($(this).data('address'));
        $('#edit_city').val($(this).data('city'));
        $('#edit_state').val($(this).data('state'));
        $('#edit_zip_code').val($(this).data('zip'));

        $('#editVendorModal').modal('show');
    });

    // Update Vendor
    $('#editVendorForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/vendor/updateVendorDetails.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#editVendorModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('#editVendorForm')[0].reset();
                    fetchVendors();
                }
            }
        });
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm.trim() === '') {
            // If search term is empty, display all vendors
            displayVendors(allVendors);
        } else {
            // Filter vendors based on search term
            const filteredVendors = allVendors.filter(vendor => {
                return (
                    (vendor.vendor_name && vendor.vendor_name.toLowerCase().includes(searchTerm)) ||
                    (vendor.contact_person && vendor.contact_person.toLowerCase().includes(searchTerm)) ||
                    (vendor.phone && vendor.phone.toLowerCase().includes(searchTerm)) ||
                    (vendor.email && vendor.email.toLowerCase().includes(searchTerm)) ||
                    (vendor.address && vendor.address.toLowerCase().includes(searchTerm)) ||
                    (vendor.city && vendor.city.toLowerCase().includes(searchTerm)) ||
                    (vendor.state && vendor.state.toLowerCase().includes(searchTerm)) ||
                    (vendor.zip_code && vendor.zip_code.toLowerCase().includes(searchTerm))
                );
            });
            
            // Display filtered vendors
            displayVendors(filteredVendors);
        }
    });

    // Delete Vendor
    $(document).on('click', '.deleteVendor', function () {
        const id = $(this).data('id');
        $('#delete_vendor_id').val(id);
        $('#deleteVendorModal').modal('show');
    });
    
    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#delete_vendor_id').val();
        $.ajax({
            url: '../model/vendor/deleteVendor.php',
            method: 'POST',
            data: { vendor_id: id },
            dataType: 'json',
            success: function (response) {
                showMessage(response.message, response.status);
                if (response.status === 'success') {
                    $('#deleteVendorModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    fetchVendors();
                }
            }
        });
    });

    // Utility function
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
});