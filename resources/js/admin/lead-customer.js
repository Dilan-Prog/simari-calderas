// Bandeja de leads: el boton "Agregar como cliente" pide el formulario (fragmento HTML) y lo muestra
// en una ventana. Se usa delegacion de eventos porque la bandeja se refresca sola cada pocos segundos.
function closeModal() {
    document.getElementById('lcmHost')?.remove();
}

async function openModal(url) {
    closeModal();
    let html;
    try {
        const res = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
        if (!res.ok) throw new Error(String(res.status));
        html = await res.text();
    } catch (e) {
        window.alert('No se pudo abrir el formulario del cliente. Si el problema continua, revisa tus permisos.');
        return;
    }
    const host = document.createElement('div');
    host.id = 'lcmHost';
    host.innerHTML = html;
    document.body.appendChild(host);
    host.querySelector('input[name="full_name"]')?.focus();
}

document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-lead-customer]');
    if (open) {
        e.preventDefault();
        openModal(open.getAttribute('data-lead-customer'));
        return;
    }
    if (e.target.closest('[data-lcm-close]') || e.target.matches('[data-lcm]')) closeModal();
});

document.addEventListener('submit', async (e) => {
    const form = e.target;
    const modal = form.closest('[data-lcm]');
    if (!modal) return;
    e.preventDefault();

    const box = modal.querySelector('[data-lcm-error]');
    const buttons = modal.querySelectorAll('button[type="submit"]');
    buttons.forEach((b) => { b.disabled = true; });
    if (box) box.hidden = true;

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json().catch(() => ({}));
        if (res.ok && json.redirect) {
            window.location.href = json.redirect;
            return;
        }
        const first = json.errors ? Object.values(json.errors)[0]?.[0] : json.message;
        if (box) {
            box.textContent = first || 'No se pudo guardar el cliente. Intenta de nuevo.';
            box.hidden = false;
        }
    } catch (err) {
        if (box) {
            box.textContent = 'No se pudo conectar con el servidor. Intenta de nuevo.';
            box.hidden = false;
        }
    }
    buttons.forEach((b) => { b.disabled = false; });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
