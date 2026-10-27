// =====================================================
// Smart Healthcare SPM System - Custom JavaScript
// =====================================================

document.addEventListener('DOMContentLoaded', function () {

    // Auto-hide success/error alerts after 4 seconds
    const alerts = document.querySelectorAll('.alert-success, .alert-danger, .alert-warning');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () {
                alert.remove();
            }, 500);
        }, 4000);
    });

    // Confirm before implementing a Change Request
    const implementButtons = document.querySelectorAll('button[value="implement"]');
    implementButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('This will update the project budget and end date.\n\nAre you sure you want to implement this Change Request?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm before deleting or critical actions (if any)
    const dangerForms = document.querySelectorAll('form[data-confirm]');
    dangerForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Simple tooltips (Bootstrap)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

});
