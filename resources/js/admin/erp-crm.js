// Bandeja de leads en vivo (consulta de actividad cada pocos segundos).
import './leads-live.js';
import './lead-customer.js';
import './lead-deal.js';
import './lead-inbox-ui.js';
import './lead-channels.css';

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

// Bandeja de leads (.lw): pestanas del compositor, panel de datos en pantallas chicas y scroll del hilo.
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-lw]');
    if (!shell) return;

    // Delegado: leads-live.js reemplaza la barra superior (KPIs + boton Datos) en cada refresco.
    shell.addEventListener('click', (e) => {
        if (e.target.closest('[data-lw-info]')) shell.classList.toggle('show-info');
    });

    const composer = shell.querySelector('[data-lw-composer]');
    composer?.querySelectorAll('.lw-tabs [data-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            composer.querySelectorAll('.lw-tabs [data-tab]').forEach((t) => t.classList.toggle('is-on', t === tab));
            composer.querySelectorAll('[data-pane]').forEach((p) => { p.hidden = p.dataset.pane !== tab.dataset.tab; });
        });
    });

    const thread = shell.querySelector('[data-lw-thread]');
    if (thread) thread.scrollTop = thread.scrollHeight;
});
