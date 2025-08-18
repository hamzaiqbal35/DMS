$(document).ready(function () {
    // Store original data for filtering
    let originalReportData = [];
    let currentReportType = '';
    let charts = {
        trendChart: null,
        categoryChart: null
    };
    
    // Initialize the page
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    // Set default values for date range
    $('#dateRange').val('30');
    $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#endDate').val(today.toISOString().split('T')[0]);
    
    // Hide custom date range initially since we're using 30 days
    $('.custom-date-range').hide();
    
    // Load initial data
    loadCategories();
    updateSummaryCards();
    
    // Initialize report filters
    $('#reportFilters').on('submit', function(e) {
        e.preventDefault();
        const reportType = $('#reportType').val();
        
        if (!reportType) {
            toastr.warning('Please select a report type');
            return;
        }
        
        loadReportData();
    });

    // Handle date range change
    $('#dateRange').on('change', function() {
        const value = $(this).val();
        if (value === 'custom') {
            $('.custom-date-range').show();
        } else {
            $('.custom-date-range').hide();
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - parseInt(value));
            $('#startDate').val(startDate.toISOString().split('T')[0]);
            $('#endDate').val(endDate.toISOString().split('T')[0]);
        }
        updateSummaryCards();
    });

    // Load categories for inventory reports
    function loadCategories() {
        $.ajax({
            url: "../model/inventory/fetchCategories.php",
            method: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data) {
                    let options = '<option value="" disabled selected>Select Category</option><option value="">All Categories</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.category_id}">${c.category_name}</option>`;
                    });
                    $("#category").html(options);
                } else {
                    toastr.error("Failed to load categories");
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading categories");
            }
        });
    }

    // Load report data based on type
    function loadReportData() {
        const reportType = $("#reportType").val();
        if (!reportType || reportType === '') {
            toastr.warning("Please select a report type");
            showEmptyState("Please select a report type to view data");
            return;
        }
        
        currentReportType = reportType;
        const filters = getFilters();
        
        // Show loading state
        $("#reportTable tbody").html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        
        $.ajax({
            url: "../api/fetchReportData.php",
            method: "GET",
            data: {
                type: reportType,
                ...filters
            },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    originalReportData = res.data;
                    renderReportTable(res.data);
                    updateSummaryCards(res.data);
                    updateCharts(res);
                } else if (res.status === "empty") {
                    showEmptyState(res.message || "No data available for the selected criteria");
                } else {
                    toastr.error(res.message || "Failed to load report data");
                    showEmptyState("Error loading report data");
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Error loading report data");
                showEmptyState("Error loading report data");
            }
        });
    }

    // Get current filter values
    function getFilters() {
        const dateRange = $("#dateRange").val();
        const filters = {
            category_id: $("#category").val(),
            status: $("#status").val(),
            payment_status: $("#paymentStatus").val()
        };

        // Handle date range
        if (dateRange && dateRange !== '') {
            if (dateRange === 'custom') {
                filters.date_from = $("#startDate").val();
                filters.date_to = $("#endDate").val();
            } else if (dateRange === 'all') {
                // Don't set date filters for 'all' to get all records
                delete filters.date_from;
                delete filters.date_to;
            } else {
                const today = new Date();
                const startDate = new Date(today);
                startDate.setDate(today.getDate() - parseInt(dateRange));
                
                filters.date_from = startDate.toISOString().split('T')[0];
                filters.date_to = today.toISOString().split('T')[0];
            }
        }

        // Add type-specific filters
        if (currentReportType === 'inventory') {
            filters.stock_status = $("#stockStatus").val();
        }

        // Validate date range
        if (filters.date_from && filters.date_to) {
            const startDate = new Date(filters.date_from);
            const endDate = new Date(filters.date_to);
            if (startDate > endDate) {
                toastr.warning("Start date cannot be after end date");
                filters.date_from = filters.date_to;
                $("#startDate").val(filters.date_from);
            }
        }

        return filters;
    }

    // Render report table based on data
    function renderReportTable(data) {
        const tbody = $("#reportTable tbody");
        tbody.empty();

        if (!data || Object.keys(data).length === 0) {
            showEmptyState("No data available for the selected criteria");
            return;
        }

        let items = [];
        switch(currentReportType) {
            case 'sales':
                items = data.sales || [];
                break;
            case 'purchases':
                items = data.purchases || [];
                break;
            case 'inventory':
                items = data.items || [];
                break;
            case 'customers':
                items = data.customers || [];
                break;
            case 'vendors':
                items = data.vendors || [];
                break;
        }

        if (items.length === 0) {
            showEmptyState("No records found");
            return;
        }

        items.forEach(item => {
            const row = createTableRow(item);
            tbody.append(row);
        });

        // Update record count
        $("#recordCount").text(`Showing ${items.length} records`);
    }

    // Create table row based on report type
    function createTableRow(item) {
        let row = '<tr>';
        
        switch(currentReportType) {
            case 'sales':
                row += `
                    <td>${formatDate(item.sale_date)}</td>
                    <td>${item.invoice_number || 'N/A'}</td>
                    <td>Sales</td>
                    <td>${item.items_details || 'N/A'}</td>
                    <td>PKR ${formatNumber(item.sale_total_amount)}</td>
                    <td><span class="badge bg-${getStatusColor(item.payment_status)}">${item.payment_status || 'N/A'}</span></td>
                    <td><button class="btn btn-sm btn-info show-details" data-type="sales" data-id="${item.sale_id}"><i class="fas fa-info-circle"></i> Details</button></td>
                `;
                break;
            case 'purchases':
                row += `
                    <td>${formatDate(item.purchase_date)}</td>
                    <td>${item.purchase_number || 'N/A'}</td>
                    <td>Purchase</td>
                    <td>${item.materials_details || 'N/A'}</td>
                    <td>PKR ${formatNumber(item.purchase_total_amount)}</td>
                    <td><span class="badge bg-${getStatusColor(item.payment_status)}">${item.payment_status || 'N/A'}</span></td>
                    <td><button class="btn btn-sm btn-info show-details" data-type="purchases" data-id="${item.purchase_id}"><i class="fas fa-info-circle"></i> Details</button></td>
                `;
                break;
            case 'inventory':
                row += `
                    <td>${formatDate(item.updated_at)}</td>
                    <td>${item.item_number || 'N/A'}</td>
                    <td>Inventory</td>
                    <td>${item.item_name || 'N/A'}</td>
                    <td>${item.current_stock} ${item.unit_of_measure}</td>
                    <td><span class="badge bg-${getStockStatusColor(item.stock_status)}">${item.stock_status || 'N/A'}</span></td>
                    <td><button class="btn btn-sm btn-info show-details" data-type="inventory" data-id="${item.item_id}"><i class="fas fa-info-circle"></i> Details</button></td>
                `;
                break;
            case 'customers':
                row += `
                    <td>${formatDate(item.created_at)}</td>
                    <td>${item.customer_name || 'N/A'}</td>
                    <td>Customer</td>
                    <td>${item.phone || 'N/A'}</td>
                    <td>PKR ${formatNumber(item.total_spent)}</td>
                    <td><span class="badge bg-${getStatusColor(item.status)}">${item.status || 'N/A'}</span></td>
                    <td><button class="btn btn-sm btn-info show-details" data-type="customers" data-id="${item.customer_id}"><i class="fas fa-info-circle"></i> Details</button></td>
                `;
                break;
            case 'vendors':
                row += `
                    <td>${formatDate(item.created_at)}</td>
                    <td>${item.vendor_name || 'N/A'}</td>
                    <td>Vendor</td>
                    <td>${item.phone || 'N/A'}</td>
                    <td>PKR ${formatNumber(item.total_spent)}</td>
                    <td><span class="badge bg-${getStatusColor(item.status)}">${item.status || 'N/A'}</span></td>
                    <td><button class="btn btn-sm btn-info show-details" data-type="vendors" data-id="${item.vendor_id}"><i class="fas fa-info-circle"></i> Details</button></td>
                `;
                break;
        }
        
        row += '</tr>';
        return row;
    }

    // Update summary cards with report data
    function updateSummaryCards(data) {
        // Get date range for profit margin calculation
        const dateRange = $('#dateRange').val();
        let startDate, endDate;
        
        if (dateRange === 'custom') {
            startDate = $('#startDate').val();
            endDate = $('#endDate').val();
        } else {
            endDate = new Date();
            startDate = new Date();
            startDate.setDate(endDate.getDate() - (parseInt(dateRange) || 30));
        }

        // Format dates for API
        const formatDate = (date) => {
            return date instanceof Date ? date.toISOString().split('T')[0] : date;
        };

        // Fetch profit margin data
        $.ajax({
            url: '../api/fetchReportData.php',
            method: 'GET',
            data: {
                action: 'get_profit_margin',
                start_date: formatDate(startDate),
                end_date: formatDate(endDate)
            },
            success: function(response) {
                if (response.status === 'success') {
                    const totalSales = parseFloat(response.data.total_sales) || 0;
                    const totalPurchases = parseFloat(response.data.total_purchases) || 0;
                    const profit = totalSales - totalPurchases;
                    
                    // Calculate profit margin
                    let profitMargin = 0;
                    if (totalPurchases > 0) {
                        profitMargin = (profit / totalPurchases) * 100;
                    } else if (profit > 0) {
                        // If there are no purchases but we have profit, show 100%
                        profitMargin = 100;
                    }
                    
                    // Update both percentage and value
                    $('#profitMargin').text(profitMargin.toFixed(2) + '%');
                    $('#profitValue').text('PKR ' + formatNumber(profit));
                    
                    // Update period text
                    const periodText = dateRange === 'custom' 
                        ? `${formatDate(startDate)} to ${formatDate(endDate)}`
                        : `Last ${dateRange || 30} Days`;
                    $('#profitMarginPeriod').text(periodText);
                }
            },
            error: function(xhr, status, error) {
            }
        });

        // Update Inventory Value
        const categoryId = $('#category').val();
        $.ajax({
            url: '../api/fetchReportData.php',
            method: 'GET',
            data: {
                action: 'get_inventory_value',
                category_id: categoryId || null
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#inventoryValue').text('PKR ' + formatNumber(response.data.total_value));
                    
                    // Update category text
                    const categoryText = categoryId 
                        ? $('#category option:selected').text()
                        : 'All Categories';
                    $('#inventoryValueCategory').text(categoryText);
                }
            },
            error: function(xhr, status, error) {
            }
        });
    }

    // Calculate profit margin
    function calculateProfitMargin(sales, purchases) {
        if (purchases === 0) return 0;
        const profit = sales - purchases;
        return (profit / purchases) * 100;
    }

    // Update charts with report data
    function updateCharts(response) {
        if (!response) {
            return;
        }

        // Update sales vs purchases trend chart
        if (response.sales_trend && response.purchases_trend) {
            updateTrendChart(response.sales_trend, response.purchases_trend);
        }

        // Update category distribution chart
        if (response.category_summary) {
            updateCategoryChart(response.category_summary);
        }
    }

    // Update trend chart
    function updateTrendChart(salesTrend, purchasesTrend) {
        const ctx = document.getElementById('salesPurchasesChart');
        if (!ctx) {
            return;
        }

        if (window.trendChart) {
            window.trendChart.destroy();
            window.trendChart = null;
        }

        // Ensure we have valid data
        const salesLabels = salesTrend.labels || [];
        const salesData = salesTrend.data || [];
        const purchaseLabels = purchasesTrend.labels || [];
        const purchaseData = purchasesTrend.data || [];

        // Merge labels to get all unique dates
        const allLabels = [...new Set([...salesLabels, ...purchaseLabels])].sort();

        // Create datasets with proper data mapping
        const salesDataset = {
            label: 'Sales',
            data: allLabels.map(label => {
                const index = salesLabels.indexOf(label);
                return index !== -1 ? salesData[index] : 0;
            }),
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.1,
            fill: false
        };

        const purchaseDataset = {
            label: 'Purchases',
            data: allLabels.map(label => {
                const index = purchaseLabels.indexOf(label);
                return index !== -1 ? purchaseData[index] : 0;
            }),
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            tension: 0.1,
            fill: false
        };

        try {
            window.trendChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [salesDataset, purchaseDataset]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'PKR ' + formatNumber(value);
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
        }
    }

    // Update category chart
    function updateCategoryChart(categoryData) {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        if (window.categoryChart) {
            window.categoryChart.destroy();
            window.categoryChart = null;
        }

        const labels = Object.keys(categoryData);
        const data = labels.map(label => categoryData[label].count);

        window.categoryChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // Show empty state message
    function showEmptyState(message) {
        $("#reportTable tbody").html(`
            <tr>
                <td colspan="7" class="text-center">
                    <div class="empty-state py-4">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                        <h5 class="text-muted">${message || 'No Data Available'}</h5>
                        <p class="text-center">Select filters above to generate your report</p>
                    </div>
                </td>
            </tr>
        `);
        $("#recordCount").text('No records found');
        
        // Reset summary cards
        updateSummaryCards(null);
    }

    // Get status color for badges
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'partial': 'info',
            'paid': 'success',
            'active': 'success',
            'inactive': 'danger'
        };
        return colors[status?.toLowerCase()] || 'secondary';
    }

    // Get stock status color
    function getStockStatusColor(status) {
        const colors = {
            'out_of_stock': 'danger',
            'low_stock': 'warning',
            'sufficient': 'success'
        };
        return colors[status?.toLowerCase()] || 'secondary';
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Format number
    function formatNumber(number) {
        if (isNaN(number)) return '0.00';
        return parseFloat(number).toFixed(2);
    }

    // Handle Export Dropdown Clicks (direct export, no modal)
    $(document).on('click', '.export-action', function(e) {
        e.preventDefault();
        const format = $(this).data('export-format');
        if (!format || (format !== 'pdf' && format !== 'csv')) {
            toastr.warning('Please select a valid export format');
            return;
        }
        if (!currentReportType) {
            toastr.warning('Please select a report type and load data first');
            return;
        }
        // Gather current filters
        const filters = getFilters();
        // Show loading toast
        toastr.info('Exporting report as ' + format.toUpperCase() + '...');
        $.ajax({
            url: "../api/exportReport.php",
            method: "POST",
            data: {
                ...filters,
                export_format: format,
                report_type: currentReportType
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response, status, xhr) {
                // Get filename from header
                const disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = 'report.' + format;
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = decodeURIComponent(matches[1].replace(/['"]/g, ''));
                    }
                }
                // Download file
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
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred during export.');
            }
        });
    });

    // Show details modal on button click
    $(document).on('click', '.show-details', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        let item = null;
        switch(type) {
            case 'sales':
                item = (originalReportData.sales || []).find(x => x.sale_id == id);
                break;
            case 'purchases':
                item = (originalReportData.purchases || []).find(x => x.purchase_id == id);
                break;
            case 'inventory':
                item = (originalReportData.items || []).find(x => x.item_id == id);
                break;
            case 'customers':
                item = (originalReportData.customers || []).find(x => x.customer_id == id);
                break;
            case 'vendors':
                item = (originalReportData.vendors || []).find(x => x.vendor_id == id);
                break;
        }
        if (!item) {
            $('#recordDetailBody').html('<div class="text-danger">Record not found.</div>');
        } else {
            $('#recordDetailBody').html(generateDetailHtml(type, item));
        }
        $('#recordDetailModal').modal('show');
    });

    // Generate detail HTML for modal
    function generateDetailHtml(type, item) {
        let html = '<div class="container-fluid">';
        switch(type) {
            case 'sales':
                html += `<h5>Sale #${item.invoice_number}</h5><hr>`;
                html += `<p><strong>Date:</strong> ${formatDate(item.sale_date)}</p>`;
                html += `<p><strong>Customer:</strong> ${item.customer_name}</p>`;
                html += `<p><strong>Items:</strong> ${item.items_details}</p>`;
                html += `<p><strong>Total Amount:</strong> PKR ${formatNumber(item.sale_total_amount)}</p>`;
                html += `<p><strong>Payment Status:</strong> <span class="badge bg-${getStatusColor(item.payment_status)}">${item.payment_status}</span></p>`;
                html += `<p><strong>Notes:</strong> ${item.notes || '-'}</p>`;
                break;
            case 'purchases':
                html += `<h5>Purchase #${item.purchase_number}</h5><hr>`;
                html += `<p><strong>Date:</strong> ${formatDate(item.purchase_date)}</p>`;
                html += `<p><strong>Vendor:</strong> ${item.vendor_name}</p>`;
                html += `<p><strong>Materials:</strong> ${item.materials_details}</p>`;
                html += `<p><strong>Total Amount:</strong> PKR ${formatNumber(item.purchase_total_amount)}</p>`;
                html += `<p><strong>Payment Status:</strong> <span class="badge bg-${getStatusColor(item.payment_status)}">${item.payment_status}</span></p>`;
                html += `<p><strong>Delivery Status:</strong> ${item.delivery_status || '-'}</p>`;
                html += `<p><strong>Notes:</strong> ${item.notes || '-'}</p>`;
                break;
            case 'inventory':
                html += `<h5>Item: ${item.item_name}</h5><hr>`;
                html += `<p><strong>Item Number:</strong> ${item.item_number}</p>`;
                html += `<p><strong>Category:</strong> ${item.category_name}</p>`;
                html += `<p><strong>Current Stock:</strong> ${item.current_stock} ${item.unit_of_measure}</p>`;
                html += `<p><strong>Minimum Stock:</strong> ${item.minimum_stock}</p>`;
                html += `<p><strong>Status:</strong> <span class="badge bg-${getStockStatusColor(item.stock_status)}">${item.stock_status}</span></p>`;
                html += `<p><strong>Last Updated:</strong> ${formatDate(item.updated_at)}</p>`;
                html += `<p><strong>Description:</strong> ${item.description || '-'}</p>`;
                break;
            case 'customers':
                html += `<h5>Customer: ${item.customer_name}</h5><hr>`;
                html += `<p><strong>Phone:</strong> ${item.phone}</p>`;
                html += `<p><strong>Email:</strong> ${item.email || '-'}</p>`;
                html += `<p><strong>Address:</strong> ${item.address || '-'}</p>`;
                html += `<p><strong>City:</strong> ${item.city || '-'}</p>`;
                html += `<p><strong>Status:</strong> <span class="badge bg-${getStatusColor(item.status)}">${item.status}</span></p>`;
                html += `<p><strong>Total Spent:</strong> PKR ${formatNumber(item.total_spent)}</p>`;
                html += `<p><strong>Total Orders:</strong> ${item.total_orders}</p>`;
                break;
            case 'vendors':
                html += `<h5>Vendor: ${item.vendor_name}</h5><hr>`;
                html += `<p><strong>Phone:</strong> ${item.phone}</p>`;
                html += `<p><strong>Email:</strong> ${item.email || '-'}</p>`;
                html += `<p><strong>Address:</strong> ${item.address || '-'}</p>`;
                html += `<p><strong>City:</strong> ${item.city || '-'}</p>`;
                html += `<p><strong>Status:</strong> <span class="badge bg-${getStatusColor(item.status)}">${item.status}</span></p>`;
                html += `<p><strong>Total Spent:</strong> PKR ${formatNumber(item.total_spent)}</p>`;
                html += `<p><strong>Total Orders:</strong> ${item.total_orders}</p>`;
                break;
        }
        html += '</div>';
        return html;
    }

    // Handle report type change
    $("#reportType").change(function() {
        const type = $(this).val();
        if (type && type !== '') {
            currentReportType = type;
            // Remove automatic data loading
            showEmptyState("Click 'Apply Filters' to view data");
        } else {
            showEmptyState("Please select a report type to view data");
        }
    });

    // Handle reset button click
    $("#resetBtn").click(function() {
        // Reset all form fields
        $("#reportFilters")[0].reset();
        // Hide custom date range if visible
        $(".custom-date-range").hide();
        // Show empty state
        showEmptyState("Please select filters and click 'Apply Filters' to view data");
        // Reset current report type
        currentReportType = '';
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});