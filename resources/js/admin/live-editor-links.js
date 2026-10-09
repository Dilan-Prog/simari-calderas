import '../../css/admin/live-editor-links.css';

/**
 * Enlaces en línea para los campos de texto del editor en vivo.
 * Gramática (idéntica al render del servidor): [texto](destino)
 *  - texto no puede contener "]"
 *  - destino: sin espacios ni ")", empieza con "/" (no "//") o http(s)://
 */

const LINK_RE = /\[([^\]]+)\]\(((?:\/(?!\/)|https?:\/\/)[^\s)]*)\)/g;

export function isSafeUrl(url) {
    if (typeof url !== 'string') return false;
    const u = url.trim();
    if (!u || /[\s)]/.test(u)) return false;
    if (/^\/(?!\/)/.test(u)) return true;
    return /^https?:\/\/[^\s/]+/i.test(u);
}

/** Devuelve {start,end,label,url,labelStart,labelEnd} del enlace que contiene pos (o intersecta [pos,end]). */
export function findLinkAt(value, pos, end) {
    const v = String(value ?? '');
    const a = Number.isFinite(pos) ? pos : 0;
    const b = Number.isFinite(end) ? Math.max(end, a) : a;
    const re = new RegExp(LINK_RE.source, 'g');
    let m;
    while ((m = re.exec(v)) !== null) {
        const start = m.index;
        const stop = start + m[0].length;
        const hit = a === b ? (a >= start && a <= stop) : (a < stop && b > start);
        if (hit) {
            return { start, end: stop, label: m[1], url: m[2], labelStart: start + 1, labelEnd: start + 1 + m[1].length };
        }
        if (start > b) break;
    }
    return null;
}

function stripLinks(text) {
    return String(text).replace(new RegExp(LINK_RE.source, 'g'), '$1');
}

function cleanLabel(text) {
    return stripLinks(text).replace(/[\[\]]/g, '');
}

function cleanUrl(url) {
    return String(url).trim().replace(/\s/g, '%20').replace(/\)/g, '%29');
}

/** Envuelve value[start,end] como enlace; si start===end envuelve todo el valor. Devuelve {value,start,end}. */
export function wrapSelection(value, start, end, url) {
    const v = String(value ?? '');
    const safe = cleanUrl(url);
    let s = Math.max(0, Math.min(start ?? 0, v.length));
    let e = Math.max(0, Math.min(end ?? s, v.length));
    if (e < s) [s, e] = [e, s];
    if (s === e) { s = 0; e = v.length; }
    // Si toca un enlace existente, solo se cambia su destino.
    const existing = findLinkAt(v, s, e);
    if (existing) {
        const out = v.slice(0, existing.start) + '[' + existing.label + '](' + safe + ')' + v.slice(existing.end);
        return { value: out, start: existing.start, end: existing.start + existing.label.length + safe.length + 4 };
    }
    const label = cleanLabel(v.slice(s, e));
    if (!label.trim()) {
        return { value: v, start: s, end: e };
    }
    const mark = '[' + label + '](' + safe + ')';
    return { value: v.slice(0, s) + mark + v.slice(e), start: s, end: s + mark.length };
}

/** Quita el enlace que contiene pos dejando solo su texto. Devuelve {value,start,end}. */
export function unwrapAt(value, pos, end) {
    const v = String(value ?? '');
    const l = findLinkAt(v, pos, end);
    if (!l) return { value: v, start: pos ?? 0, end: end ?? pos ?? 0 };
    return { value: v.slice(0, l.start) + l.label + v.slice(l.end), start: l.start, end: l.start + l.label.length };
}

/* ------------------------------------------------------------------ */
/* UI                                                                 */
/* ------------------------------------------------------------------ */

