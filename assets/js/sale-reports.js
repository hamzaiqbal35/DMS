$(document).ready(function() {
    let originalReportData = [];
    
    // Function to load customers into the filter dropdown
    function loadCustomers() {
        $.ajax({
            url: "../model/customer/getCustomers.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Select Customer (All)</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.customer_id}">${c.customer_name}</option>`;
                    });
                    $("#customer_id").html(options);
                } else {
                    toastr.error("Failed to load customers for filter.");
                    console.error("Customer filter load error:", res);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading customers for filter.");
                console.error("Customer filter AJAX error:", error);
            }
        });
    }

    // Function to fetch and render sales report data
    function fetchReportData() {
        const filters = $("#reportFiltersForm").serialize();
        $("#reportTable tbody").html('<tr><td colspan="12" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report data...</td></tr>');
        $("#emptyState").addClass('d-none');

        $.ajax({
            url: "../model/sale/fetchSaleList.php",
            method: "GET",
            data: filters,
            dataType: "json",
            success: function(res) {
                const tbody = $("#reportTable tbody");
                tbody.empty();
                $("#recordCount").text('');

                if (res.status === "success" && res.data && res.data.length > 0) {
                    res.data.forEach(sale => {
                        // Determine sale type badge
                        let saleTypeBadge = '';
                        if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                            saleTypeBadge = `<span class="badge bg-info" title="From Customer Order">
                                <i class="fas fa-shopping-cart me-1"></i>From Order
                            </span>`;
                        } else {
                            saleTypeBadge = `<span class="badge bg-secondary">
                                <i class="fas fa-plus me-1"></i>Direct Sale
                            </span>`;
                        }

                        // Format items display
                        let itemsDisplay = '-';
                        if (sale.items_details) {
                            const itemsArr = sale.items_details.split(';').map(s => s.trim()).filter(Boolean);
                            if (itemsArr.length > 1) {
                                itemsDisplay = `${itemsArr.length} items`;
                            } else if (itemsArr.length === 1) {
                                itemsDisplay = itemsArr[0];
                            }
                        }
                        
                        // Format amounts with proper null handling
                        const totalAmount = parseFloat(sale.display_total_amount || 0).toFixed(2);
                        const paidAmount = parseFloat(sale.display_paid_amount || 0).toFixed(2);
                        const pendingAmount = parseFloat(sale.display_pending_amount || 0).toFixed(2);

                        tbody.append(`
                            <tr>
                                <td>${sale.invoice_number}</td>
                                <td>${sale.customer_name}</td>
                                <td>${saleTypeBadge}</td>
                                <td>${sale.sale_date}</td>
                                <td>${itemsDisplay}</td>
                                <td class="text-end">PKR ${totalAmount}</td>
                                <td class="text-end">PKR ${paidAmount}</td>
                                <td class="text-end">PKR ${pendingAmount}</td>
                                <td><span class="badge bg-${getStatusColor(sale.payment_status)}">${sale.payment_status}</span></td>
                                <td><span class="badge bg-${getStatusColor(sale.order_status)}">${sale.order_status}</span></td>
                                <td>${sale.tracking_number && sale.tracking_number !== 'N/A' ? sale.tracking_number : '_'}</td>
                                <td>${sale.created_by_name || 'N/A'}</td>
                            </tr>
                        `);
                    });
                    $("#recordCount").text(`${res.data.length} Records Found`);
                    updateSummary(res.data);

                } else if (res.status === "empty") {
                    $("#emptyState").removeClass('d-none');
                    updateSummary([]);
                } else if (res.status === "success" && (!res.data || res.data.length === 0)) {
                    // No error, just show empty state
                    $("#emptyState").removeClass('d-none');
                    updateSummary([]);
                } else {
                    toastr.error(res.message || "Failed to fetch report data.");
                    console.error("Report data fetch error:", res);
                    $("#emptyState").removeClass('d-none');
                    $("#emptyState h5").text('Error Loading Data');
                    $("#emptyState p").text(res.message || 'An error occurred while fetching report data.');
                    updateSummary([]);
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error fetching report data.");
                console.error("Report data AJAX error:", error);
                $("#reportTable tbody").empty();
                $("#emptyState").removeClass('d-none');
                $("#emptyState h5").text('Network Error');
                $("#emptyState p").text('Could not connect to the server to fetch report data.');
                updateSummary([]);
            }
        });
    }

    // Function to update summary cards
    function updateSummary(salesData) {
        let totalAmount = 0;
        let totalPaid = 0;
        let totalPending = 0;
        const uniqueCustomers = new Set();
        const paymentStatusCounts = {
            'pending': 0,
            'partial': 0,
            'paid': 0
        };
        const orderStatusCounts = {
            'pending': 0,
            'confirmed': 0,
            'processing': 0,
            'shipped': 0,
            'delivered': 0,
            'cancelled': 0
        };
        const saleTypeCounts = {
            'Direct Sale': 0,
            'From Customer Order': 0
        };

        if (salesData && salesData.length > 0) {
            salesData.forEach(sale => {
                totalAmount += parseFloat(sale.display_total_amount || 0);
                totalPaid += parseFloat(sale.display_paid_amount || 0);
                totalPending += parseFloat(sale.display_pending_amount || 0);
                
                uniqueCustomers.add(sale.customer_id);
                
                // Count payment statuses
                if (sale.payment_status) {
                    paymentStatusCounts[sale.payment_status]++;
                }
                
                // Count order statuses
                if (sale.order_status) {
                    orderStatusCounts[sale.order_status]++;
                }
                
                // Count sale types
                if (sale.customer_order_id && sale.customer_order_id !== 'N/A') {
                    saleTypeCounts['From Customer Order']++;
                } else {
                    saleTypeCounts['Direct Sale']++;
                }
            });
        }

        const averageSale = salesData && salesData.length > 0 ? totalAmount / salesData.length : 0;

        // Update summary cards
        $("#totalRecords").text(salesData ? salesData.length : 0);
        $("#totalAmount").text(`PKR ${totalAmount.toFixed(2)}`);
        $("#totalPaid").text(`PKR ${totalPaid.toFixed(2)}`);
        $("#totalPending").text(`PKR ${totalPending.toFixed(2)}`);
        $("#uniqueCustomers").text(uniqueCustomers.size);
        $("#averageSale").text(`PKR ${averageSale.toFixed(2)}`);

        $("#summaryCards").show();
    }

    // Get status color
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success',
            'confirmed': 'info',
            'processing': 'primary',
            'shipped': 'info',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }

    // Function to handle report export
    function exportReport(format) {
        if (!format) {
            toastr.error("Export format not specified.");
            return;
        }
        
        const filters = $("#reportFiltersForm").serialize();
        const exportUrl = `../model/sale/exportSales.php`;
        
        console.log('Export Debug: Starting export with format:', format);
        console.log('Export Debug: Filters:', filters);
        
        // Use AJAX POST request to send filters and format
        $.ajax({
            url: exportUrl,
            method: "POST",
            data: filters + '&format=' + format,
            xhrFields: {
                responseType: 'blob'
            },
            beforeSend: function() {
                toastr.info('Generating report...');
                console.log('Export Debug: Request sent');
            },
            success: function(response, status, xhr) {
                console.log('Export Debug: Response received');
                console.log('Export Debug: Response size:', response.size);
                console.log('Export Debug: Response type:', response.type);
                
                // Get filename from content-disposition header
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = 'Sales_Report_' + new Date().toISOString().slice(0, 10) + '.' + format;
                
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                
                console.log('Export Debug: Filename:', filename);
                
                // Create download link
                const blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                console.log('Export Debug: Download initiated');
                toastr.success('Report exported successfully!');
            },
            error: function(xhr, status, error) {
                console.error('Export Debug: Error occurred:', error);
                console.error('Export Debug: Status:', status);
                console.error('Export Debug: Response:', xhr.responseText);
                toastr.error("Error exporting report.");
                console.error("Export error:", error);
            }
        });
    }

    // Event handlers
    $("#reportFiltersForm").submit(function(e) {
        e.preventDefault();
        fetchReportData();
    });

    $("#resetBtn").click(function() {
        $("#reportFiltersForm")[0].reset();
        fetchReportData();
    });

    // Export button handlers
    $(document).on('click', '[data-export-format]', function(e) {
        e.preventDefault();
        const format = $(this).data('export-format');
        exportReport(format);
    });

    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $("#date_from").val(thirtyDaysAgo.toISOString().split('T')[0]);
    $("#date_to").val(today.toISOString().split('T')[0]);

    // Initialize
    loadCustomers();
    fetchReportData();

    // Auto-refresh every 5 minutes
    setInterval(function() {
        if ($("#reportFiltersForm").is(':visible')) {
            fetchReportData();
        }
    }, 300000); // 5 minutes
});
