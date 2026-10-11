@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Pagos - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('payments', 'edit');
    $dirs = ['in' => 'Cobros de clientes', 'out' => 'Pagos a proveedores'];
    $without = fn ($key) => route('admin.payments.index', collect(request()->query())->except([$key, 'page'])->all());
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'url' => $without('search')];
    if (request('direction') && isset($dirs[request('direction')])) $chips[] = ['label' => $dirs[request('direction')], 'url' => $without('direction')];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estatus: ' . $statuses[request('status')], 'url' => $without('status')];
    if (request('currency')) $chips[] = ['label' => 'Moneda: ' . request('currency'), 'url' => $without('currency')];
    if (request('method') && isset($methods[(int) request('method')])) $chips[] = ['label' => 'Método: ' . $methods[(int) request('method')], 'url' => $without('method')];
    if (request('from')) $chips[] = ['label' => 'Desde: ' . request('from'), 'url' => $without('from')];
    if (request('to')) $chips[] = ['label' => 'Hasta: ' . request('to'), 'url' => $without('to')];

    // Vistas: cada una fija dirección y/o estatus y conserva los demás filtros.
    $viewUrl = fn (array $set) => route('admin.payments.index', collect(request()->query())->except(['direction', 'status', 'page'])->merge($set)->all());
    $views = [
        ['label' => 'Todos', 'url' => $viewUrl([]), 'active' => !request('direction') && !request('status')],
        ['label' => 'Recibidos', 'url' => $viewUrl(['direction' => 'in']), 'active' => request('direction') === 'in' && !request('status')],
        ['label' => 'Emitidos', 'url' => $viewUrl(['direction' => 'out']), 'active' => request('direction') === 'out' && !request('status')],
        ['label' => 'Por conciliar', 'url' => $viewUrl(['status' => 'aplicado']), 'active' => request('status') === 'aplicado' && !request('direction')],
    ];
@endphp
<div class="erp-page fin-page">

    <x-erp.page-header
        title="Pagos" code="FIN4"
        desc="Cobros de clientes y pagos a proveedores, aplicación a documentos y conciliación"
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], ['Pagos', null]]">
        <a href="{{ route('admin.payments.export', request()->query()) }}" class="erp-btn">Exportar CSV</a>
        <a href="{{ route('admin.payments.reconciliation') }}" class="erp-btn">Conciliación</a>
        @if ($user->hasPermission('payments', 'create'))
            <a href="{{ route('admin.payments.create', ['direction' => 'out']) }}" class="erp-btn">Pago a proveedor</a>
            <a href="{{ route('admin.payments.create', ['direction' => 'in']) }}" class="erp-btn erp-btn--primary">+ Registrar cobro</a>
        @endif
    </x-erp.page-header>

    <div class="erp-tiles fin-tiles">
        <x-erp.kpi-tile title="Cobrado este mes" :value="\App\Support\ErpUi::money($stats['in_month'])" tone="ok" caption="MXN equivalente" />
        <x-erp.kpi-tile title="Pagado este mes" :value="\App\Support\ErpUi::money($stats['out_month'])" tone="info" caption="MXN equivalente" />
        <x-erp.kpi-tile title="Por conciliar" :value="(string) $stats['to_reconcile']" :tone="$stats['to_reconcile'] > 0 ? 'warn' : 'ok'"
            caption="pagos aplicados sin conciliar" :href="route('admin.payments.index', ['status' => 'aplicado'])" />
        <x-erp.kpi-tile title="Sin aplicar" :value="(string) $stats['pending']" :tone="$stats['pending'] > 0 ? 'bad' : 'ok'"
            caption="pagos pendientes de aplicar" :href="route('admin.payments.index', ['status' => 'pendiente'])" />
    </div>

    <x-erp.worklist :views="$views">
        <x-slot:toolbar>
            <form method="GET" class="fin-filterform fin-noprint">
                @if (request('direction')) <input type="hidden" name="direction" value="{{ request('direction') }}"> @endif
                @if (request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                <div class="erp-search">
                    <input type="text" name="search" placeholder="Buscar por folio, referencia o contraparte…" value="{{ request('search') }}" aria-label="Buscar">
                </div>
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

        <x-erp.filter-chips :chips="$chips" :clear-url="route('admin.payments.index')" />

        @if ($canEdit)
            <x-erp.batch-bar noun="pagos">
                <button type="button" class="erp-btn erp-btn--sm" data-fin-batch-patch
                        data-url-template="{{ route('admin.payments.reconcile', '__ID__') }}" data-confirm="¿Marcar como conciliados los pagos seleccionados?">Conciliar</button>
            </x-erp.batch-bar>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        @if ($canEdit)<th class="fin-check"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todos"></th>@endif
                        <th>Folio</th><th>Tipo</th><th>Cliente / proveedor</th><th>Fecha</th><th>Método / referencia</th>
                        <th class="is-num">Monto</th><th class="is-num">Sin aplicar</th><th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr>
                        @if ($canEdit)
                            <td class="fin-check">
                                @if ($p->status === 'aplicado')
                                    <input type="checkbox" class="erp-row-check" value="{{ $p->id }}" aria-label="Seleccionar {{ $p->number }}">
                                @endif
                            </td>
                        @endif
                        <td class="erp-cell-folio"><a href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                        <td><x-erp.chip :tone="$p->isIncoming() ? 'ok' : 'info'" :label="$p->isIncoming() ? 'Recibido' : 'Emitido'" /></td>
                        <td class="erp-cell-main">{{ $p->partyName() }}</td>
                        <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td>{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference)<div class="erp-cell-sub">{{ $p->reference }}</div>@endif</td>
                        <td class="is-num"><strong>${{ number_format($p->amount, 2) }}</strong><span class="fin-cur">{{ $p->currency }}</span></td>
                        <td class="is-num">{{ $p->status === 'cancelado' ? '—' : '$' . number_format($p->unappliedAmount(), 2) }}</td>
                        <td><x-erp.chip :tone="\App\Models\Payment::statusTone($p->status)" :label="\App\Models\Payment::statusLabel($p->status)" /></td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding: 0;">
                        <x-erp.empty title="No hay pagos con estos filtros" text="Prueba quitando algún filtro o cambia de vista." />
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
