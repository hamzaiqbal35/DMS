$(document).ready(function () {
    // Store original data for filtering
    let originalInvoiceData = [];
    
    // Load vendors into filter dropdown
    function loadVendors() {
        $.get("../model/vendor/getVendors.php", function (res) {
            if (res.status === "success") {
                let options = '<option value="">Filter by Vendor</option>';
                res.data.forEach(v => {
                    options += `<option value="${v.vendor_name}">${v.vendor_name}</option>`;
                });
                $("#filterVendor").html(options);
            }
        });
    }

    // Load purchases with invoice status
    function loadInvoices() {
        $.get("../model/purchase/fetchPurchaseList.php", function (res) {
            let rows = "";
            const tableBody = $("#invoiceTable tbody");
            const emptyState = $("#emptyState");
            
            if (res.status === "success" && res.data && res.data.length > 0) {
                // Store original data for filtering
                originalInvoiceData = res.data;
                
                res.data.forEach(row => {
                    const hasInvoice = row.invoice_file ? true : false;
                    rows += `
                        <tr data-vendor-name="${row.vendor_name}" data-has-invoice="${hasInvoice}">
                            <td>${row.purchase_number}</td>
                            <td>${row.vendor_name}</td>
                            <td>${row.purchase_date}</td>
                            <td>PKR ${parseFloat(row.total_price).toFixed(2)}</td>
                            <td>
                                <span class="badge bg-${hasInvoice ? 'success' : 'warning'}">
                                    ${hasInvoice ? 'Has Invoice' : 'No Invoice'}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        ${hasInvoice ? `
                                            <li>
                                                <a class="dropdown-item viewInvoiceBtn" href="#" data-id="${row.purchase_id}" data-file="${row.invoice_file}">
                                                    <i class="fas fa-eye me-2"></i> View Invoice
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteInvoiceBtn" href="#" data-id="${row.purchase_id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete Invoice
                                                </a>
                                            </li>
                                        ` : `
                                            <li>
                                                <a class="dropdown-item uploadInvoiceBtn" href="#" data-id="${row.purchase_id}">
                                                    <i class="fas fa-upload me-2"></i> Upload Invoice
                                                </a>
                                            </li>
                                        `}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tableBody.html(rows);
                emptyState.addClass('d-none');
            } else {
                originalInvoiceData = [];
                tableBody.html('');
                emptyState.removeClass('d-none');
            }
        });
    }

    // Search and Filter functionality
    function applyFilters() {
        const searchText = $("#searchInput").val().toLowerCase().trim();
        const vendorFilter = $("#filterVendor").val().trim();
        const statusFilter = $("#filterStatus").val().trim();

        let visibleCount = 0;

        $("#invoiceTable tbody tr").each(function() {
            const row = $(this);
            const purchaseNumber = row.find('td:eq(0)').text().toLowerCase();
            const vendorName = row.find('td:eq(1)').text().toLowerCase();
            const hasInvoice = row.data('has-invoice');
            
            // Search filter
            const matchesSearch = !searchText || 
                                purchaseNumber.includes(searchText) || 
                                vendorName.includes(searchText);
            
            // Vendor filter
            const matchesVendor = !vendorFilter || row.data('vendor-name') === vendorFilter;
            
            // Status filter
            const matchesStatus = !statusFilter || 
                                (statusFilter === 'has_invoice' && hasInvoice) ||
                                (statusFilter === 'no_invoice' && !hasInvoice);

            const shouldShow = matchesSearch && matchesVendor && matchesStatus;
            
            if (shouldShow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });

        // Show/hide empty state
        if (visibleCount === 0 && originalInvoiceData.length > 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Matching Purchases Found');
            $("#emptyState p").text('Try adjusting your search or filter criteria.');
        } else if (originalInvoiceData.length === 0) {
            $("#emptyState").removeClass('d-none');
            $("#emptyState h5").text('No Purchases Found');
            $("#emptyState p").text('No purchases available to display.');
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
    $("#filterVendor, #filterStatus").on('change', function() {
        applyFilters();
    });

    // Handle reset filters
    $("#resetFilters").click(function () {
        $("#searchInput").val("");
        $("#filterVendor").val("");
        $("#filterStatus").val("");
        applyFilters();
    });

    // Handle upload invoice button click
    $(document).on("click", ".uploadInvoiceBtn", function () {
        const purchaseId = $(this).data("id");
        $("#upload_purchase_id").val(purchaseId);
        $("#uploadInvoiceModal").modal("show");
    });

    // Handle upload invoice form submission
    $("#uploadInvoiceForm").submit(function (e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Validate file input
        const fileInput = $('#invoice_file')[0];
        if (!fileInput.files || fileInput.files.length === 0) {
            toastr.error('Please select a file to upload');
            return;
        }

        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (fileInput.files[0].size > maxSize) {
            toastr.error('File size exceeds the 5MB limit');
            return;
        }

        // Validate file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!allowedTypes.includes(fileInput.files[0].type)) {
            toastr.error('Invalid file type. Only PDF, JPEG, and PNG files are allowed');
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            url: "../model/purchase/attachInvoice.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.status === "success") {
                    $("#uploadInvoiceModal").modal("hide");
                    $("#uploadInvoiceForm")[0].reset();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    loadInvoices();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Upload Error:", error);
                toastr.error("Failed to upload invoice. Please try again.");
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Handle view invoice button click
    $(document).on("click", ".viewInvoiceBtn", function () {
        const filePath = $(this).data("file");
        const fileUrl = "../" + filePath;
        
        // Set download link with API endpoint
        const filename = filePath.split('/').pop(); // Extract filename from path
        
        // Prevent opening in new tab and trigger direct download
        $("#downloadInvoice").off('click').on('click', function(e) {
            e.preventDefault();
            
            console.log('[DEBUG] Downloading purchase invoice:', filename);
            
            // Create hidden iframe for download without redirect
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = `../api/sale/downloadInvoice.php?filename=${encodeURIComponent(filename)}`;
            document.body.appendChild(iframe);
            
            // Remove iframe after download starts
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 3000);
            
            console.log('[DEBUG] Download initiated via iframe for purchase invoice:', filename);
        });
        
        // Preview file based on type
        const fileExtension = filePath.split('.').pop().toLowerCase();
        let previewHtml = '';
        
        if (fileExtension === 'pdf') {
            previewHtml = `<iframe src="${fileUrl}" width="100%" height="500px" frameborder="0"></iframe>`;
        } else if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
            previewHtml = `<img src="${fileUrl}" class="img-fluid" alt="Invoice Preview">`;
        }
        
        $("#invoicePreview").html(previewHtml);
        $("#viewInvoiceModal").modal("show");
    });

    // Open delete modal
    $(document).on("click", ".deleteInvoiceBtn", function () {
        const purchaseId = $(this).data("id");
        $("#delete_invoice_purchase_id").val(purchaseId);
        $("#deleteInvoiceModal").modal("show");
    });

    // Confirm delete
    $("#confirmDeleteInvoiceBtn").click(function () {
        const purchaseId = $("#delete_invoice_purchase_id").val();
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        $.post("../model/purchase/deleteInvoice.php", { purchase_id: purchaseId }, function (res) {
            if (res.status === "success") {
                $("#deleteInvoiceModal").modal("hide");
                loadInvoices();
                showMessage(res.message, "success");
            } else {
                showMessage(res.message, "error");
            }
        }, "json").always(() => {
            $("#confirmDeleteInvoiceBtn").prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i> Delete Invoice');
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
    loadVendors();
    loadInvoices();
}); 