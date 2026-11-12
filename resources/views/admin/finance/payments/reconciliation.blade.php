@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@push('styles')
<style>
/* Pagos · conciliación — teléfono (≤768px). Escritorio sin cambios: fuera del @media solo se oculta .pay-m-only. */
.pay-rec .pay-m-only { display: none; }
@media (max-width: 768px) {
    .pay-rec { padding: 12px; overflow-x: clip; }
    .pay-rec * { box-sizing: border-box; }

    /* Encabezado */
    .pay-rec .erp-page-head { flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 14px; }
    .pay-rec .erp-page-head > div:first-child { min-width: 0; }
    .pay-rec .erp-title { font-size: 22px; overflow-wrap: anywhere; }
    .pay-rec .erp-subtitle { max-width: none; overflow-wrap: anywhere; }
    .pay-rec .erp-crumbs { flex-wrap: wrap; }
    .pay-rec .erp-head-actions { width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .pay-rec .erp-head-actions .erp-btn { width: 100%; min-height: 44px; justify-content: center; text-align: center; font-size: 14px; white-space: normal; }
    .pay-rec .erp-head-actions .erp-btn--primary { grid-column: 1 / -1; order: -1; }

    /* KPIs en 2 columnas */
    .pay-rec .erp-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .pay-rec .erp-tiles > * { min-width: 0; overflow-wrap: anywhere; }
    .pay-rec .erp-tile-value { font-size: 21px; overflow-wrap: normal; word-break: break-word; }

    /* Vistas en una fila desplazable */
    .pay-rec .erp-views { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding: 10px 12px; gap: 6px; }
    .pay-rec .erp-views::-webkit-scrollbar { display: none; }
    .pay-rec .erp-views-label { display: none; }
    .pay-rec .erp-views .erp-pill { flex: 0 0 auto; white-space: nowrap; min-height: 36px; padding: 6px 14px; font-size: 13px; display: inline-flex; align-items: center; }

    /* Filtros apilados a todo lo ancho */
    .pay-rec .erp-worklist > .erp-toolbar { padding: 12px; }
    .pay-rec .fin-filterform { gap: 8px; min-width: 0; }
    .pay-rec .fin-filterform .erp-search { flex: 1 1 100%; min-width: 0; }
    .pay-rec .fin-filterform .erp-search input { width: 100%; min-height: 44px; font-size: 16px; }
    .pay-rec .fin-filterform .erp-select { flex: 1 1 100%; width: 100%; min-width: 0; min-height: 44px; font-size: 16px; }
    .pay-rec .fin-filterform label { flex: 1 1 calc(50% - 4px); min-width: 0; font-size: 12px; }
    .pay-rec .fin-filterform label .erp-input { width: 100%; min-width: 0; min-height: 44px; font-size: 16px; }
    .pay-rec .fin-filterform .erp-btn { flex: 1 1 100%; min-height: 44px; justify-content: center; font-size: 14px; }
    .pay-rec .erp-chips-row { padding: 10px 12px 0; }
    .pay-rec .erp-chips-row .erp-pill { max-width: 100%; white-space: normal; overflow-wrap: anywhere; min-height: 32px; }

    /* Selección en lote: «Seleccionar todos» visible y barra fija abajo */
    .pay-rec .pay-selall-m { display: flex; align-items: center; gap: 10px; min-height: 44px; margin: 10px 12px 0; padding: 0 12px; border: 1px solid var(--erp-line); border-radius: 10px; font-size: 14px; font-weight: 600; color: var(--erp-text); cursor: pointer; }
    .pay-rec .pay-selall-m input { width: 20px; height: 20px; margin: 0; accent-color: var(--erp-accent); }
    .pay-rec .erp-batch.is-open { position: fixed; left: 12px; right: 12px; bottom: calc(12px + env(safe-area-inset-bottom, 0px)); z-index: 60; margin: 0; flex-wrap: wrap; gap: 8px; padding: 10px 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, .25); font-size: 13.5px; }
    .pay-rec .erp-batch.is-open > span { flex: 1 1 auto; }
    .pay-rec .erp-batch .erp-btn { min-height: 44px; padding: 8px 16px; font-size: 14px; justify-content: center; }
    .pay-rec:has(.erp-batch.is-open) { padding-bottom: 96px; }

    /* Tabla → tarjetas */
    .pay-rec .erp-table-wrap { overflow-x: visible; }
    .pay-rec .erp-table, .pay-rec .erp-table tbody { display: block; width: 100%; }
    .pay-rec .erp-table thead { display: none; }
    .pay-rec .erp-table tbody { padding: 12px; }
    .pay-rec .erp-table tbody tr.pay-row {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px 12px;
        grid-template-areas: "folio status" "party party" "type date" "method method" "docs docs" "amount amount" "action action";
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid var(--erp-line); border-radius: 10px; background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .pay-rec.pay-rec--batch .erp-table tbody tr.pay-row {
        grid-template-columns: 36px minmax(0, 1fr) minmax(0, 1fr);
        grid-template-areas: "check folio status" ". party party" ". type date" ". method method" ". docs docs" ". amount amount" "action action action";
    }
    .pay-rec .erp-table tbody tr.pay-row:last-child { margin-bottom: 0; }
    .pay-rec .erp-table tbody tr.pay-row:hover { background: #fff; }
    .pay-rec .erp-table tbody tr.pay-row > td { display: block; padding: 0; border: 0; min-width: 0; width: auto; text-align: left; font-size: 13px; overflow-wrap: anywhere; }
    .pay-rec .pay-row td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 3px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--erp-faint); }
    .pay-rec .pay-c-check { grid-area: check; align-self: center; }
    .pay-rec .pay-c-check input { width: 22px !important; height: 22px !important; margin: 0; }
    .pay-rec .pay-c-folio { grid-area: folio; align-self: center; font-size: 15px; }
    .pay-rec .pay-c-folio a { display: inline-flex; align-items: center; min-height: 36px; }
    .pay-rec .pay-c-status { grid-area: status; justify-self: end; align-self: center; text-align: right !important; }
    .pay-rec .pay-c-status .erp-chip { white-space: normal; }
    .pay-rec .pay-c-party { grid-area: party; font-size: 14px !important; font-weight: 700; padding-top: 10px !important; border-top: 1px solid var(--erp-line-soft) !important; }
    .pay-rec .pay-c-type { grid-area: type; }
    .pay-rec .pay-c-date { grid-area: date; }
    .pay-rec .pay-c-method { grid-area: method; }
    .pay-rec .pay-c-amount { grid-area: amount; padding: 8px 10px !important; border-radius: 8px; background: #f6f7f9; }
    .pay-rec .pay-c-amount strong { font-size: 16px; }
    .pay-rec .pay-c-amount { display: flex !important; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .pay-rec .pay-c-amount::before { margin: 0 !important; }
    .pay-rec .pay-c-docs { grid-area: docs; }
    .pay-rec .pay-c-docs .fin-link { display: inline-flex; align-items: center; min-height: 32px; }
    .pay-rec .pay-c-status .erp-cell-sub { font-size: 11px; }
    .pay-rec .pay-row.is-rec { border-left: 3px solid var(--erp-ok-dot, #22c55e); }
    .pay-rec .pay-row:not(.is-rec) { border-left: 3px solid var(--erp-warn-dot, #f59e0b); }
    /* Acción por tarjeta: conciliar / quitar ≥44px a todo lo ancho */
    .pay-rec .pay-c-action { grid-area: action; padding-top: 10px !important; border-top: 1px solid var(--erp-line-soft) !important; }
    .pay-rec .pay-c-action .fin-inline-form { display: block; }
    .pay-rec .pay-c-action .erp-btn { width: 100%; min-height: 44px; justify-content: center; font-size: 14px; padding: 10px 14px; }
    /* Tiles: 3 KPIs → 2 + 1 */
    .pay-rec .erp-tiles > *:last-child:nth-child(odd) { grid-column: 1 / -1; }
    .pay-rec .pay-c-amount, .pay-rec .pay-c-free { overflow-wrap: normal !important; }
    .pay-rec .pay-c-amount .fin-cur { display: inline-block; white-space: nowrap; }
    .pay-rec .erp-table tbody tr.pay-row-empty, .pay-rec .erp-table tbody tr.pay-row-empty > td { display: block; width: 100%; }
    .pay-rec .erp-empty { text-align: center; }

    /* Pie: conteo + paginación */
    .pay-rec .erp-wl-footer { flex-direction: column; align-items: center; text-align: center; gap: 10px; padding: 12px; font-size: 13px; }
    .pay-rec .erp-wl-footer > div { width: 100%; }
    .pay-rec .admin-pagination { justify-content: center; margin-top: 0; }
    .pay-rec .admin-pagination-list { justify-content: center; flex-wrap: wrap; gap: 4px; }
    .pay-rec .admin-pagination-btn { min-width: 40px; min-height: 40px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; }
    .pay-rec .admin-pagination-dots { min-width: auto; }
}
</style>
@endpush
@section('title')
    Conciliación - Pagos - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('payments', 'edit');
    $dirs = ['in' => 'Cobros de clientes', 'out' => 'Pagos a proveedores'];
    $without = fn ($key) => route('admin.payments.reconciliation', collect(request()->query())->except([$key, 'page'])->all());
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'url' => $without('search')];
    if (request('direction') && isset($dirs[request('direction')])) $chips[] = ['label' => $dirs[request('direction')], 'url' => $without('direction')];
    if (request('currency')) $chips[] = ['label' => 'Moneda: ' . request('currency'), 'url' => $without('currency')];
    if (request('method') && isset($methods[(int) request('method')])) $chips[] = ['label' => 'Método: ' . $methods[(int) request('method')], 'url' => $without('method')];
    if (request('from')) $chips[] = ['label' => 'Desde: ' . request('from'), 'url' => $without('from')];
    if (request('to')) $chips[] = ['label' => 'Hasta: ' . request('to'), 'url' => $without('to')];

    $viewUrl = fn ($v) => route('admin.payments.reconciliation', collect(request()->query())->except(['view', 'page'])->put('view', $v)->all());
    $views = [
        ['label' => 'Por conciliar', 'url' => $viewUrl('pendientes'), 'active' => $view === 'pendientes'],
        ['label' => 'Conciliados', 'url' => $viewUrl('conciliados'), 'active' => $view === 'conciliados'],
        ['label' => 'Todos', 'url' => $viewUrl('todos'), 'active' => $view === 'todos'],
    ];
@endphp
<div class="erp-page fin-page pay-rec {{ $canEdit ? 'pay-rec--batch' : '' }}">

    <x-erp.page-header
        title="Conciliación" code="FIN5"
        desc="Pagos aplicados a documentos pendientes de conciliar. Márcalos como conciliados cuando coincidan con tu estado de cuenta bancario."
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], ['Pagos', route('admin.payments.index')], ['Conciliación', null]]">
        <a href="{{ route('admin.payments.index') }}" class="erp-btn">Ver todos los pagos</a>
    </x-erp.page-header>

    <div class="erp-tiles fin-tiles">
        <x-erp.kpi-tile title="Por conciliar" :value="(string) $stats['to_reconcile']" :tone="$stats['to_reconcile'] > 0 ? 'warn' : 'ok'"
            :caption="\App\Support\ErpUi::money($stats['to_reconcile_amount']) . ' MXN equivalente'" :href="$viewUrl('pendientes')" />
        <x-erp.kpi-tile title="Conciliados este mes" :value="(string) $stats['reconciled_month']" tone="ok" caption="pagos conciliados" :href="$viewUrl('conciliados')" />
        <x-erp.kpi-tile title="Sin aplicar" :value="(string) $stats['pending']" :tone="$stats['pending'] > 0 ? 'bad' : 'ok'"
            caption="deben aplicarse antes de conciliar" :href="route('admin.payments.index', ['status' => 'pendiente'])" />
    </div>

    <x-erp.worklist :views="$views">
        <x-slot:toolbar>
            <form method="GET" class="fin-filterform fin-noprint">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="erp-search">
                    <input type="text" name="search" placeholder="Buscar por folio, referencia o contraparte…" value="{{ request('search') }}" aria-label="Buscar">
                </div>
                <select name="direction" class="erp-select" onchange="this.form.submit()" aria-label="Tipo">
                    <option value="">Cobros y pagos</option>
                    @foreach ($dirs as $k => $label)
                        <option value="{{ $k }}" @selected(request('direction') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="method" class="erp-select" onchange="this.form.submit()" aria-label="Método de pago">
                    <option value="">Método de pago</option>
                    @foreach ($methods as $id => $name)
                        <option value="{{ $id }}" @selected((int) request('method') === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="currency" class="erp-select" onchange="this.form.submit()" aria-label="Moneda">
                    <option value="">Moneda</option>
                    <option value="MXN" @selected(request('currency') === 'MXN')>MXN</option>
                    <option value="USD" @selected(request('currency') === 'USD')>USD</option>
                </select>
                <label>Desde <input type="date" name="from" class="erp-input" value="{{ request('from') }}"></label>
                <label>Hasta <input type="date" name="to" class="erp-input" value="{{ request('to') }}"></label>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <x-erp.filter-chips :chips="$chips" :clear-url="route('admin.payments.reconciliation', ['view' => $view])" />

        @if ($canEdit)
            @if ($payments->isNotEmpty())
                <label class="pay-m-only pay-selall-m"><input type="checkbox" data-pay-selall-m> Seleccionar todos</label>
            @endif
            <x-erp.batch-bar noun="pagos">
                @if ($view !== 'conciliados')
                    <button type="button" class="erp-btn erp-btn--sm" data-fin-batch-patch
                            data-url-template="{{ route('admin.payments.reconcile', '__ID__') }}" data-confirm="¿Marcar como conciliados los pagos seleccionados?">Marcar conciliados</button>
                @endif
                @if ($view !== 'pendientes')
                    <button type="button" class="erp-btn erp-btn--sm" data-fin-batch-patch
                            data-url-template="{{ route('admin.payments.unreconcile', '__ID__') }}" data-confirm="¿Quitar la conciliación de los pagos seleccionados?">Quitar conciliación</button>
                @endif
            </x-erp.batch-bar>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        @if ($canEdit)<th class="fin-check"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todos"></th>@endif
                        <th>Folio</th><th>Tipo</th><th>Cliente / proveedor</th><th>Fecha</th><th>Método / referencia</th>
                        <th>Aplicado a</th><th class="is-num">Monto</th><th>Conciliación</th>
                        @if ($canEdit)<th class="fin-noprint"></th>@endif
                    </tr>
                </thead>
                <tbody>
                @forelse ($payments as $p)
                    @php $isRec = $p->status === 'conciliado'; @endphp
                    <tr class="pay-row {{ $isRec ? 'is-rec' : '' }}">
                        @if ($canEdit)
                            <td class="fin-check pay-c-check"><input type="checkbox" class="erp-row-check" value="{{ $p->id }}" aria-label="Seleccionar {{ $p->number }}"></td>
                        @endif
                        <td class="erp-cell-folio pay-c-folio"><a href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                        <td class="pay-c-type" data-label="Tipo"><x-erp.chip :tone="$p->isIncoming() ? 'ok' : 'info'" :label="$p->isIncoming() ? 'Recibido' : 'Emitido'" /></td>
                        <td class="erp-cell-main pay-c-party" data-label="Cliente / proveedor">{{ $p->partyName() }}</td>
                        <td class="pay-c-date" data-label="Fecha">{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td class="pay-c-method" data-label="Método / referencia">{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference)<div class="erp-cell-sub">{{ $p->reference }}</div>@endif</td>
                        <td class="pay-c-docs" data-label="Aplicado a">
                            @forelse ($p->docs as $d)
                                <a class="fin-link" href="{{ route($p->isIncoming() ? 'admin.receivables.show' : 'admin.payables.show', $d->id) }}">{{ $d->number }}</a>@if (!$loop->last), @endif
                            @empty
                                —
                            @endforelse
                        </td>
                        <td class="is-num pay-c-amount" data-label="Monto"><strong>${{ number_format($p->amount, 2) }}</strong><span class="fin-cur">{{ $p->currency }}</span></td>
                        <td class="pay-c-status">
                            <x-erp.chip :tone="$isRec ? 'ok' : 'warn'" :label="$isRec ? 'Conciliado' : 'Por conciliar'" />
                            @if ($isRec && $p->reconciled_at)<div class="erp-cell-sub">{{ $p->reconciled_at->format('d/m/Y H:i') }}</div>@endif
                        </td>
                        @if ($canEdit)
                            <td class="fin-noprint pay-c-action">
                                <form method="POST" action="{{ route($isRec ? 'admin.payments.unreconcile' : 'admin.payments.reconcile', $p->id) }}" class="fin-inline-form">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="erp-btn erp-btn--sm {{ $isRec ? '' : 'erp-btn--primary' }}">{{ $isRec ? 'Quitar' : 'Conciliar' }}</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr class="pay-row-empty"><td colspan="10" style="padding: 0;">
                        <x-erp.empty :title="$view === 'conciliados' ? 'Aún no hay pagos conciliados' : 'No hay pagos por conciliar'"
                            text="Los pagos aplicados a documentos aparecen aquí para conciliarse." />
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>Mostrando {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} de {{ $payments->total() }} pagos</span>
            <div>{{ $payments->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
@push('scripts')
<script>
/* Móvil: «Seleccionar todos» replica el checkbox de cabecera (oculto en tarjetas) que maneja erp-ui.js. */
(function () {
    var m = document.querySelector('[data-pay-selall-m]');
    if (!m) return;
    var head = function () { return document.querySelector('[data-erp-select-all]'); };
    m.addEventListener('change', function () {
        var h = head();
        if (!h) return;
        h.checked = m.checked;
        h.dispatchEvent(new Event('change', { bubbles: true }));
    });
    document.addEventListener('change', function (e) {
        if (e.target === m) return;
        if (!e.target.matches('.erp-row-check, [data-erp-select-all]')) return;
        setTimeout(function () {
            var h = head();
            if (h) { m.checked = h.checked; m.indeterminate = h.indeterminate; }
        }, 0);
    });
})();
</script>
@endpush
