<style>
    .table-responsive {
    overflow-x: auto;
}

.table th {
    white-space: nowrap;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.alert {
    margin-bottom: 1rem;
    padding: 0.75rem 1.25rem;
}

/* Focus styles for better accessibility */
.form-control:focus,
.form-select:focus,
.btn:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Improved button spacing */
.modal-footer .btn {
    margin-left: 0.5rem;
}

/* Limit modal height and enable vertical scroll inside modal body */
.custom-scrollable-modal {
    max-width: 800px; /* Adjust as needed */
}

.custom-scrollable-modal .modal-content {
    max-height: 70vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.custom-scrollable-modal .modal-body {
    overflow-y: auto;
    padding: 1rem;
    flex: 1 1 auto;
}

/* Optional: fix modal footer to stay in view */
.custom-scrollable-modal .modal-footer {
    border-top: 1px solid #dee2e6;
    background: #fff;
}

/* Position the modal a bit to the right and down */
.custom-modal-position {
    margin-top: 50px;
    margin-left: 270px; 
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group-sm .btn {
        padding: 0.375rem 0.75rem;
    }
}
</style>