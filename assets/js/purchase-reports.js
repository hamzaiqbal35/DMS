$(document).ready(function() {
    let currentData = []; // Store current data for export
    let currentExportFormat = 'csv'; // Default export format

    // Load vendors into dropdown
    function loadVendors() {
        $.get("../model/vendor/getVendors.php", function(res) {
            if (res.status === "success") {
                let options = '<option value="">Select Vendor (All)</option>';
                res.data.forEach(v => {
                    options += `<option value="${v.vendor_id}">${v.vendor_name}</option>`;
                });
                $("#vendor_id").html(options);
            }
        }).fail(function() {
            console.error("Failed to load vendors");
            toastr.error("Failed to load vendors");
        });
    }

    // Load report data with proper filter handling
    function loadReportData() {
        const formData = new FormData($("#reportFiltersForm")[0]);
        
        // Create URLSearchParams and only include non-empty values
        const params = new URLSearchParams();
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                params.append(key, value.trim());
            }
        }

        // Show loading state
        const tableBody = $("#reportTable tbody");
        tableBody.html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.get("../model/purchase/fetchPurchaseList.php?" + params.toString(), function(res) {
            const emptyState = $("#emptyState");
            
            if (res.status === "success" && res.data && res.data.length > 0) {
                currentData = res.data; // Store for export
                let rows = "";
                
                res.data.forEach(row => {
                    rows += `
                        <tr>
                            <td><strong>${row.purchase_number}</strong></td>
                            <td>${row.vendor_name}</td>
                            <td>${formatDate(row.purchase_date)}</td>
                            <td>${row.material_name}</td>
                            <td class="text-end">${parseFloat(row.quantity).toLocaleString()}</td>
                            <td class="text-end">PKR ${parseFloat(row.unit_price).toLocaleString()}</td>
                            <td class="text-end"><strong>PKR ${parseFloat(row.total_price).toLocaleString()}</strong></td>
                            <td><span class="badge bg-${getPaymentStatusColor(row.payment_status)}">${capitalizeFirst(row.payment_status)}</span></td>
                            <td><span class="badge bg-${getDeliveryStatusColor(row.delivery_status)}">${capitalizeFirst(row.delivery_status)}</span></td>
                        </tr>
                    `;
                });
                
                tableBody.html(rows);
                emptyState.addClass('d-none');
                
                // Update summary cards
                updateSummaryCards(res.data);
                $("#summaryCards").show();
                $("#recordCount").text(`Showing ${res.data.length} records`);
                
            } else {
                currentData = []; // Clear data
                tableBody.html('');
                emptyState.removeClass('d-none');
                $("#summaryCards").hide();
                $("#recordCount").text('No records found');
            }
        }).fail(function(xhr, status, error) {
            console.error("AJAX Error:", error);
            tableBody.html('<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            toastr.error("Failed to load report data");
        });
    }

    // Update summary cards
    function updateSummaryCards(data) {
        const totalRecords = data.length;
        const totalAmount = data.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
        const pendingPayments = data.filter(item => item.payment_status.toLowerCase() === 'pending').length;
        const uniqueVendors = [...new Set(data.map(item => item.vendor_name))].length;

        $("#totalRecords").text(totalRecords.toLocaleString());
        $("#totalAmount").text(`PKR ${totalAmount.toLocaleString()}`);
        $("#pendingPayments").text(pendingPayments.toLocaleString());
        $("#uniqueVendors").text(uniqueVendors.toLocaleString());
    }

    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }

    function getPaymentStatusColor(status) {
        switch(status.toLowerCase()) {
            case 'paid': return 'success';
            case 'partial': return 'warning';
            case 'pending': return 'danger';
            default: return 'secondary';
        }
    }

    function getDeliveryStatusColor(status) {
        switch(status.toLowerCase()) {
            case 'delivered': return 'success';
            case 'in_transit': return 'info';
            case 'pending': return 'warning';
            case 'delayed': return 'danger';
            default: return 'secondary';
        }
    }

    // Handle form submission
    $("#reportFiltersForm").on('submit', function(e) {
        e.preventDefault();
        loadReportData();
    });

    // Handle reset button
    $("#resetBtn").on('click', function(e) {
        e.preventDefault();
        $("#reportFiltersForm")[0].reset();
        
        // Reset to default date range
        const today = new Date();
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        $("#date_from").val(thirtyDaysAgo.toISOString().split('T')[0]);
        $("#date_to").val(today.toISOString().split('T')[0]);
        
        // Reload data
        loadReportData();
    });

    // Function to set export format
    window.setExportFormat = function(format) {
        currentExportFormat = format;
        // Update button text to show selected format
        const formatText = format.toUpperCase();
        $("#exportBtn").html(`<i class="fas fa-download me-2"></i> Export as ${formatText}`);
    };

    // Function to export report
    window.exportReport = function() {
        if (currentData.length === 0) {
            toastr.warning("No data to export. Please apply filters and load data first.");
            return;
        }

        const formData = new FormData();
        
        // Add filters to form data
        formData.append('export_format', currentExportFormat);
        formData.append('vendor_id', $("#vendor_id").val());
        formData.append('date_from', $("#date_from").val());
        formData.append('date_to', $("#date_to").val());
        formData.append('min_amount', $("#min_amount").val());
        formData.append('max_amount', $("#max_amount").val());
        formData.append('payment_status', $("#payment_status").val());

        // Show loading state
        const exportBtn = $("#exportBtn");
        const originalText = exportBtn.html();
        exportBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Exporting...');

        // Create a temporary form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../model/purchase/exportPurchases.php';
        form.target = '_blank'; // Open in new tab for PDF

        // Add all form fields to the export form
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        // Reset button state after a short delay
        setTimeout(() => {
            exportBtn.prop('disabled', false).html(originalText);
        }, 1000);

        toastr.success(`Exporting report as ${currentExportFormat.toUpperCase()}...`);
    };

    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $("#date_from").val(thirtyDaysAgo.toISOString().split('T')[0]);
    $("#date_to").val(today.toISOString().split('T')[0]);

    // Initial load
    loadVendors();
    loadReportData();
});