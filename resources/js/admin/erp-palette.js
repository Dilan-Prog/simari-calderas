// Barra de comandos Ctrl+K: salta a módulos y documentos (por folio).
// Se carga en el layout admin; el endpoint viene en <meta name="erp-search-url">.
(function () {
    const meta = document.querySelector('meta[name="erp-search-url"]');
    if (!meta) return;
    const url = meta.getAttribute('content');

    let backdrop = null;
    let input = null;
    let list = null;
    let items = [];
    let active = 0;
    let timer = null;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function build() {
        backdrop = document.createElement('div');
        backdrop.className = 'erp-palette-backdrop';
        backdrop.innerHTML = `
            <div class="erp-palette" role="dialog" aria-label="Buscar módulo o documento">
                <input type="text" class="erp-palette-input" placeholder="Módulo o folio (ej. COT-2026, PRY-…)" autocomplete="off">
                <div class="erp-palette-list"></div>
                <div class="erp-palette-hint"><kbd>↑</kbd> <kbd>↓</kbd> navegar · <kbd>Enter</kbd> abrir · <kbd>Esc</kbd> cerrar</div>
            </div>`;
        document.body.appendChild(backdrop);
        input = backdrop.querySelector('input');
        list = backdrop.querySelector('.erp-palette-list');
        backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop) close(); });
        input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 180); });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') { e.preventDefault(); if (items[active]) window.location.href = items[active].url; }
            else if (e.key === 'Escape') { close(); }
        });
    }

    function move(d) {
        if (!items.length) return;
        active = (active + d + items.length) % items.length;
        paint();
    }

    function paint() {
        let html = '';
        let lastGroup = null;
        items.forEach((it, i) => {
            if (it.group !== lastGroup) { html += `<div class="erp-palette-group">${esc(it.group)}</div>`; lastGroup = it.group; }
            html += `<a class="erp-palette-item ${i === active ? 'is-active' : ''}" href="${esc(it.url)}"><span>${esc(it.label)}</span><small>${esc(it.meta || '')}</small></a>`;
        });
        list.innerHTML = html || '<div class="erp-palette-group">Sin resultados</div>';
        const a = list.querySelector('.is-active');
        if (a) a.scrollIntoView({ block: 'nearest' });
    }

    async function load() {
        try {
            const res = await fetch(url + '?q=' + encodeURIComponent(input.value), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            items = [
                ...(data.documents || []).map((d) => ({ group: 'Documentos', label: `${d.type} ${d.number}`, meta: d.status || '', url: d.url })),
                ...(data.modules || []).map((m) => ({ group: 'Módulos', label: m.label, meta: '', url: m.url })),
            ];
            active = 0;
            paint();
        } catch (e) { /* silencioso */ }
    }

    function open() {
        if (!backdrop) build();
        backdrop.classList.add('is-open');
        input.value = '';
        input.focus();
        load();
    }

    function close() {
        if (backdrop) backdrop.classList.remove('is-open');
    }

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            backdrop && backdrop.classList.contains('is-open') ? close() : open();
        }
    });
    document.addEventListener('click', (e) => {
        if (e.target.closest && e.target.closest('[data-erp-palette-open]')) open();
    });
})();
