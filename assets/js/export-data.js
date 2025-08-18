$(document).ready(function () {
    // --- State ---
    let exportHistory = [];
    let summaryStats = { total: 0, amount: 0 };

    // --- Tooltips ---
    $('[data-bs-toggle="tooltip"]').tooltip();

    // --- Dynamic Filter Logic ---
    function updateFieldVisibility() {
        const type = $('#exportType').val();
        // Hide all advanced fields by default
        $('#customer').closest('.col-md-6, .col-lg-4').hide();
        $('#customer').hide(); // Hide the select element itself
        $('#vendor').closest('.col-md-6, .col-lg-4').hide();
        $('#vendor').hide(); // Hide the select element itself
        $('#stockStatus').closest('.col-md-6, .col-lg-4').hide();
        $('#stockStatus').hide(); // Hide the select element itself
        $('#category').closest('.col-md-6, .col-lg-4').show();
        $('#category').show(); // Show the select element itself
        
        if (type === 'sales') {
            $('#customer').closest('.col-md-6, .col-lg-4').show();
            $('#customer').show(); // Show the select element itself
            $('#category').closest('.col-md-6, .col-lg-4').hide();
            $('#category').hide(); // Hide the select element itself
            // Ensure customer data is loaded
            if ($('#customer option').length <= 1) {
                loadCustomers();
            }
        } else if (type === 'purchases') {
            $('#vendor').closest('.col-md-6, .col-lg-4').show();
            $('#vendor').show(); // Show the select element itself
            $('#category').closest('.col-md-6, .col-lg-4').hide();
            $('#category').hide(); // Hide the select element itself
            // Ensure vendor data is loaded
            if ($('#vendor option').length <= 1) {
                loadVendors();
            }
        } else if (type === 'inventory') {
            $('#stockStatus').closest('.col-md-6, .col-lg-4').show();
            $('#stockStatus').show(); // Show the select element itself
            $('#category').closest('.col-md-6, .col-lg-4').show();
            $('#category').show(); // Show the select element itself
            // Ensure category data is loaded
            if ($('#category option').length <= 1) {
                loadCategories();
            }
        } else {
            // For customers/vendors, hide category/stock
            $('#category').closest('.col-md-6, .col-lg-4').hide();
            $('#category').hide(); // Hide the select element itself
        }
    }
    $('#exportType').change(function() {
        updateFieldVisibility();
        // Force reload data for visible fields
        const type = $(this).val();
        if (type === 'sales') {
            loadCustomers();
        } else if (type === 'purchases') {
            loadVendors();
        } else if (type === 'inventory') {
            loadCategories();
        }
    });
    updateFieldVisibility();

    // --- Custom Date Range ---
    $('#dateRange').on('change', function () {
        if ($(this).val() === 'custom') {
            $('.custom-date-range').show();
        } else {
            $('.custom-date-range').hide();
        }
    });

    // --- Load Categories, Customers, Vendors ---
    function loadCategories() {
        console.log('Loading categories...');
        $.ajax({
            url: '../model/inventory/fetchCategories.php',
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function (res) {
                console.log('Categories response:', res);
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let options = '<option value="">All Categories</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.category_id}">${c.category_name}</option>`;
                    });
                    $('#category').html(options);
                    console.log('Categories loaded successfully');
                } else {
                    console.error('Failed to load categories:', res.message || 'No data received');
                    $('#category').html('<option value="">No categories available</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading categories:', error, 'Status:', status, 'Response:', xhr.responseText);
                $('#category').html('<option value="">Error loading categories</option>');
            }
        });
    }
    
    function loadCustomers() {
        console.log('Loading customers...');
        $.ajax({
            url: '../model/customer/getCustomers.php',
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function (res) {
                console.log('Customers response:', res);
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let options = '<option value="">All Customers</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.customer_id}">${c.customer_name}</option>`;
                    });
                    $('#customer').html(options);
                    console.log('Customers loaded successfully');
                } else {
                    console.error('Failed to load customers:', res.message || 'No data received');
                    $('#customer').html('<option value="">No customers available</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading customers:', error, 'Status:', status, 'Response:', xhr.responseText);
                $('#customer').html('<option value="">Error loading customers</option>');
            }
        });
    }
    
    function loadVendors() {
        console.log('Loading vendors...');
        $.ajax({
            url: '../model/vendor/getVendors.php',
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function (res) {
                console.log('Vendors response:', res);
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let options = '<option value="">All Vendors</option>';
                    res.data.forEach(v => {
                        options += `<option value="${v.vendor_id}">${v.vendor_name}</option>`;
                    });
                    $('#vendor').html(options);
                    console.log('Vendors loaded successfully');
                } else {
                    console.error('Failed to load vendors:', res.message || 'No data received');
                    $('#vendor').html('<option value="">No vendors available</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading vendors:', error, 'Status:', status, 'Response:', xhr.responseText);
                $('#vendor').html('<option value="">Error loading vendors</option>');
            }
        });
    }
    
    loadCategories();

    // Ensure data is loaded after a short delay to handle any timing issues
    setTimeout(function() {
        // Reload data if any selects are empty
        if ($('#customer option').length <= 1) {
            loadCustomers();
        }
        if ($('#vendor option').length <= 1) {
            loadVendors();
        }
        if ($('#category option').length <= 1) {
            loadCategories();
        }
    }, 500);

    // Initialize export history and summary cards
    loadExportHistory();

    // --- Export Form Submission ---
    $('#exportFilters').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        // Get form values
        const dateRange = $('#dateRange').val();
        const exportType = formData.get('export_type');
        const exportFormat = formData.get('export_format');

        // Validate and set form data
        formData.set('dateRange', dateRange);

        if (!exportType) {
            toastr.warning('Please select an export type.');
            return;
        }
        formData.set('report_type', exportType); // Ensure report_type is set for backend

        // Remove any 'all' value if not selected
        if (exportType !== 'all') {
            formData.delete('export_type');
        }

        if (!exportFormat) {
            toastr.warning('Please select an export format.');
            return;
        }

        // Set a human-readable date range label
        let dateRangeLabel = '';
        if (dateRange === 'custom') {
            dateRangeLabel = $('#startDate').val() + ' to ' + $('#endDate').val();
        } else if (dateRange) {
            dateRangeLabel = $('#dateRange option:selected').text();
        } else {
            dateRangeLabel = 'All Time';
        }
        formData.set('date_range_label', dateRangeLabel);
        // Show loading state
        const submitBtn = $(form).find('button[type="submit"]');
        const originalBtnHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Exporting...');
        // AJAX request
        $.ajax({
            url: '../api/exportReport.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhrFields: { responseType: 'blob' },
            success: function (response, status, xhr) {
                // Download file
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = 'report.' + exportFormat;
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = decodeURIComponent(matches[1].replace(/['"]/g, ''));
                    }
                }
                const blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
                if (blob.size === 0) {
                    toastr.error('No data found for export.');
                    return;
                }
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                toastr.success('Report exported successfully.');
                toastr.info('Do not refresh the page after export. Use the Export History table to re-download your file.');
                setTimeout(loadExportHistory, 1000); // Always refresh history after export
            },
            error: function (xhr, status, error) {
                toastr.error('An error occurred during export.');
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    });

    // --- Reset Button ---
    $('#resetExportBtn').click(function () {
        $('#exportFilters')[0].reset();
        $('.custom-date-range').hide();
        updateFieldVisibility();
        
        // Reload all data after reset
        setTimeout(function() {
            loadCategories();
            loadCustomers();
            loadVendors();
        }, 100);
    });

    // --- Refresh Button ---
    $('#refreshExportBtn').click(function () {
        loadExportHistory();
        toastr.info('Export data refreshed.');
    });

    // --- Export History Table (real API implementation) ---
    function loadExportHistory() {
        $.ajax({
            url: '../api/getExportHistory.php',
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                const tbody = $('#exportHistoryTable tbody');
                tbody.empty();
                
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    $('#emptyExportState').addClass('d-none');
                    res.data.forEach(hist => {
                        const hasDownload = hist.file_path ? true : false;
                        tbody.append(`
                            <tr>
                                <td>${hist.date}</td>
                                <td>${hist.type}</td>
                                <td>${hist.format}</td>
                                <td>${hist.range}</td>
                                <td>${hist.user}</td>
                                <td>${hist.size}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary export-actions-dropdown" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                            <i class="fas fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item view-export-details" href="#" data-id="${hist.id}"><i class="fas fa-info-circle me-2"></i>Details</a></li>
                                            ${hasDownload ? `<li><a class="dropdown-item download-export-file" href="${hist.file_path}"><i class="fas fa-download me-2"></i>Download</a></li>` : ''}
                                            <li><a class="dropdown-item delete-export-record" href="#" data-id="${hist.id}"><i class="fas fa-trash-alt me-2 text-danger"></i>Delete</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                    $('#exportRecordCount').text(`Showing ${res.data.length} records`);
                } else {
                    $('#emptyExportState').removeClass('d-none');
                    $('#exportRecordCount').text('No records found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading export history:', error);
                $('#emptyExportState').removeClass('d-none');
                $('#exportRecordCount').text('Error loading history');
                // Don't show error toast for empty history - it's normal for new users
                if (xhr.status !== 200) {
                    toastr.error('Failed to load export history');
                }
            }
        });
    }

    // --- Export Details Modal (real API implementation) ---
    $(document).on('click', '.view-export-details', function () {
        const id = $(this).data('id');
        
        // Show loading state
        $('#exportDetailBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#exportDetailModal').modal('show');
        
        // Fetch export details
        $.ajax({
            url: '../api/getExportHistory.php',
            method: 'GET',
            data: { export_id: id },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    const hist = res.data[0];
                    $('#exportDetailBody').html(`
                        <h5>Export #${hist.id}</h5><hr>
                        <p><strong>Date:</strong> ${hist.date}</p>
                        <p><strong>Type:</strong> ${hist.type}</p>
                        <p><strong>Format:</strong> ${hist.format}</p>
                        <p><strong>Date Range:</strong> ${hist.range}</p>
                        <p><strong>Exported By:</strong> ${hist.user}</p>
                        <p><strong>File Size:</strong> ${hist.size}</p>
                        <p><strong>Filename:</strong> ${hist.filename || 'N/A'}</p>
                    `);
                } else {
                    $('#exportDetailBody').html('<div class="text-danger">Export record not found.</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#exportDetailBody').html('<div class="text-danger">Error loading export details.</div>');
                console.error('Error loading export details:', error);
            }
        });
    });

    // --- Delete Export Record ---
    let exportIdToDelete = null;
    $(document).on('click', '.delete-export-record', function () {
        exportIdToDelete = $(this).data('id');
        $('#deleteExportModal').modal('show');
    });
    $('#confirmDeleteExport').click(function () {
        if (!exportIdToDelete) return;
        $.ajax({
            url: '../api/deleteExportHistory.php',
            method: 'POST',
            data: { export_id: exportIdToDelete },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success('Export record deleted successfully.');
                    loadExportHistory();
                } else {
                    toastr.error(res.message || 'Failed to delete export record.');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error deleting export record.');
            },
            complete: function() {
                $('#deleteExportModal').modal('hide');
                exportIdToDelete = null;
            }
        });
    });
});