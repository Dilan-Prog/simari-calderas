@extends('customers.layouts.master')

@section('title', 'Calendario de servicios')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .pc-nav { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:1rem; }
        .pc-nav__group { display:flex; align-items:center; gap:.5rem; }
        .pc-btn {
            height:36px; min-width:36px; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px;
            background:var(--ts-bg-white); color:var(--ts-text-body); font-size:.8125rem; font-weight:500;
            cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-family:var(--ts-font);
        }
        .pc-btn:hover { background:#F9FAFB; }
        .pc-month { font-size:1.125rem; font-weight:700; color:var(--ts-text-title); min-width:10rem; text-align:center; text-transform:capitalize; }

        .pc-legend { display:flex; flex-wrap:wrap; gap:.5rem 1rem; margin-bottom:1rem; font-size:.75rem; color:var(--ts-text-muted); }
        .pc-legend__item { display:inline-flex; align-items:center; gap:.375rem; }
        .pc-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
        .pc-dot--scheduled   { background:var(--ts-sched-text); }
        .pc-dot--in_progress { background:var(--ts-prog-text); }
        .pc-dot--completed   { background:var(--ts-done-text); }
        .pc-dot--cancelled   { background:#9CA3AF; }

        .pc-wrap { position:relative; }
        .pc-wrap.is-loading::after {
            content:''; position:absolute; inset:0; background:rgba(255,255,255,.55); border-radius:8px;
        }
        .pc-error { display:none; margin-bottom:1rem; padding:.75rem 1rem; border-radius:6px; background:#FEF2F2; color:#B91C1C; font-size:.8125rem; }

        /* Grilla mensual (>= 720px) */
        .pc-grid-view { display:block; }
        .pc-agenda { display:none; }
        .pc-cal-cell { cursor:default; min-height:110px; }
        .pc-cal-cell:hover { background:var(--ts-bg-white); }
        .pc-cal-cell.ts-cal-cell--other-month:hover { background:var(--ts-bg-page); }
        a.pc-event { text-decoration:none; gap:4px; }
        a.pc-event .pc-event__time { font-weight:700; flex-shrink:0; }
        a.pc-event .pc-event__title { overflow:hidden; text-overflow:ellipsis; }
        .pc-more { font-size:.6875rem; color:var(--ts-text-muted); padding:0 4px; background:none; border:none; cursor:pointer; text-align:left; font-family:var(--ts-font); }
        .pc-more:hover { color:var(--ts-primary); }

        /* Agenda (< 720px) */
        .pc-ag-day { background:var(--ts-bg-white); border-radius:8px; box-shadow:var(--ts-shadow-sm); margin-bottom:.75rem; overflow:hidden; }
        .pc-ag-day__head { padding:.5rem .875rem; background:var(--ts-table-header); color:#E5E7EB; font-size:.75rem; font-weight:600; text-transform:capitalize; }
        .pc-ag-day--today .pc-ag-day__head { background:var(--ts-primary); color:#fff; }
        a.pc-ag-item {
            display:flex; align-items:center; gap:.75rem; padding:.75rem .875rem; text-decoration:none;
            border-bottom:1px solid var(--ts-border-light); border-left:3px solid transparent;
        }
        a.pc-ag-item:last-child { border-bottom:none; }
        .pc-ag-item__time { font-size:.8125rem; font-weight:700; width:3rem; flex-shrink:0; }
        .pc-ag-item__body { flex:1; min-width:0; }
        .pc-ag-item__title { font-size:.875rem; font-weight:600; color:var(--ts-text-title); }
        .pc-ag-item__meta { font-size:.75rem; color:var(--ts-text-muted); }
        .pc-ag-empty { text-align:center; color:var(--ts-text-light); font-size:.875rem; padding:2rem 1rem; background:var(--ts-bg-white); border-radius:8px; }

        @media (max-width: 719px) {
            .pc-grid-view { display:none; }
            .pc-agenda { display:block; }
            .pc-month { min-width:0; flex:1; }
            .pc-nav__group--nav { width:100%; }
        }
    </style>
@endpush

@section('content')
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Calendario</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Calendario de servicios</h1>
            <p class="ts-subtitle">Tus servicios programados, en proceso, completados y cancelados</p>
        </div>
    </div>

    <div class="pc-nav">
        <div class="pc-nav__group pc-nav__group--nav">
            <button type="button" class="pc-btn" id="pc-prev" aria-label="Mes anterior">&#8249;</button>
            <div class="pc-month" id="pc-month-label">&nbsp;</div>
            <button type="button" class="pc-btn" id="pc-next" aria-label="Mes siguiente">&#8250;</button>
        </div>
        <div class="pc-nav__group">
            <button type="button" class="pc-btn" id="pc-today">Hoy</button>
        </div>
    </div>

    <div class="pc-legend" aria-label="Leyenda de estados">
        <span class="pc-legend__item"><span class="pc-dot pc-dot--scheduled"></span>Programado</span>
        <span class="pc-legend__item"><span class="pc-dot pc-dot--in_progress"></span>En proceso</span>
        <span class="pc-legend__item"><span class="pc-dot pc-dot--completed"></span>Completado</span>
        <span class="pc-legend__item"><span class="pc-dot pc-dot--cancelled"></span>Cancelado</span>
    </div>

    <div class="pc-error" id="pc-error" role="alert">No se pudo cargar el calendario. Intenta de nuevo.</div>

    <div class="pc-wrap" id="pc-wrap">
        <div class="pc-grid-view">
            <div class="ts-calendar-wrap">
                <div class="ts-cal-head">
                    <div class="ts-cal-head__day">Lun</div>
                    <div class="ts-cal-head__day">Mar</div>
                    <div class="ts-cal-head__day">Mié</div>
                    <div class="ts-cal-head__day">Jue</div>
                    <div class="ts-cal-head__day">Vie</div>
                    <div class="ts-cal-head__day">Sáb</div>
                    <div class="ts-cal-head__day">Dom</div>
                </div>
                <div class="ts-cal-body" id="pc-grid" style="min-width:0"></div>
            </div>
        </div>

        <div class="pc-agenda" id="pc-agenda"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var EVENTS_URL = @json(route('customer.calendar.events'));
    var MONTHS = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var DAYS   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    var MAX_PER_CELL = 3;

    var now = new Date();
    var current = new Date(now.getFullYear(), now.getMonth(), 1);
    var cache = {};
    var seq = 0;

    var elLabel  = document.getElementById('pc-month-label');
    var elGrid   = document.getElementById('pc-grid');
    var elAgenda = document.getElementById('pc-agenda');
    var elWrap   = document.getElementById('pc-wrap');
    var elError  = document.getElementById('pc-error');

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function ym(d)  { return d.getFullYear() + '-' + pad(d.getMonth() + 1); }

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function eventLink(ev, variant) {
        var a = el('a');
        a.href = ev.url;
        a.title = (ev.time ? ev.time + ' · ' : '') + ev.title + ' (' + ev.number + ') — ' + ev.status_label;
        if (variant === 'grid') {
            a.className = 'ts-event ts-event--' + ev.status + ' pc-event';
            if (ev.time) a.appendChild(el('span', 'pc-event__time', ev.time));
            a.appendChild(el('span', 'pc-event__title', ev.title));
        } else {
            a.className = 'pc-ag-item ts-event--' + ev.status;
            a.style.height = 'auto';
            a.appendChild(el('div', 'pc-ag-item__time', ev.time || 'Todo el día'));
            var body = el('div', 'pc-ag-item__body');
            body.appendChild(el('div', 'pc-ag-item__title', ev.title));
            body.appendChild(el('div', 'pc-ag-item__meta', ev.number + ' · ' + ev.status_label));
            a.appendChild(body);
        }
        return a;
    }

    function groupByDate(events) {
        var map = {};
        events.forEach(function (ev) { (map[ev.date] = map[ev.date] || []).push(ev); });
        return map;
    }

    function render(events) {
        var byDate = groupByDate(events);
        var todayStr = ymd(new Date());

        elLabel.textContent = MONTHS[current.getMonth()] + ' ' + current.getFullYear();

        // ── Grilla (lunes primero) ──
        elGrid.textContent = '';
        var first = new Date(current.getFullYear(), current.getMonth(), 1);
        var offset = (first.getDay() + 6) % 7;
        var daysInMonth = new Date(current.getFullYear(), current.getMonth() + 1, 0).getDate();
        var totalCells = Math.ceil((offset + daysInMonth) / 7) * 7;

        for (var i = 0; i < totalCells; i++) {
            var d = new Date(current.getFullYear(), current.getMonth(), 1 - offset + i);
            var other = d.getMonth() !== current.getMonth();
            var key = ymd(d);
            var cell = el('div', 'ts-cal-cell pc-cal-cell' + (other ? ' ts-cal-cell--other-month' : '') + (key === todayStr ? ' ts-cal-cell--today' : ''));
            var inner = el('div', 'ts-cell-inner');
            var numWrap = el('div', 'ts-cell-number-wrap');
            numWrap.appendChild(el('div', 'ts-cell-number' + (key === todayStr ? ' ts-cell-number--today' : '') + (other ? ' ts-cell-number--other' : ''), String(d.getDate())));
            inner.appendChild(numWrap);

            var list = el('div', 'ts-cell-events');
            var dayEvents = other ? [] : (byDate[key] || []);
            var expanded = false;

            (function (list, dayEvents) {
                function paint(all) {
                    list.textContent = '';
                    var shown = all ? dayEvents : dayEvents.slice(0, MAX_PER_CELL);
                    shown.forEach(function (ev) { list.appendChild(eventLink(ev, 'grid')); });
                    if (!all && dayEvents.length > MAX_PER_CELL) {
                        var more = el('button', 'pc-more', '+' + (dayEvents.length - MAX_PER_CELL) + ' más');
                        more.type = 'button';
                        more.addEventListener('click', function () { paint(true); });
                        list.appendChild(more);
                    }
                }
                paint(false);
            })(list, dayEvents);

            inner.appendChild(list);
            cell.appendChild(inner);
            elGrid.appendChild(cell);
        }

        // ── Agenda (móvil) ──
        elAgenda.textContent = '';
        var dates = Object.keys(byDate).sort();
        if (!dates.length) {
            elAgenda.appendChild(el('div', 'pc-ag-empty', 'No hay servicios en este mes'));
        }
        dates.forEach(function (key) {
            var p = key.split('-');
            var date = new Date(+p[0], +p[1] - 1, +p[2]);
            var day = el('div', 'pc-ag-day' + (key === todayStr ? ' pc-ag-day--today' : ''));
            day.appendChild(el('div', 'pc-ag-day__head', DAYS[date.getDay()] + ' ' + date.getDate() + ' de ' + MONTHS[date.getMonth()]));
            byDate[key].forEach(function (ev) { day.appendChild(eventLink(ev, 'agenda')); });
            elAgenda.appendChild(day);
        });
    }

    function load() {
        var month = ym(current);
        var my = ++seq;
        elError.style.display = 'none';

        if (cache[month]) { render(cache[month]); return; }

        elWrap.classList.add('is-loading');
        fetch(EVENTS_URL + '?month=' + encodeURIComponent(month), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function (data) {
            cache[month] = data.events || [];
            if (my === seq) render(cache[month]);
        })
        .catch(function () {
            if (my === seq) { elError.style.display = 'block'; render([]); }
        })
        .finally(function () { if (my === seq) elWrap.classList.remove('is-loading'); });
    }

    function go(delta) {
        current = new Date(current.getFullYear(), current.getMonth() + delta, 1);
        load();
    }

    document.getElementById('pc-prev').addEventListener('click', function () { go(-1); });
    document.getElementById('pc-next').addEventListener('click', function () { go(1); });
    document.getElementById('pc-today').addEventListener('click', function () {
        var t = new Date();
        current = new Date(t.getFullYear(), t.getMonth(), 1);
        load();
    });

    load();
})();
</script>
@endpush
