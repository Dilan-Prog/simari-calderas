// Bandeja de leads en vivo: consulta una huella ligera cada pocos segundos y, si hubo actividad,
// vuelve a pedir la pagina y reemplaza solo la lista, los contadores y la conversacion.
// No toca el compositor (lo que estas escribiendo se conserva) ni el buscador.
const POLL_MS = 5000;
const SLOW_MS = 20000;

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

        // Panel de datos: no se reemplaza mientras tengas un campo enfocado ahi (p. ej. un desplegable abierto).
        const info = shell.querySelector('.lw-info');
        const typing = info && [...info.querySelectorAll('textarea, input[type="text"]')].some((el) => el.value.trim() !== '');
        if (info && !info.contains(document.activeElement) && !typing) {
            const open = [...info.querySelectorAll('details')].map((d) => d.open);
            swap('.lw-info', next);
            info.querySelectorAll('details').forEach((d, i) => { d.open = !!open[i]; });
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
