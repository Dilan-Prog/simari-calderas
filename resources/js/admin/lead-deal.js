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

// Monto con formato de numero (20,000.50) mientras se escribe; al enviar se manda sin comas.
// Delegado porque el panel se vuelve a pintar con el refresco en vivo.
function moneyRaw(value) {
    return String(value).replace(/[^\d.]/g, '').replace(/^(\d*\.\d*).*$/, '$1');
}

function moneyFormat(raw) {
    if (raw === '') return '';
    const [int, dec] = raw.split('.');
    const grouped = (int === '' ? '0' : int.replace(/^0+(?=\d)/, '')).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return dec === undefined ? grouped : grouped + '.' + dec.slice(0, 2);
}

document.addEventListener('input', (e) => {
    const el = e.target.closest('input[data-money]');
    if (!el) return;
    const digitsBeforeCaret = moneyRaw(el.value.slice(0, el.selectionStart ?? el.value.length)).length;
    el.value = moneyFormat(moneyRaw(el.value));
    let seen = 0;
    let pos = 0;
    while (pos < el.value.length && seen < digitsBeforeCaret) {
        if (/[\d.]/.test(el.value[pos])) seen++;
        pos++;
    }
    el.setSelectionRange(pos, pos);
});

document.addEventListener('focusout', (e) => {
    const el = e.target.closest('input[data-money]');
    if (!el || el.value === '') return;
    const raw = moneyRaw(el.value);
    el.value = moneyFormat(raw.includes('.') ? Number(raw).toFixed(2) : raw + '.00');
});

document.addEventListener('submit', (e) => {
    e.target.querySelectorAll('input[data-money]').forEach((el) => { el.value = moneyRaw(el.value); });
}, true);
