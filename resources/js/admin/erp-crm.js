// ERP crm: JS del módulo (entrada de Vite).
// Los selectores con data-crm-autosubmit envían su formulario de filtros al cambiar.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-crm-autosubmit]').forEach((el) => {
        el.addEventListener('change', () => el.closest('form')?.submit());
    });

    // Confirmación ligera para acciones masivas (p. ej. reintentar todas las fallidas).
    document.querySelectorAll('form[data-crm-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.crmConfirm)) {
                e.preventDefault();
            }
        });
    });
});
