// Chart utilities
const Charts = {
    // Initialize chart if canvas exists
    initChart: function(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas element with id '${canvasId}' not found.`);
            return null;
        }

        try {
            const ctx = canvas.getContext('2d');
            return new Chart(ctx, config);
        } catch (error) {
            console.error(`Error initializing chart for '${canvasId}':`, error);
            return null;
        }
    },

    // Default chart options
    defaultOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        }
    },

    // Helper to get responsive font sizes
    getResponsiveFontSizes: function() {
        let titleFontSize = 18;
        let labelFontSize = 14;
        let tickFontSize = 12;
        let tooltipTitleFontSize = 16;
        let tooltipBodyFontSize = 14;

        if (window.innerWidth <= 768) { 
            titleFontSize = 14;
            labelFontSize = 10;
            tickFontSize = 8;
            tooltipTitleFontSize = 12;
            tooltipBodyFontSize = 10;
        } else if (window.innerWidth <= 992) { 
            titleFontSize = 16;
            labelFontSize = 12;
            tickFontSize = 10;
            tooltipTitleFontSize = 14;
            tooltipBodyFontSize = 12;
        }
        return { titleFontSize, labelFontSize, tickFontSize, tooltipTitleFontSize, tooltipBodyFontSize };
    },

    // Helper to get responsive doughnut chart font sizes
    getDoughnutResponsiveFontSizes: function() {
        let legendFontSize = 14;
        let tooltipTitleFontSize = 14;
        let tooltipBodyFontSize = 12;
        let chartTitleFontSize = 22;

        if (window.innerWidth <= 768) { 
            legendFontSize = 10;
            tooltipTitleFontSize = 10;
            tooltipBodyFontSize = 9;
            chartTitleFontSize = 16;
        } else if (window.innerWidth <= 992) { 
            legendFontSize = 12;
            tooltipTitleFontSize = 12;
            tooltipBodyFontSize = 11;
            chartTitleFontSize = 18;
        }
        return { legendFontSize, tooltipTitleFontSize, tooltipBodyFontSize, chartTitleFontSize };
    },

    // Create line chart
    createLineChart: function(canvasId, data, options = {}) {
        const config = {
            type: 'line',
            data: data,
            options: { ...this.defaultOptions, ...options }
        };
        return this.initChart(canvasId, config);
    },

    // Create sales overview chart
    createSalesChart: function(canvasId) {
        let { titleFontSize, labelFontSize, tickFontSize, tooltipTitleFontSize, tooltipBodyFontSize } = this.getResponsiveFontSizes();

        const config = {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Number of Sales',
                        data: [],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointHoverBorderColor: '#4361ee',
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Total Amount (PKR)',
                        data: [],
                        borderColor: '#4cc9f0',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#4cc9f0',
                        pointBorderColor: '#fff',
                        pointHoverBorderColor: '#4cc9f0',
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                stacked: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Sales Overview (Last 6 Months)',
                        font: {
                            size: titleFontSize,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'y1') {
                                    label += 'PKR ' + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: tooltipTitleFontSize,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: tooltipBodyFontSize
                        },
                        cornerRadius: 4,
                        padding: 10
                    }
                },
                scales: {
                    x: {
                         ticks: {
                            font: {
                                size: tickFontSize
                            }
                         },
                         grid: {
                             display: false
                         }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Sales',
                            font: {
                                size: labelFontSize,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: tickFontSize
                            }
                        },
                        grid: {
                           color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Total Amount (PKR)',
                             font: {
                                size: labelFontSize,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: tickFontSize
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        };

        const chart = this.initChart(canvasId, config);

        // Add resize event listener to update chart font sizes dynamically
        window.addEventListener('resize', () => {
            let { titleFontSize: newTitleFontSize, labelFontSize: newLabelFontSize, tickFontSize: newTickFontSize, tooltipTitleFontSize: newTooltipTitleFontSize, tooltipBodyFontSize: newTooltipBodyFontSize } = this.getResponsiveFontSizes();
            
            if (chart) {
                // Update title font size
                if (chart.options.plugins && chart.options.plugins.title && chart.options.plugins.title.font) {
                    chart.options.plugins.title.font.size = newTitleFontSize;
                }

                // Update tooltip font sizes
                if (chart.options.plugins && chart.options.plugins.tooltip) {
                    if (chart.options.plugins.tooltip.titleFont) {
                        chart.options.plugins.tooltip.titleFont.size = newTooltipTitleFontSize;
                    }
                    if (chart.options.plugins.tooltip.bodyFont) {
                        chart.options.plugins.tooltip.bodyFont.size = newTooltipBodyFontSize;
                    }
                }

                // Update scales font sizes
                if (chart.options.scales) {
                    if (chart.options.scales.x && chart.options.scales.x.ticks && chart.options.scales.x.ticks.font) {
                        chart.options.scales.x.ticks.font.size = newTickFontSize;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.title && chart.options.scales.y.title.font) {
                        chart.options.scales.y.title.font.size = newLabelFontSize;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.ticks && chart.options.scales.y.ticks.font) {
                        chart.options.scales.y.ticks.font.size = newTickFontSize;
                    }
                    if (chart.options.scales.y1 && chart.options.scales.y1.title && chart.options.scales.y1.title.font) {
                        chart.options.scales.y1.title.font.size = newLabelFontSize;
                    }
                    if (chart.options.scales.y1 && chart.options.scales.y1.ticks && chart.options.scales.y1.ticks.font) {
                        chart.options.scales.y1.ticks.font.size = newTickFontSize;
                    }
                }
                chart.update();
            }
        });

        // Fetch and update chart data
        fetch('../api/fetchSalesChartData.php')
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    chart.data.labels = result.data.labels;
                    chart.data.datasets[0].data = result.data.datasets[0].data;
                    chart.data.datasets[1].data = result.data.datasets[1].data;
                    chart.update();
                } else {
                    console.error('Error fetching sales chart data:', result.message);
                }
            })
            .catch(error => {
                console.error('Error fetching sales chart data:', error);
            });

        return chart;
    },

    // Create bar chart
    createBarChart: function(canvasId, data, options = {}) {
        const config = {
            type: 'bar',
            data: data,
            options: { ...this.defaultOptions, ...options }
        };
        return this.initChart(canvasId, config);
    },

    // Create pie chart
    createPieChart: function(canvasId, data, options = {}) {
        const config = {
            type: 'pie',
            data: data,
            options: { ...this.defaultOptions, ...options }
        };
        return this.initChart(canvasId, config);
    },

    // Create doughnut chart with product details on hover
    createDoughnutChart: function(canvasId, data, options = {}, categoryProducts = []) {
        let { legendFontSize, tooltipTitleFontSize, tooltipBodyFontSize, chartTitleFontSize } = this.getDoughnutResponsiveFontSizes();

        const config = {
            type: 'doughnut',
            data: data,
            options: { 
                ...this.defaultOptions,
                plugins: {
                    ...this.defaultOptions.plugins,
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            font: {
                                size: legendFontSize
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => (parseFloat(a) || 0) + (parseFloat(b) || 0), 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} units (${percentage}%)`;
                            },
                            afterLabel: function(context) {
                                // Get products for this category
                                const categoryIndex = context.dataIndex;
                                const products = categoryProducts[categoryIndex] || [];
                                
                                if (products.length === 0) {
                                    return [];
                                }

                                // Create lines for each product
                                const productLines = ['', 'Products:'];
                                products.forEach(product => {
                                    productLines.push(`• ${product.name}: ${product.stock} units`);
                                });
                                
                                // Limit to top 10 products to avoid overwhelming tooltip
                                if (products.length > 10) {
                                    productLines.push(`... and ${products.length - 10} more products`);
                                }
                                
                                return productLines;
                            }
                        },
                        displayColors: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        caretPadding: 10,
                        padding: 12,
                        bodyFont: {
                            size: tooltipBodyFontSize
                        },
                        titleFont: {
                            size: tooltipTitleFontSize,
                            weight: 'bold'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Inventory',
                        font: {
                            size: chartTitleFontSize,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    }
                },
                cutout: '60%',
                ...options
            }
        };
        const chart = this.initChart(canvasId, config);

        // Add resize event listener for doughnut chart
        window.addEventListener('resize', () => {
            let { legendFontSize: newLegendFontSize, tooltipTitleFontSize: newTooltipTitleFontSize, tooltipBodyFontSize: newTooltipBodyFontSize, chartTitleFontSize: newChartTitleFontSize } = this.getDoughnutResponsiveFontSizes();
            
            if (chart) {
                // Update legend font size
                if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels && chart.options.plugins.legend.labels.font) {
                    chart.options.plugins.legend.labels.font.size = newLegendFontSize;
                }

                // Update tooltip font sizes
                if (chart.options.plugins && chart.options.plugins.tooltip) {
                    if (chart.options.plugins.tooltip.titleFont) {
                        chart.options.plugins.tooltip.titleFont.size = newTooltipTitleFontSize;
                    }
                    if (chart.options.plugins.tooltip.bodyFont) {
                        chart.options.plugins.tooltip.bodyFont.size = newTooltipBodyFontSize;
                    }
                }

                // Update chart title font size
                if (chart.options.plugins && chart.options.plugins.title && chart.options.plugins.title.font) {
                    chart.options.plugins.title.font.size = newChartTitleFontSize;
                }
                chart.update();
            }
        });
        return chart;
    }
};

// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sales chart
    const salesChart = document.getElementById('salesChart');
    if (salesChart) {
        Charts.createSalesChart('salesChart');
    }

    // Initialize inventory chart
    const inventoryChart = document.getElementById('inventoryChart');
    if (inventoryChart) {
        // Fetch inventory data from API
        $.ajax({
            url: '../api/fetchChartData.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Charts.createDoughnutChart('inventoryChart', {
                        labels: response.data.labels,
                        datasets: response.data.datasets
                    }, {}, response.data.categoryProducts);
                } else {
                    console.error('Failed to fetch chart data:', response.message);
                    // Show error message in chart area
                    const canvas = document.getElementById('inventoryChart');
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#666';
                    ctx.font = '16px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Failed to load chart data', canvas.width/2, canvas.height/2);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching chart data:', error);
                // Show error message in chart area
                const canvas = document.getElementById('inventoryChart');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#666';
                    ctx.font = '16px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Error loading chart data', canvas.width/2, canvas.height/2);
                }
            }
        });
    }

    // Update Total Inventory card
    $.ajax({
        url: '/DMS/api/fetchInventoryData.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Update the Total Inventory card
                const totalInventoryElement = document.getElementById('totalInventory');
                if (totalInventoryElement) {
                    // Format the number with commas and round off
                    const roundedStock = Math.round(response.data.total_stock);
                    const formattedStock = roundedStock.toLocaleString();
                    totalInventoryElement.textContent = `${formattedStock} Units`;
                }
            } else {
                console.error('Failed to fetch inventory stats:', response.message);
                const totalInventoryElement = document.getElementById('totalInventory');
                if (totalInventoryElement) {
                    totalInventoryElement.textContent = 'Error loading data';
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching inventory stats:', error);
            const totalInventoryElement = document.getElementById('totalInventory');
            if (totalInventoryElement) {
                totalInventoryElement.textContent = 'Error loading data';
            }
        }
    });

    // Update Customers card
    $.ajax({
        url: '/DMS/api/fetchCustomerData.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const customerElement = document.getElementById('totalCustomers');
                if (customerElement) {
                    const formattedCustomers = response.data.total_customers.toLocaleString();
                    customerElement.textContent = formattedCustomers;
                }
            } else {
                console.error('Failed to fetch customer stats:', response.message);
                const customerElement = document.getElementById('totalCustomers');
                if (customerElement) {
                    customerElement.textContent = 'Error';
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching customer stats:', error);
            const customerElement = document.getElementById('totalCustomers');
            if (customerElement) {
                customerElement.textContent = 'Error';
            }
        }
    });

    // Update Vendors card
    $.ajax({
        url: '/DMS/api/fetchVendorData.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const vendorElement = document.getElementById('totalVendors');
                if (vendorElement) {
                    const formattedVendors = response.data.total_vendors.toLocaleString();
                    vendorElement.textContent = formattedVendors;
                }
            } else {
                console.error('Failed to fetch vendor stats:', response.message);
                const vendorElement = document.getElementById('totalVendors');
                if (vendorElement) {
                    vendorElement.textContent = 'Error';
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching vendor stats:', error);
            const vendorElement = document.getElementById('totalVendors');
            if (vendorElement) {
                vendorElement.textContent = 'Error';
            }
        }
    });

    // Update Purchases card
    $.ajax({
        url: '/DMS/api/fetchPurchaseData.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const purchaseElement = document.getElementById('totalPurchases');
                if (purchaseElement) {
                    const formattedAmount = new Intl.NumberFormat('en-PK', {
                        style: 'currency',
                        currency: 'PKR',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(response.data.total_purchases);
                    
                    purchaseElement.textContent = formattedAmount;
                }
            } else {
                console.error('Failed to fetch purchase stats:', response.message);
                const purchaseElement = document.getElementById('totalPurchases');
                if (purchaseElement) {
                    purchaseElement.textContent = 'Error';
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching purchase stats:', error);
            const purchaseElement = document.getElementById('totalPurchases');
            if (purchaseElement) {
                purchaseElement.textContent = 'Error';
            }
        }
    });
});

$('#addVendorModal').on('show.bs.modal', function () {
    $(this).find('select[name="status"]').val('active');
});

$('#edit_status').val($(this).data('status'));