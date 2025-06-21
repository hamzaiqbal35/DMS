// Independent Chart Management System
const ReportCharts = {
    charts: {
        salesPurchasesChart: null,
        categoryChart: null,
        stockChart: null,
        paymentChart: null
    },

    // Initialize all charts
    initCharts() {
        this.initSalesPurchasesChart();
        this.initCategoryChart();
        this.initStockChart();
        this.initPaymentChart();
        this.loadAllChartData();
    },

    // Initialize Sales vs Purchases Trend Chart
    initSalesPurchasesChart() {
        const ctx = document.getElementById('salesPurchasesChart').getContext('2d');
        this.charts.salesPurchasesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Sales',
                        data: [],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Purchases',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'PKR'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'PKR ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    },

    // Initialize Category Distribution Chart
    initCategoryChart() {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        this.charts.categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    },

    // Initialize Stock Level Chart
    initStockChart() {
        const ctx = document.getElementById('stockChart').getContext('2d');
        this.charts.stockChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Current Stock',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.8)'
                    },
                    {
                        label: 'Minimum Stock',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.8)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    // Initialize Payment Status Chart
    initPaymentChart() {
        const ctx = document.getElementById('paymentChart').getContext('2d');
        this.charts.paymentChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Partial', 'Pending'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    },

    // Load all chart data
    loadAllChartData() {
        this.loadSalesPurchasesData();
        this.loadCategoryData();
        this.loadStockData();
        this.loadPaymentData();
    },

    // Load Sales vs Purchases data
    loadSalesPurchasesData() {
        const dateRange = $('#chartDateRange').val() || '30';
        $.ajax({
            url: '../api/fetchChartData.php',
            method: 'GET',
            data: {
                type: 'trend',
                period: dateRange
            },
            success: (response) => {
                if (response.status === 'success') {
                    this.updateSalesPurchasesChart(response.data.sales, response.data.purchases);
                }
            }
        });
    },

    // Load Category Distribution data
    loadCategoryData() {
        // Get the selected type (count, stock, value)
        var chartType = 'count';
        var select = document.getElementById('categoryChartType');
        if (select) chartType = select.value;
        $.ajax({
            url: '../api/fetchChartData.php',
            method: 'GET',
            data: {
                type: 'category'
            },
            success: (response) => {
                if (response.status === 'success') {
                    this.updateCategoryChart(response.data, chartType);
                }
            }
        });
    },

    // Load Stock Level data
    loadStockData() {
        var stockFilter = 'all';
        var select = document.getElementById('stockChartFilter');
        if (select) stockFilter = select.value;
        $.ajax({
            url: '../api/fetchChartData.php',
            method: 'GET',
            data: {
                type: 'stock',
                stock_status: stockFilter
            },
            success: (response) => {
                if (response.status === 'success') {
                    this.updateStockChart(response.data);
                }
            }
        });
    },

    // Load Payment Status data
    loadPaymentData() {
        var period = '30';
        var select = document.getElementById('paymentChartPeriod');
        if (select) period = select.value;
        var source = 'combined';
        var radios = document.getElementsByName('paymentStatusSource');
        if (radios) {
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    source = radios[i].value;
                    break;
                }
            }
        }
        $.ajax({
            url: '../api/fetchChartData.php',
            method: 'GET',
            data: {
                type: 'payment',
                period: period,
                source: source
            },
            success: (response) => {
                if (response.status === 'success') {
                    this.updatePaymentChart(response.data, source);
                } else {
                    console.error('Payment chart API error:', response.message);
                    this.showPaymentChartError(response.message || 'Failed to load payment data');
                }
            },
            error: (xhr, status, error) => {
                console.error('Payment chart AJAX error:', error);
                this.showPaymentChartError('Network error loading payment data');
            }
        });
    },

    // Update Sales vs Purchases Chart
    updateSalesPurchasesChart(salesData, purchasesData) {
        const chart = this.charts.salesPurchasesChart;
        if (!chart) return;

        // Collect all unique dates from both sales and purchases
        const dateSet = new Set([
            ...salesData.map(item => item.date),
            ...purchasesData.map(item => item.date)
        ]);
        // Sort dates ascending
        const allDates = Array.from(dateSet).sort();

        // Create a map for quick lookup
        const salesMap = {};
        salesData.forEach(item => { salesMap[item.date] = item.amount; });
        const purchasesMap = {};
        purchasesData.forEach(item => { purchasesMap[item.date] = item.amount; });

        // Build aligned data arrays
        const salesAmounts = allDates.map(date => salesMap[date] || 0);
        const purchaseAmounts = allDates.map(date => purchasesMap[date] || 0);

        chart.data.labels = allDates;
        chart.data.datasets[0].data = salesAmounts;
        chart.data.datasets[1].data = purchaseAmounts;
        chart.update();
    },

    // Update Category Distribution Chart
    updateCategoryChart(categoryData, chartType) {
        const chart = this.charts.categoryChart;
        if (!chart) return;

        const labels = Object.keys(categoryData);
        let values = [];
        let labelText = '';
        if (chartType === 'stock') {
            values = Object.values(categoryData).map(item => item.total_stock);
            labelText = 'By Stock';
        } else if (chartType === 'value') {
            values = Object.values(categoryData).map(item => item.total_value);
            labelText = 'By Value';
        } else {
            values = Object.values(categoryData).map(item => item.count);
            labelText = 'By Count';
        }

        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        if (!chart.options.plugins) chart.options.plugins = {};
        if (!chart.options.plugins.title) chart.options.plugins.title = {};
        chart.options.plugins.title.display = true;
        chart.options.plugins.title.text = `Category Distribution (${labelText})`;
        chart.update();
    },

    // Update Stock Level Chart
    updateStockChart(stockData) {
        const chart = this.charts.stockChart;
        if (!chart) return;

        // Get top 10 items by current stock
        const sortedItems = [...stockData].sort((a, b) => b.current_stock - a.current_stock).slice(0, 10);

        chart.data.labels = sortedItems.map(item => item.item_name);
        chart.data.datasets[0].data = sortedItems.map(item => item.current_stock);
        chart.data.datasets[1].data = sortedItems.map(item => item.minimum_stock);
        chart.update();
    },

    // Show error in Payment Status Chart
    showPaymentChartError(message) {
        const chart = this.charts.paymentChart;
        if (!chart) return;

        // Show error message
        chart.data.labels = ['Error'];
        chart.data.datasets[0].data = [1];
        chart.data.datasets[0].backgroundColor = ['#dc3545'];
        
        if (!chart.options.plugins) chart.options.plugins = {};
        if (!chart.options.plugins.title) chart.options.plugins.title = {};
        chart.options.plugins.title.display = true;
        chart.options.plugins.title.text = message || 'Error Loading Payment Data';
        
        // Disable tooltips for error state
        chart.options.plugins.tooltip = {
            enabled: false
        };
        
        chart.update();
    },

    // Update Payment Status Chart
    updatePaymentChart(paymentData, source) {
        const chart = this.charts.paymentChart;
        if (!chart) return;

        // Check if no data is available
        if (paymentData.no_data) {
            // Show no data message
            chart.data.labels = ['No Data'];
            chart.data.datasets[0].data = [1];
            chart.data.datasets[0].backgroundColor = ['#f8f9fa'];
            
            if (!chart.options.plugins) chart.options.plugins = {};
            if (!chart.options.plugins.title) chart.options.plugins.title = {};
            chart.options.plugins.title.display = true;
            chart.options.plugins.title.text = paymentData.message || 'No Payment Data Available';
            
            // Disable tooltips for no data state
            chart.options.plugins.tooltip = {
                enabled: false
            };
            
            chart.update();
            return;
        }

        // Get dynamic statuses from the data
        const statuses = Object.keys(paymentData);
        const counts = statuses.map(status => paymentData[status].count);
        
        // Generate colors for each status
        const colors = [
            'rgba(75, 192, 192, 0.8)',   // Green for paid
            'rgba(255, 206, 86, 0.8)',   // Yellow for partial
            'rgba(255, 99, 132, 0.8)',   // Red for pending
            'rgba(54, 162, 235, 0.8)',   // Blue for other statuses
            'rgba(153, 102, 255, 0.8)',  // Purple
            'rgba(255, 159, 64, 0.8)',   // Orange
            'rgba(199, 199, 199, 0.8)',  // Gray
            'rgba(83, 102, 255, 0.8)'    // Indigo
        ];

        // Capitalize status names for display
        const labels = statuses.map(status => status.charAt(0).toUpperCase() + status.slice(1));

        chart.data.labels = labels;
        chart.data.datasets[0].data = counts;
        chart.data.datasets[0].backgroundColor = colors.slice(0, statuses.length);
        
        // Re-enable tooltips
        chart.options.plugins.tooltip = {
            enabled: true,
            callbacks: {
                label: function(context) {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                    const status = statuses[context.dataIndex];
                    const amount = paymentData[status]?.amount || 0;
                    return [
                        `${label}: ${value} (${percentage}%)`,
                        `Amount: PKR ${amount.toLocaleString()}`
                    ];
                }
            }
        };
        
        if (!chart.options.plugins) chart.options.plugins = {};
        if (!chart.options.plugins.title) chart.options.plugins.title = {};
        chart.options.plugins.title.display = true;
        
        let title = '';
        if (source === 'sales') title = 'Sales Payment Status';
        else if (source === 'purchases') title = 'Purchases Payment Status';
        else title = 'Combined Sales & Purchases Payment Status';
        
        // Add period info to title
        const period = $('#paymentChartPeriod').val() || '30';
        title += ` (Last ${period} Days)`;
        
        chart.options.plugins.title.text = title;
        chart.update();
    }
};

// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    ReportCharts.initCharts();

    // Add chart filter event listeners
    $('#chartDateRange').change(function() {
        ReportCharts.loadSalesPurchasesData();
    });

    // Listen for payment status source toggle
    $(document).on('change', 'input[name="paymentStatusSource"]', function() {
        ReportCharts.loadPaymentData();
    });

    // Listen for payment period change
    $('#paymentChartPeriod').change(function() {
        ReportCharts.loadPaymentData();
    });

    // Listen for stock chart filter change
    $('#stockChartFilter').change(function() {
        ReportCharts.loadStockData();
    });

    // Refresh charts every 5 minutes
    setInterval(() => {
        ReportCharts.loadAllChartData();
    }, 300000);
});
