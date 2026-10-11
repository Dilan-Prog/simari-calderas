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
    $isRec = $cfg['kind'] === 'receivable';
    $user = auth()->user();
    $without = fn ($key) => route($r . '.index', collect(request()->query())->except([$key, 'page'])->all());
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'url' => $without('search')];
    if (request($pc) && isset($parties[(int) request($pc)])) $chips[] = ['label' => $cfg['party_label'] . ': ' . $parties[(int) request($pc)], 'url' => $without($pc)];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estatus: ' . $statuses[request('status')], 'url' => $without('status')];
    if (request('currency')) $chips[] = ['label' => 'Moneda: ' . request('currency'), 'url' => $without('currency')];
    if (request('due_from')) $chips[] = ['label' => 'Vence desde: ' . request('due_from'), 'url' => $without('due_from')];
    if (request('due_to')) $chips[] = ['label' => 'Vence hasta: ' . request('due_to'), 'url' => $without('due_to')];

    // Vistas guardadas: conservan los demás filtros y solo cambian el estatus.
    $viewUrl = fn ($status) => route($r . '.index', collect(request()->query())->except(['status', 'page'])->when($status, fn ($c) => $c->put('status', $status))->all());
    $views = [
        ['label' => 'Todas','url' => $viewUrl(null), 'active' => !request('status')],
        ['label' => 'Abiertas', 'url' => $viewUrl('abiertas'), 'active' => request('status') === 'abiertas'],
        ['label' => 'Vencidas', 'url' => $viewUrl('vencida'), 'active' => request('status') === 'vencida'],
        ['label' => 'Pagadas', 'url' => $viewUrl('pagada'), 'active' => request('status') === 'pagada'],
    ];
