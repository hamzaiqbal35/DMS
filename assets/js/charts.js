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

    // Create line chart
    createLineChart: function(canvasId, data, options = {}) {
        const config = {
            type: 'line',
            data: data,
            options: { ...this.defaultOptions, ...options }
        };
        return this.initChart(canvasId, config);
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
                                size: 12
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
                        displayColors: false, // Hide color indicators for cleaner look
                        backgroundColor: 'rgba(0, 0, 0, 0.9)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        caretPadding: 10,
                        padding: 12,
                        bodyFont: {
                            size: 12
                        },
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Inventory Distribution',
                        font: {
                            size: 16,
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
        return this.initChart(canvasId, config);
    }
};

// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Sales trend chart
    const salesChart = document.getElementById('salesChart');
    if (salesChart) {
        Charts.createLineChart('salesChart', {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        });
    }

    // Inventory distribution doughnut chart
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
                    }, {}, response.data.categoryProducts); // Pass products data
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
});

$('#addVendorModal').on('show.bs.modal', function () {
    $(this).find('select[name="status"]').val('active');
});

$('#edit_status').val($(this).data('status'));