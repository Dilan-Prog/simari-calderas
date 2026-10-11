// ERP production: JS del módulo (entrada de Vite).

function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

/** Confirmaciones en botones con data-confirm y cancelación con devolución de consumo. */
function initConfirms() {
    document.querySelectorAll('[data-confirm]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
        });
    });

    document.querySelectorAll('[data-cancel-form]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const consumed = form.dataset.consumed === '1';
            const msg = consumed
                ? 'La orden ya consumió componentes. Si continúas se cancelará y los componentes consumidos regresarán al almacén. ¿Continuar?'
                : '¿Cancelar la orden? Se liberarán las reservas.';
            if (!window.confirm(msg)) { e.preventDefault(); return; }
            form.querySelector('[name=return_consumed]').value = consumed ? '1' : '0';
        });
    });

    // Evita doble envío.
    document.querySelectorAll('.prd-page form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            form.querySelectorAll('button[type=submit]').forEach((b) => { setTimeout(() => { b.disabled = true; }, 0); });
        });
    });
}

/** Buscador de productos (con BOM vigente) del formulario. */
function initProductPicker(root) {
    const searchUrl = root.dataset.searchUrl;
    const input = root.querySelector('[data-picker-input]');
    const hidden = root.querySelector('[data-picker-id]');
    const list = root.querySelector('.prd-picker-list');
    if (!input || !hidden || !list) return;

    const run = debounce(async () => {
        const q = input.value.trim();
        hidden.value = '';
        if (q.length < 2) { list.hidden = true; return; }
        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const items = await res.json();
            list.innerHTML = '';
            if (!items.length) {
                const none = document.createElement('div');
                none.className = 'prd-picker-item';
                const s = document.createElement('small');
                s.textContent = 'Sin resultados (el producto necesita una BOM vigente)';
                none.appendChild(s);
                list.appendChild(none);
            }
            items.forEach((p) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'prd-picker-item';
                const name = document.createElement('span');
                name.textContent = p.name;
                const sku = document.createElement('small');
                sku.textContent = (p.sku || '') + ' · existencia ' + (p.stock ?? 0);
                b.append(name, sku);
                b.addEventListener('click', () => {
                    hidden.value = p.id;
                    input.value = p.name;
                    list.hidden = true;
                });
                list.appendChild(b);
            });
            list.hidden = false;
        } catch (e) { list.hidden = true; }
    }, 250);

    input.addEventListener('input', run);
    document.addEventListener('click', (e) => { if (!root.contains(e.target)) list.hidden = true; });
}

/* ───────────────────────── Calendario de servicios ───────────────────────── */

const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DOW = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const DOW_LONG = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const STATUS_TONE = { draft: 'neu', scheduled: 'info', in_progress: 'warn', completed: 'ok', cancelled: 'bad' };
const NO_TECH = 'Sin asignar';
const NO_PROJECT = 'Sin proyecto';

const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parseIso = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
const addDays = (d, n) => { const r = new Date(d); r.setDate(r.getDate() + n); return r; };
const startOfWeek = (d) => addDays(d, -((d.getDay() + 6) % 7)); // lunes

function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined && text !== null) n.textContent = text;
    return n;
}

