@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Pagos - Admin
@endsection
@section('content')
@php
    $chips = [];
    $dirs = ['in' => 'Cobros de clientes', 'out' => 'Pagos a proveedores'];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'remove' => 'search'];
    if (request('direction') && isset($dirs[request('direction')])) $chips[] = ['label' => $dirs[request('direction')], 'remove' => 'direction'];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estatus: ' . $statuses[request('status')], 'remove' => 'status'];
    if (request('currency')) $chips[] = ['label' => 'Moneda: ' . request('currency'), 'remove' => 'currency'];
    if (request('method') && isset($methods[(int) request('method')])) $chips[] = ['label' => 'Método: ' . $methods[(int) request('method')], 'remove' => 'method'];
    if (request('from')) $chips[] = ['label' => 'Desde: ' . request('from'), 'remove' => 'from'];
    if (request('to')) $chips[] = ['label' => 'Hasta: ' . request('to'), 'remove' => 'to'];
    $without = fn ($key) => route('admin.payments.index', collect(request()->query())->except([$key, 'page'])->all());
    $user = auth()->user();
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <strong>Pagos</strong></p>
            <h1 class="erp-title">Pagos</h1>
            <p class="erp-subtitle">Cobros de clientes y pagos a proveedores, aplicación a documentos y conciliación</p>
        </div>
        <div class="fin-head-actions">
            <a href="{{ route('admin.payments.export', request()->query()) }}" class="fin-btn">Exportar CSV</a>
            @if ($user->hasPermission('payments', 'create'))
                <a href="{{ route('admin.payments.create', ['direction' => 'out']) }}" class="fin-btn">Pago a proveedor</a>
                <a href="{{ route('admin.payments.create', ['direction' => 'in']) }}" class="fin-btn fin-btn-primary">+ Registrar cobro</a>
            @endif
        </div>
    </div>

    <div class="fin-kpis">
        <div class="fin-kpi is-ok"><span>Cobrado este mes</span><strong>${{ number_format($stats['in_month'], 2) }}</strong><small>MXN equivalente</small></div>
        <div class="fin-kpi"><span>Pagado este mes</span><strong>${{ number_format($stats['out_month'], 2) }}</strong><small>MXN equivalente</small></div>
        <a class="fin-kpi" href="{{ route('admin.payments.index', ['status' => 'aplicado']) }}"><span>Por conciliar</span><strong>{{ $stats['to_reconcile'] }}</strong><small>pagos aplicados sin conciliar</small></a>
        <a class="fin-kpi {{ $stats['pending'] > 0 ? 'is-bad' : '' }}" href="{{ route('admin.payments.index', ['status' => 'pendiente']) }}"><span>Sin aplicar</span><strong>{{ $stats['pending'] }}</strong><small>pagos pendientes de aplicar</small></a>
    </div>

    <form method="GET" class="fin-filters fin-noprint">
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio, referencia o contraparte..." value="{{ request('search') }}">
        <select name="direction" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Cobros y pagos</option>
            @foreach ($dirs as $k => $label)
                <option value="{{ $k }}" @selected(request('direction') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los estatus</option>
            @foreach ($statuses as $k => $label)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="method" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Método de pago</option>
            @foreach ($methods as $id => $name)
                <option value="{{ $id }}" @selected((int) request('method') === $id)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="currency" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Moneda</option>
            <option value="MXN" @selected(request('currency') === 'MXN')>MXN</option>
            <option value="USD" @selected(request('currency') === 'USD')>USD</option>
        </select>
        <label>Desde <input type="date" name="from" class="users-manager-input" value="{{ request('from') }}"></label>
        <label>Hasta <input type="date" name="to" class="users-manager-input" value="{{ request('to') }}"></label>
        <button type="submit" class="fin-btn">Filtrar</button>
    </form>

    @if (count($chips))
        <div class="fin-chips fin-noprint">
            @foreach ($chips as $c)
                <a href="{{ $without($c['remove']) }}" class="fin-chip">{{ $c['label'] }} <span aria-hidden="true">×</span></a>
            @endforeach
            <a href="{{ route('admin.payments.index') }}" class="fin-chip-clear">Limpiar todo</a>
        </div>
    @endif

    <main class="table-container-clients-manager">
        <div class="fin-scroll">
            <table class="fin-table">
                <thead>
                    <tr><th>FOLIO</th><th>TIPO</th><th>CONTRAPARTE</th><th>FECHA</th><th>MÉTODO</th><th class="num">MONTO</th><th class="num">SIN APLICAR</th><th>ESTATUS</th></tr>
                </thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr>
                        <td><a class="fin-link" href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                        <td>{{ $p->directionLabel() }}</td>
                        <td>{{ $p->partyName() }}</td>
                        <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td>{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference)<div class="fin-muted">{{ $p->reference }}</div>@endif</td>
                        <td class="num">${{ number_format($p->amount, 2) }} <span class="fin-muted">{{ $p->currency }}</span></td>
                        <td class="num">{{ $p->status === 'cancelado' ? '—' : '$' . number_format($p->unappliedAmount(), 2) }}</td>
                        <td><span class="erp-chip erp-tone-{{ \App\Models\Payment::statusTone($p->status) }}">{{ \App\Models\Payment::statusLabel($p->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="fin-empty">No hay pagos con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>
    <div style="margin-top:16px;">{{ $payments->links('admin.components.pagination') }}</div>

</section>
</div>
@endsection
