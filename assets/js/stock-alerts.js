$(document).ready(function () {
    fetchStockAlerts();
    
    // Refresh stock alerts
    $('#refreshStockAlerts').on('click', function() {
        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Refreshing...');
        btn.prop('disabled', true);
        
        fetchStockAlerts();
        
        setTimeout(function() {
            btn.html('<i class="fas fa-sync-alt me-2"></i> Refresh Alerts');
            btn.prop('disabled', false);
        }, 1000);
    });

    function fetchStockAlerts() {
        $.ajax({
            url: '../model/inventory/fetchStockAlerts.php',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                const tbody = $('#stockAlertTable tbody');
                tbody.empty();

                if (res.status === 'success') {
                    if (res.data.length > 0) {
                        $('#emptyState').addClass('d-none');
                        
                        res.data.forEach(item => {
                            const rowClass = item.current_stock < item.minimum_stock ? 'stock-low' : '';
                            const stockStatus = item.current_stock < item.minimum_stock ? 
                                `<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> Low Stock</span>` : 
                                `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Adequate</span>`;
                            
                            tbody.append(`
                                <tr class="${rowClass}">
                                    <td><span class="badge badge-id">${item.item_number}</span></td>
                                    <td><strong>${item.item_name}</strong></td>
                                    <td>${item.category_name}</td>
                                    <td>${item.unit_of_measure}</td>
                                    <td>₨ ${parseFloat(item.unit_price).toLocaleString()}</td>
                                    <td><strong class="${item.current_stock < item.minimum_stock ? 'text-danger' : ''}">${item.current_stock}</strong></td>
                                    <td>${item.minimum_stock}</td>
                                    <td>
                                        ${stockStatus}
                                        <span class="badge bg-${item.status === 'active' ? 'info' : 'secondary'} ms-1">${item.status}</span>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        $('#emptyState').removeClass('d-none');
                    }
                } else if (res.status === 'empty') {
                    $('#emptyState').removeClass('d-none');
                } else {
                    $('#stockAlertMessage').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>${res.message}
                        </div>
                    `);
                }
            },
            error: function () {
                $('#stockAlertMessage').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Failed to load stock alert data. Please try again.
                    </div>
                `);
            }
        });
    }
});