/**
 * Selector de rango de fechas personalizado del módulo Estadísticas —
 * mismo componente/UX que ya existe en Cotizaciones
 * (resources/views/admin/quotes/index.blade.php, prefijo quotes-dp-),
 * adaptado aquí con prefijo stats-dp- e IDs propios. Aplicar un rango
 * (o un preset rápido dentro del modal) llena #statsDateFrom/#statsDateTo
 * y envía #statsPeriodForm — StatisticsPeriodResolver::resolve() da
 * prioridad a date_from/date_to sobre el slug de periodo cuando ambos
 * son fechas válidas.
 */
(function () {
    'use strict';

    var MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    var MSHORT = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    var state = { startDate: null, endDate: null, hoverDate: null, leftMonth: null, rightMonth: null };

    function norm(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }

    function parseISO(s) {
        if (!s) return null;
        var p = s.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }

    function fmtISO(d) {
        if (!d) return '';
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    function fmtDate(d) {
        if (!d) return '';
        return String(d.getDate()).padStart(2, '0') + ' ' + MSHORT[d.getMonth()] + ' ' + d.getFullYear();
    }

    function isToday(d) {
        var t = new Date();
        return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
    }

    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function effectiveRange() {
        var s = state.startDate, e = state.endDate || state.hoverDate;
        if (!s) return { s: null, e: null };
        if (!e) return { s: s, e: null };
        var ns = norm(s), ne = norm(e);
        return ns <= ne ? { s: ns, e: ne } : { s: ne, e: ns };
    }

    function openModal() {
        document.getElementById('statsDatePickerOverlay').classList.add('stats-dp-open');
        renderCalendars();
        updateFooter();
        updateApplyBtn();
    }

    function closeModal() {
        document.getElementById('statsDatePickerOverlay').classList.remove('stats-dp-open');
        state.hoverDate = null;
    }

    function renderCalendars() {
        renderCal('statsDpCalLeft', state.leftMonth);
        renderCal('statsDpCalRight', state.rightMonth);
        document.getElementById('statsDpLeftTitle').textContent = MONTHS[state.leftMonth.getMonth()] + ' ' + state.leftMonth.getFullYear();
        document.getElementById('statsDpRightTitle').textContent = MONTHS[state.rightMonth.getMonth()] + ' ' + state.rightMonth.getFullYear();
    }

    function renderCal(id, monthDate) {
        var el = document.getElementById(id);
        var yr = monthDate.getFullYear(), mo = monthDate.getMonth();
        var first = new Date(yr, mo, 1), last = new Date(yr, mo + 1, 0);
        var total = last.getDate();
        var dow = first.getDay();
        var off = dow === 0 ? 6 : dow - 1;
        var prevLast = new Date(yr, mo, 0).getDate();
        var cells = Math.ceil((off + total) / 7) * 7;
        var r = effectiveRange();
        var html = '', cur = 1, nxt = 1;

        for (var i = 0; i < cells; i++) {
            var d;
            if (i < off) {
                d = new Date(yr, mo - 1, prevLast - off + i + 1);
                html += buildCell(d, true, r);
            } else if (cur <= total) {
                d = new Date(yr, mo, cur++);
                html += buildCell(d, false, r);
            } else {
                d = new Date(yr, mo + 1, nxt++);
                html += buildCell(d, true, r);
            }
        }

        el.innerHTML = html;

        el.querySelectorAll('.stats-dp-day-btn[data-date]').forEach(function (btn) {
            btn.addEventListener('click', function () { handleClick(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseenter', function () { handleHover(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseleave', function () { state.hoverDate = null; restyleCalendars(); updateFooter(); });
        });
    }

    // FIX: seleccionar la 2da fecha de un rango que cruza de un mes al
    // siguiente (p. ej. 08 ago -> 30 sep, como en el bug reportado) fallaba
    // de forma intermitente. Causa: mientras el mouse viaja hacia la 2da
    // fecha, cada celda que se sobrevuela disparaba handleHover() ->
    // renderCalendars() -> innerHTML de AMBOS calendarios completo (destruye
    // y recrea cada <button>, con sus listeners). En un recorrido largo
    // (cruzando de un calendario al otro) eso ocurre docenas de veces antes
    // del click final, y existe una ventana real donde el click puede
    // resolverse contra un botón que el navegador ya está reemplazando —
    // el clic no llega a ningún listener vivo y la selección se pierde en
    // silencio (el botón "Aplicar rango" se queda deshabilitado).
    // Fix: el día a día que se muestra nunca cambia por un hover/click de
    // selección (solo cambia al navegar de mes) — así que hover/click ahora
    // solo REESTILIZAN las celdas ya existentes (mismos nodos, mismos
    // listeners), sin volver a construir el DOM. renderCal()/innerHTML solo
    // se usa para abrir el modal o cambiar de mes, donde los días sí cambian.
    function cellClasses(d, other, r) {
        if (other) {
            return { cellCls: 'stats-dp-day-cell', btnCls: 'stats-dp-day-btn stats-dp-day-other' };
        }

        var nd = norm(d);
        var isStart = r.s && sameDay(nd, r.s);
        var isEnd = r.e && sameDay(nd, r.e);
        var inRange = r.s && r.e && nd > r.s && nd < r.e;
        var isSingle = isStart && isEnd;

        var cellCls = 'stats-dp-day-cell';
        if (isSingle) cellCls += ' stats-dp-cell-single';
        else if (isStart) cellCls += ' stats-dp-cell-start';
        else if (isEnd) cellCls += ' stats-dp-cell-end';
        else if (inRange) cellCls += ' stats-dp-cell-in-range';

        var btnCls = 'stats-dp-day-btn';
        if (isStart || isEnd) btnCls += ' stats-dp-day-selected';
        else if (inRange) btnCls += ' stats-dp-day-in-range';
        if (isToday(d)) btnCls += ' stats-dp-day-today';

        return { cellCls: cellCls, btnCls: btnCls };
    }

    function buildCell(d, other, r) {
        var num = d.getDate(), iso = fmtISO(d);
        var cls = cellClasses(d, other, r);

        if (other) {
            return '<div class="' + cls.cellCls + '"><button type="button" class="' + cls.btnCls + '" disabled tabindex="-1" aria-hidden="true">' + num + '</button></div>';
        }

        return '<div class="' + cls.cellCls + '"><button type="button" class="' + cls.btnCls + '" data-date="' + iso + '" aria-label="' + iso + '">' + num + '</button></div>';
    }

    function restyleCal(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var r = effectiveRange();

        el.querySelectorAll('.stats-dp-day-cell').forEach(function (cellEl) {
            var btn = cellEl.querySelector('.stats-dp-day-btn');
            if (!btn) return;
            if (btn.classList.contains('stats-dp-day-other')) return; // días del mes adyacente: nunca cambian de estilo
            var d = parseISO(btn.dataset.date);
            var cls = cellClasses(d, false, r);
            cellEl.className = cls.cellCls;
            btn.className = cls.btnCls;
        });
    }

    function restyleCalendars() {
        restyleCal('statsDpCalLeft');
        restyleCal('statsDpCalRight');
    }

    function handleClick(d) {
        var nd = norm(d);
        if (!state.startDate || (state.startDate && state.endDate)) {
            state.startDate = nd; state.endDate = null;
        } else {
            var ns = norm(state.startDate);
            if (nd < ns) { state.startDate = nd; state.endDate = null; }
            else { state.endDate = nd; }
        }
        restyleCalendars(); updateFooter(); updateApplyBtn();
    }

    function handleHover(d) {
        if (state.startDate && !state.endDate) {
            state.hoverDate = norm(d);
            restyleCalendars(); updateFooter();
        }
    }

    function updateFooter() {
        var r = effectiveRange();
        document.getElementById('statsDpDisplayStart').innerHTML = r.s
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.s) + '</span>'
            : '<span style="color:#9CA3AF">Inicio</span>';
        document.getElementById('statsDpDisplayEnd').innerHTML = r.e
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.e) + '</span>'
            : '<span style="color:#9CA3AF">Fin</span>';
    }

    function updateApplyBtn() {
        document.getElementById('statsDpApplyBtn').disabled = !(state.startDate && state.endDate);
    }

    function updateTrigger(s, e) {
        var lbl = document.getElementById('statsDatePickerLabel');
        var trg = document.getElementById('statsDatePickerTrigger');
        if (s && e) { lbl.textContent = fmtDate(s) + ' → ' + fmtDate(e); trg.classList.add('has-value'); }
    }

    function applyRange() {
        if (!state.startDate || !state.endDate) return;
        var r = effectiveRange();
        document.getElementById('statsDateFrom').value = fmtISO(r.s);
        document.getElementById('statsDateTo').value = fmtISO(r.e);
        updateTrigger(r.s, r.e);
        closeModal();
        document.getElementById('statsPeriodForm').submit();
    }

    function clearRange() {
        state.startDate = state.endDate = state.hoverDate = null;
        document.getElementById('statsDateFrom').value = '';
        document.getElementById('statsDateTo').value = '';
        renderCalendars(); updateFooter(); updateApplyBtn();
    }

    function applyQuick(s, e) {
        state.startDate = s; state.endDate = e; state.hoverDate = null;
        document.getElementById('statsDateFrom').value = fmtISO(s);
        document.getElementById('statsDateTo').value = fmtISO(e);
        updateTrigger(s, e);
        closeModal();
        document.getElementById('statsPeriodForm').submit();
    }

    function prevMonth() {
        state.leftMonth = new Date(state.leftMonth.getFullYear(), state.leftMonth.getMonth() - 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() - 1, 1);
        renderCalendars();
    }
    function nextMonth() {
        state.leftMonth = new Date(state.leftMonth.getFullYear(), state.leftMonth.getMonth() + 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() + 1, 1);
        renderCalendars();
    }

    function quickRange(key) {
        var t = new Date(); t = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        var s, e;
        if (key === 'today') { s = t; e = t; }
        else if (key === '7days') { s = new Date(t); s.setDate(s.getDate() - 6); e = t; }
        else if (key === '30days') { s = new Date(t); s.setDate(s.getDate() - 29); e = t; }
        else if (key === 'thismonth') { s = new Date(t.getFullYear(), t.getMonth(), 1); e = t; }
        else if (key === 'lastmonth') { s = new Date(t.getFullYear(), t.getMonth() - 1, 1); e = new Date(t.getFullYear(), t.getMonth(), 0); }
        else if (key === '90days') { s = new Date(t); s.setDate(s.getDate() - 89); e = t; }
        if (s && e) applyQuick(s, e);
    }

    function init() {
        var trg = document.getElementById('statsDatePickerTrigger');
        if (!trg) return; // otra página del admin sin este componente

        var now = new Date();
        state.leftMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        state.rightMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);

        var from = trg.dataset.initialFrom;
        var to = trg.dataset.initialTo;
        if (from) state.startDate = parseISO(from);
        if (to) state.endDate = parseISO(to);
        if (state.startDate) {
            state.leftMonth = new Date(state.startDate.getFullYear(), state.startDate.getMonth(), 1);
            state.rightMonth = new Date(state.startDate.getFullYear(), state.startDate.getMonth() + 1, 1);
            var r = effectiveRange();
            updateTrigger(r.s, r.e);
        }

        trg.addEventListener('click', openModal);
        document.getElementById('statsDpCloseBtn').addEventListener('click', closeModal);
        document.getElementById('statsDpPrevMonth').addEventListener('click', prevMonth);
        document.getElementById('statsDpNextMonth').addEventListener('click', nextMonth);
        document.getElementById('statsDpApplyBtn').addEventListener('click', applyRange);
        document.getElementById('statsDpClearBtn').addEventListener('click', clearRange);
        document.getElementById('statsDpCancelBtn').addEventListener('click', closeModal);

        document.getElementById('statsDatePickerOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        document.querySelectorAll('[data-quick-range]').forEach(function (btn) {
            btn.addEventListener('click', function () { quickRange(this.dataset.quickRange); });
        });
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
