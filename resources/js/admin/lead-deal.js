// Bandeja de leads: "Crear cotizacion" abre el formulario de cotizaciones dentro de una ventana que ocupa el 80% de la
// pantalla (iframe en modo embed, con los datos del cliente ya cargados). Al guardar, el formulario avisa con
// postMessage y la bandeja se recarga para mostrar la cotizacion ligada al negocio y el monto nuevo.
// Se usa delegacion de eventos porque la bandeja se refresca sola cada pocos segundos.
function closeQuoteModal() {
    document.getElementById('lqmHost')?.remove();
    document.body.style.overflow = '';
}

function openQuoteModal(url) {
    closeQuoteModal();
    const host = document.createElement('div');
    host.id = 'lqmHost';
    host.className = 'lqm-overlay';
    host.setAttribute('role', 'dialog');
    host.setAttribute('aria-modal', 'true');
    host.setAttribute('aria-label', 'Nueva cotizacion');
    host.innerHTML = '<div class="lqm-box">'
        + '<div class="lqm-head"><strong>Nueva cotizacion</strong><button type="button" class="lqm-close" data-lqm-close aria-label="Cerrar">&times;</button></div>'
        + '<iframe class="lqm-frame" title="Nueva cotizacion"></iframe>'
        + '</div>';
    document.body.appendChild(host);
    document.body.style.overflow = 'hidden';
    host.querySelector('iframe').src = url;
}

document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-lead-quote]');
    if (open) {
        e.preventDefault();
        openQuoteModal(open.getAttribute('data-lead-quote'));
        return;
    }
    if (e.target.closest('[data-lqm-close]') || e.target.matches('.lqm-overlay')) closeQuoteModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeQuoteModal();
});

window.addEventListener('message', (e) => {
    if (e.origin !== window.location.origin || !e.data || e.data.type !== 'equiterm:quote-saved') return;
    closeQuoteModal();
    window.location.reload();
});
