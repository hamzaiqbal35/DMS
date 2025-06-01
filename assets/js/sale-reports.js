$(document).ready(function() {
    // Function to load customers into the filter dropdown
    function loadCustomers() {
        $.ajax({
            url: "../model/customer/getCustomers.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="">Select Customer (All)</option>';
                    res.data.forEach(customer => {
                        options += `<option value="${customer.customer_id}">${customer.customer_name}</option>`;
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
        $("#reportTable tbody").html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading report data...</td></tr>');
        $("#emptyState").addClass('d-none');

        $.ajax({
            url: "../model/sale/fetchSaleList.php", // Reusing fetchSaleList.php for filtered data
            method: "GET",
            data: filters,
            dataType: "json",
            success: function(res) {
                const tbody = $("#reportTable tbody");
                tbody.empty();
                $("#recordCount").text(''); // Clear previous count

                if (res.status === "success" && res.data && res.data.length > 0) {
                    res.data.forEach(sale => {
                        tbody.append(`
                            <tr>
                                <td>${sale.invoice_number}</td>
                                <td>${sale.customer_name}</td>
                                <td>${sale.sale_date}</td>
                                <td>${sale.item_name}</td>
                                <td>${sale.quantity}</td>
                                <td>PKR ${parseFloat(sale.unit_price).toFixed(2)}</td>
                                <td>PKR ${parseFloat(sale.total_price).toFixed(2)}</td>
                                <td><span class="badge bg-${getStatusColor(sale.payment_status)}">${sale.payment_status}</span></td>
                                <td>${sale.created_by_name || 'N/A'}</td>
                            </tr>
                        `);
                    });
                    $("#recordCount").text(`${res.data.length} Records Found`);
                    updateSummary(res.data); // Update summary based on filtered data

                } else if (res.status === "empty") {
                    $("#emptyState").removeClass('d-none');
                    updateSummary([]); // Reset summary if no data
                } 
                 else {
                    toastr.error(res.message || "Failed to fetch report data.");
                    console.error("Report data fetch error:", res);
                    $("#emptyState").removeClass('d-none');
                    $("#emptyState h5").text('Error Loading Data');
                    $("#emptyState p").text(res.message || 'An error occurred while fetching report data.');
                    updateSummary([]); // Reset summary on error
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error fetching report data.");
                console.error("Report data AJAX error:", error);
                $("#reportTable tbody").empty();
                $("#emptyState").removeClass('d-none');
                $("#emptyState h5").text('Network Error');
                $("#emptyState p").text('Could not connect to the server to fetch report data.');
                updateSummary([]); // Reset summary on AJAX error
            }
        });
    }

    // Function to update summary cards
    function updateSummary(salesData) {
        let totalAmount = 0;
        let pendingAmount = 0;
        const uniqueCustomers = new Set();

        if (salesData && salesData.length > 0) {
            salesData.forEach(sale => {
                totalAmount += parseFloat(sale.total_price);
                if (sale.payment_status !== 'paid') {
                    // Note: This assumes total_price is the pending amount if not paid.
                    // You might need a separate field in the DB for paid amount if needed.
                     pendingAmount += parseFloat(sale.total_price);
                }
                 uniqueCustomers.add(sale.customer_id);
            });
        }

        $("#totalRecords").text(salesData ? salesData.length : 0);
        $("#totalAmount").text(`PKR ${totalAmount.toFixed(2)}`);
        $("#pendingPayments").text(`PKR ${pendingAmount.toFixed(2)}`);
        $("#uniqueCustomers").text(uniqueCustomers.size);
        $("#summaryCards").show();
    }

     // Get status color
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success'
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
        
        // Use AJAX POST request to send filters and format
        $.ajax({
            url: exportUrl,
            method: "POST",
            data: filters + '&export_format=' + format,
            xhrFields: {
                responseType: 'blob' // Crucial for handling file downloads
            },
            beforeSend: function() {
                 // Optional: Show loading indicator
                 toastr.info('Generating report...');
            },
            success: function(response, status, xhr) {
                // Get filename from content-disposition header
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = "sales_report." + format; // Default filename
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\r\n]*=((['"]).*?\2|[^;\r\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = decodeURIComponent(matches[1].replace(/['"]/g, ''));
                    }
                }

                // Create a blob from the response and trigger download
                const blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                toastr.success('Report generated successfully.');
            },
            error: function(xhr, status, error) {
                // Handle errors, especially if the server returns a non-file response (like text error)
                if (xhr.response) {
                    const reader = new FileReader();
                    reader.onload = function() {
                         try {
                            const errorRes = JSON.parse(reader.result);
                            toastr.error(errorRes.message || 'An error occurred during export.');
                             console.error('Export error response:', errorRes);
                         } catch (e) {
                            // If not JSON, display raw response or generic error
                            toastr.error('An error occurred during export.');
                             console.error('Export error response:', reader.result);
                         }
                    };
                    reader.readAsText(xhr.response); // Read the error response as text
                } else {
                    toastr.error('An unknown error occurred during export.');
                     console.error('Export AJAX error:', error);
                }
            }
        });
    }

    // Event listener for export dropdown items
    $('.dropdown-menu a[data-export-format]').click(function(e) {
        e.preventDefault();
        const format = $(this).data('export-format');
        exportReport(format);
    });

    // Handle filter form submission
    $("#reportFiltersForm").submit(function(e) {
        e.preventDefault();
        fetchReportData();
    });

    // Handle reset filters button click
    $("#resetBtn").click(function() {
        $("#reportFiltersForm")[0].reset();
        fetchReportData(); // Fetch data with no filters
    });

    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $("#date_from").val(thirtyDaysAgo.toISOString().split('T')[0]);
    $("#date_to").val(today.toISOString().split('T')[0]);

    // Initial load of customers and report data
    loadCustomers();
    fetchReportData(); // Load report data on page load
});
