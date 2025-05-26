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

    // Create doughnut chart
    createDoughnutChart: function(canvasId, data, options = {}) {
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
                    }
                },
                cutout: '60%'
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
        Charts.createDoughnutChart('inventoryChart', {
            labels: ['Raw Materials', 'Finished Goods', 'Work in Progress', 'Packaging'],
            datasets: [{
                data: [35, 25, 20, 20],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        });
    }
});
