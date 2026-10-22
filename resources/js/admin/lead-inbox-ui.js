// Bandeja de leads: interfaz del diseño v2 (modo oscuro, secciones plegables, respuestas rapidas).
// Todo va delegado en document porque leads-live.js reemplaza partes del DOM cada que hay actividad.
const THEME_KEY = 'lw-theme';
const SEC_KEY = 'lw-sec:';

function store(op, key, value) {
    try {
        if (op === 'get') return window.localStorage.getItem(key);
        window.localStorage.setItem(key, value);
    } catch { /* almacenamiento no disponible: se ignora */ }
    return null;
}

const shellEl = () => document.querySelector('.lw-shell[data-lw]');

// ───────── Modo oscuro (solo la bandeja) ─────────
function applyTheme(dark) {
    const shell = shellEl();
    if (!shell) return;
    if (dark) shell.setAttribute('data-theme', 'dark');
    else shell.removeAttribute('data-theme');
    document.querySelectorAll('[data-lw-theme-toggle]').forEach((b) => {
        b.setAttribute('aria-pressed', dark ? 'true' : 'false');
        const label = b.querySelector('span');
        if (label) label.textContent = dark ? 'Modo claro' : 'Modo oscuro';
    });
}

// ───────── Secciones plegables del panel derecho ─────────
// Aplica el estado guardado a cada <details data-sec>. `skipForced` respeta las que el servidor abre a proposito (errores).
export function restoreSections(root = document, overrides = null) {
    root.querySelectorAll('details[data-sec]').forEach((d) => {
        const key = d.dataset.sec;
        if (overrides && Object.prototype.hasOwnProperty.call(overrides, key)) {
            d.open = overrides[key];
            return;
        }
        if (d.hasAttribute('data-force-open')) return;
        const saved = store('get', SEC_KEY + key);
        if (saved === '1') d.open = true;
        else if (saved === '0') d.open = false;
    });
}

export function sectionStates(root = document) {
    const map = {};
    root.querySelectorAll('details[data-sec]').forEach((d) => { map[d.dataset.sec] = d.open; });
    return map;
}

// ───────── Respuestas rapidas ─────────
function activeTab() {
    return document.querySelector('[data-lw-composer] .lw-tabs .is-on')?.dataset.tab || 'whatsapp';
}

function quickTarget() {
    const tab = activeTab();
    const composer = document.querySelector('[data-lw-composer]');
    if (!composer) return null;
    if (tab === 'nota') return composer.querySelector('[data-pane="nota"] textarea');
    if (tab === 'whatsapp') {
        const radio = composer.querySelector('[data-wa-mode][value="text"]');
        if (radio && !radio.disabled && !radio.checked) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const ta = composer.querySelector('[data-pane="whatsapp"] textarea[name="text"]');
        return ta && !ta.disabled ? ta : null;
    }
    return null;
}

function syncQuick() {
    const bar = document.querySelector('[data-lw-quick]');
    if (bar) bar.hidden = activeTab() === 'correo';
}

// Atajo "/": al teclear "/" al inicio del cuadro se filtran las respuestas rapidas por lo que sigue.
function filterQuick(term) {
    const bar = document.querySelector('[data-lw-quick]');
    if (!bar) return;
    const q = term.trim().toLowerCase();
    bar.classList.toggle('is-slash', term !== '');
    bar.querySelectorAll('[data-quick-text]').forEach((b) => {
        b.hidden = q !== '' && !b.textContent.toLowerCase().includes(q);
    });
}

document.addEventListener('input', (e) => {
    const ta = e.target;
    if (!(ta instanceof HTMLTextAreaElement) || !ta.closest('[data-lw-composer]')) return;
    const m = ta.value.match(/^\s*\/(.*)$/s);
    filterQuick(m ? m[1].split('\n')[0] : '');
});

document.addEventListener('keydown', (e) => {
    const ta = e.target;
    if (e.key !== 'Enter' || e.shiftKey || !(ta instanceof HTMLTextAreaElement) || !ta.closest('[data-lw-composer]')) return;
    if (!/^\s*\//.test(ta.value)) return;
    const first = document.querySelector('[data-lw-quick] [data-quick-text]:not([hidden])');
    if (first) { e.preventDefault(); first.click(); }
});

function init() {
    const shell = shellEl();
    if (!shell) return;

    applyTheme(store('get', THEME_KEY) === 'dark');
    restoreSections(shell);
    syncQuick();
}

document.addEventListener('click', (e) => {
    const themeBtn = e.target.closest?.('[data-lw-theme-toggle]');
    if (themeBtn) {
        const shell = shellEl();
        if (!shell) return;
        const dark = shell.getAttribute('data-theme') !== 'dark';
        applyTheme(dark);
        store('set', THEME_KEY, dark ? 'dark' : 'light');
        return;
    }

    const emoToggle = e.target.closest?.('[data-lw-emoji-toggle]');
    if (emoToggle) {
        const pop = emoToggle.closest('.lw-input-row')?.querySelector('[data-lw-emoji]');
        if (pop) pop.hidden = !pop.hidden;
        return;
    }
    const emoPick = e.target.closest?.('[data-lw-emoji-pick]');
    if (emoPick) {
        const ta = document.querySelector('[data-lw-composer] textarea[data-lw-text]');
        if (ta && !ta.disabled) {
            const s = ta.selectionStart ?? ta.value.length;
            const en = ta.selectionEnd ?? s;
            ta.value = ta.value.slice(0, s) + emoPick.dataset.lwEmojiPick + ta.value.slice(en);
            const pos = s + emoPick.dataset.lwEmojiPick.length;
            ta.focus();
            ta.setSelectionRange(pos, pos);
        }
        emoPick.closest('[data-lw-emoji]').hidden = true;
        return;
    }
    if (!e.target.closest?.('.lw-input-row')) document.querySelectorAll('[data-lw-emoji]').forEach((p) => { p.hidden = true; });

    const quick = e.target.closest?.('[data-quick-text]');
    if (quick) {
        const ta = quickTarget();
        if (!ta) return;
        const text = quick.dataset.quickText;
        const slash = /^\s*\//.test(ta.value);
        ta.value = (ta.value.trim() === '' || slash) ? text : ta.value.replace(/\s+$/, '') + ' ' + text;
        filterQuick('');
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
        ta.dispatchEvent(new Event('input', { bubbles: true }));
        return;
    }

    // "Escribir" del contacto: abre la pestana de WhatsApp del compositor.
    const open = e.target.closest?.('[data-wa-open]');
    if (open) {
        e.preventDefault();
        const composer = document.querySelector('[data-lw-composer]');
        composer?.querySelector('.lw-tabs [data-tab="whatsapp"]')?.click();
        composer?.scrollIntoView({ block: 'nearest' });
        return;
    }

    if (e.target.closest?.('.lw-tabs [data-tab]')) setTimeout(syncQuick, 0);
});

// "toggle" no burbujea: se escucha en captura para recordar cada seccion por su data-sec.
document.addEventListener('toggle', (e) => {
    const d = e.target;
    if (!(d instanceof HTMLDetailsElement) || !d.matches('details[data-sec]') || d.hasAttribute('data-force-open')) return;
    store('set', SEC_KEY + d.dataset.sec, d.open ? '1' : '0');
}, true);

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
