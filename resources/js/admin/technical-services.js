/* ============================================================
   TECHNICAL SERVICES — Vanilla JS
   ============================================================ */

(function () {
    'use strict';

    // ── State ───────────────────────────────────────────────
    const state = {
        viewMode: 'calendar',       // 'calendar' | 'table'
        periodView: 'month',        // 'month' | 'week' | 'day'
        currentDate: new Date(),
        services: [],
        filterTechnician: '',
        filterStatus: '',
        draggingId: null,
        tooltip: { el: null, serviceId: null },
        autosaveTimer: null,
        autosaveLastSaved: null,
    };

    // ── DOM refs (set on init) ───────────────────────────────
    let calendarEl, calendarView, tableView, tooltip, overlay, quickModal;

    // ── Helpers ─────────────────────────────────────────────
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function pad(n) { return String(n).padStart(2, '0'); }

    function dateKey(d) {
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }

    function formatDateDisplay(iso) {
        if (!iso) return '—';
        const [y, m, d] = iso.split('-');
        const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        return `${d} ${months[parseInt(m,10)-1]} ${y}`;
    }

    function monthName(m) {
        return ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][m];
    }

    function dayName(d) {
        return ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'][d];
    }

    function statusClass(s) {
        return {
            scheduled:   'ts-event--scheduled',
            in_progress: 'ts-event--in_progress',
            completed:   'ts-event--completed',
            cancelled:   'ts-event--cancelled',
        }[s] ?? 'ts-event--scheduled';
    }

    function statusLabel(s) {
        return {
            scheduled:   'Programado',
            in_progress: 'En Proceso',
            completed:   'Completado',
            cancelled:   'Cancelado',
        }[s] ?? s;
    }

    function getFilteredServices() {
        return state.services.filter(sv => {
            if (state.filterStatus && sv.status !== state.filterStatus) return false;
            if (state.filterTechnician) {
                const techs = (sv.assigned_technicians ?? []).map(t => String(t.id));
                if (!techs.includes(String(state.filterTechnician))) return false;
            }
            return true;
        });
    }

    // ── Calendar: Month View ─────────────────────────────────
    function buildMonthGrid() {
        const year  = state.currentDate.getFullYear();
        const month = state.currentDate.getMonth();
        const today = new Date();
        const todayKey = dateKey(today);

        const firstDay = new Date(year, month, 1);
        // Monday-based: Sun=6, Mon=0 … Sat=5
        let startOffset = (firstDay.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrev  = new Date(year, month, 0).getDate();

        const services = getFilteredServices();
        const byDate = {};
        services.forEach(sv => {
            const k = sv.service_date ? sv.service_date.substring(0, 10) : null;
            if (k) { (byDate[k] = byDate[k] ?? []).push(sv); }
        });

        // Header
        const headHtml = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']
            .map(d => `<div class="ts-cal-head__day">${d}</div>`).join('');

        // Cells
        let cells = '';
        const totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;

        for (let i = 0; i < totalCells; i++) {
            let day, iso, isCurrentMonth = true;

            if (i < startOffset) {
                day = daysInPrev - startOffset + i + 1;
                const m = month === 0 ? 11 : month - 1;
                const y = month === 0 ? year - 1 : year;
                iso = `${y}-${pad(m+1)}-${pad(day)}`;
                isCurrentMonth = false;
            } else if (i >= startOffset + daysInMonth) {
                day = i - startOffset - daysInMonth + 1;
                const m = month === 11 ? 0 : month + 1;
                const y = month === 11 ? year + 1 : year;
                iso = `${y}-${pad(m+1)}-${pad(day)}`;
                isCurrentMonth = false;
            } else {
                day = i - startOffset + 1;
                iso = `${year}-${pad(month+1)}-${pad(day)}`;
            }

            const isToday = iso === todayKey;
            const cellClass = [
                'ts-cal-cell',
                !isCurrentMonth ? 'ts-cal-cell--other-month' : '',
                isToday ? 'ts-cal-cell--today' : '',
            ].filter(Boolean).join(' ');

            const numClass = isToday
                ? 'ts-cell-number ts-cell-number--today'
                : isCurrentMonth ? 'ts-cell-number' : 'ts-cell-number ts-cell-number--other';

            const dayEvents = (byDate[iso] ?? []).slice(0, 3);
            const extra = (byDate[iso] ?? []).length - 3;

            const eventsHtml = dayEvents.map(sv => buildEventEl(sv)).join('');
            const moreHtml = extra > 0
                ? `<div class="ts-event-more">+${extra} más</div>` : '';

            cells += `
            <div class="${cellClass}"
                 data-date="${iso}"
                 data-current="${isCurrentMonth}"
                 ondragover="TechnicalServices.onDragOver(event)"
                 ondrop="TechnicalServices.onDrop(event)"
                 onclick="TechnicalServices.onCellClick(event, '${iso}')">
                <div class="ts-cell-inner">
                    <div class="ts-cell-number-wrap">
                        <div class="${numClass}">${day}</div>
                    </div>
                    <div class="ts-cell-events">${eventsHtml}${moreHtml}</div>
                </div>
            </div>`;
        }

        return `
        <div class="ts-cal-head">${headHtml}</div>
        <div class="ts-cal-body">${cells}</div>`;
    }

    function buildEventEl(sv) {
        const label = `${sv.service_type_label ?? 'Servicio'} — ${sv.customer_name ?? ''}`;
        return `<div class="ts-event ${statusClass(sv.status)}"
                     draggable="true"
                     data-id="${sv.id}"
                     onmouseenter="TechnicalServices.showTooltip(event, ${sv.id})"
                     onmouseleave="TechnicalServices.hideTooltip()"
                     ondragstart="TechnicalServices.onDragStart(event, ${sv.id})"
                     onclick="TechnicalServices.onEventClick(event, ${sv.id})"
                     title="${label}">
                    ${label}
               </div>`;
    }

    // ── Calendar: Week View ─────────────────────────────────
    function buildWeekGrid() {
        const d = new Date(state.currentDate);
        const dayOfWeek = (d.getDay() + 6) % 7; // Mon=0
        d.setDate(d.getDate() - dayOfWeek);

        const today = new Date();
        const todayKey = dateKey(today);

        const services = getFilteredServices();
        const byDate = {};
        services.forEach(sv => {
            const k = sv.service_date ? sv.service_date.substring(0, 10) : null;
            if (k) { (byDate[k] = byDate[k] ?? []).push(sv); }
        });

        const days = [];
        for (let i = 0; i < 7; i++) {
            const dd = new Date(d);
            dd.setDate(d.getDate() + i);
            days.push(dd);
        }

        const headCells = ['<div class="ts-week-head__label"></div>',
            ...days.map(dd => {
                const iso = dateKey(dd);
                const isToday = iso === todayKey;
                return `<div class="ts-week-head__label${isToday?' ts-week-head__label--today':''}">
                    ${dayName(dd.getDay())}<span>${dd.getDate()}</span>
                </div>`;
            })].join('');

        const hours = [];
        for (let h = 6; h <= 20; h++) hours.push(h);

        const rows = hours.map(h => {
            const timeLabel = `${pad(h)}:00`;
            const dayCols = days.map(dd => {
                const iso = dateKey(dd);
                const evs = (byDate[iso] ?? []).filter(sv => {
                    if (!sv.service_time) return false;
                    const sh = parseInt(sv.service_time.split(':')[0], 10);
                    return sh === h;
                });
                const evHtml = evs.map(sv => buildEventEl(sv)).join('');
                return `<div class="ts-week-day-col"
                             data-date="${iso}"
                             ondragover="TechnicalServices.onDragOver(event)"
                             ondrop="TechnicalServices.onDrop(event)"
                             onclick="TechnicalServices.onCellClick(event, '${iso}')">
                             ${evHtml}
                        </div>`;
            }).join('');
            return `<div class="ts-week-time-col">${timeLabel}</div>${dayCols}`;
        }).join('');

        return `
        <div class="ts-week-head" style="display:grid;grid-template-columns:60px repeat(7,1fr)">${headCells}</div>
        <div class="ts-week-grid" style="display:grid;grid-template-columns:60px repeat(7,1fr)">${rows}</div>`;
    }

    // ── Calendar: Day View ──────────────────────────────────
    function buildDayGrid() {
        const d = state.currentDate;
        const iso = dateKey(d);
        const services = getFilteredServices().filter(sv =>
            sv.service_date && sv.service_date.substring(0,10) === iso
        );

        const dayNames = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const header = `<div class="ts-day-header">
            ${dayNames[d.getDay()]} ${d.getDate()} de ${monthName(d.getMonth())} ${d.getFullYear()}
        </div>`;

        const hours = [];
        for (let h = 6; h <= 22; h++) hours.push(h);

        const slots = hours.map(h => {
            const evs = services.filter(sv => {
                if (!sv.service_time) return h === 8;
                return parseInt(sv.service_time.split(':')[0], 10) === h;
            });
            const evHtml = evs.map(sv => buildEventEl(sv)).join('');
            return `<div class="ts-day-slot">
                <div class="ts-day-slot__time">${pad(h)}:00</div>
                <div class="ts-day-slot__events"
                     data-date="${iso}"
                     ondragover="TechnicalServices.onDragOver(event)"
                     ondrop="TechnicalServices.onDrop(event)"
                     onclick="TechnicalServices.onCellClick(event, '${iso}')">
                     ${evHtml}
                </div>
            </div>`;
        }).join('');

        return `${header}${slots}`;
    }

    // ── Render ──────────────────────────────────────────────
    function renderCalendar() {
        if (!calendarEl) return;

        updateNavTitle();

        let html = '';
        if (state.periodView === 'month')      html = buildMonthGrid();
        else if (state.periodView === 'week')  html = buildWeekGrid();
        else                                    html = buildDayGrid();

        calendarEl.innerHTML = `<div class="ts-calendar-wrap">${html}</div>`;
    }

    function updateNavTitle() {
        const el = document.getElementById('ts-nav-title');
        if (!el) return;

        const d = state.currentDate;
        if (state.periodView === 'month') {
            el.textContent = `${monthName(d.getMonth())} ${d.getFullYear()}`;
        } else if (state.periodView === 'week') {
            const start = new Date(d);
            const offset = (d.getDay() + 6) % 7;
            start.setDate(d.getDate() - offset);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            el.textContent = `${start.getDate()} – ${end.getDate()} ${monthName(d.getMonth())} ${d.getFullYear()}`;
        } else {
            el.textContent = `${d.getDate()} de ${monthName(d.getMonth())} ${d.getFullYear()}`;
        }
    }

    // ── Navigation ──────────────────────────────────────────
    function navigate(dir) {
        const d = state.currentDate;
        if (state.periodView === 'month') {
            d.setMonth(d.getMonth() + dir);
        } else if (state.periodView === 'week') {
            d.setDate(d.getDate() + dir * 7);
        } else {
            d.setDate(d.getDate() + dir);
        }
        state.currentDate = new Date(d);
        renderCalendar();
    }

    function goToday() {
        state.currentDate = new Date();
        renderCalendar();
    }

    // ── Toggle view mode ────────────────────────────────────
    function setViewMode(mode) {
        state.viewMode = mode;
        document.querySelectorAll('.ts-view-toggle__btn').forEach(b => {
            b.classList.toggle('ts-view-toggle__btn--active', b.dataset.view === mode);
        });
        if (calendarView) calendarView.style.display = mode === 'calendar' ? '' : 'none';
        if (tableView)    tableView.style.display    = mode === 'table'    ? '' : 'none';
    }

    // ── Period tab ──────────────────────────────────────────
    function setPeriod(period) {
        state.periodView = period;
        document.querySelectorAll('.ts-period-tab').forEach(b => {
            b.classList.toggle('ts-period-tab--active', b.dataset.period === period);
        });
        renderCalendar();
    }

    // ── Filters ─────────────────────────────────────────────
    function applyFilters() {
        renderCalendar();
    }

    // ── Drag & Drop ─────────────────────────────────────────
    function onDragStart(e, id) {
        state.draggingId = id;
        e.dataTransfer.effectAllowed = 'move';
        e.target.classList.add('ts-event--dragging');
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const cell = e.currentTarget;
        cell.classList.add('ts-cal-cell--dragover');
    }

    function onDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const cell = e.currentTarget;
        cell.classList.remove('ts-cal-cell--dragover');

        const newDate = cell.dataset.date;
        const id = state.draggingId;
        if (!id || !newDate) return;

        updateEventDate(id, newDate);
    }

    async function updateEventDate(serviceId, newDate) {
        // optimistic update
        const sv = state.services.find(s => s.id == serviceId);
        const oldDate = sv ? sv.service_date : null;
        if (sv) sv.service_date = newDate;
        renderCalendar();

        try {
            const res = await fetch(`/admin/technical-services/${serviceId}/update-date`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ service_date: newDate }),
            });
            const data = await res.json();
            if (!data.success) {
                if (sv && oldDate) sv.service_date = oldDate;
                renderCalendar();
                showNotification(data.message ?? 'No se pudo mover el servicio.', 'error');
            }
        } catch {
            if (sv && oldDate) sv.service_date = oldDate;
            renderCalendar();
            showNotification('Error de red al mover el servicio.', 'error');
        }
    }

    // ── Tooltip ─────────────────────────────────────────────
    function showTooltip(e, id) {
        e.stopPropagation();
        const sv = state.services.find(s => s.id == id);
        if (!sv || !tooltip) return;

        const techs = (sv.assigned_technicians ?? [])
            .map(t => t.name).join(', ') || '—';

        tooltip.innerHTML = `
            <div class="ts-tooltip__num">${sv.service_number ?? ''}</div>
            <div class="ts-tooltip__title">${sv.service_type_label ?? 'Servicio'} — ${sv.customer_name ?? ''}</div>
            <div class="ts-tooltip__row">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                ${formatDateDisplay(sv.service_date)} ${sv.service_time ? '· ' + sv.service_time : ''}
            </div>
            <div class="ts-tooltip__row">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                ${techs}
            </div>
        `;

        const rect = e.target.getBoundingClientRect();
        let left = rect.right + 8;
        let top  = rect.top;

        tooltip.classList.add('ts-tooltip--visible');
        const tw = tooltip.offsetWidth;
        if (left + tw > window.innerWidth - 12) left = rect.left - tw - 8;
        tooltip.style.left = `${left}px`;
        tooltip.style.top  = `${top}px`;
    }

    function hideTooltip() {
        if (tooltip) tooltip.classList.remove('ts-tooltip--visible');
    }

    // ── Cell / Event click ──────────────────────────────────
    function onCellClick(e, iso) {
        // prevent if clicking on an event
        if (e.target.closest('.ts-event')) return;
        openQuickModal(iso);
    }

    function onEventClick(e, id) {
        e.stopPropagation();
        hideTooltip();
        const sv = state.services.find(s => s.id == id);
        if (sv) {
            window.location.href = `/admin/technical-services/${id}`;
        }
    }

    // ── Quick Modal (cell click) ─────────────────────────────
    function openQuickModal(iso) {
        if (!overlay) return;
        const input = document.getElementById('ts-quick-date');
        if (input) input.value = iso;
        overlay.classList.add('ts-overlay--open');
    }

    function closeQuickModal() {
        if (overlay) overlay.classList.remove('ts-overlay--open');
    }

    // ── Notification toast ──────────────────────────────────
    function showNotification(msg, type = 'info') {
        const existing = document.getElementById('ts-toast');
        if (existing) existing.remove();

        const el = document.createElement('div');
        el.id = 'ts-toast';
        Object.assign(el.style, {
            position: 'fixed',
            bottom: '1.5rem',
            right: '1.5rem',
            padding: '0.75rem 1.25rem',
            borderRadius: '8px',
            fontSize: '0.875rem',
            fontWeight: '500',
            zIndex: '9999',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            transition: 'opacity 0.3s',
            background: type === 'error' ? '#FEE2E2' : '#F0FDF4',
            color:      type === 'error' ? '#DC2626'  : '#16A34A',
            border:     `1px solid ${type === 'error' ? '#FECACA' : '#BBF7D0'}`,
        });
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3500);
    }

    // ── Multi-step wizard ────────────────────────────────────
    function initWizard() {
        const form = document.getElementById('ts-wizard-form');
        if (!form) return;

        const btnNext = document.getElementById('ts-btn-next');
        const btnBack = document.getElementById('ts-btn-back');

        if (btnNext) {
            btnNext.addEventListener('click', async () => {
                if (!validateCurrentStep()) return;
                await saveCurrentStep();
            });
        }
        if (btnBack) {
            btnBack.addEventListener('click', () => {
                const step = parseInt(form.dataset.step ?? 1);
                if (step > 1) {
                    window.location.href = form.dataset.stepUrl.replace('__STEP__', step - 1);
                } else {
                    window.location.href = form.dataset.indexUrl;
                }
            });
        }

        initTechnicianSearch();
        initMaterialRows();
        startAutosave();
    }

    function validateCurrentStep() {
        const form = document.getElementById('ts-wizard-form');
        if (!form) return true;

        let valid = true;
        form.querySelectorAll('[required]').forEach(el => {
            const field = el.closest('.ts-field');
            if (!el.value.trim()) {
                valid = false;
                if (field) field.classList.add('has-error');
                const errEl = field ? field.querySelector('.ts-field-error') : null;
                if (errEl) errEl.textContent = 'Este campo es requerido.';
            } else {
                if (field) field.classList.remove('has-error');
            }
        });
        return valid;
    }

    async function saveCurrentStep() {
        const form = document.getElementById('ts-wizard-form');
        if (!form) return;

        const step = parseInt(form.dataset.step ?? 1);
        const url = form.dataset.saveUrl;
        if (!url) return;

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                body: formData,
            });
            const data = await res.json();
            if (data.success || data.redirect) {
                window.location.href = data.redirect ??
                    form.dataset.stepUrl.replace('__STEP__', step + 1);
            } else {
                showNotification(data.message ?? 'Error al guardar.', 'error');
                if (data.errors) showFieldErrors(data.errors);
            }
        } catch {
            showNotification('Error de red al guardar.', 'error');
        }
    }

    function showFieldErrors(errors) {
        Object.entries(errors).forEach(([name, msgs]) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (!el) return;
            const field = el.closest('.ts-field');
            if (field) {
                field.classList.add('has-error');
                const errEl = field.querySelector('.ts-field-error');
                if (errEl) errEl.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            }
        });
    }

    // ── Autosave ─────────────────────────────────────────────
    function startAutosave() {
        const form = document.getElementById('ts-wizard-form');
        if (!form) return;

        form.addEventListener('input', () => {
            clearTimeout(state.autosaveTimer);
            setAutosaveStatus('saving');
            state.autosaveTimer = setTimeout(async () => {
                const url = form.dataset.saveUrl;
                if (!url) return;
                const formData = new FormData(form);
                formData.append('_method', 'PUT');
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken() },
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.success || data.redirect) {
                        state.autosaveLastSaved = new Date();
                        setAutosaveStatus('saved');
                    }
                } catch { /* silent */ }
            }, 2000);
        });

        setInterval(() => {
            if (state.autosaveLastSaved) {
                const secs = Math.round((Date.now() - state.autosaveLastSaved) / 1000);
                const msg = secs < 60
                    ? `Guardado hace ${secs}s`
                    : `Guardado hace ${Math.floor(secs/60)}min`;
                setAutosaveStatus('saved', msg);
            }
        }, 30000);
    }

    function setAutosaveStatus(status, customMsg) {
        const el = document.getElementById('ts-autosave');
        if (!el) return;
        el.className = `ts-autosave ts-autosave--${status}`;
        el.textContent = customMsg ??
            (status === 'saving' ? 'Guardando...' : 'Guardado');
    }

    // ── Technician search ────────────────────────────────────
    function initTechnicianSearch() {
        const input   = document.getElementById('ts-tech-search-input');
        const results = document.getElementById('ts-tech-results');
        if (!input || !results) return;

        let searchTimer;
        input.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = input.value.trim();
            if (q.length < 2) { results.classList.remove('ts-tech-results--open'); return; }
            searchTimer = setTimeout(() => fetchTechnicians(q), 300);
        });

        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.classList.remove('ts-tech-results--open');
            }
        });
    }

    async function fetchTechnicians(q) {
        const results = document.getElementById('ts-tech-results');
        if (!results) return;

        try {
            const searchUrl = document.getElementById('ts-wizard-form')?.dataset.searchTechUrl;
            if (!searchUrl) return;
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            });
            const data = await res.json();
            renderTechResults(data.technicians ?? data ?? []);
        } catch { /* silent */ }
    }

    function renderTechResults(techs) {
        const results = document.getElementById('ts-tech-results');
        if (!results) return;

        if (!techs.length) {
            results.innerHTML = '<div class="ts-tech-result-item" style="color:#9CA3AF">Sin resultados</div>';
        } else {
            results.innerHTML = techs.map(t => `
                <div class="ts-tech-result-item" onclick="TechnicalServices.addTechnician(${t.id}, '${escapeAttr(t.name)}', '${escapeAttr(t.role ?? '')}')">
                    <div class="ts-tech-avatar">${initials(t.name)}</div>
                    <div>
                        <div style="font-weight:500">${t.name}</div>
                        <div style="font-size:0.75rem;color:#6B7280">${t.role ?? ''}</div>
                    </div>
                </div>
            `).join('');
        }
        results.classList.add('ts-tech-results--open');
    }

    function addTechnician(id, name, role) {
        const list = document.getElementById('ts-assigned-list');
        const results = document.getElementById('ts-tech-results');
        const input = document.getElementById('ts-tech-search-input');
        if (!list) return;

        if (document.querySelector(`.ts-assigned-item[data-tech-id="${id}"]`)) {
            if (results) results.classList.remove('ts-tech-results--open');
            return;
        }

        const emptyEl = list.querySelector('.ts-assigned-empty');
        if (emptyEl) emptyEl.remove();

        const item = document.createElement('div');
        item.className = 'ts-assigned-item';
        item.dataset.techId = id;
        item.innerHTML = `
            <div class="ts-tech-avatar">${initials(name)}</div>
            <div style="flex:1">
                <div class="ts-assigned-item__name">${name}</div>
                <div class="ts-assigned-item__role">${role}</div>
            </div>
            <input type="hidden" name="technician_ids[]" value="${id}">
            <button type="button" class="ts-remove-tech" onclick="TechnicalServices.removeTechnician(this)"
                    title="Quitar técnico">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>`;
        list.appendChild(item);

        if (results) results.classList.remove('ts-tech-results--open');
        if (input) input.value = '';
    }

    function removeTechnician(btn) {
        const item = btn.closest('.ts-assigned-item');
        if (!item) return;
        item.remove();
        const list = document.getElementById('ts-assigned-list');
        if (list && !list.querySelector('.ts-assigned-item')) {
            list.innerHTML = '<div class="ts-assigned-empty">Ningún técnico asignado aún</div>';
        }
    }

    // ── Materials ────────────────────────────────────────────
    function initMaterialRows() {
        const btn = document.getElementById('ts-add-material-row');
        if (btn) btn.addEventListener('click', addMaterialRow);
    }

    function addMaterialRow() {
        const tbody = document.querySelector('#ts-materials-tbody');
        if (!tbody) return;
        const idx = tbody.querySelectorAll('tr').length;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="materials[${idx}][name]" class="ts-mat-input" placeholder="Nombre del material"></td>
            <td><input type="number" name="materials[${idx}][quantity]" class="ts-mat-input" min="0" step="any" placeholder="0"></td>
            <td><input type="text" name="materials[${idx}][unit]" class="ts-mat-input" placeholder="Unidad"></td>
            <td><input type="text" name="materials[${idx}][notes]" class="ts-mat-input" placeholder="Notas (opcional)"></td>
            <td>
                <button type="button" class="ts-action-btn ts-action-btn--danger"
                        onclick="this.closest('tr').remove()" title="Eliminar fila">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
            </td>`;
        tbody.appendChild(tr);
    }

    // ── Cancel service confirmation ──────────────────────────
    function confirmCancelService(id, num) {
        if (confirm(`¿Cancelar el servicio ${num}? Esta acción no se puede deshacer.`)) {
            updateServiceStatus(id, 'cancelled');
        }
    }

    async function updateServiceStatus(id, status) {
        try {
            const res = await fetch(`/admin/technical-services/${id}/update-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ status }),
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                showNotification(data.message ?? 'No se pudo actualizar.', 'error');
            }
        } catch {
            showNotification('Error de red.', 'error');
        }
    }

    // ── Utils ────────────────────────────────────────────────
    function initials(name) {
        return (name ?? '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
    }

    function escapeAttr(s) {
        return String(s).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    // ── Keyboard ─────────────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            hideTooltip();
            closeQuickModal();
        }
    });

    // ── Init ─────────────────────────────────────────────────
    function init() {
        calendarEl   = document.getElementById('ts-calendar');
        calendarView = document.getElementById('ts-calendar-view');
        tableView    = document.getElementById('ts-table-view');
        overlay      = document.getElementById('ts-quick-overlay');
        quickModal   = document.getElementById('ts-quick-modal');

        // Create tooltip element
        tooltip = document.getElementById('ts-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'ts-tooltip';
            tooltip.className = 'ts-tooltip';
            document.body.appendChild(tooltip);
        }

        // Load services from data attribute
        if (calendarEl) {
            try {
                state.services = JSON.parse(calendarEl.dataset.services ?? '[]');
            } catch { state.services = []; }

            const m = parseInt(calendarEl.dataset.month);
            const y = parseInt(calendarEl.dataset.year);
            if (!isNaN(m) && !isNaN(y)) {
                state.currentDate = new Date(y, m - 1, 1);
            }
        }

        // View toggle
        document.querySelectorAll('.ts-view-toggle__btn').forEach(btn => {
            btn.addEventListener('click', () => setViewMode(btn.dataset.view));
        });

        // Period tabs
        document.querySelectorAll('.ts-period-tab').forEach(btn => {
            btn.addEventListener('click', () => setPeriod(btn.dataset.period));
        });

        // Navigation
        const btnPrev  = document.getElementById('ts-nav-prev');
        const btnNext  = document.getElementById('ts-nav-next');
        const btnToday = document.getElementById('ts-nav-today');
        if (btnPrev)  btnPrev.addEventListener('click',  () => navigate(-1));
        if (btnNext)  btnNext.addEventListener('click',  () => navigate(1));
        if (btnToday) btnToday.addEventListener('click', () => goToday());

        // Filters
        const filterTech   = document.getElementById('ts-filter-tech');
        const filterStatus = document.getElementById('ts-filter-status');
        if (filterTech) filterTech.addEventListener('change', e => {
            state.filterTechnician = e.target.value;
            applyFilters();
        });
        if (filterStatus) filterStatus.addEventListener('change', e => {
            state.filterStatus = e.target.value;
            applyFilters();
        });

        // Overlay click to close
        if (overlay) {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) closeQuickModal();
            });
        }

        // Close tooltip on outside click
        document.addEventListener('click', e => {
            if (!e.target.closest('.ts-event')) hideTooltip();
        });

        // Render
        if (calendarEl) renderCalendar();

        // Wizard
        initWizard();
    }

    // ── Public API ───────────────────────────────────────────
    window.TechnicalServices = {
        showTooltip,
        hideTooltip,
        onDragStart,
        onDragOver,
        onDrop,
        onCellClick,
        onEventClick,
        addTechnician,
        removeTechnician,
        confirmCancelService,
        updateServiceStatus,
        closeQuickModal,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
