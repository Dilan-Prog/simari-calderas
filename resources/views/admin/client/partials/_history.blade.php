{{-- Historial del cliente: línea de tiempo + listas por área. Vars: $customer, $history (CustomerHistoryService::build) --}}
@php
    $typeColors = [
        'quote' => '#2563eb', 'order' => '#7c3aed', 'delivery' => '#0d9488', 'service' => '#ea580c', 'report' => '#c2410c',
        'store' => '#db2777', 'monitor' => '#0891b2', 'deal' => '#4f46e5', 'project' => '#65a30d', 'lead' => '#6b7280',
        'receivable' => '#b45309', 'payment' => '#15803d',
    ];
    $kpi = $history['kpis'];
    $timeline = $history['timeline'];
    $present = $timeline->pluck('type')->unique()->all();
@endphp

@push('styles')
<style>
    .ch-count { display:inline-block; margin-left:6px; min-width:20px; padding:1px 7px; border-radius:999px; background:#f3f4f6; color:#4b5563; font-size:11px; font-weight:700; }
    .tab-show-client.active .ch-count { background:#fff1e8; color:#c2410c; }
    .ch-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px; }
    .ch-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; }
    .ch-kpi small { display:block; color:#6b7280; font-size:12px; margin-bottom:4px; }
    .ch-kpi strong { display:block; font-size:18px; color:#111827; line-height:1.25; word-break:break-word; }
    .ch-kpi span { color:#9ca3af; font-size:12px; }
    .ch-filters { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 14px; }
    .ch-chip { border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:999px; padding:5px 12px; font-size:12.5px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
    .ch-chip i { width:8px; height:8px; border-radius:50%; display:inline-block; }
    .ch-chip.is-on { background:#fff1e8; border-color:#fdba74; color:#9a3412; font-weight:600; }
    .ch-month { margin:18px 0 8px; font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#6b7280; }
    .ch-list { list-style:none; margin:0; padding:0 0 0 18px; border-left:2px solid #e5e7eb; }
    .ch-item { position:relative; margin:0 0 10px; }
    .ch-item::before { content:''; position:absolute; left:-25px; top:16px; width:10px; height:10px; border-radius:50%; background:var(--ch-c,#9ca3af); border:2px solid #fff; box-shadow:0 0 0 1px #e5e7eb; }
    .ch-card { display:flex; flex-wrap:wrap; align-items:center; gap:6px 14px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:10px 14px; text-decoration:none; color:inherit; }
    a.ch-card:hover { border-color:#fdba74; background:#fffaf5; }
    .ch-date { font-size:12px; color:#6b7280; min-width:78px; }
    .ch-type { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--ch-c,#6b7280); min-width:130px; }
    .ch-main { flex:1; min-width:180px; font-size:13.5px; color:#111827; }
    .ch-main small { display:block; color:#6b7280; font-size:12px; }
    .ch-badge { font-size:12px; padding:2px 10px; border-radius:999px; background:#f3f4f6; color:#374151; white-space:nowrap; }
    .ch-amount { font-size:13px; font-weight:600; color:#111827; white-space:nowrap; }
    .ch-group + .ch-group { margin-top:22px; }
    .ch-group h3 { margin:0 0 8px; font-size:14px; color:#111827; }
    .ch-group h3 span { color:#9ca3af; font-weight:400; font-size:12px; margin-left:6px; }
    .ch-table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
    .ch-table th { text-align:left; font-size:11px; letter-spacing:.05em; color:#6b7280; padding:9px 12px; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .ch-table td { padding:9px 12px; font-size:13px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    .ch-table tr:last-child td { border-bottom:0; }
    .ch-table a { color:#c2410c; font-weight:600; text-decoration:none; }
    .ch-table a:hover { text-decoration:underline; }
    .ch-empty { padding:22px; text-align:center; color:#9ca3af; font-size:13px; background:#fff; border:1px dashed #e5e7eb; border-radius:10px; }
    .ch-note { margin:8px 0 0; font-size:12px; color:#9ca3af; }
    @media (max-width:700px) { .ch-type { min-width:0; } .ch-date { min-width:0; } }
</style>
@endpush

{{-- ═════ Tab: Historial (línea de tiempo) ═════ --}}
<div class="tab-content-show-client" id="history">
    <div class="ch-kpis">
        @isset($kpi['quotes_total'])
            <div class="ch-kpi"><small>Cotizaciones</small><strong>{{ $kpi['quotes_total'] }}</strong>
                <span>{{ $kpi['quotes_accepted'] ?? 0 }} aceptadas{{ !empty($kpi['accepted_amount']) ? ' · ' . $kpi['accepted_amount'] : '' }}</span></div>
        @endisset
        @isset($kpi['services_done'])
            <div class="ch-kpi"><small>Servicios realizados</small><strong>{{ $kpi['services_done'] }}</strong><span>completados</span></div>
        @endisset
        @if (array_key_exists('balance', $kpi))
            <div class="ch-kpi"><small>Saldo por cobrar</small><strong>{{ $kpi['balance'] ?? '$0.00' }}</strong><span>cuentas abiertas</span></div>
        @endif
        <div class="ch-kpi"><small>Última actividad</small>
            <strong>{{ $kpi['last_activity'] ? $kpi['last_activity']->locale('es')->translatedFormat('d \d\e F, Y') : '—' }}</strong>
            <span>entre todos los módulos que puedes ver</span></div>
    </div>

    @if ($timeline->isEmpty())
        <div class="ch-empty">Este cliente todavía no tiene movimientos registrados.</div>
    @else
        <div class="ch-filters" role="group" aria-label="Filtrar por tipo" id="chFilters">
            <button type="button" class="ch-chip is-on" data-type="all">Todo</button>
            @foreach ($history['types'] as $type => $label)
                @if (in_array($type, $present, true))
                    <button type="button" class="ch-chip" data-type="{{ $type }}"><i style="background:{{ $typeColors[$type] ?? '#9ca3af' }}"></i>{{ $label }}</button>
                @endif
            @endforeach
        </div>

        @foreach ($timeline->groupBy(fn ($e) => $e['date']?->format('Y-m') ?? 'sin-fecha') as $ym => $events)
            <div class="ch-month-block" data-month>
                <div class="ch-month">{{ $ym === 'sin-fecha' ? 'Sin fecha' : $events->first()['date']->locale('es')->translatedFormat('F Y') }}</div>
                <ol class="ch-list">
                    @foreach ($events as $e)
                        @php $tag = $e['url'] ? 'a' : 'div'; @endphp
                        <li class="ch-item" data-type="{{ $e['type'] }}" style="--ch-c: {{ $typeColors[$e['type']] ?? '#9ca3af' }}">
                            <{{ $tag }} class="ch-card" @if ($e['url']) href="{{ $e['url'] }}" @endif>
                                <span class="ch-date">{{ $e['date']?->format('d/m/Y') ?? '—' }}</span>
                                <span class="ch-type">{{ $history['types'][$e['type']] ?? $e['type'] }}</span>
                                <span class="ch-main">{{ $e['folio'] }}@if ($e['title'])<small>{{ $e['title'] }}</small>@endif</span>
                                @if ($e['status'])<span class="ch-badge">{{ $e['status'] }}</span>@endif
                                @if ($e['amount'])<span class="ch-amount">{{ $e['amount'] }}</span>@endif
                            </{{ $tag }}>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
        <p class="ch-note">Se muestran los movimientos más recientes de cada área. El detalle completo está en cada pestaña.</p>
    @endif
</div>

{{-- ═════ Tabs por área ═════ --}}
@foreach ($history['sections'] as $key => $section)
    <div class="tab-content-show-client" id="hist-{{ $key }}">
        @foreach ($section['groups'] as $group)
            <div class="ch-group">
                <h3>{{ $group['label'] }} <span>{{ $group['total'] }} en total{{ $group['total'] > count($group['rows']) ? ' · se muestran las ' . count($group['rows']) . ' más recientes' : '' }}</span></h3>
                @if (empty($group['rows']))
                    <div class="ch-empty">Sin registros.</div>
                @else
                    <div class="table-scroll">
                        <table class="ch-table">
                            <thead>
                                <tr><th>FECHA</th><th>FOLIO</th><th>DETALLE</th><th>ESTADO</th><th style="text-align:right">MONTO</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $r)
                                    <tr>
                                        <td>{{ $r['date']?->format('d/m/Y') ?? '—' }}</td>
                                        <td>@if ($r['url'])<a href="{{ $r['url'] }}">{{ $r['folio'] }}</a>@else{{ $r['folio'] }}@endif</td>
                                        <td>{{ $r['title'] ?? '—' }}</td>
                                        <td>@if ($r['status'])<span class="ch-badge">{{ $r['status'] }}</span>@else — @endif</td>
                                        <td style="text-align:right">{{ $r['amount'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endforeach

<script>
    (function () {
        var box = document.getElementById('chFilters');
        if (!box) return;
        box.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.ch-chip');
            if (!btn) return;
            var type = btn.getAttribute('data-type');
            box.querySelectorAll('.ch-chip').forEach(function (c) { c.classList.toggle('is-on', c === btn); });
            document.querySelectorAll('#history .ch-item').forEach(function (li) {
                li.hidden = !(type === 'all' || li.getAttribute('data-type') === type);
            });
            document.querySelectorAll('#history [data-month]').forEach(function (m) {
                m.hidden = !m.querySelector('.ch-item:not([hidden])');
            });
        });
    })();
</script>
