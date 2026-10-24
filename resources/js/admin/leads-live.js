// Bandeja de leads en vivo: consulta una huella ligera cada pocos segundos y, si hubo actividad,
// vuelve a pedir la pagina y reemplaza solo la lista, los contadores y la conversacion.
// No toca el compositor (lo que estas escribiendo se conserva) ni el buscador.
import { restoreSections, sectionStates } from './lead-inbox-ui.js';

const POLL_MS = 5000;
const SLOW_MS = 20000;

// El compositor no se reemplaza (para no perder lo que escribes), pero la ventana de 24 h de WhatsApp puede
// abrirse (el cliente escribio) o cerrarse mientras tanto: se ajusta solo lo que depende de ella.
function syncWhatsappWindow(shell, next) {
    const cur = shell.querySelector('[data-pane="whatsapp"] [data-wa-form]');
    const nxt = next.querySelector('[data-pane="whatsapp"] [data-wa-form]');
    if (!cur || !nxt || cur.dataset.waWindow === nxt.dataset.waWindow) return;

    const opened = nxt.dataset.waWindow === 'open';
    cur.dataset.waWindow = nxt.dataset.waWindow;

    const mode = cur.querySelector('.lw-wa-mode');
    const nextMode = nxt.querySelector('.lw-wa-mode');

    // Aviso "Ventana de 24 h cerrada" (va justo antes del selector Mensaje / Plantilla).
    const curNotice = cur.querySelector('.lw-wa-closed');
    const nextNotice = nxt.querySelector('.lw-wa-closed');
    if (nextNotice && !curNotice && mode) mode.before(nextNotice.cloneNode(true));
    else if (!nextNotice && curNotice) curNotice.remove();

    // Selector Mensaje / Plantilla, caja de texto y emoji.
    if (mode && nextMode) mode.className = nextMode.className;
    ['[data-wa-mode][value="text"]', 'textarea[name="text"]', '[data-lw-emoji-toggle]'].forEach((sel) => {
        const a = cur.querySelector(sel);
        const b = nxt.querySelector(sel);
        if (a && b) a.disabled = b.disabled;
    });

    // Respuestas rapidas: solo existen con la ventana abierta (van justo antes del panel de WhatsApp).
    const pane = cur.closest('[data-pane="whatsapp"]');
    const quick = pane?.parentElement.querySelector('[data-lw-quick]');
    const nextQuick = next.querySelector('[data-lw-quick]');
    if (nextQuick && !quick && pane) pane.parentElement.insertBefore(nextQuick.cloneNode(true), pane);
    else if (!nextQuick && quick) quick.remove();

    // Abierta: lo natural es escribir un mensaje; cerrada: solo plantilla.
    const radio = cur.querySelector(`[data-wa-mode][value="${opened ? 'text' : 'template'}"]`);
    if (radio && !radio.checked) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function start() {
    const shell = document.querySelector('[data-lw]');
    if (!shell) return;

    const liveUrl = location.pathname.replace(/\/+$/, '') + '/live';
    let fingerprint = null;
    let busy = false;
    let failures = 0;
    let timer = null;

    // Lead que se esta viendo: se fija explicitamente para que un lead nuevo no cambie la conversacion.
    const currentLeadId = () => {
        const href = shell.querySelector('.lw-row.is-active')?.getAttribute('href');
        if (!href) return null;
        try { return new URL(href, location.href).searchParams.get('lead'); } catch { return null; }
    };

    const swap = (selector, next) => {
        const a = shell.querySelector(selector);
        const b = next.querySelector(selector);
        if (!a || !b) return;
        a.className = b.className;
        a.innerHTML = b.innerHTML;
    };

    async function refresh() {
        const url = new URL(location.href);
        const id = currentLeadId();
        if (id) url.searchParams.set('lead', id);

        const res = await fetch(url.toString(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
        if (!res.ok || res.redirected) throw new Error('refresh failed');
        const next = new DOMParser().parseFromString(await res.text(), 'text/html').querySelector('[data-lw]');
        if (!next) throw new Error('no shell');

        // Panel izquierdo (el buscador no se toca) y barra superior.
        swap('.lw-tiles', next);
        swap('.lw-chips', next);
        swap('.lw-rows', next);
        swap('.lw-kpis', next);

        // Conversacion: se conserva el scroll salvo que ya estuvieras al final.
        const thread = shell.querySelector('[data-lw-thread]');
        const nextThread = next.querySelector('[data-lw-thread]');
        if (thread && nextThread) {
            const wasAtBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
            const top = thread.scrollTop;
            thread.innerHTML = nextThread.innerHTML;
            thread.scrollTop = wasAtBottom ? thread.scrollHeight : top;
        }
        swap('.lw-conv-head', next);
        swap('.lw-stages', next);
        syncWhatsappWindow(shell, next);

        // Panel de datos: no se reemplaza mientras tengas un campo enfocado ahi (p. ej. un desplegable abierto).
        const info = shell.querySelector('.lw-info');
        const typing = info && [...info.querySelectorAll('textarea, input[type="text"]')].some((el) => el.value.trim() !== '');
        if (info && !info.contains(document.activeElement) && !typing) {
            // Secciones plegables: se conservan por data-sec (clave estable); el resto de <details> (p. ej. Editar contacto) por posicion.
            const states = sectionStates(info);
            const plain = [...info.querySelectorAll('details:not([data-sec])')].map((d) => d.open);
            swap('.lw-info', next);
            restoreSections(info, states);
            info.querySelectorAll('details:not([data-sec])').forEach((d, i) => { d.open = !!plain[i]; });
        }
    }

    async function tick() {
        if (busy || document.hidden) return schedule(POLL_MS);
        busy = true;
        try {
            const res = await fetch(liveUrl, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('poll failed');
            const { fp } = await res.json();
            failures = 0;
            if (fingerprint === null) {
                fingerprint = fp;
            } else if (fp !== fingerprint) {
                await refresh();
                fingerprint = fp;
            }
        } catch {
            failures += 1;
        } finally {
            busy = false;
        }
        schedule(failures >= 3 ? SLOW_MS : POLL_MS);
    }

    function schedule(ms) {
        clearTimeout(timer);
        timer = setTimeout(tick, ms);
    }

    document.addEventListener('visibilitychange', () => { if (!document.hidden) schedule(300); });
    schedule(POLL_MS);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
else start();
