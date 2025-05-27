$(document).ready(function () {
    // Load material data
    function loadMaterials() {
        $.ajax({
            url: '/DMS/model/rawMaterial/showMaterial.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                let rows = '';
                if (data.status === 'success' && Array.isArray(data.data)) {
                    data.data.forEach(function (item) {
                        rows += `
                            <tr>
                                <td>${item.material_id}</td>
                                <td>${item.material_code}</td>
                                <td>${item.material_name}</td>
                                <td>${item.unit_of_measure}</td>
                                <td>${item.status}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item editMaterialBtn" href="#" 
                                                    data-id="${item.material_id}" 
                                                    data-code="${item.material_code}"
                                                    data-name="${item.material_name}" 
                                                    data-unit="${item.unit_of_measure}" 
                                                    data-description="${item.description}" 
                                                    data-status="${item.status}">
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
                    rows = `<tr><td colspan="6" class="text-center">No raw materials found.</td></tr>`;
                }
                $('#materialTable tbody').html(rows);

                // Reapply filter
                const currentSearch = $('#searchInput').val().toLowerCase();
                if (currentSearch) {
                    filterMaterials(currentSearch);
                }
            },
            error: function () {
                $('#materialTable tbody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load raw materials.</td></tr>');
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
                        <td colspan="6" class="text-center">
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
            url: '/DMS/model/rawMaterial/insertMaterial.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                const modal = $('#addMaterialModal');
                modal.modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                $('#addMaterialForm')[0].reset();
                showMessage(response.status, response.message);
                loadMaterials();
            },
            error: function () {
                showMessage('danger', 'Error adding material.');
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
        $('#editMaterialModal').modal('show');
    });

    // Update Material
    $('#editMaterialForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '/DMS/model/rawMaterial/updateMaterial.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                closeModal('editMaterialModal');
                $('#editMaterialForm')[0].reset();
                showMessage(response.status, response.message);
                loadMaterials();
            },
            error: function () {
                showMessage('danger', 'Error updating material.');
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
            url: '/DMS/model/rawMaterial/deleteMaterial.php',
            method: 'POST',
            data: { material_id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    closeModal('deleteMaterialModal');
                    showMessage('success', response.message);
                    loadMaterials();
                } else {
                    showMessage('danger', response.message || 'Error deleting material.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Delete error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showMessage('danger', 'Error deleting material. Please try again.');
            }
        });
    });

    function showMessage(msg, type = 'success') {
        // Use toastr for better looking notifications
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        if (type === 'success') {
            toastr.success(msg);
        } else {
            toastr.error(msg);
        }
    }

    loadMaterials();
});