@endphp
<div class="erp-page fin-page">

    <x-erp.page-header
        :title="$cfg['title']"
        :code="$isRec ? 'FIN2' : 'FIN3'"
        :desc="($isRec ? 'Saldos de clientes, vencimientos y cobranza' : 'Saldos con proveedores, vencimientos y pagos programados') . ' · montos en MXN equivalente'"
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], [$cfg['title'], null]]">
        <a href="{{ route($r . '.export', request()->query()) }}" class="erp-btn">Exportar CSV</a>
        @if ($user->hasPermission('payments', 'create'))
            <a href="{{ route('admin.payments.create', ['direction' => $isRec ? 'in' : 'out']) }}" class="erp-btn">{{ $isRec ? 'Registrar cobro' : 'Registrar pago' }}</a>
        @endif
        @if ($user->hasPermission($cfg['perm'], 'create'))
            <a href="{{ route($r . '.create') }}" class="erp-btn erp-btn--primary">+ Nueva {{ $cfg['singular'] }}</a>
        @endif
    </x-erp.page-header>

    @include('admin.finance.docs._tabs', ['active' => 'index'])

    <div class="erp-tiles fin-tiles" style="margin-top: 16px;">
        <x-erp.kpi-tile :title="$isRec ? 'Por cobrar' : 'Por pagar'" :value="\App\Support\ErpUi::money($summary['open'])" tone="info" :caption="$summary['count'] . ' documentos abiertos'" />
        <x-erp.kpi-tile title="Vencido" :value="\App\Support\ErpUi::money($summary['overdue'])" :tone="$summary['overdue'] > 0 ? 'bad' : 'ok'"
            :caption="$summary['overdue_count'] . ' documentos'" :href="route($r . '.index', ['status' => 'vencida'])" />
        <x-erp.kpi-tile title="Flujo próximos 30 días" :value="\App\Support\ErpUi::money($summary['next'])" tone="ok"
            :caption="($isRec ? 'por cobrar' : 'por pagar') . ' con vencimiento en 30 días o menos'" />
    </div>

    <x-erp.worklist :views="$views">
        <x-slot:toolbar>
            <form method="GET" class="fin-filterform fin-noprint">
                @if (request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                <div class="erp-search">
                    <input type="text" name="search" placeholder="Buscar por folio o {{ strtolower($cfg['party_label']) }}…" value="{{ request('search') }}" aria-label="Buscar">
                </div>
                <select name="{{ $pc }}" class="erp-select" onchange="this.form.submit()" aria-label="{{ $cfg['party_label'] }}">
                    <option value="">{{ $cfg['party_label'] }}: todos</option>
                    @foreach ($parties as $id => $name)
                        <option value="{{ $id }}" @selected((int) request($pc) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="currency" class="erp-select" onchange="this.form.submit()" aria-label="Moneda">
                    <option value="">Moneda</option>
                    <option value="MXN" @selected(request('currency') === 'MXN')>MXN</option>
                    <option value="USD" @selected(request('currency') === 'USD')>USD</option>
                </select>
                <label>Vence desde <input type="date" name="due_from" class="erp-input" value="{{ request('due_from') }}"></label>
                <label>Vence hasta <input type="date" name="due_to" class="erp-input" value="{{ request('due_to') }}"></label>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <x-erp.filter-chips :chips="$chips" :clear-url="route($r . '.index')" />

        @if ($user->hasPermission('payments', 'create'))
            <x-erp.batch-bar noun="documentos">
                <button type="button" class="erp-btn erp-btn--sm" data-fin-batch-pay
                        data-url="{{ route('admin.payments.create') }}" data-direction="{{ $isRec ? 'in' : 'out' }}" data-party-param="{{ $pc }}">
                    {{ $isRec ? 'Registrar cobro' : 'Registrar pago' }}
                </button>
            </x-erp.batch-bar>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        @if ($user->hasPermission('payments', 'create'))
                            <th class="fin-check"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todos"></th>
                        @endif
                        <th>Folio</th><th>{{ $cfg['party_label'] }}</th><th>Emisión</th><th>Vencimiento</th>
                        <th class="is-num">Total</th><th class="is-num">Pagado</th><th class="is-num">Saldo</th><th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($docs as $d)
                    <tr>
                        @if ($user->hasPermission('payments', 'create'))
                            <td class="fin-check">
                                @if ($d->isOpen())
                                    <input type="checkbox" class="erp-row-check" value="{{ $d->id }}" data-party="{{ $d->{$pc} }}" aria-label="Seleccionar {{ $d->number }}">
                                @endif
                            </td>
                        @endif
                        <td class="erp-cell-folio"><a href="{{ route($r . '.show', $d->id) }}">{{ $d->number }}</a></td>
                        <td>
                            <div class="erp-cell-main">{{ $d->partyName() }}</div>
                            <div class="erp-cell-sub"><a href="{{ route($r . '.statement', $d->{$pc}) }}" class="fin-link">Estado de cuenta</a></div>
                        </td>
                        <td>{{ $d->issue_date->format('d/m/Y') }}</td>
                        <td>
                            <x-erp.due :date="$d->due_date" :closed="!$d->isOpen()" />
                            @if ($d->isOpen())
                                <span class="fin-due-note {{ $d->daysToDue() < 0 ? 'is-bad' : '' }}">{{ $d->daysToDue() < 0 ? 'Vencida hace ' . abs($d->daysToDue()) . ' d' : ($d->daysToDue() === 0 ? 'Vence hoy' : 'Faltan ' . $d->daysToDue() . ' d') }}</span>
                            @endif
                        </td>
                        <td class="is-num">${{ number_format($d->total, 2) }}<span class="fin-cur">{{ $d->currency }}</span></td>
                        <td class="is-num">${{ number_format($d->paid_amount, 2) }}</td>
                        <td class="is-num"><strong>${{ number_format($d->balance, 2) }}</strong></td>
                        <td><x-erp.chip :tone="$d::statusTone($d->status)" :label="$d::statusLabel($d->status)" /></td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding: 0;">
                        <x-erp.empty :title="'No hay ' . strtolower($cfg['plural']) . ' con estos filtros'" text="Prueba quitando algún filtro o cambia de vista." />
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>Mostrando {{ $docs->firstItem() ?? 0 }}–{{ $docs->lastItem() ?? 0 }} de {{ $docs->total() }} {{ strtolower($cfg['plural']) }}</span>
            <div>{{ $docs->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
