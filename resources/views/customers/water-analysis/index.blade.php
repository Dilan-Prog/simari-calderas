@extends('customers.layouts.master')

@section('title', 'Reporte de análisis químico')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.monitoring.partials.styles')
    <style>
        .wa-legend { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; font-size: .75rem; color: var(--ts-text-muted, #6B7280); margin: 0 0 .75rem; }
        .wa-legend span { display: inline-flex; align-items: center; gap: .375rem; }
        .wa-dot { width: 10px; height: 10px; border-radius: 9999px; display: inline-block; }
        .wa-dot--ok { background: #16A34A; }
        .wa-dot--bajo { background: #D97706; }
        .wa-dot--alto { background: #DC2626; }
        .wa-dot--sin_rango { background: #9CA3AF; }

        .wa-badge { display: inline-flex; align-items: center; padding: .15rem .55rem; border-radius: 9999px; font-size: .6875rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
        .wa-badge:empty { display: none; }
        .wa-badge--ok { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
        .wa-badge--bajo { background: #FFFBEB; color: #B45309; border-color: #FDE68A; }
        .wa-badge--alto { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
        .wa-badge--sin_rango { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }

        .wa-form-top { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1rem; }
        .wa-form-top .ts-field { width: 220px; max-width: 100%; }
        .wa-form-top .wa-existing { flex: 1 1 280px; margin: 0; }

        .wa-table { min-width: 640px; }
        .wa-table td { vertical-align: middle; }
        .wa-table .wa-param { font-weight: 600; color: var(--ts-text-title, #111827); }
        .wa-result { display: flex; align-items: center; gap: .625rem; flex-wrap: wrap; }
        .wa-result .ts-input { width: 140px; text-align: right; font-variant-numeric: tabular-nums; }
        .wa-in--ok { border-color: #16A34A !important; box-shadow: 0 0 0 2px rgba(22, 163, 74, .15); }
        .wa-in--bajo { border-color: #D97706 !important; box-shadow: 0 0 0 2px rgba(217, 119, 6, .18); }
        .wa-in--alto { border-color: #DC2626 !important; box-shadow: 0 0 0 2px rgba(220, 38, 38, .15); }
        .wa-range-none { color: var(--ts-text-light, #9CA3AF); }

        .wa-notes { margin-top: 1rem; }
        .wa-form-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1rem; }

        .wa-chart-box { position: relative; height: 280px; }
        .wa-chart-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .wa-chart-head .ts-field { width: 280px; max-width: 100%; }

        .wa-hist td.wa-cell { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
        .wa-cell--ok { color: #15803D; }
        .wa-cell--bajo { color: #B45309; }
        .wa-cell--alto { color: #B91C1C; }
        .wa-cell--sin_rango, .wa-cell--none { color: #4B5563; font-weight: 500 !important; }
        .wa-cell--none { color: var(--ts-text-light, #9CA3AF); }
        .wa-cell small { display: block; font-size: .625rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .wa-date { font-weight: 600; color: var(--ts-text-title, #111827); white-space: nowrap; }
        .wa-date small { display: block; font-weight: 400; color: var(--ts-text-muted, #6B7280); text-transform: capitalize; }
        .wa-note-text { max-width: 260px; overflow-wrap: anywhere; }

        .wa-filter { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; margin-bottom: 1rem; }
        .wa-filter .ts-field { width: 180px; max-width: 100%; }

        @media (max-width: 640px) {
            .wa-form-top .ts-field, .wa-filter .ts-field { width: 100%; flex: 1 1 100%; }
            .wa-result .ts-input { width: 120px; }
            .wa-chart-box { height: 240px; }
        }
    </style>
@endpush

@section('content')
@php
    $statusLabels = [
        'ok'        => 'Dentro de rango',
        'bajo'      => 'Bajo',
        'alto'      => 'Alto',
        'sin_rango' => 'Sin rango definido',
    ];
    $fmtLimit = fn ($p, $v) => $v === null ? '—' : $p->formatValue($v);
    $canSave = $canCreate || $canEdit;
    $initialExists = (bool) $initialDay['exists'];
    $formAllowed = $initialExists ? $canEdit : $canCreate;
    $hasFilter = filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Reporte de análisis químico</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Reporte de análisis químico</h1>
            <p class="ts-subtitle">Registra cada día los resultados del análisis del agua (dureza, pH, alcalinidad, conductividad y sólidos totales disueltos) y consulta tu historial.</p>
        </div>
    </div>

    {{-- ── Registro del día ─────────────────────────────────────────────── --}}
    @if ($canSave)
        <section class="ts-card" id="wa-form-card" aria-labelledby="wa-form-title">
            <h2 class="ts-card__title" id="wa-form-title">Registro del día</h2>
            <p class="ts-card__subtitle">Captura el resultado de cada parámetro. Puedes dejar vacío el que no mediste; se requiere al menos uno.</p>

            <form method="POST" id="wa-form" novalidate
                  action="{{ $initialExists && $initialDay['update_url'] ? $initialDay['update_url'] : route('customer.water-analysis.store') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="wa-method" {{ $initialExists ? '' : 'disabled' }}>

                <div class="wa-form-top">
                    <div class="ts-field">
                        <label class="ts-label" for="wa-date">Fecha <span class="ts-label__req">*</span></label>
                        <input type="date" class="ts-input @error('report_date') mn-input-error @enderror" id="wa-date" name="report_date"
                               value="{{ $initialDate }}" max="{{ $today }}" required>
                        @error('report_date') <span class="mn-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="mn-alert mn-alert--info wa-existing" id="wa-existing" role="status" {{ $initialExists ? '' : 'hidden' }}>
                        Ya existe un registro de esta fecha, se actualizará.
                    </div>
                    <div class="mn-alert mn-alert--warn wa-existing" id="wa-denied" role="status" {{ $formAllowed ? 'hidden' : '' }}>
                        <span id="wa-denied-text">{{ $initialExists ? 'Ya existe un registro de esta fecha y tu rol no permite modificarlo.' : 'Tu rol no permite crear registros.' }}</span>
                    </div>
                </div>

                @error('values') <div class="mn-alert mn-alert--err" role="alert">{{ $message }}</div> @enderror

                <div class="mn-table-wrap">
                    <table class="mn-table wa-table">
                        <thead>
                            <tr>
                                <th scope="col">Parámetro</th>
                                <th scope="col">Unidad</th>
                                <th scope="col" class="mn-num">Valor mínimo</th>
                                <th scope="col" class="mn-num">Valor máximo</th>
                                <th scope="col">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parameters as $p)
                                @php
                                    $val = old('values.' . $p->id, $initialDay['values']->{$p->id} ?? null);
                                @endphp
                                <tr data-param="{{ $p->id }}" data-min="{{ $p->min_value }}" data-max="{{ $p->max_value }}">
                                    <td class="wa-param">{{ $p->name }}</td>
                                    <td>{{ $p->unit ?: '—' }}</td>
                                    <td class="mn-num">{!! $p->min_value === null ? '<span class="wa-range-none">—</span>' : e($fmtLimit($p, $p->min_value)) !!}</td>
                                    <td class="mn-num">{!! $p->max_value === null ? '<span class="wa-range-none">—</span>' : e($fmtLimit($p, $p->max_value)) !!}</td>
                                    <td>
                                        <div class="wa-result">
                                            <input type="number" step="any" min="0" @if ($p->code === 'ph') max="14" @endif
                                                   inputmode="decimal" class="ts-input wa-input @error('values.' . $p->id) mn-input-error @enderror"
                                                   name="values[{{ $p->id }}]" id="wa-v-{{ $p->id }}" placeholder="0"
                                                   value="{{ $val }}" aria-label="Resultado de {{ $p->name }}"
                                                   {{ $formAllowed ? '' : 'readonly' }}>
                                            <span class="wa-badge" data-badge aria-live="polite"></span>
                                        </div>
                                        @error('values.' . $p->id) <span class="mn-error">{{ $message }}</span> @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="ts-field wa-notes">
                    <label class="ts-label" for="wa-notes">Observaciones (opcional)</label>
                    <textarea class="ts-textarea" id="wa-notes" name="notes" rows="2" maxlength="1000"
                              placeholder="Condiciones de la toma, correcciones aplicadas..." {{ $formAllowed ? '' : 'readonly' }}>{{ old('notes', $initialDay['notes']) }}</textarea>
                    @error('notes') <span class="mn-error">{{ $message }}</span> @enderror
                </div>

                <div class="wa-form-foot">
                    <div class="wa-legend" style="margin:0">
                        <span><i class="wa-dot wa-dot--ok"></i> Dentro de rango</span>
                        <span><i class="wa-dot wa-dot--bajo"></i> Bajo</span>
                        <span><i class="wa-dot wa-dot--alto"></i> Alto</span>
                        <span><i class="wa-dot wa-dot--sin_rango"></i> Sin rango definido</span>
                    </div>
                    <button type="submit" class="ts-btn ts-btn--primary" id="wa-submit" {{ $formAllowed ? '' : 'disabled' }}>
                        {{ $initialExists ? 'Actualizar registro' : 'Guardar registro' }}
                    </button>
                </div>
            </form>
        </section>
    @else
        <div class="mn-alert mn-alert--info" role="status">Tu rol solo permite consultar el historial del análisis químico.</div>
    @endif

    {{-- ── Tendencia ────────────────────────────────────────────────────── --}}
    <section class="ts-card" aria-labelledby="wa-chart-title">
        <div class="wa-chart-head">
            <div>
                <h2 class="ts-card__title" id="wa-chart-title">Tendencia de los últimos 30 días</h2>
                <p class="ts-card__subtitle" style="margin:0">Las líneas punteadas marcan los valores mínimo y máximo, cuando están definidos.</p>
            </div>
            <div class="ts-field">
                <label class="ts-label" for="wa-chart-param">Parámetro</label>
                <div class="ts-field-select-wrap">
                    <select class="ts-select-field" id="wa-chart-param">
                        @foreach ($parameters as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}{{ $p->unit ? ' (' . $p->unit . ')' : '' }}</option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>
            </div>
        </div>
        @if ($chart['has_data'])
            <div class="wa-chart-box"><canvas id="wa-chart" role="img" aria-label="Gráfica de tendencia del parámetro seleccionado"></canvas></div>
        @else
            <div class="mn-empty" style="padding:1.5rem 1rem">
                <p style="margin:0">Aún no hay registros en los últimos 30 días para graficar.</p>
            </div>
        @endif
    </section>

    {{-- ── Historial ────────────────────────────────────────────────────── --}}
    <section class="ts-card" id="wa-history" aria-labelledby="wa-hist-title">
        <h2 class="ts-card__title" id="wa-hist-title">Historial</h2>
        <p class="ts-card__subtitle">Un renglón por día, del más reciente al más antiguo. El color indica si el valor está dentro de los límites vigentes.</p>

        <form method="GET" action="{{ route('customer.water-analysis.index') }}" class="wa-filter">
            <div class="ts-field">
                <label class="ts-label" for="wa-from">Desde</label>
                <input type="date" class="ts-input" id="wa-from" name="from" value="{{ $filters['from'] ?? '' }}" max="{{ $today }}">
                @error('from') <span class="mn-error">{{ $message }}</span> @enderror
            </div>
            <div class="ts-field">
                <label class="ts-label" for="wa-to">Hasta</label>
                <input type="date" class="ts-input" id="wa-to" name="to" value="{{ $filters['to'] ?? '' }}" max="{{ $today }}">
                @error('to') <span class="mn-error">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="ts-btn">Filtrar</button>
            @if ($hasFilter)
                <a href="{{ route('customer.water-analysis.index') }}" class="ts-btn">Limpiar filtros</a>
            @endif
        </form>

        <div class="wa-legend">
            <span><i class="wa-dot wa-dot--ok"></i> Dentro de rango</span>
            <span><i class="wa-dot wa-dot--bajo"></i> Bajo</span>
            <span><i class="wa-dot wa-dot--alto"></i> Alto</span>
            <span><i class="wa-dot wa-dot--sin_rango"></i> Sin rango definido</span>
        </div>

        @if ($rows->isEmpty())
            <div class="mn-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 2v7.5L4.5 19a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>
                </svg>
                <p>{{ $hasFilter ? 'No hay registros en el periodo seleccionado.' : 'Aún no has registrado análisis químicos.' }}</p>
            </div>
        @else
            <div class="mn-table-wrap">
                <table class="mn-table wa-hist">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            @foreach ($parameters as $p)
                                <th scope="col" class="mn-num" title="{{ $p->name }}{{ $p->unit ? ' (' . $p->unit . ')' : '' }}">{{ $p->code === 'tds' ? 'STD/TDS' : $p->name }}</th>
                            @endforeach
                            <th scope="col">Observaciones</th>
                            <th scope="col" class="mn-num">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $r = $row['report']; @endphp
                            <tr>
                                <td class="wa-date">{{ $r->report_date->locale('es')->isoFormat('D MMM YYYY') }}<small>{{ $r->report_date->locale('es')->isoFormat('dddd') }}</small></td>
                                @foreach ($parameters as $p)
                                    @php $c = $row['cells'][$p->id]; @endphp
                                    <td class="mn-num wa-cell wa-cell--{{ $c['status'] ?? 'none' }}"
                                        @if ($c['status']) title="{{ $statusLabels[$c['status']] }}" @endif>
                                        {{ $c['text'] }}
                                        @if (in_array($c['status'], ['bajo', 'alto'], true))
                                            <small>{{ $statusLabels[$c['status']] }}</small>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="wa-note-text">{{ $r->notes ?: '—' }}</td>
                                <td class="mn-actions">
                                    @if ($canEdit)
                                        <button type="button" class="ts-btn mn-btn-sm" data-wa-load="{{ $r->report_date->toDateString() }}">Registrar / editar</button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="ts-btn ts-btn--danger mn-btn-sm" data-wa-delete
                                                data-action="{{ route('customer.water-analysis.destroy', $r->id) }}"
                                                data-date="{{ $r->report_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}">Eliminar</button>
                                    @endif
                                    @if (! $canEdit && ! $canDelete)
                                        <span class="mn-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="ts-pagination mn-no-print">
                    <span class="ts-pagination__info">Mostrando {{ $reports->firstItem() }}–{{ $reports->lastItem() }} de {{ $reports->total() }}</span>
                    <div class="ts-pagination__links">{{ $reports->links() }}</div>
                </div>
            @endif
        @endif
    </section>
</div>

@if ($canDelete)
    {{-- Modal: confirmar eliminación --}}
    <div class="mn-modal" id="wa-modal-delete" role="alertdialog" aria-modal="true" aria-labelledby="wa-del-title" aria-hidden="true">
        <div class="mn-modal__card mn-modal__card--sm">
            <form method="POST" action="#" id="wa-delete-form">
                @csrf
                @method('DELETE')
                <div class="mn-modal__head">
                    <h2 class="mn-modal__title" id="wa-del-title">¿Eliminar este registro?</h2>
                    <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
                </div>
                <div class="mn-confirm">
                    Se eliminará el análisis del <strong id="wa-del-date"></strong> con todos sus resultados. Esta acción no se puede deshacer.
                </div>
                <div class="mn-modal__foot">
                    <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                    <button type="submit" class="ts-btn ts-btn--danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@include('customers.monitoring.partials.modal-js')
@if ($chart['has_data'])
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
@endif
<script>
(function () {
    var CFG = {
        canCreate: @json((bool) $canCreate),
        canEdit: @json((bool) $canEdit),
        today: @json($today),
        storeUrl: @json(route('customer.water-analysis.store')),
        dayUrl: @json(route('customer.water-analysis.day', ['date' => '__D__'])),
        chart: @json($chart),
        labels: @json($statusLabels)
    };

    // ── Formulario: estado en vivo y precarga por fecha ──────────────────
    var form = document.getElementById('wa-form');
    if (form) {
        var dateInput = document.getElementById('wa-date');
        var method = document.getElementById('wa-method');
        var existing = document.getElementById('wa-existing');
        var denied = document.getElementById('wa-denied');
        var deniedText = document.getElementById('wa-denied-text');
        var submit = document.getElementById('wa-submit');
        var notes = document.getElementById('wa-notes');
        var rows = Array.prototype.slice.call(form.querySelectorAll('tr[data-param]'));
        var seq = 0;

        function num(s) { return s === '' || s === null || s === undefined ? null : parseFloat(String(s).replace(',', '.')); }

        function statusOf(row, v) {
            if (v === null || isNaN(v)) return null;
            var min = num(row.getAttribute('data-min')), max = num(row.getAttribute('data-max'));
            if (min === null && max === null) return 'sin_rango';
            if (min !== null && v < min) return 'bajo';
            if (max !== null && v > max) return 'alto';
            return 'ok';
        }

        function paint(row) {
            var input = row.querySelector('.wa-input');
            var badge = row.querySelector('[data-badge]');
            var st = statusOf(row, num(input.value));
            input.classList.remove('wa-in--ok', 'wa-in--bajo', 'wa-in--alto');
            badge.className = 'wa-badge';
            badge.textContent = '';
            if (!st) return;
            if (st !== 'sin_rango') input.classList.add('wa-in--' + st);
            badge.classList.add('wa-badge--' + st);
            badge.textContent = CFG.labels[st];
        }

        function setMode(day) {
            var exists = !!day.exists;
            var allowed = exists ? CFG.canEdit : CFG.canCreate;
            existing.hidden = !exists;
            method.disabled = !exists;
            form.action = exists && day.update_url ? day.update_url : CFG.storeUrl;
            submit.textContent = exists ? 'Actualizar registro' : 'Guardar registro';
            submit.disabled = !allowed;
            denied.hidden = allowed;
            deniedText.textContent = exists ? 'Ya existe un registro de esta fecha y tu rol no permite modificarlo.' : 'Tu rol no permite crear registros.';
            rows.forEach(function (r) { r.querySelector('.wa-input').readOnly = !allowed; });
            notes.readOnly = !allowed;
        }

        function fill(day) {
            rows.forEach(function (r) {
                var v = day.values ? day.values[r.getAttribute('data-param')] : undefined;
                r.querySelector('.wa-input').value = (v === undefined || v === null) ? '' : v;
                paint(r);
            });
            notes.value = day.notes || '';
            setMode(day);
        }

        function loadDay(date) {
            if (!date || date > CFG.today) return Promise.resolve();
            var mine = ++seq;
            return fetch(CFG.dayUrl.replace('__D__', encodeURIComponent(date)), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
              .then(function (day) { if (mine === seq) fill(day); })
              .catch(function () { /* sin red: se conserva lo que haya en pantalla */ });
        }

        rows.forEach(function (r) {
            var input = r.querySelector('.wa-input');
            input.addEventListener('input', function () { paint(r); });
            paint(r);
        });
        dateInput.addEventListener('change', function () { loadDay(dateInput.value); });

        // "Registrar / editar" desde el historial: carga esa fecha en el formulario.
        document.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-wa-load]');
            if (!b) return;
            dateInput.value = b.getAttribute('data-wa-load');
            loadDay(dateInput.value).then(function () {
                document.getElementById('wa-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
                var first = rows[0] && rows[0].querySelector('.wa-input');
                if (first && !first.readOnly) setTimeout(function () { first.focus({ preventScroll: true }); }, 350);
            });
        });

        form.addEventListener('submit', function (ev) {
            if (submit.disabled) { ev.preventDefault(); return; }
            if (!dateInput.value) { ev.preventDefault(); dateInput.focus(); return; }
            var any = rows.some(function (r) { return r.querySelector('.wa-input').value.trim() !== ''; });
            if (!any) {
                ev.preventDefault();
                rows[0].querySelector('.wa-input').focus();
                alert('Captura al menos un resultado.');
            }
        });
    }

    // ── Eliminar ─────────────────────────────────────────────────────────
    var delModal = document.getElementById('wa-modal-delete');
    if (delModal) {
        document.addEventListener('click', function (ev) {
            var b = ev.target.closest('[data-wa-delete]');
            if (!b) return;
            document.getElementById('wa-delete-form').action = b.getAttribute('data-action');
            document.getElementById('wa-del-date').textContent = b.getAttribute('data-date');
            window.mnModal.setTrigger(b);
            window.mnModal.open(delModal, '.ts-btn');
        });
    }

    // ── Gráfica de tendencia (Chart.js autoalojado) ──────────────────────
    var canvas = document.getElementById('wa-chart');
    if (canvas && window.Chart) {
        var select = document.getElementById('wa-chart-param');
        var chart = null;

        function build() {
            var s = CFG.chart.series.filter(function (x) { return String(x.id) === select.value; })[0];
            if (!s) return;
            var n = CFG.chart.labels.length;
            var datasets = [{
                label: s.name + (s.unit ? ' (' + s.unit + ')' : ''),
                data: s.values,
                borderColor: '#ff6213', backgroundColor: '#ff6213',
                borderWidth: 2, tension: .25, spanGaps: true, pointRadius: 3, pointHoverRadius: 5
            }];
            function ref(label, v, color) {
                datasets.push({ label: label, data: new Array(n).fill(v), borderColor: color, borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, pointHoverRadius: 0, fill: false });
            }
            if (s.min !== null) ref('Mínimo', s.min, '#D97706');
            if (s.max !== null) ref('Máximo', s.max, '#DC2626');

            if (chart) chart.destroy();
            chart = new Chart(canvas, {
                type: 'line',
                data: { labels: CFG.chart.labels, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 14 } } },
                    scales: {
                        x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false } },
                        y: { beginAtZero: false, title: { display: !!s.unit, text: s.unit || '' } }
                    }
                }
            });
        }
        select.addEventListener('change', build);
        build();
    }
})();
</script>
@endpush
