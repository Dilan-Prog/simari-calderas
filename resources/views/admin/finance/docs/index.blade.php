@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    {{ $cfg['title'] }} - Admin
@endsection
@section('content')
@php
    $r = $cfg['route'];
    $pc = $cfg['party_col'];
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'remove' => 'search'];
    if (request($pc) && isset($parties[(int) request($pc)])) $chips[] = ['label' => $cfg['party_label'] . ': ' . $parties[(int) request($pc)], 'remove' => $pc];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estatus: ' . $statuses[request('status')], 'remove' => 'status'];
    if (request('currency')) $chips[] = ['label' => 'Moneda: ' . request('currency'), 'remove' => 'currency'];
    if (request('due_from')) $chips[] = ['label' => 'Vence desde: ' . request('due_from'), 'remove' => 'due_from'];
    if (request('due_to')) $chips[] = ['label' => 'Vence hasta: ' . request('due_to'), 'remove' => 'due_to'];
    $without = fn ($key) => route($r . '.index', collect(request()->query())->except([$key, 'page'])->all());
    $isRec = $cfg['kind'] === 'receivable';
    $user = auth()->user();
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <strong>{{ $cfg['title'] }}</strong></p>
            <h1 class="erp-title">{{ $cfg['title'] }}</h1>
            <p class="erp-subtitle">{{ $isRec ? 'Saldos de clientes, vencimientos y cobranza' : 'Saldos con proveedores, vencimientos y pagos programados' }} · montos en MXN equivalente</p>
        </div>
        <div class="fin-head-actions">
            <a href="{{ route($r . '.export', request()->query()) }}" class="fin-btn">Exportar CSV</a>
            @if ($user->hasPermission('payments', 'create'))
                <a href="{{ route('admin.payments.create', ['direction' => $isRec ? 'in' : 'out']) }}" class="fin-btn">{{ $isRec ? 'Registrar cobro' : 'Registrar pago' }}</a>
            @endif
            @if ($user->hasPermission($cfg['perm'], 'create'))
                <a href="{{ route($r . '.create') }}" class="fin-btn fin-btn-primary">+ Nueva {{ $cfg['singular'] }}</a>
            @endif
        </div>
    </div>

    @include('admin.finance.docs._tabs', ['active' => 'index'])

    <div class="fin-kpis">
        <div class="fin-kpi"><span>{{ $isRec ? 'Por cobrar' : 'Por pagar' }}</span><strong>${{ number_format($summary['open'], 2) }}</strong><small>{{ $summary['count'] }} documentos abiertos</small></div>
        <a class="fin-kpi {{ $summary['overdue'] > 0 ? 'is-bad' : '' }}" href="{{ route($r . '.index', ['status' => 'vencida']) }}"><span>Vencido</span><strong>${{ number_format($summary['overdue'], 2) }}</strong><small>{{ $summary['overdue_count'] }} documentos</small></a>
        <div class="fin-kpi"><span>Flujo próximos 30 días</span><strong>${{ number_format($summary['next'], 2) }}</strong><small>{{ $isRec ? 'por cobrar' : 'por pagar' }} con vencimiento en 30 días o menos</small></div>
    </div>

    <form method="GET" class="fin-filters fin-noprint">
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio o {{ strtolower($cfg['party_label']) }}..." value="{{ request('search') }}">
        <select name="{{ $pc }}" class="users-manager-input" onchange="this.form.submit()">
            <option value="">{{ $cfg['party_label'] }}: todos</option>
            @foreach ($parties as $id => $name)
                <option value="{{ $id }}" @selected((int) request($pc) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="status" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los estatus</option>
            @foreach ($statuses as $k => $label)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="currency" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Moneda</option>
            <option value="MXN" @selected(request('currency') === 'MXN')>MXN</option>
            <option value="USD" @selected(request('currency') === 'USD')>USD</option>
        </select>
        <label>Vence desde <input type="date" name="due_from" class="users-manager-input" value="{{ request('due_from') }}"></label>
        <label>Vence hasta <input type="date" name="due_to" class="users-manager-input" value="{{ request('due_to') }}"></label>
        <button type="submit" class="fin-btn">Filtrar</button>
    </form>

    @if (count($chips))
        <div class="fin-chips fin-noprint">
            @foreach ($chips as $c)
                <a href="{{ $without($c['remove']) }}" class="fin-chip">{{ $c['label'] }} <span aria-hidden="true">×</span></a>
            @endforeach
            <a href="{{ route($r . '.index') }}" class="fin-chip-clear">Limpiar todo</a>
        </div>
    @endif

    <main class="table-container-clients-manager">
        <div class="fin-scroll">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th>FOLIO</th><th>{{ strtoupper($cfg['party_label']) }}</th><th>EMISIÓN</th><th>VENCIMIENTO</th>
                        <th class="num">TOTAL</th><th class="num">PAGADO</th><th class="num">SALDO</th><th>ESTATUS</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($docs as $d)
                    <tr>
                        <td><a class="fin-link" href="{{ route($r . '.show', $d->id) }}">{{ $d->number }}</a></td>
                        <td>
                            {{ $d->partyName() }}
                            <div class="fin-muted"><a href="{{ route($r . '.statement', $d->{$pc}) }}">Estado de cuenta</a></div>
                        </td>
                        <td>{{ $d->issue_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="fin-due fin-due-{{ $d->dueTone() }}"><i></i>{{ $d->due_date->format('d/m/Y') }}</span>
                            @if ($d->isOpen())
                                <div class="fin-muted">{{ $d->daysToDue() < 0 ? 'Vencida hace ' . abs($d->daysToDue()) . ' d' : ($d->daysToDue() === 0 ? 'Vence hoy' : 'Faltan ' . $d->daysToDue() . ' d') }}</div>
                            @endif
                        </td>
                        <td class="num">${{ number_format($d->total, 2) }} <span class="fin-muted">{{ $d->currency }}</span></td>
                        <td class="num">${{ number_format($d->paid_amount, 2) }}</td>
                        <td class="num"><strong>${{ number_format($d->balance, 2) }}</strong></td>
                        <td><span class="erp-chip erp-tone-{{ $d::statusTone($d->status) }}">{{ $d::statusLabel($d->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="fin-empty">No hay {{ strtolower($cfg['plural']) }} con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>
    <div style="margin-top:16px;">{{ $docs->links('admin.components.pagination') }}</div>

</section>
</div>
@endsection
