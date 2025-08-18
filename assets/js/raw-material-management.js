$(document).ready(function () {
    // Load material data
    function loadMaterials() {
        $.ajax({
            url: '../model/rawMaterial/showMaterial.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                let rows = '';
                if (data.status === 'success' && Array.isArray(data.data)) {
                    data.data.forEach(function (item) {
                        const stockLevel = parseFloat(item.current_stock) || 0;
                        const stockClass = stockLevel <= 0 ? 'text-danger' : 
                                         stockLevel <= (parseFloat(item.minimum_stock) || 0) ? 'text-warning' : 'text-success';
                        
                        rows += `
                            <tr>
                                <td>${item.material_id}</td>
                                <td>${item.material_code}</td>
                                <td>${item.material_name}</td>
                                <td>${item.unit_of_measure}</td>
                                <td>
                                    <span class="${stockClass}">
                                        <i class="fas fa-box me-1"></i>
                                        ${stockLevel.toFixed(2)}
                                    </span>
                                </td>
                                <td>${item.status}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item viewStockBtn" href="#" 
                                                    data-id="${item.material_id}"
                                                    data-name="${item.material_name}">
                                                    <i class="fas fa-chart-line me-2"></i> View Stock History
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item reduceStockBtn" href="#" 
                                                    data-id="${item.material_id}"
                                                    data-name="${item.material_name}"
                                                    data-unit="${item.unit_of_measure}"
                                                    data-stock="${item.current_stock}">
                                                    <i class="fas fa-minus-circle me-2"></i> Reduce Stock
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item editMaterialBtn" href="#" 
                                                    data-id="${item.material_id}" 
                                                    data-code="${item.material_code}"
                                                    data-name="${item.material_name}" 
                                                    data-unit="${item.unit_of_measure}" 
                                                    data-description="${item.description}" 
                                                    data-status="${item.status}"
                                                    data-min-stock="${item.minimum_stock || 0}">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger deleteMaterialBtn" href="#" 
                                                    data-id="${item.material_id}">
                                                    <i class="fas fa-trash-alt me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    rows = `<tr><td colspan="7" class="text-center">No raw materials found.</td></tr>`;
                }
                $('#materialTable tbody').html(rows);

                // Reapply filter
                const currentSearch = $('#searchInput').val().toLowerCase();
                if (currentSearch) {
                    filterMaterials(currentSearch);
                }
            },
            error: function () {
                $('#materialTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Failed to load raw materials.</td></tr>');
            }
        });
    }

    // Search functionality
    $('#searchInput').on('keyup', function () {
        const searchTerm = $(this).val().toLowerCase();
        filterMaterials(searchTerm);
    });

    function filterMaterials(searchTerm) {
        const rows = $('#materialTable tbody tr');
        if (searchTerm === '') {
            rows.show();
            $('#emptyState').toggleClass('d-none', rows.length > 0);
            return;
        }

        let matchFound = false;
        rows.each(function () {
            const row = $(this);
            const name = row.find('td:eq(2)').text().toLowerCase();
            const code = row.find('td:eq(1)').text().toLowerCase();
            if (name.includes(searchTerm) || code.includes(searchTerm)) {
                row.show();
                matchFound = true;
            } else {
                row.hide();
            }
        });

        if (!matchFound) {
            $('#emptyState').removeClass('d-none');
            if ($('#no-search-results').length === 0) {
                $('#materialTable tbody').append(`
                    <tr id="no-search-results">
                        <td colspan="7" class="text-center">
                            No raw materials matching "${searchTerm}" found
                        </td>
                    </tr>
                `);
            }
        } else {
            $('#emptyState').addClass('d-none');
            $('#no-search-results').remove();
        }
    }

    // Function to properly close modal and remove backdrop
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
            // Remove backdrop and modal-open class
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    }

    // Add Material
    $('#addMaterialForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/rawMaterial/insertMaterial.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                const modal = $('#addMaterialModal');
                modal.modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                $('#addMaterialForm')[0].reset();
                showMessage(response.message, response.status);
                loadMaterials();
            },
            error: function () {
                showMessage('Error adding material.', 'error');
            }
        });
    });

    // Show Edit Modal
    $(document).on('click', '.editMaterialBtn', function () {
        $('#edit_material_id').val($(this).data('id'));
        $('#edit_material_code').val($(this).data('code'));
        $('#edit_material_name').val($(this).data('name'));
        $('#edit_unit_of_measure').val($(this).data('unit'));
        $('#edit_description').val($(this).data('description'));
        $('#edit_status').val($(this).data('status'));
        $('#edit_min_stock').val($(this).data('min-stock'));
        $('#editMaterialModal').modal('show');
    });

    // Update Material
    $('#editMaterialForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../model/rawMaterial/updateMaterial.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                closeModal('editMaterialModal');
                $('#editMaterialForm')[0].reset();
                showMessage(response.message, response.status);
                loadMaterials();
            },
            error: function () {
                showMessage('Error updating material.', 'error');
            }
        });
    });

    // Delete material
    $(document).on('click', '.deleteMaterialBtn', function () {
        const id = $(this).data('id');
        $('#delete_material_id').val(id);
        $('#deleteMaterialModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function () {
        const id = $('#delete_material_id').val();
        $.ajax({
            url: '../model/rawMaterial/deleteMaterial.php',
            method: 'POST',
            data: { material_id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeModal('deleteMaterialModal');
                    showMessage(response.message, 'success');
                    loadMaterials();
                } else {
                    showMessage(response.message || 'Error deleting material.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Delete error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showMessage('Error deleting material. Please try again.', 'error');
            }
        });
    });

    // Show flash message
    function showMessage(message, type = 'success') {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    // Handle view stock history
    $(document).on('click', '.viewStockBtn', function() {
        const materialId = $(this).data('id');
        const materialName = $(this).data('name');
        
        $('#stockHistoryMaterialName').text(materialName);
        
        $.ajax({
            url: '../model/rawMaterial/getStockHistory.php',
            method: 'GET',
            data: { material_id: materialId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Update current and minimum stock values
                    $('#currentStockValue').text(parseFloat(res.data.current_stock).toFixed(2));
                    $('#minimumStockValue').text(parseFloat(res.data.minimum_stock).toFixed(2));
                    
                    // Update stock history table
                    let rows = '';
                    res.data.history.forEach(function(record) {
                        const typeClass = record.type === 'addition' ? 'text-success' : 'text-danger';
                        const typeIcon = record.type === 'addition' ? 'fa-plus' : 'fa-minus';
                        const reference = record.source === 'Purchase' ? `Purchase # ${record.reference}` : record.reference; // Use reason for reduction logs

                        rows += `
                            <tr>
                                <td>${record.date}</td>
                                <td><span class="${typeClass}"><i class="fas ${typeIcon} me-1"></i>${record.type}</span></td>
                                <td>${parseFloat(record.amount).toFixed(2)}</td>
                                <td>${reference}</td>
                            </tr>
                        `;
                    });
                    
                    if (rows === '') {
                        rows = '<tr><td colspan="4" class="text-center">No stock history found</td></tr>';
                    }
                    
                    $('#stockHistoryTableBody').html(rows);
                    $('#stockHistoryModal').modal('show');
                } else {
                    toastr.error(res.message || 'Failed to load stock history');
                }
            },
            error: function() {
                toastr.error('Error loading stock history');
            }
        });
    });

    // Handle reduce stock button click
    $(document).on('click', '.reduceStockBtn', function() {
        const materialId = $(this).data('id');
        const materialName = $(this).data('name');
        const materialUnit = $(this).data('unit');
        const currentStock = $(this).data('stock');
        
        $('#reduce_material_id').val(materialId);
        $('#reduceStockMaterialName').text(materialName);
        $('#reduceStockMaterialUnit').text(materialUnit);
        $('#reduceStockCurrentStock').text(parseFloat(currentStock).toFixed(2));
        $('#reduce_quantity').val(''); // Clear previous value
        $('#reduction_reason').val(''); // Clear previous value
        $('#reduce_notes').val(''); // Clear previous value
        
        $('#reduceStockModal').modal('show');
    });

    // Handle reduce stock form submission
    $('#reduceStockForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reducing...');

        $.ajax({
            url: '../model/rawMaterial/reduceStock.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    closeModal('reduceStockModal');
                    $('#reduceStockForm')[0].reset();
                    showMessage(response.message, 'success');
                    loadMaterials(); // Reload table to show updated stock
                } else {
                    showMessage(response.message || 'Error reducing stock.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Reduce stock error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showMessage('Error reducing stock. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    loadMaterials();
});
