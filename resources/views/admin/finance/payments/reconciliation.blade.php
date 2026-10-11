@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
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
<div class="erp-page fin-page">

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
                    <tr>
                        @if ($canEdit)
                            <td class="fin-check"><input type="checkbox" class="erp-row-check" value="{{ $p->id }}" aria-label="Seleccionar {{ $p->number }}"></td>
                        @endif
                        <td class="erp-cell-folio"><a href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                        <td><x-erp.chip :tone="$p->isIncoming() ? 'ok' : 'info'" :label="$p->isIncoming() ? 'Recibido' : 'Emitido'" /></td>
                        <td class="erp-cell-main">{{ $p->partyName() }}</td>
                        <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td>{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference)<div class="erp-cell-sub">{{ $p->reference }}</div>@endif</td>
                        <td>
                            @forelse ($p->docs as $d)
                                <a class="fin-link" href="{{ route($p->isIncoming() ? 'admin.receivables.show' : 'admin.payables.show', $d->id) }}">{{ $d->number }}</a>@if (!$loop->last), @endif
                            @empty
                                —
                            @endforelse
                        </td>
                        <td class="is-num"><strong>${{ number_format($p->amount, 2) }}</strong><span class="fin-cur">{{ $p->currency }}</span></td>
                        <td>
                            <x-erp.chip :tone="$isRec ? 'ok' : 'warn'" :label="$isRec ? 'Conciliado' : 'Por conciliar'" />
                            @if ($isRec && $p->reconciled_at)<div class="erp-cell-sub">{{ $p->reconciled_at->format('d/m/Y H:i') }}</div>@endif
                        </td>
                        @if ($canEdit)
                            <td class="fin-noprint">
                                <form method="POST" action="{{ route($isRec ? 'admin.payments.unreconcile' : 'admin.payments.reconcile', $p->id) }}" class="fin-inline-form">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="erp-btn erp-btn--sm {{ $isRec ? '' : 'erp-btn--primary' }}">{{ $isRec ? 'Quitar' : 'Conciliar' }}</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding: 0;">
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