const NAME_BLOCK = /slug|url|color|image|alt|price|phone|email|class|html/i;
const TYPE_LABELS = { producto: 'Producto', product: 'Producto', coleccion: 'Colección', collection: 'Colección', servicio: 'Servicio', service: 'Servicio', categoria: 'Categoría', category: 'Categoría', marca: 'Marca', brand: 'Marca' };
const TYPE_ORDER = ['Producto', 'Colección', 'Servicio', 'Categoría', 'Marca'];
const LINK_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.5-1.5"/></svg>';

function typeLabel(t) {
    const k = String(t ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    return TYPE_LABELS[k] || (t ? String(t) : 'Otro');
}

function el(tag, props, children) {
    const n = document.createElement(tag);
    if (props) {
        Object.keys(props).forEach((k) => {
            if (k === 'text') n.textContent = props[k];
            else if (k === 'class') n.className = props[k];
            else n.setAttribute(k, props[k]);
        });
    }
    (children || []).forEach((c) => n.appendChild(c));
    return n;
}

export function mountLinkTools(root, options) {
    if (!root) return null;
    if (root.__leLinkTools) return root.__leLinkTools;
    const opts = options || {};
    const sel = new WeakMap(); // campo -> [start,end]

    const btn = el('button', { type: 'button', class: 'le-link-btn', 'aria-label': 'Insertar enlace', title: 'Insertar enlace' });
    btn.innerHTML = LINK_ICON;
    document.body.appendChild(btn);

    let current = null;      // campo con botón visible
    let hideTimer = null;
    let pop = null;          // popover abierto
    let popState = null;

    function eligible(f) {
        try {
            if (!f || f.nodeType !== 1 || !root.contains(f)) return false;
            const tag = f.tagName;
            if (tag === 'INPUT') {
                const t = (f.getAttribute('type') || 'text').toLowerCase();
                if (t !== 'text') return false;
            } else if (tag !== 'TEXTAREA') return false;
            if (f.readOnly || f.disabled || f.hasAttribute('data-no-link')) return false;
            if (f.closest('[data-no-link]') || f.closest('.le-link-pop')) return false;
            if (NAME_BLOCK.test(f.id || '') || NAME_BLOCK.test(f.getAttribute('name') || '')) return false;
            if (typeof opts.exclude === 'function' && opts.exclude(f)) return false;
            return true;
        } catch (_) { return false; }
    }

    function scan() {
        root.querySelectorAll('input, textarea').forEach((f) => {
            if (f.hasAttribute('data-le-link-ready')) return;
            if (eligible(f)) f.setAttribute('data-le-link-ready', '1');
        });
        if (current && !document.contains(current)) hideButton(true);
    }

    function remember(f) {
        try { sel.set(f, [f.selectionStart ?? 0, f.selectionEnd ?? 0]); } catch (_) { /* noop */ }
    }

    function placeButton() {
        if (!current || !document.contains(current)) return hideButton(true);
        const r = current.getBoundingClientRect();
        if (r.width < 60 || r.height < 14) return hideButton(true);
        const isArea = current.tagName === 'TEXTAREA';
        const top = isArea ? r.top + 6 : r.top + (r.height - 24) / 2;
        btn.style.top = (top + window.scrollY) + 'px';
        btn.style.left = (r.right - 30 + window.scrollX) + 'px';
    }

    function showButton(f) {
        clearTimeout(hideTimer);
        current = f;
        btn.classList.add('is-visible');
        placeButton();
    }

    function hideButton(now) {
        clearTimeout(hideTimer);
        const run = () => {
            if (pop) return;
            btn.classList.remove('is-visible');
            current = null;
        };
        if (now) run(); else hideTimer = setTimeout(run, 180);
    }

    function fieldFrom(t) {
        const f = t && t.closest ? t.closest('input, textarea') : null;
        return f && f.hasAttribute('data-le-link-ready') ? f : null;
    }

    /* ---------------- eventos delegados ---------------- */
    root.addEventListener('focusin', (e) => {
        if (!e.target.hasAttribute || !e.target.hasAttribute('data-le-link-ready')) scan();
        const f = fieldFrom(e.target);
        if (f) showButton(f);
    });
    root.addEventListener('mouseover', (e) => {
        if (pop || !e.target.closest) return;
        let f = fieldFrom(e.target);
        if (!f && e.target.closest('input, textarea')) { scan(); f = fieldFrom(e.target); }
        if (f) showButton(f);
    });
    root.addEventListener('mouseout', (e) => { if (fieldFrom(e.target) && !pop) hideButton(); });
    root.addEventListener('focusout', (e) => { if (fieldFrom(e.target)) { remember(e.target); if (!pop) hideButton(); } });
    ['select', 'keyup', 'mouseup', 'input'].forEach((ev) => root.addEventListener(ev, (e) => {
        const f = fieldFrom(e.target);
        if (f) { remember(f); if (f === current) placeButton(); }
    }));
    btn.addEventListener('mouseenter', () => clearTimeout(hideTimer));
    btn.addEventListener('mouseleave', () => { if (!pop) hideButton(); });
    // Evita que el botón robe foco/selección antes de leerla.
    btn.addEventListener('pointerdown', (e) => { if (current) remember(current); e.preventDefault(); });
    btn.addEventListener('mousedown', (e) => e.preventDefault());
    btn.addEventListener('click', (e) => { e.preventDefault(); if (current) openPopover(current); });
    const reposition = () => { if (pop) positionPop(); else if (current) placeButton(); };
    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);

    /* ---------------- popover ---------------- */
    function positionPop() {
        if (!pop || !popState || !document.contains(popState.field)) return;
        const r = popState.field.getBoundingClientRect();
        const ph = pop.offsetHeight;
        const pw = pop.offsetWidth;
        let top = r.bottom + 6;
        if (top + ph > window.innerHeight - 8 && r.top - ph - 6 > 8) top = r.top - ph - 6;
        const left = Math.min(Math.max(8, r.left), window.innerWidth - pw - 8);
        pop.style.top = (top + window.scrollY) + 'px';
        pop.style.left = (left + window.scrollX) + 'px';
    }

    function closePopover(refocus) {
        if (!pop) return;
        const f = popState && popState.field;
        if (popState && popState.abort) popState.abort.abort();
        document.removeEventListener('mousedown', onOutside, true);
        document.removeEventListener('keydown', onKey, true);
        pop.remove();
        pop = null;
        popState = null;
        if (refocus && f && document.contains(f)) { try { f.focus({ preventScroll: true }); } catch (_) { /* noop */ } }
        hideButton();
    }

    function onOutside(e) {
        if (pop && !pop.contains(e.target) && !btn.contains(e.target)) closePopover(false);
    }

    function onKey(e) {
        if (!pop) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); closePopover(true); return; }
        if (e.key === 'Tab') {
            const items = Array.from(pop.querySelectorAll('input, button:not(:disabled)'));
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }

    function commit(field, res) {
        field.value = res.value;
        try { field.setSelectionRange(res.start, res.end); } catch (_) { /* noop */ }
        field.dispatchEvent(new Event('input', { bubbles: true }));
        if (typeof opts.onChange === 'function') { try { opts.onChange(field); } catch (_) { /* noop */ } }
    }

    function openPopover(field) {
        if (pop) closePopover(false);
        const value = field.value || '';
        const saved = sel.get(field) || [field.selectionStart ?? 0, field.selectionEnd ?? 0];
        const s = Math.min(saved[0], value.length);
        const e = Math.min(saved[1], value.length);
        const existing = findLinkAt(value, s, e);
        const selected = existing ? existing.label : value.slice(s, e);
        const hasSel = !existing && s !== e && selected.trim() !== '';
        const empty = value.trim() === '';

        popState = { field, chosen: null, label: '', abort: null };
        const uid = 'lelp' + Math.random().toString(36).slice(2, 7);

        const title = el('p', { class: 'le-link-pop__title', id: uid + '-t', text: existing ? 'Editar enlace' : 'Insertar enlace' });
        const selTxt = existing ? '«' + existing.label + '» → ' + existing.url
            : empty ? 'Campo vacío: se insertará un enlace nuevo'
                : hasSel ? '«' + selected + '»' : 'Todo el campo';
        const selBox = el('p', { class: 'le-link-pop__sel', text: selTxt, title: selTxt });

        const search = el('input', { type: 'text', id: uid + '-q', placeholder: 'Buscar producto, colección, servicio…', autocomplete: 'off', 'aria-controls': uid + '-r', 'data-no-link': '1' });
        const results = el('ul', { class: 'le-link-pop__results', id: uid + '-r', role: 'listbox', 'aria-label': 'Resultados' });
        const status = el('div', { class: 'le-link-pop__status', role: 'status', 'aria-live': 'polite', text: opts.searchUrl ? 'Escribe al menos 2 letras.' : '' });
        const urlIn = el('input', { type: 'text', id: uid + '-u', placeholder: '/ruta o https://…', autocomplete: 'off', 'data-no-link': '1' });
        urlIn.value = existing ? existing.url : '';
        const err = el('div', { class: 'le-link-pop__error', role: 'alert' });
        const apply = el('button', { type: 'button', class: 'le-link-apply', text: 'Aplicar' });
        const cancel = el('button', { type: 'button', text: 'Cancelar' });
        const actions = el('div', { class: 'le-link-pop__actions' });
        if (existing) actions.appendChild(el('button', { type: 'button', class: 'le-link-remove', text: 'Quitar enlace' }));
        actions.appendChild(cancel);
        actions.appendChild(apply);

        const kids = [title, selBox];
        if (opts.searchUrl) {
            kids.push(el('label', { class: 'le-link-pop__label', for: uid + '-q', text: 'Buscar destino interno' }), search, status, results);
        }
        kids.push(el('label', { class: 'le-link-pop__label', for: uid + '-u', text: 'O escribe una URL' }), urlIn, err, actions);
        pop = el('div', { class: 'le-link-pop', role: 'dialog', 'aria-modal': 'false', 'aria-labelledby': uid + '-t' }, kids);
        document.body.appendChild(pop);
        positionPop();
        document.addEventListener('mousedown', onOutside, true);
        document.addEventListener('keydown', onKey, true);

        const refreshApply = () => {
            const u = urlIn.value.trim();
            apply.disabled = !isSafeUrl(u);
            err.textContent = u && !isSafeUrl(u) ? 'Usa una ruta que empiece con / o una URL http(s)://' : '';
        };
        urlIn.addEventListener('input', () => { popState.chosen = null; refreshApply(); });
        urlIn.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') { ev.preventDefault(); if (!apply.disabled) apply.click(); } });
        refreshApply();

        function choose(item, node) {
            popState.chosen = item;
            popState.label = item.label || '';
            urlIn.value = item.url;
            results.querySelectorAll('[aria-selected="true"]').forEach((n) => n.setAttribute('aria-selected', 'false'));
            node.setAttribute('aria-selected', 'true');
            refreshApply();
            apply.focus();
        }

        function renderResults(list) {
            results.textContent = '';
            const valid = (Array.isArray(list) ? list : []).filter((i) => i && typeof i.url === 'string' && isSafeUrl(i.url));
            status.textContent = valid.length ? '' : 'Sin resultados.';
            const groups = {};
            valid.forEach((i) => { (groups[typeLabel(i.type)] = groups[typeLabel(i.type)] || []).push(i); });
            Object.keys(groups).sort((a, b) => (TYPE_ORDER.indexOf(a) + 1 || 99) - (TYPE_ORDER.indexOf(b) + 1 || 99)).forEach((g) => {
                results.appendChild(el('li', { class: 'le-link-pop__group', role: 'presentation', text: g }));
                groups[g].forEach((i) => {
                    const b = el('button', { type: 'button', class: 'le-link-pop__item', role: 'option', 'aria-selected': 'false' });
                    b.appendChild(document.createTextNode(String(i.label || i.url)));
                    if (i.hint) b.appendChild(el('small', { text: String(i.hint) }));
                    b.addEventListener('click', () => choose(i, b));
                    results.appendChild(el('li', { role: 'presentation' }, [b]));
                });
            });
            positionPop();
        }

        let timer = null;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            const q = search.value.trim();
            if (popState && popState.abort) popState.abort.abort();
            if (q.length < 2) { results.textContent = ''; status.textContent = 'Escribe al menos 2 letras.'; return; }
            status.textContent = 'Buscando…';
            timer = setTimeout(async () => {
                const ac = typeof AbortController !== 'undefined' ? new AbortController() : null;
                if (popState) popState.abort = ac;
                try {
                    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
                    if (opts.csrfToken) headers['X-CSRF-TOKEN'] = opts.csrfToken;
                    const sep = opts.searchUrl.indexOf('?') === -1 ? '?' : '&';
                    const res = await fetch(opts.searchUrl + sep + 'q=' + encodeURIComponent(q), { headers, credentials: 'same-origin', signal: ac ? ac.signal : undefined });
                    if (!res.ok) throw new Error('http');
                    const data = await res.json();
                    if (pop) renderResults(Array.isArray(data) ? data : (data && data.data) || []);
                } catch (x) {
                    if (x && x.name === 'AbortError') return;
                    if (pop) { results.textContent = ''; status.textContent = 'No se pudo buscar. Escribe la URL manualmente.'; }
                }
            }, 250);
        });
        search.addEventListener('keydown', (ev) => {
            if (ev.key === 'ArrowDown') { const f = results.querySelector('.le-link-pop__item'); if (f) { ev.preventDefault(); f.focus(); } }
        });
        results.addEventListener('keydown', (ev) => {
            const items = Array.from(results.querySelectorAll('.le-link-pop__item'));
            const i = items.indexOf(document.activeElement);
            if (ev.key === 'ArrowDown' && i < items.length - 1) { ev.preventDefault(); items[i + 1].focus(); }
            else if (ev.key === 'ArrowUp') { ev.preventDefault(); (i > 0 ? items[i - 1] : search).focus(); }
        });

        apply.addEventListener('click', () => {
            const url = urlIn.value.trim();
            if (!isSafeUrl(url)) { refreshApply(); return; }
            let res;
            if (empty) {
                const label = cleanLabel(popState.label || '') || 'texto del enlace';
                const mark = '[' + label + '](' + cleanUrl(url) + ')';
                res = { value: mark, start: 0, end: mark.length };
            } else {
                res = wrapSelection(value, s, e, url);
                if (res.value === value && !existing) { err.textContent = 'Selecciona un texto para convertirlo en enlace.'; return; }
            }
            commit(field, res);
            closePopover(true);
        });
        const rm = actions.querySelector('.le-link-remove');
        if (rm) rm.addEventListener('click', () => { commit(field, unwrapAt(value, s, e)); closePopover(true); });
        cancel.addEventListener('click', () => closePopover(true));

        (opts.searchUrl ? search : urlIn).focus();
    }

    /* ---------------- observer ---------------- */
    let raf = 0;
    const mo = new MutationObserver(() => {
        if (raf) return;
        raf = requestAnimationFrame(() => { raf = 0; scan(); if (current) placeButton(); });
    });
    mo.observe(root, { childList: true, subtree: true });
    scan();

    const api = {
        destroy() {
            mo.disconnect();
            closePopover(false);
            btn.remove();
            window.removeEventListener('scroll', reposition, true);
            window.removeEventListener('resize', reposition);
            delete root.__leLinkTools;
        },
        rescan: scan,
    };
    root.__leLinkTools = api;
    return api;
}
