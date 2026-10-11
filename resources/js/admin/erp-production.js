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

const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parseIso = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
const addDays = (d, n) => { const r = new Date(d); r.setDate(r.getDate() + n); return r; };
const startOfWeek = (d) => addDays(d, -((d.getDay() + 6) % 7)); // lunes

function initCalendar(root) {
    const body = root.querySelector('[data-cal-body]');
    const titleEl = root.querySelector('[data-cal-title]');
    const toast = root.querySelector('[data-cal-toast]');
    const canEdit = root.dataset.canEdit === '1';
    const csrf = root.dataset.csrf;
    const state = { view: 'month', cursor: new Date(), events: [] };
    const HOUR_FROM = 6;
    const HOUR_TO = 21;
    let dragged = null;

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
        render();
    }

    function eventEl(ev) {
        const a = document.createElement(canEdit && ev.editable ? 'div' : 'div');
        a.className = `cal-ev cal-st-${ev.status} cal-pr-${ev.priority}`;
        a.tabIndex = 0;
        a.dataset.id = ev.id;
        const t = document.createElement('span');
        t.className = 'cal-ev-time';
        t.textContent = ev.time ? `${ev.time}${ev.end_time ? '–' + ev.end_time : ''}` : 'Todo el día';
        const n = document.createElement('span');
        n.className = 'cal-ev-title';
        n.textContent = ev.title;
        a.append(t, n);
        const tip = [ev.status_label, ev.type, ev.project && `Proyecto ${ev.project}`, ev.technicians.length && `Técnicos: ${ev.technicians.join(', ')}`, ev.priority !== 'normal' && `Prioridad ${ev.priority_label}`]
            .filter(Boolean).join(' · ');
        a.title = tip;
        a.addEventListener('click', () => { window.location.href = ev.url; });
        a.addEventListener('keydown', (e) => { if (e.key === 'Enter') window.location.href = ev.url; });
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

    function makeDropTarget(el, date, time) {
        if (!canEdit) return;
        el.addEventListener('dragover', (e) => { if (dragged) { e.preventDefault(); el.classList.add('is-over'); } });
        el.addEventListener('dragleave', () => el.classList.remove('is-over'));
        el.addEventListener('drop', (e) => {
            e.preventDefault();
            el.classList.remove('is-over');
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

    const byDate = () => {
        const m = {};
        state.events.forEach((ev) => { (m[ev.date] = m[ev.date] || []).push(ev); });
        return m;
    };

    function renderGrid(days, compact) {
        const map = byDate();
        const todayIso = iso(new Date());
        const grid = document.createElement('div');
        grid.className = 'cal-grid';
        DOW.forEach((d) => { const h = document.createElement('div'); h.className = 'cal-dow'; h.textContent = d; grid.appendChild(h); });
        days.forEach((d) => {
            const key = iso(d);
            const cell = document.createElement('div');
            cell.className = 'cal-cell' + (compact && d.getMonth() !== state.cursor.getMonth() ? ' is-out' : '') + (key === todayIso ? ' is-today' : '');
            const num = document.createElement('button');
            num.type = 'button';
            num.className = 'cal-daynum';
            num.textContent = d.getDate();
            num.addEventListener('click', () => { state.cursor = d; state.view = 'day'; syncViewButtons(); load(); });
            cell.appendChild(num);
            (map[key] || []).forEach((ev) => cell.appendChild(eventEl(ev)));
            makeDropTarget(cell, key, null);
            grid.appendChild(cell);
        });
        return grid;
    }

    function renderDay() {
        const key = iso(state.cursor);
        const evs = state.events.filter((e) => e.date === key);
        const wrap = document.createElement('div');
        wrap.className = 'cal-day';
        const allDay = evs.filter((e) => !e.time);
        const adRow = document.createElement('div');
        adRow.className = 'cal-hour';
        const adLabel = document.createElement('div');
        adLabel.className = 'cal-hour-label';
        adLabel.textContent = 'Sin hora';
        const adSlot = document.createElement('div');
        adSlot.className = 'cal-hour-slot';
        allDay.forEach((ev) => adSlot.appendChild(eventEl(ev)));
        makeDropTarget(adSlot, key, null);
        adRow.append(adLabel, adSlot);
        wrap.appendChild(adRow);
        for (let h = HOUR_FROM; h <= HOUR_TO; h++) {
            const row = document.createElement('div');
            row.className = 'cal-hour';
            const label = document.createElement('div');
            label.className = 'cal-hour-label';
            label.textContent = `${pad(h)}:00`;
            const slot = document.createElement('div');
            slot.className = 'cal-hour-slot';
            evs.filter((e) => e.time && parseInt(e.time.slice(0, 2), 10) === h).forEach((ev) => slot.appendChild(eventEl(ev)));
            makeDropTarget(slot, key, `${pad(h)}:00`);
            row.append(label, slot);
            wrap.appendChild(row);
        }
        // Eventos fuera del horario visible.
        const outside = evs.filter((e) => e.time && (parseInt(e.time.slice(0, 2), 10) < HOUR_FROM || parseInt(e.time.slice(0, 2), 10) > HOUR_TO));
        outside.forEach((ev) => adSlot.appendChild(eventEl(ev)));
        return wrap;
    }

    /** Agenda por día: se muestra en móvil (el grid se oculta con CSS). */
    function renderAgenda() {
        const [s, e] = range();
        const map = byDate();
        const list = document.createElement('div');
        list.className = 'cal-agenda';
        let any = false;
        for (let d = new Date(s); d <= e; d = addDays(d, 1)) {
            const key = iso(d);
            const evs = map[key] || [];
            if (!evs.length) continue;
            any = true;
            const h = document.createElement('div');
            h.className = 'cal-agenda-day';
            h.textContent = `${DOW_LONG[d.getDay()]} ${d.getDate()} de ${MONTHS[d.getMonth()]}`;
            list.appendChild(h);
            evs.forEach((ev) => {
                const row = document.createElement('div');
                row.className = 'cal-agenda-row';
                row.appendChild(eventEl(ev));
                if (canEdit && ev.editable) {
                    const inp = document.createElement('input');
                    inp.type = 'date';
                    inp.value = ev.date;
                    inp.className = 'prd-input prd-input-sm';
                    inp.title = 'Reprogramar';
                    inp.addEventListener('change', () => { if (inp.value) reschedule(ev, inp.value, null); });
                    row.appendChild(inp);
                }
                list.appendChild(row);
            });
        }
        if (!any) {
            const empty = document.createElement('div');
            empty.className = 'prd-empty';
            empty.textContent = 'Sin servicios en este periodo.';
            list.appendChild(empty);
        }
        return list;
    }

    function render() {
        body.innerHTML = '';
        const [s, e] = range();
        const days = [];
        for (let d = new Date(s); d <= e; d = addDays(d, 1)) days.push(new Date(d));
        const main = state.view === 'day' ? renderDay() : renderGrid(days, state.view === 'month');
        main.classList.add('cal-main');
        body.appendChild(main);
        const agenda = renderAgenda();
        agenda.classList.add('cal-agenda-wrap');
        body.appendChild(agenda);
    }

    function syncViewButtons() {
        root.querySelectorAll('[data-cal-view]').forEach((b) => b.classList.toggle('is-active', b.dataset.calView === state.view));
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
    root.querySelectorAll('[data-cal-filter]').forEach((s) => s.addEventListener('change', load));

    // Vista inicial: agenda semanal en móvil sigue usando el grid oculto por CSS.
    load();
}

document.addEventListener('DOMContentLoaded', () => {
    initConfirms();
    const form = document.querySelector('[data-prd-form]');
    if (form) initProductPicker(form);
    const cal = document.querySelector('[data-service-calendar]');
    if (cal) initCalendar(cal);
});
