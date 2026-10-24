// Aplica/quita .is-hot segun el evento 'equiterm:hot' (leads y tareas con respuesta pendiente).
import '../../css/admin/components/hot.css';

function apply(detail) {
    const sets = { lead: new Set((detail?.leads || []).map(String)), task: new Set((detail?.tasks || []).map(String)) };
    ['lead', 'task'].forEach((kind) => {
        document.querySelectorAll('[data-hot-' + kind + ']').forEach((el) => {
            el.classList.toggle('is-hot', sets[kind].has(String(el.getAttribute('data-hot-' + kind))));
        });
    });
}

window.addEventListener('equiterm:hot', (e) => apply(e.detail));
