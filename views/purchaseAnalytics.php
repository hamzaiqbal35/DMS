<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../inc/config/auth.php"; // Ensure user authentication
require_jwt_auth(); // Enforce JWT authentication
require_once "../inc/header.php"; // Include header
require_once "../inc/navigation.php"; // Include sidebar navigation
?>

<div class="page-wrapper">
    <main class="main-content">
        <div class="container-fluid fade-in">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-chart-line text-dark me-2"></i>Purchase Analytics</h2>
                </div>
            </div>

            <!-- Monthly Purchase Trends -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Monthly Purchase Trends</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyPurchasesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Vendors and Items -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Top Vendors by Value</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="topVendorsTable">
                                    <thead>
                                        <tr>
                                            <th>Vendor</th>
                                            <th>Purchases</th>
                                            <th>Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Most Purchased Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="topItemsTable">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Purchases</th>
                                            <th>Total Quantity</th>
                                            <th>Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Delivery Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; display: flex; justify-content: center; align-items: center;">
                                <canvas id="deliveryStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Payment Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; display: flex; justify-content: center; align-items: center;">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../inc/footer.php'; ?>

<!-- Scripts -->
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/scripts.js"></script>
<script src="../assets/js/animations.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Styles -->
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
<link href="../assets/css/styles.css" rel="stylesheet">
<link href="../assets/css/animations.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch analytics data
    $.ajax({
        url: '../model/purchase/analyzePurchases.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const data = response.data;
                
                // Initialize Monthly Purchases Chart
                const monthlyCtx = document.getElementById('monthlyPurchasesChart').getContext('2d');
                
                // Sort monthly data chronologically (oldest to newest for proper trend display)
                const sortedMonthlyData = data.monthly_purchases.sort((a, b) => {
                    return new Date(a.month + '-01') - new Date(b.month + '-01');
                });
                
                // Format month labels for better display
                const formattedLabels = sortedMonthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
                });
                
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: formattedLabels,
                        datasets: [{
                            label: 'Total Amount (PKR)',
                            data: sortedMonthlyData.map(item => parseFloat(item.total_amount)),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(75, 192, 192)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Monthly Purchase Trends (Last 12 Months)'
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
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });

                // Populate Top Vendors Table
                const vendorsTable = $('#topVendorsTable tbody');
                data.top_vendors.forEach(vendor => {
                    vendorsTable.append(`
                        <tr>
                            <td>${vendor.vendor_name}</td>
                            <td>${vendor.purchase_count}</td>
                            <td>PKR ${parseFloat(vendor.total_value).toLocaleString()}</td>
                        </tr>
                    `);
                });

                // Populate Top Items Table
                const itemsTable = $('#topItemsTable tbody');
                data.top_items.forEach(item => {
                    itemsTable.append(`
                        <tr>
                            <td>${item.material_name}</td>
                            <td>${item.purchase_count}</td>
                            <td>${parseFloat(item.total_quantity).toLocaleString()}</td>
                            <td>PKR ${parseFloat(item.total_value).toLocaleString()}</td>
                        </tr>
                    `);
                });

                // Initialize Delivery Status Chart
                const deliveryCtx = document.getElementById('deliveryStatusChart').getContext('2d');
                new Chart(deliveryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.status_distribution.map(item => item.delivery_status),
                        datasets: [{
                            data: data.status_distribution.map(item => item.count),
                            backgroundColor: [
                                'rgb(255, 99, 132)',
                                'rgb(54, 162, 235)',
                                'rgb(255, 205, 86)',
                                'rgb(75, 192, 192)',
                                'rgb(153, 102, 255)'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });

                // Initialize Payment Status Chart
                const paymentCtx = document.getElementById('paymentStatusChart').getContext('2d');
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.payment_distribution.map(item => item.payment_status),
                        datasets: [{
                            data: data.payment_distribution.map(item => item.count),
                            backgroundColor: [
                                'rgb(255, 99, 132)',
                                'rgb(54, 162, 235)',
                                'rgb(255, 205, 86)',
                                'rgb(75, 192, 192)'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            } else {
                toastr.error('Failed to load analytics data');
            }
        },
        error: function() {
            toastr.error('Error loading analytics data');
        }
    });
});
</script>