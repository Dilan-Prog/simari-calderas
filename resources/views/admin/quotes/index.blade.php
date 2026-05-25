@extends('admin.layouts.master')

@section('title', 'Cotizaciones - Admin')


@section('content')
<div class="quotes-page">

    {{-- Header --}}
    <div class="quotes-header">
        <div>
            <div class="quotes-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="quotes-breadcrumb-current">Cotizaciones</span>
            </div>
            <h1 class="quotes-title">Gestión de Cotizaciones</h1>
            <p class="quotes-subtitle">Crea y administra cotizaciones para tus clientes</p>
        </div>
        <a href="{{ route('admin.quotes.create') }}" class="quotes-btn-new">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Nueva Cotización
        </a>
    </div>

    {{-- Filters --}}
    <form id="quotes-filter-form" method="GET" action="{{ route('admin.quotes.index') }}" class="quotes-filters-card">
        <div class="quotes-filters-grid">

            <div class="quotes-filter-field">
                <span class="quotes-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" name="search" class="quotes-filter-input" placeholder="Buscar por folio, cliente o empresa..." value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="quotes-select-wrap">
                <select name="status" class="quotes-filter-select">
                    <option value="">Todos los estados</option>
                    <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Borrador</option>
                    <option value="sent"     {{ request('status') === 'sent'     ? 'selected' : '' }}>Enviada</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Aceptada</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazada</option>
                    <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expirada</option>
                </select>
                <span class="quotes-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            {{-- Date picker trigger (hidden inputs live here inside the form) --}}
            <div class="quotes-datepicker-wrapper">
                <button type="button" id="datePickerTrigger"
                        class="quotes-dp-trigger{{ (request('date_from') || request('date_to')) ? ' has-value' : '' }}"
                        data-initial-from="{{ request('date_from') }}"
                        data-initial-to="{{ request('date_to') }}"
                        aria-label="Seleccionar rango de fechas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                    <span id="datePickerLabel" class="quotes-dp-trigger-label">Seleccionar rango de fechas</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <input type="hidden" name="date_from" id="dateFrom" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to"   id="dateTo"   value="{{ request('date_to') }}">
            </div>

            <button type="submit" class="quotes-btn-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
                Filtrar
            </button>
        </div>
    </form>

    {{-- Table card --}}
    <div class="quotes-table-card">
        <div class="quotes-table-scroll">
            <table class="quotes-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Receptor</th>
                        <th>Empresa</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Válido hasta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotes as $quote)
                    <tr>
                        <td><a href="{{ route('admin.quotes.show', $quote) }}" class="quotes-td-folio">{{ $quote->quote_number }}</a></td>
                        <td class="quotes-td-receptor">{{ $quote->guest_name }}</td>
                        <td class="quotes-td-empresa">{{ $quote->guest_company ?? '—' }}</td>
                        <td class="quotes-td-total">{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</td>
                        <td>
                            @php
                                $badgeMap = [
                                    'draft'    => ['class' => 'quotes-badge-draft',    'label' => 'Borrador'],
                                    'sent'     => ['class' => 'quotes-badge-sent',     'label' => 'Enviada'],
                                    'accepted' => ['class' => 'quotes-badge-accepted', 'label' => 'Aceptada'],
                                    'rejected' => ['class' => 'quotes-badge-rejected', 'label' => 'Rechazada'],
                                    'expired'  => ['class' => 'quotes-badge-expired',  'label' => 'Expirada'],
                                ];
                                $badge = $badgeMap[$quote->status] ?? ['class' => 'quotes-badge-draft', 'label' => $quote->status];
                            @endphp
                            <span class="quotes-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td class="quotes-td-fecha">{{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}</td>
                        <td>
                            <div class="quotes-actions">
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="quotes-action-btn" title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if($quote->isEditable())
                                <a href="{{ route('admin.quotes.edit', $quote) }}" class="quotes-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                @endif
                                <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return confirm('¿Cancelar esta cotización?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="quotes-action-btn quotes-action-btn-delete" title="Cancelar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="quotes-empty-row">
                        <td colspan="7">
                            <div class="quotes-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                <p>No se encontraron cotizaciones con los filtros actuales.</p>
                                @if(request()->hasAny(['search','status','date_from','date_to']))
                                <a href="{{ route('admin.quotes.index') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Limpiar filtros</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotes->total() > 0)
        <div class="quotes-pagination-bar">
            <span class="quotes-pagination-info">
                Mostrando <strong>{{ $quotes->firstItem() }}-{{ $quotes->lastItem() }}</strong>
                de <strong>{{ $quotes->total() }}</strong> cotizaciones
            </span>
            @if($quotes->hasPages())
            {{ $quotes->appends(request()->query())->links() }}
            @endif
        </div>
        @endif
    </div>

    {{-- ═══ Date picker modal (position:fixed, fuera del flow) ═══ --}}
    <div id="datePickerOverlay" class="quotes-dp-overlay" role="dialog" aria-modal="true" aria-label="Selector de rango de fechas">
        <div class="quotes-dp-modal">

            <div class="quotes-dp-header">
                <div class="quotes-dp-header-text">
                    <h3>Seleccionar rango de fechas</h3>
                    <p>Elige una fecha de inicio y una de fin</p>
                </div>
                <button type="button" id="dpCloseBtn" class="quotes-dp-close" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="quotes-dp-body">
                <div class="quotes-dp-quick">
                    <span class="quotes-dp-quick-label">Rangos rápidos</span>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="today">Hoy</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="7days">Últimos 7 días</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="30days">Últimos 30 días</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="thismonth">Este mes</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="lastmonth">Mes anterior</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="90days">Últimos 90 días</button>
                </div>

                <div class="quotes-dp-calendars">
                    <div class="quotes-dp-nav">
                        <button type="button" id="dpPrevMonth" class="quotes-dp-nav-btn" aria-label="Mes anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <div class="quotes-dp-months">
                            <div id="dpLeftTitle"  class="quotes-dp-month-title"></div>
                            <div id="dpRightTitle" class="quotes-dp-month-title"></div>
                        </div>
                        <button type="button" id="dpNextMonth" class="quotes-dp-nav-btn" aria-label="Mes siguiente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>

                    <div class="quotes-dp-cals">
                        <div class="quotes-dp-calendar">
                            <div class="quotes-dp-weekdays">
                                <div class="quotes-dp-weekday">L</div><div class="quotes-dp-weekday">M</div>
                                <div class="quotes-dp-weekday">M</div><div class="quotes-dp-weekday">J</div>
                                <div class="quotes-dp-weekday">V</div><div class="quotes-dp-weekday">S</div>
                                <div class="quotes-dp-weekday">D</div>
                            </div>
                            <div id="dpCalLeft" class="quotes-dp-days"></div>
                        </div>
                        <div class="quotes-dp-calendar">
                            <div class="quotes-dp-weekdays">
                                <div class="quotes-dp-weekday">L</div><div class="quotes-dp-weekday">M</div>
                                <div class="quotes-dp-weekday">M</div><div class="quotes-dp-weekday">J</div>
                                <div class="quotes-dp-weekday">V</div><div class="quotes-dp-weekday">S</div>
                                <div class="quotes-dp-weekday">D</div>
                            </div>
                            <div id="dpCalRight" class="quotes-dp-days"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quotes-dp-footer">
                <div class="quotes-dp-footer-dates">
                    <div class="quotes-dp-date-box" id="dpDisplayStart"><span style="color:#9CA3AF">Inicio</span></div>
                    <span class="quotes-dp-footer-arrow">→</span>
                    <div class="quotes-dp-date-box" id="dpDisplayEnd"><span style="color:#9CA3AF">Fin</span></div>
                </div>
                <div class="quotes-dp-footer-actions">
                    <button type="button" id="dpClearBtn"  class="quotes-dp-btn-clear">Limpiar</button>
                    <button type="button" id="dpCancelBtn" class="quotes-dp-btn-cancel">Cancelar</button>
                    <button type="button" id="dpApplyBtn"  class="quotes-dp-btn-apply" disabled>Aplicar rango</button>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var MONTHS  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var MSHORT  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    var state = { startDate: null, endDate: null, hoverDate: null, leftMonth: null, rightMonth: null };

    /* ── helpers ───────────────────────────── */
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

    /* ── open / close ──────────────────────── */
    function openModal() {
        document.getElementById('datePickerOverlay').classList.add('quotes-dp-open');
        renderCalendars();
        updateFooter();
        updateApplyBtn();
    }

    function closeModal() {
        document.getElementById('datePickerOverlay').classList.remove('quotes-dp-open');
        state.hoverDate = null;
    }

    /* ── render ────────────────────────────── */
    function renderCalendars() {
        renderCal('dpCalLeft',  state.leftMonth);
        renderCal('dpCalRight', state.rightMonth);
        document.getElementById('dpLeftTitle').textContent  = MONTHS[state.leftMonth.getMonth()]  + ' ' + state.leftMonth.getFullYear();
        document.getElementById('dpRightTitle').textContent = MONTHS[state.rightMonth.getMonth()] + ' ' + state.rightMonth.getFullYear();
    }

    function renderCal(id, monthDate) {
        var el    = document.getElementById(id);
        var yr    = monthDate.getFullYear(), mo = monthDate.getMonth();
        var first = new Date(yr, mo, 1), last = new Date(yr, mo + 1, 0);
        var total = last.getDate();
        var dow   = first.getDay(); // 0=Sun
        var off   = dow === 0 ? 6 : dow - 1; // Mon-based offset
        var prevLast = new Date(yr, mo, 0).getDate();
        var cells = Math.ceil((off + total) / 7) * 7;
        var r     = effectiveRange();
        var html  = '', cur = 1, nxt = 1;

        for (var i = 0; i < cells; i++) {
            var d, other;
            if (i < off) {
                d = new Date(yr, mo - 1, prevLast - off + i + 1); other = true;
            } else if (cur <= total) {
                d = new Date(yr, mo, cur++); other = false;
            } else {
                d = new Date(yr, mo + 1, nxt++); other = true;
            }
            html += buildCell(d, other, r);
        }

        el.innerHTML = html;

        el.querySelectorAll('.quotes-dp-day-btn[data-date]').forEach(function (btn) {
            btn.addEventListener('click',      function () { handleClick(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseenter', function () { handleHover(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseleave', function () { state.hoverDate = null; renderCalendars(); updateFooter(); });
        });
    }

    function buildCell(d, other, r) {
        var num = d.getDate(), iso = fmtISO(d);

        if (other) {
            return '<div class="quotes-dp-day-cell"><button class="quotes-dp-day-btn quotes-dp-day-other" disabled tabindex="-1" aria-hidden="true">' + num + '</button></div>';
        }

        var nd = norm(d);
        var isStart  = r.s && sameDay(nd, r.s);
        var isEnd    = r.e && sameDay(nd, r.e);
        var inRange  = r.s && r.e && nd > r.s && nd < r.e;
        var isSingle = isStart && isEnd;

        var cellCls = 'quotes-dp-day-cell';
        if (isSingle)     cellCls += ' quotes-dp-cell-single';
        else if (isStart) cellCls += ' quotes-dp-cell-start';
        else if (isEnd)   cellCls += ' quotes-dp-cell-end';
        else if (inRange) cellCls += ' quotes-dp-cell-in-range';

        var btnCls = 'quotes-dp-day-btn';
        if (isStart || isEnd) btnCls += ' quotes-dp-day-selected';
        else if (inRange)     btnCls += ' quotes-dp-day-in-range';
        if (isToday(d))       btnCls += ' quotes-dp-day-today';

        return '<div class="' + cellCls + '"><button class="' + btnCls + '" data-date="' + iso + '" aria-label="' + iso + '">' + num + '</button></div>';
    }

    /* ── interactions ──────────────────────── */
    function handleClick(d) {
        var nd = norm(d);
        if (!state.startDate || (state.startDate && state.endDate)) {
            state.startDate = nd; state.endDate = null;
        } else {
            var ns = norm(state.startDate);
            if (nd < ns) { state.startDate = nd; state.endDate = null; }
            else          { state.endDate = nd; }
        }
        renderCalendars(); updateFooter(); updateApplyBtn();
    }

    function handleHover(d) {
        if (state.startDate && !state.endDate) {
            state.hoverDate = norm(d);
            renderCalendars(); updateFooter();
        }
    }

    /* ── UI updates ────────────────────────── */
    function updateFooter() {
        var r = effectiveRange();
        document.getElementById('dpDisplayStart').innerHTML = r.s
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.s) + '</span>'
            : '<span style="color:#9CA3AF">Inicio</span>';
        document.getElementById('dpDisplayEnd').innerHTML = r.e
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.e) + '</span>'
            : '<span style="color:#9CA3AF">Fin</span>';
    }

    function updateApplyBtn() {
        document.getElementById('dpApplyBtn').disabled = !(state.startDate && state.endDate);
    }

    function updateTrigger(s, e) {
        var lbl = document.getElementById('datePickerLabel');
        var trg = document.getElementById('datePickerTrigger');
        if (s && e) { lbl.textContent = fmtDate(s) + ' → ' + fmtDate(e); trg.classList.add('has-value'); }
        else if (s) { lbl.textContent = fmtDate(s); trg.classList.add('has-value'); }
        else        { lbl.textContent = 'Seleccionar rango de fechas'; trg.classList.remove('has-value'); }
    }

    /* ── apply / clear ─────────────────────── */
    function applyRange() {
        if (!state.startDate || !state.endDate) return;
        var r = effectiveRange();
        document.getElementById('dateFrom').value = fmtISO(r.s);
        document.getElementById('dateTo').value   = fmtISO(r.e);
        updateTrigger(r.s, r.e);
        closeModal();
        document.getElementById('quotes-filter-form').submit();
    }

    function clearRange() {
        state.startDate = state.endDate = state.hoverDate = null;
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value   = '';
        renderCalendars(); updateFooter(); updateApplyBtn(); updateTrigger(null, null);
    }

    function applyQuick(s, e) {
        state.startDate = s; state.endDate = e; state.hoverDate = null;
        document.getElementById('dateFrom').value = fmtISO(s);
        document.getElementById('dateTo').value   = fmtISO(e);
        updateTrigger(s, e);
        closeModal();
        document.getElementById('quotes-filter-form').submit();
    }

    /* ── navigation ────────────────────────── */
    function prevMonth() {
        state.leftMonth  = new Date(state.leftMonth.getFullYear(),  state.leftMonth.getMonth()  - 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() - 1, 1);
        renderCalendars();
    }
    function nextMonth() {
        state.leftMonth  = new Date(state.leftMonth.getFullYear(),  state.leftMonth.getMonth()  + 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() + 1, 1);
        renderCalendars();
    }

    /* ── quick ranges ──────────────────────── */
    function quickRange(key) {
        var t = new Date(); t = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        var s, e;
        if      (key === 'today')     { s = t; e = t; }
        else if (key === '7days')     { s = new Date(t); s.setDate(s.getDate() - 6); e = t; }
        else if (key === '30days')    { s = new Date(t); s.setDate(s.getDate() - 29); e = t; }
        else if (key === 'thismonth') { s = new Date(t.getFullYear(), t.getMonth(), 1); e = t; }
        else if (key === 'lastmonth') { s = new Date(t.getFullYear(), t.getMonth() - 1, 1); e = new Date(t.getFullYear(), t.getMonth(), 0); }
        else if (key === '90days')    { s = new Date(t); s.setDate(s.getDate() - 89); e = t; }
        if (s && e) applyQuick(s, e);
    }

    /* ── init ──────────────────────────────── */
    function init() {
        var now = new Date();
        state.leftMonth  = new Date(now.getFullYear(), now.getMonth(), 1);
        state.rightMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);

        var trg  = document.getElementById('datePickerTrigger');
        var from = trg && trg.dataset.initialFrom;
        var to   = trg && trg.dataset.initialTo;
        if (from) state.startDate = parseISO(from);
        if (to)   state.endDate   = parseISO(to);
        if (state.startDate) {
            state.leftMonth  = new Date(state.startDate.getFullYear(), state.startDate.getMonth(), 1);
            state.rightMonth = new Date(state.startDate.getFullYear(), state.startDate.getMonth() + 1, 1);
            var r = effectiveRange();
            updateTrigger(r.s, r.e);
        }

        trg && trg.addEventListener('click', openModal);
        document.getElementById('dpCloseBtn').addEventListener('click', closeModal);
        document.getElementById('dpPrevMonth').addEventListener('click', prevMonth);
        document.getElementById('dpNextMonth').addEventListener('click', nextMonth);
        document.getElementById('dpApplyBtn').addEventListener('click', applyRange);
        document.getElementById('dpClearBtn').addEventListener('click', clearRange);
        document.getElementById('dpCancelBtn').addEventListener('click', closeModal);

        document.getElementById('datePickerOverlay').addEventListener('click', function (e) {
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
</script>
@endpush