function initCalendar(root) {
    const body = root.querySelector('[data-cal-body]');
    const titleEl = root.querySelector('[data-cal-title]');
    const toast = root.querySelector('[data-cal-toast]');
    const alertsEl = root.querySelector('[data-cal-alerts]');
    const panel = root.querySelector('[data-cal-panel]');
    const canEdit = root.dataset.canEdit === '1';
    const csrf = root.dataset.csrf;
    const state = { view: 'month', group: 'tech', cursor: new Date(), events: [], selected: null };
    const HOUR_FROM = 6;
    const HOUR_TO = 21;
    const MAX_MONTH_EVENTS = 3;
    let dragged = null;

    const todayIso = () => iso(new Date());
    const isLate = (ev) => !['completed', 'cancelled'].includes(ev.status) && ev.date < todayIso();

    const filters = () => {
        const f = {};
        root.querySelectorAll('[data-cal-filter]').forEach((s) => { if (s.value) f[s.dataset.calFilter] = s.value; });
        return f;
    };

    function range() {
        const c = state.cursor;
        if (state.view === 'day') return [new Date(c), new Date(c)];
        if (state.view === 'week') { const s = startOfWeek(c); return [s, addDays(s, 6)]; }
        const first = new Date(c.getFullYear(), c.getMonth(), 1);
        const last = new Date(c.getFullYear(), c.getMonth() + 1, 0);
        return [startOfWeek(first), addDays(startOfWeek(last), 6)];
    }

    function title() {
        const c = state.cursor;
        if (state.view === 'month') return `${MONTHS[c.getMonth()]} ${c.getFullYear()}`;
        if (state.view === 'day') return `${DOW_LONG[c.getDay()]} ${c.getDate()} de ${MONTHS[c.getMonth()]} ${c.getFullYear()}`;
        const [s, e] = range();
        return `${s.getDate()} ${MONTHS[s.getMonth()].slice(0, 3)} – ${e.getDate()} ${MONTHS[e.getMonth()].slice(0, 3)} ${e.getFullYear()}`;
    }

    function flash(msg, bad) {
        toast.textContent = msg;
        toast.className = 'cal-toast ' + (bad ? 'is-bad' : 'is-ok');
        toast.hidden = false;
        clearTimeout(flash.t);
        flash.t = setTimeout(() => { toast.hidden = true; }, 3500);
    }

    async function load() {
        const [s, e] = range();
        titleEl.textContent = title();
        const qs = new URLSearchParams({ start: iso(s), end: iso(e), ...filters() });
        try {
            const res = await fetch(`${root.dataset.eventsUrl}?${qs}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('http ' + res.status);
            state.events = (await res.json()).events || [];
        } catch (err) {
            state.events = [];
            flash('No se pudieron cargar los servicios.', true);
        }
        if (state.selected) state.selected = state.events.find((x) => x.id === state.selected.id) || null;
        render();
    }

    /* ── Panel lateral con el servicio seleccionado ── */
    function renderPanel() {
        if (!panel) return;
        panel.innerHTML = '';
        const ev = state.selected;
        if (!ev) {
            panel.appendChild(el('div', 'cal-panel-empty', 'Selecciona un servicio del calendario para ver su detalle.'));
            return;
        }
        panel.appendChild(el('span', 'erp-eyebrow', 'SERVICIO TÉCNICO'));
        const head = el('div', 'cal-panel-head');
        head.appendChild(el('h2', 'cal-panel-title', ev.number || ev.title));
        const chip = el('span', `erp-chip erp-chip--${STATUS_TONE[ev.status] || 'neu'}`);
        chip.append(el('i', 'erp-dot'), document.createTextNode(ev.status_label || ev.status));
        head.appendChild(chip);
        panel.appendChild(head);
        if (isLate(ev)) panel.appendChild(el('div', 'cal-panel-late', 'Servicio retrasado: la fecha programada ya pasó.'));
        const dl = el('dl', 'cal-panel-facts');
        const when = parseIso(ev.date);
        const rows = [
            ['Cliente', ev.customer],
            ['Tipo', ev.type],
            ['Proyecto', ev.project],
            ['Fecha', `${DOW_LONG[when.getDay()]} ${when.getDate()} de ${MONTHS[when.getMonth()]}`],
            ['Horario', ev.time ? `${ev.time}${ev.end_time ? ' – ' + ev.end_time : ''}` : 'Todo el día'],
            ['Prioridad', ev.priority_label || ev.priority],
            ['Técnicos', ev.technicians.length ? ev.technicians.join(', ') : NO_TECH],
        ];
        rows.forEach(([k, v]) => {
            const d = el('div');
            d.append(el('dt', null, k), el('dd', null, v || '—'));
            dl.appendChild(d);
        });
        panel.appendChild(dl);
        const actions = el('div', 'cal-panel-actions');
        const open = el('a', 'erp-btn erp-btn--primary', 'Abrir servicio');
        open.href = ev.url;
        actions.appendChild(open);
        const close = el('button', 'erp-btn', 'Cerrar');
        close.type = 'button';
        close.addEventListener('click', () => select(null));
        actions.appendChild(close);
        panel.appendChild(actions);
        if (canEdit && ev.editable) panel.appendChild(el('p', 'cal-panel-hint', 'Arrastra la tarjeta a otro día para reprogramar.'));
    }

    function select(ev) {
        state.selected = ev;
        root.querySelectorAll('.cal-ev').forEach((n) => n.classList.toggle('is-selected', !!ev && Number(n.dataset.id) === ev.id));
        renderPanel();
        if (ev && window.matchMedia('(max-width: 1000px)').matches && panel) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── Alertas calculadas con el periodo cargado ── */
    function renderAlerts() {
        if (!alertsEl) return;
        alertsEl.innerHTML = '';
        const active = state.events.filter((e) => !['completed', 'cancelled'].includes(e.status));
        const groups = [
            { tone: 'bad', title: 'Servicios retrasados', list: active.filter(isLate), text: (n) => `${n} ${n === 1 ? 'servicio con fecha vencida sin completar' : 'servicios con fecha vencida sin completar'}.` },
            { tone: 'warn', title: 'Sin técnico asignado', list: active.filter((e) => !e.technicians.length), text: (n) => `${n} ${n === 1 ? 'servicio necesita' : 'servicios necesitan'} técnico.` },
            { tone: 'acc', title: 'Prioridad urgente', list: active.filter((e) => e.priority === 'urgent'), text: (n) => `${n} ${n === 1 ? 'servicio urgente' : 'servicios urgentes'} en el periodo.` },
        ].filter((g) => g.list.length);
        alertsEl.hidden = !groups.length;
        groups.forEach((g) => {
            const b = el('button', `cal-alert cal-alert--${g.tone}`);
            b.type = 'button';
            b.append(el('i', `erp-dot erp-dot--${g.tone}`));
            const wrap = el('div');
            wrap.append(el('strong', null, g.title), el('span', null, g.text(g.list.length)));
            b.appendChild(wrap);
            b.addEventListener('click', () => select(g.list[0]));
            alertsEl.appendChild(b);
        });
    }

    /* ── Tarjeta de evento ── */
    function eventEl(ev, opts = {}) {
        const a = el('div', `cal-ev cal-st-${ev.status} cal-pr-${ev.priority}` + (state.selected && state.selected.id === ev.id ? ' is-selected' : ''));
        a.tabIndex = 0;
        a.setAttribute('role', 'button');
        a.dataset.id = ev.id;
        const top = el('span', 'cal-ev-top');
        top.appendChild(el('span', 'cal-ev-time', ev.time ? `${ev.time}${ev.end_time && !opts.compact ? '–' + ev.end_time : ''}` : 'Todo el día'));
        if (isLate(ev)) top.appendChild(el('span', 'cal-ev-late', 'RETRASO'));
        a.append(top, el('span', 'cal-ev-title', ev.title));
        if (!opts.compact) {
            const meta = [ev.type, ev.technicians.join(', ')].filter(Boolean).join(' · ');
            if (meta) a.appendChild(el('span', 'cal-ev-meta', meta));
        }
        a.title = [ev.status_label, ev.type, ev.project && `Proyecto ${ev.project}`, ev.technicians.length && `Técnicos: ${ev.technicians.join(', ')}`, ev.priority !== 'normal' && `Prioridad ${ev.priority_label}`]
            .filter(Boolean).join(' · ');
        a.setAttribute('aria-label', `${ev.title}, ${a.title}`);
        a.addEventListener('click', () => select(ev));
        a.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(ev); } });
        if (canEdit && ev.editable) {
            a.draggable = true;
            a.classList.add('is-draggable');
            a.addEventListener('dragstart', (e) => {
                dragged = ev;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', String(ev.id));
                a.classList.add('is-dragging');
            });
            a.addEventListener('dragend', () => { a.classList.remove('is-dragging'); dragged = null; });
        }
        return a;
    }

    function makeDropTarget(node, date, time) {
        if (!canEdit) return;
        node.addEventListener('dragover', (e) => { if (dragged) { e.preventDefault(); node.classList.add('is-over'); } });
        node.addEventListener('dragleave', () => node.classList.remove('is-over'));
        node.addEventListener('drop', (e) => {
            e.preventDefault();
            node.classList.remove('is-over');
            if (dragged) reschedule(dragged, date, time);
        });
    }

    async function reschedule(ev, date, time) {
        if (ev.date === date && (!time || time === ev.time)) return;
        const url = root.dataset.rescheduleUrl.replace('__ID__', ev.id);
        const payload = { service_date: date };
        if (time) payload.service_time = time;
        try {
            const res = await fetch(url, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                flash(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'No se pudo reprogramar.'), true);
            } else {
                flash('Servicio reprogramado.', false);
            }
        } catch (e) {
            flash('Error de red al reprogramar.', true);
        }
        load();
    }

    const byDate = (list = state.events) => {
        const m = {};
        list.forEach((ev) => { (m[ev.date] = m[ev.date] || []).push(ev); });
        return m;
    };

    /* ── Vista mes ── */
    function renderMonth(days) {
        const map = byDate();
        const today = todayIso();
        const grid = el('div', 'cal-grid');
        DOW.forEach((d) => grid.appendChild(el('div', 'cal-dow', d)));
        days.forEach((d) => {
            const key = iso(d);
            const cell = el('div', 'cal-cell' + (d.getMonth() !== state.cursor.getMonth() ? ' is-out' : '') + (key === today ? ' is-today' : ''));
            const num = el('button', 'cal-daynum', d.getDate());
            num.type = 'button';
            num.setAttribute('aria-label', `Ver el día ${d.getDate()} de ${MONTHS[d.getMonth()]}`);
            num.addEventListener('click', () => { state.cursor = d; state.view = 'day'; syncViewButtons(); load(); });
            cell.appendChild(num);
            const evs = map[key] || [];
            evs.slice(0, MAX_MONTH_EVENTS).forEach((ev) => cell.appendChild(eventEl(ev, { compact: true })));
            if (evs.length > MAX_MONTH_EVENTS) {
                const more = el('button', 'cal-more', `+${evs.length - MAX_MONTH_EVENTS} más`);
                more.type = 'button';
                more.addEventListener('click', () => { state.cursor = d; state.view = 'day'; syncViewButtons(); load(); });
                cell.appendChild(more);
            }
            makeDropTarget(cell, key, null);
            grid.appendChild(cell);
        });
        return grid;
    }

    /* ── Vista semana: filas por técnico o por proyecto ── */
    function weekRows() {
        const events = state.events;
        const rows = new Map();
        const add = (label, sub, ev) => {
            if (!rows.has(label)) rows.set(label, { label, sub, events: [] });
            if (ev) rows.get(label).events.push(ev);
        };
        if (state.group === 'tech') {
            const sel = root.querySelector('[data-cal-filter="technician_id"]');
            if (sel) {
                [...sel.options].filter((o) => o.value).forEach((o) => {
                    if (!sel.value || sel.value === o.value) add(o.textContent.trim(), 'Técnico', null);
                });
            }
            events.forEach((ev) => {
                if (!ev.technicians.length) add(NO_TECH, 'Requieren técnico', ev);
                else ev.technicians.forEach((t) => add(t, 'Técnico', ev));
            });
        } else {
            events.forEach((ev) => add(ev.project ? `Proyecto ${ev.project}` : NO_PROJECT, ev.project ? 'Proyecto' : 'Servicios sueltos', ev));
        }
        return [...rows.values()];
    }

    function renderWeek(days) {
        const today = todayIso();
        const wrap = el('div', 'cal-week-scroll');
        const inner = el('div', 'cal-week');
        const head = el('div', 'cal-week-row cal-week-head');
        head.appendChild(el('div', 'cal-week-label', state.group === 'tech' ? 'Asignado' : 'Proyecto'));
        days.forEach((d) => {
            const h = el('div', 'cal-week-dayhead' + (iso(d) === today ? ' is-today' : ''), `${DOW[(d.getDay() + 6) % 7]} ${d.getDate()}`);
            head.appendChild(h);
        });
        inner.appendChild(head);
        const rows = weekRows();
        if (!rows.length) {
            inner.appendChild(el('div', 'cal-week-empty', 'Sin servicios en esta semana.'));
        }
        rows.forEach((r) => {
            const row = el('div', 'cal-week-row');
            const lab = el('div', 'cal-week-label');
            lab.append(el('strong', null, r.label), el('span', null, `${r.sub} · ${r.events.length} ${r.events.length === 1 ? 'servicio' : 'servicios'}`));
            row.appendChild(lab);
            const map = byDate(r.events);
            days.forEach((d) => {
                const key = iso(d);
                const cell = el('div', 'cal-week-cell' + (key === today ? ' is-today' : ''));
                (map[key] || []).forEach((ev) => cell.appendChild(eventEl(ev)));
                makeDropTarget(cell, key, null);
                row.appendChild(cell);
            });
            inner.appendChild(row);
        });
        wrap.appendChild(inner);
        return wrap;
    }

    /* ── Vista día (por hora, para reprogramar la hora arrastrando) ── */
    function renderDay() {
        const key = iso(state.cursor);
        const evs = state.events.filter((e) => e.date === key);
        const wrap = el('div', 'cal-day');
        const hourOf = (e) => parseInt(e.time.slice(0, 2), 10);
        const adRow = el('div', 'cal-hour');
        adRow.appendChild(el('div', 'cal-hour-label', 'Sin hora'));
        const adSlot = el('div', 'cal-hour-slot');
        evs.filter((e) => !e.time).forEach((ev) => adSlot.appendChild(eventEl(ev)));
        evs.filter((e) => e.time && (hourOf(e) < HOUR_FROM || hourOf(e) > HOUR_TO)).forEach((ev) => adSlot.appendChild(eventEl(ev)));
        makeDropTarget(adSlot, key, null);
        adRow.appendChild(adSlot);
        wrap.appendChild(adRow);
        for (let h = HOUR_FROM; h <= HOUR_TO; h++) {
            const row = el('div', 'cal-hour');
            row.appendChild(el('div', 'cal-hour-label', `${pad(h)}:00`));
            const slot = el('div', 'cal-hour-slot');
            evs.filter((e) => e.time && hourOf(e) === h).forEach((ev) => slot.appendChild(eventEl(ev)));
            makeDropTarget(slot, key, `${pad(h)}:00`);
            row.appendChild(slot);
            wrap.appendChild(row);
        }
        return wrap;
    }

    /** Agenda por día: se muestra en móvil (la rejilla se oculta con CSS). */
    function renderAgenda() {
        const [s, e] = range();
        const map = byDate();
        const list = el('div', 'cal-agenda');
        let any = false;
        for (let d = new Date(s); d <= e; d = addDays(d, 1)) {
            const key = iso(d);
            const evs = map[key] || [];
            if (!evs.length) continue;
            any = true;
            list.appendChild(el('div', 'cal-agenda-day', `${DOW_LONG[d.getDay()]} ${d.getDate()} de ${MONTHS[d.getMonth()]}`));
            evs.forEach((ev) => {
                const row = el('div', 'cal-agenda-row');
                row.appendChild(eventEl(ev));
                if (canEdit && ev.editable) {
                    const inp = el('input', 'erp-input prd-input-sm');
                    inp.type = 'date';
                    inp.value = ev.date;
                    inp.title = 'Reprogramar';
                    inp.setAttribute('aria-label', `Reprogramar ${ev.title}`);
                    inp.addEventListener('change', () => { if (inp.value) reschedule(ev, inp.value, null); });
                    row.appendChild(inp);
                }
                list.appendChild(row);
            });
        }
        if (!any) list.appendChild(el('div', 'prd-empty', 'Sin servicios en este periodo.'));
        return list;
    }

    function render() {
        body.innerHTML = '';
        const [s, e] = range();
        const days = [];
        for (let d = new Date(s); d <= e; d = addDays(d, 1)) days.push(new Date(d));
        const main = state.view === 'day' ? renderDay() : state.view === 'week' ? renderWeek(days) : renderMonth(days);
        main.classList.add('cal-main');
        body.appendChild(main);
        const agenda = renderAgenda();
        agenda.classList.add('cal-agenda-wrap');
        body.appendChild(agenda);
        renderAlerts();
        renderPanel();
    }

    function syncViewButtons() {
        root.querySelectorAll('[data-cal-view]').forEach((b) => {
            const on = b.dataset.calView === state.view;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        root.querySelectorAll('[data-cal-group]').forEach((b) => {
            const on = b.dataset.calGroup === state.group;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        const groups = root.querySelector('[data-cal-groups]');
        if (groups) groups.hidden = state.view !== 'week';
    }

    function shift(dir) {
        const c = state.cursor;
        if (state.view === 'month') state.cursor = new Date(c.getFullYear(), c.getMonth() + dir, 1);
        else state.cursor = addDays(c, dir * (state.view === 'week' ? 7 : 1));
        load();
    }

    root.querySelector('[data-cal-prev]').addEventListener('click', () => shift(-1));
    root.querySelector('[data-cal-next]').addEventListener('click', () => shift(1));
    root.querySelector('[data-cal-today]').addEventListener('click', () => { state.cursor = new Date(); load(); });
    root.querySelectorAll('[data-cal-view]').forEach((b) => b.addEventListener('click', () => {
        state.view = b.dataset.calView;
        syncViewButtons();
        load();
    }));
    root.querySelectorAll('[data-cal-group]').forEach((b) => b.addEventListener('click', () => {
        state.group = b.dataset.calGroup;
        syncViewButtons();
        render();
    }));
    root.querySelectorAll('[data-cal-filter]').forEach((s) => s.addEventListener('change', load));

    syncViewButtons();
    load();
}

document.addEventListener('DOMContentLoaded', () => {
    initConfirms();
    const form = document.querySelector('[data-prd-form]');
    if (form) initProductPicker(form);
    const cal = document.querySelector('[data-service-calendar]');
    if (cal) initCalendar(cal);
});
