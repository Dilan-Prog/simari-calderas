@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
    <style>
        .smg-flash { margin: 0 0 12px; padding: 10px 14px; border-radius: 8px; font-size: 13px; }
        .smg-flash--ok { background: #ecfdf3; color: #166534; border: 1px solid #bbf7d0; }
        .smg-flash--err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .smg-top { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; }
        @media (max-width: 900px) { .smg-top { grid-template-columns: 1fr; } }
        .smg-top .erp-card { margin-bottom: 0; }
        .smg-count { font-size: 22px; font-weight: 800; margin: 6px 0 8px; }
        .smg-lines { margin: 0; padding: 0; list-style: none; font-size: 12.5px; }
        .smg-lines li { margin-bottom: 2px; }
        .smg-sug { margin-top: 6px; font-size: 12px; background: #fff7ed; border: 1px dashed #fdba74; border-radius: 6px; padding: 5px 8px; }
        .smg-modeline { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .smg-batch-extra { display: inline-flex; align-items: center; gap: 8px; }
        .smg-batch-extra .smp-input { background: #fff; color: #111827; }
        .smg-batch-extra label { font-weight: 600; }
        .smg-hint { font-size: 12px; color: var(--erp-muted, #6b7280); margin: 6px 0 0; }
    </style>
@endpush
@section('title', 'Migración de cotizaciones a servicios web - Admin')
@section('content')
@php
    $q = request()->query();
    $url = fn (array $extra = [], array $except = []) => route('admin.service-migration.index', array_filter(
        array_merge(collect($q)->except(array_merge($except, ['page']))->all(), $extra), fn ($v) => $v !== null && $v !== ''));

    $chips = [];
    if ($filters['search'] !== '') $chips[] = ['label' => 'Folio: ' . $filters['search'], 'url' => $url([], ['search'])];
    if ($filters['status'] !== '' && isset($statuses[$filters['status']])) $chips[] = ['label' => 'Estado: ' . $statuses[$filters['status']], 'url' => $url([], ['status'])];
    if ($filters['customer'] !== '') $chips[] = ['label' => 'Cliente: ' . $filters['customer'], 'url' => $url([], ['customer'])];
    if ($filters['date_from'] !== '') $chips[] = ['label' => 'Desde: ' . $filters['date_from'], 'url' => $url([], ['date_from'])];
    if ($filters['date_to'] !== '') $chips[] = ['label' => 'Hasta: ' . $filters['date_to'], 'url' => $url([], ['date_to'])];
    if ($filters['lineas']) $chips[] = ['label' => 'Con líneas de producto-servicio', 'url' => $url([], ['lineas'])];

    $views = [
        ['label' => 'Sin Servicio ID', 'url' => $url([], ['servicio']), 'active' => $filters['servicio'] === 'sin'],
        ['label' => 'Con Servicio ID', 'url' => $url(['servicio' => 'con'], []), 'active' => $filters['servicio'] === 'con'],
        ['label' => 'Todas', 'url' => $url(['servicio' => 'todas'], []), 'active' => $filters['servicio'] === 'todas'],
    ];
    $custName = function ($quote) {
        if ($quote->customer) return $quote->customer->company ?: trim($quote->customer->first_name . ' ' . $quote->customer->last_name);
        return $quote->guest_company ?: $quote->guest_name;
    };
@endphp
<div class="erp-page">

    <x-erp.page-header
        title="Migración de cotizaciones a servicios web"
        code="SRV1"
        desc="Asigna a cada cotización atendida por producto-servicio el Servicio ID (página de servicio web) que le corresponde."
        :crumbs="[['Ventas', null], ['Migración a servicios web', null]]">
        <a href="{{ route('admin.service-migration.export', request()->query()) }}" class="erp-btn">Exportar CSV</a>
    </x-erp.page-header>

    @if (session('success'))<div class="smg-flash smg-flash--ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="smg-flash smg-flash--err">{{ session('error') }}</div>@endif

    <div class="smg-top">
        <div class="erp-card">
            <div class="erp-card-body">
                <div class="erp-card-title">Avance de la migración</div>
                <div class="smg-count" id="smgProgressText">{{ $progress['migrated'] }} de {{ $progress['total'] }} cotizaciones migradas</div>
                <x-erp.progress :value="$progress['percent']" id="smgProgressBar" />
                <p class="smg-hint">Cuentan las cotizaciones que ya tienen Servicio ID o que traen líneas de producto-servicio
                    (categoría «Servicios» o nombre que empieza con: {{ implode(', ', $patterns) }}).</p>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-body">
                <div class="erp-card-title">Modo nuevo (servicios desde la página de servicio)</div>
                <p class="smg-hint" style="margin-top:4px;">
                    Activo: los buscadores de productos excluyen los productos-servicio y las cotizaciones con líneas de
                    servicio deben llevar Servicio ID. Apagado: todo funciona como hasta hoy. No se desactiva ni se borra ningún producto.
                </p>
                <div class="smg-modeline">
                    <x-erp.chip :tone="$newMode ? 'ok' : 'neu'" :label="$newMode ? 'Modo nuevo activo' : 'Modo nuevo apagado'" />
                    @if ($canNewMode)
                        <form method="POST" action="{{ route('admin.service-migration.new-mode') }}"
                              onsubmit="return confirm('{{ $newMode ? '¿Apagar el modo nuevo?' : '¿Activar el modo nuevo? Los buscadores dejarán de mostrar productos-servicio.' }}');">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $newMode ? 0 : 1 }}">
                            <button type="submit" class="erp-btn erp-btn--sm {{ $newMode ? '' : 'erp-btn--primary' }}">{{ $newMode ? 'Apagar' : 'Activar' }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="prj-toolbar-form" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="servicio" value="{{ $filters['servicio'] }}">
                <div class="erp-search">
                    <input type="search" name="search" placeholder="Folio…" value="{{ $filters['search'] }}" aria-label="Buscar por folio">
                </div>
                <input type="search" name="customer" class="erp-input" placeholder="Cliente…" value="{{ $filters['customer'] }}" aria-label="Cliente">
                <select name="status" class="erp-select" onchange="this.form.submit()" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach ($statuses as $k => $label)
                        <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="erp-input" value="{{ $filters['date_from'] }}" aria-label="Desde">
                <input type="date" name="date_to" class="erp-input" value="{{ $filters['date_to'] }}" aria-label="Hasta">
                <label style="font-size:13px;display:inline-flex;gap:6px;align-items:center;">
                    <input type="checkbox" name="lineas" value="1" @checked($filters['lineas']) onchange="this.form.submit()"> Con líneas de producto-servicio
                </label>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @if ($canEdit)
            <x-erp.batch-bar noun="cotizaciones">
                <span class="smg-batch-extra">
                    @include('admin.service-migration._picker', ['name' => 'bulk_service_page_id', 'inputId' => 'smgBulkPicker', 'compact' => true])
                    <label><input type="checkbox" id="smgBulkSync" checked> Sincronizar a servicios y reportes</label>
                    <button type="button" class="erp-btn erp-btn--sm" id="smgBulkApply">Asignar Servicio ID a seleccionadas</button>
                </span>
            </x-erp.batch-bar>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        @if ($canEdit)<th style="width:32px;"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todas"></th>@endif
                        <th>Folio</th><th>Cliente</th><th>Fecha</th><th>Estado</th>
                        <th>Líneas de servicio detectadas</th><th>Servicio ID</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    @php $quote = $row['quote']; @endphp
                    <tr data-quote-row="{{ $quote->id }}">
                        @if ($canEdit)<td><input type="checkbox" class="erp-row-check" value="{{ $quote->id }}" aria-label="Seleccionar {{ $quote->quote_number }}"></td>@endif
                        <td class="erp-cell-folio"><a href="{{ route('admin.quotes.show', $quote) }}">{{ $quote->quote_number }}</a></td>
                        <td>{{ $custName($quote) }}</td>
                        <td>{{ $quote->created_at?->format('d/m/Y') }}</td>
                        <td><x-erp.status :value="$quote->status" :label="\App\Models\Quote::statusLabel($quote->status)" /></td>
                        <td>
                            @if ($row['lines']->isEmpty())
                                <span class="erp-cell-sub">Ninguna</span>
                            @else
                                <ul class="smg-lines">
                                    @foreach ($row['lines']->take(4) as $line)
                                        <li>{{ \Illuminate\Support\Str::limit($line->product_name, 70) }}</li>
                                    @endforeach
                                    @if ($row['lines']->count() > 4)<li class="erp-cell-sub">+ {{ $row['lines']->count() - 4 }} más</li>@endif
                                </ul>
                            @endif
                        </td>
                        <td>
                            @if ($canEdit)
                                @include('admin.service-migration._picker', [
                                    'servicePage' => $quote->servicePage,
                                    'saveUrl' => route('admin.quotes.service-page', $quote),
                                    'sync' => true,
                                    'compact' => true,
                                ])
                                @if ($row['suggestion'])
                                    <div class="smg-sug" data-suggestion>
                                        Sugerido: <strong>#{{ $row['suggestion']->id }} · {{ $row['suggestion']->name }}</strong>
                                        <button type="button" class="erp-btn erp-btn--sm" data-accept-suggestion
                                                data-id="{{ $row['suggestion']->id }}"
                                                data-label="#{{ $row['suggestion']->id }} · {{ $row['suggestion']->name }}">Aceptar</button>
                                    </div>
                                @endif
                            @else
                                @if ($quote->servicePage)
                                    <a href="{{ url($quote->servicePage->publicPath()) }}" target="_blank" rel="noopener">#{{ $quote->servicePage->id }} · {{ $quote->servicePage->name }}</a>
                                @else — @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canEdit ? 7 : 6 }}"><x-erp.empty title="No hay cotizaciones que coincidan" text="Cambia los filtros o la vista para ver más cotizaciones." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>{{ $quotes->total() }} {{ $quotes->total() === 1 ? 'cotización' : 'cotizaciones' }}</span>
            @if ($quotes->hasPages())<div>{{ $quotes->links() }}</div>@endif
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function updateProgress(p) {
        if (!p) return;
        var t = document.getElementById('smgProgressText');
        if (t) t.textContent = p.migrated + ' de ' + p.total + ' cotizaciones migradas';
        var bar = document.getElementById('smgProgressBar');
        if (bar) {
            var inner = bar.querySelector('.erp-progress-bar > span');
            if (inner) inner.style.width = p.percent + '%';
            var val = bar.querySelector('.erp-progress-val');
            if (val) val.textContent = p.percent + '%';
            bar.classList.toggle('is-done', p.percent >= 100);
        }
    }

    document.addEventListener('smp:saved', function (e) {
        updateProgress(e.detail && e.detail.progress);
        var row = e.target.closest && e.target.closest('[data-quote-row]');
        var sug = row && row.querySelector('[data-suggestion]');
        if (sug) sug.remove();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-accept-suggestion]');
        if (!btn) return;
        var row = btn.closest('[data-quote-row]');
        var picker = row.querySelector('[data-smp]');
        window.SMPicker.setAndSave(picker, btn.dataset.id, btn.dataset.label);
    });

    var apply = document.getElementById('smgBulkApply');
    if (apply) {
        apply.addEventListener('click', function () {
            var ids = window.erpSelectedIds ? window.erpSelectedIds() : [];
            var root = document.getElementById('smgBulkPicker').closest('[data-smp]');
            var pageId = root.querySelector('[data-smp-value]').value;
            var label = root.querySelector('[data-smp-input]').value;
            if (!ids.length) { alert('Selecciona al menos una cotización.'); return; }
            if (!pageId) { alert('Elige primero el servicio web a asignar.'); return; }
            if (!confirm('¿Asignar «' + label + '» como Servicio ID a ' + ids.length + ' cotización(es)?')) return;
            apply.disabled = true;
            fetch('{{ route('admin.service-migration.bulk-assign') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ quote_ids: ids, service_page_id: pageId, sync: document.getElementById('smgBulkSync').checked ? 1 : 0 })
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
              .then(function (res) {
                  apply.disabled = false;
                  if (!res.ok) { alert((res.body && res.body.message) || 'No se pudo asignar.'); return; }
                  window.location.reload();
              })
              .catch(function () { apply.disabled = false; alert('Error de red.'); });
        });
    }
})();
</script>
@endpush
