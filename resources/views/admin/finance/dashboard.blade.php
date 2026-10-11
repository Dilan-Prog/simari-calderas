@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Panel de finanzas - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $money = fn ($v) => \App\Support\ErpUi::money($v);
    $totals = $aging['totals'];
    $colors = ['current' => '#16a34a', 'd30' => '#f59e0b', 'd60' => '#ea580c', 'd90' => '#dc2626', 'd90p' => '#991b1b'];
    $segments = collect($buckets)->map(fn ($label, $key) => [
        'label' => $key === 'current' ? 'Vigente' : $label . ' días',
        'value' => (float) $totals[$key], 'amount' => $money($totals[$key]), 'color' => $colors[$key],
    ])->values()->all();
    $overduePct = $rec['open'] > 0 ? round($rec['overdue'] / $rec['open'] * 100) : 0;
    $maxWeek = max(1, collect($weeks)->max(fn ($w) => max($w['in'], $w['out'])));
    $k = fn ($v) => '$' . number_format($v / 1000, 0) . ' k';
    $netFmt = fn ($v) => ($v >= 0 ? '+' : '−') . '$' . number_format(abs($v) / 1000, 0) . ' k';
    $netTotal = $cash['net'];
@endphp
<div class="erp-page fin-page">

    <x-erp.page-header
        title="Panel de finanzas" code="FIN1"
        desc="Por cobrar, por pagar, vencido y flujo proyectado a 30 días · montos en MXN equivalente."
        :crumbs="[['Finanzas', null]]">
        @if ($user->hasPermission('receivables'))<a href="{{ route('admin.receivables.index') }}" class="erp-btn">Cuentas por cobrar</a>@endif
        @if ($user->hasPermission('payables'))<a href="{{ route('admin.payables.index') }}" class="erp-btn">Cuentas por pagar</a>@endif
        @if ($user->hasPermission('payments'))
            <a href="{{ route('admin.payments.index') }}" class="erp-btn">Pagos</a>
            <a href="{{ route('admin.payments.reconciliation') }}" class="erp-btn">Conciliación</a>
        @endif
    </x-erp.page-header>

    <div class="erp-tiles fin-tiles">
        <x-erp.kpi-tile title="Por cobrar" :value="$money($rec['open'])" tone="info" :caption="$rec['count'] . ' documentos abiertos'"
            :href="$user->hasPermission('receivables') ? route('admin.receivables.index', ['status' => 'abiertas']) : null" />
        <x-erp.kpi-tile title="Vencido" :value="$money($rec['overdue'])" :tone="$rec['overdue'] > 0 ? 'bad' : 'ok'"
            :caption="$overduePct . '% de la cartera · ' . $rec['overdue_count'] . ' documentos'"
            :href="$user->hasPermission('receivables') ? route('admin.receivables.index', ['status' => 'vencida']) : null" />
        <x-erp.kpi-tile title="Por pagar" :value="$money($pay['open'])" tone="warn" :caption="$pay['count'] . ' documentos de proveedor'"
            :href="$user->hasPermission('payables') ? route('admin.payables.index', ['status' => 'abiertas']) : null" />
        <x-erp.kpi-tile title="Flujo neto 30 días" :value="($cash['net'] >= 0 ? '+' : '−') . $money(abs($cash['net']))" :tone="$cash['net'] >= 0 ? 'ok' : 'bad'"
            :caption="'Entradas ' . $money($cash['in']) . ' · Salidas ' . $money($cash['out'])"
            :href="$user->hasPermission('payments') ? route('admin.payments.index') : null" />
    </div>

    <div class="fin-dash-grid">
        <x-erp.card title="Antigüedad de saldos por cobrar" :sub="'Total ' . $money($totals['total']) . ' MXN'">
            <x-slot:actions>
                @if ($user->hasPermission('receivables'))<a href="{{ route('admin.receivables.aging') }}" class="erp-btn erp-btn--sm">Ver detalle</a>@endif
            </x-slot:actions>

            <x-erp.aging-bar :segments="$segments" :total="$totals['total']" :cols="count($segments)" />

            <div class="fin-subhead">Mayor saldo vencido</div>
            @forelse ($topOverdue as $row)
                @php
                    $tag = $row['d90p'] > 0 ? ['bad', '+90 días'] : ($row['d90'] > 0 ? ['bad', '61–90 días'] : ($row['d60'] > 0 ? ['warn', '31–60 días'] : ['warn', '1–30 días']));
                    $href = $user->hasPermission('receivables') ? route('admin.receivables.statement', $row['party_id']) : null;
                @endphp
                @if ($href)<a href="{{ $href }}" class="fin-list-row">@else<div class="fin-list-row">@endif
                    <span class="fin-grow"><b>{{ $row['party'] }}</b></span>
                    <x-erp.chip :tone="$tag[0]" :label="$tag[1]" />
                    <span class="fin-amt">{{ $money($row['overdue']) }}</span>
                @if ($href)</a>@else</div>@endif
            @empty
                <p class="fin-muted" style="margin: 0;">Sin saldos vencidos. La cartera está al corriente.</p>
            @endforelse
        </x-erp.card>

        <x-erp.card title="Flujo proyectado · 30 días" sub="Cobranza esperada vs. pagos programados (por fecha de vencimiento)">
            <x-slot:actions>
                <div class="fin-legend" aria-hidden="true">
                    <span><i style="background: var(--erp-ok-dot);"></i>Entradas</span>
                    <span><i style="background: var(--erp-neu-dot);"></i>Salidas</span>
                </div>
            </x-slot:actions>

            <div class="fin-flow" role="img" aria-label="Flujo proyectado por semana: {{ collect($weeks)->map(fn ($w) => $w['from']->format('d/m') . ' al ' . $w['to']->format('d/m') . ' entradas ' . $money($w['in']) . ', salidas ' . $money($w['out']))->implode('; ') }}">
                @foreach ($weeks as $w)
                    @php $net = $w['in'] - $w['out']; @endphp
                    <div class="fin-flow-col">
                        <div class="fin-flow-bars">
                            <span class="fin-flow-in" style="height: {{ round($w['in'] / $maxWeek * 100) }}%;" title="Entradas {{ $money($w['in']) }}"></span>
                            <span class="fin-flow-out" style="height: {{ round($w['out'] / $maxWeek * 100) }}%;" title="Salidas {{ $money($w['out']) }}"></span>
                        </div>
                        <div class="fin-flow-label">{{ $w['from']->format('d/m') }}–{{ $w['to']->format('d/m') }}</div>
                        <div class="fin-flow-net {{ $net >= 0 ? 'erp-tone-ok' : 'erp-tone-bad' }}">{{ $netFmt($net) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="fin-subhead">Próximos pagos a proveedores</div>
            @forelse ($upcoming as $p)
                @php $d = \App\Support\ErpUi::due($p->due_date); $href = $user->hasPermission('payables') ? route('admin.payables.show', $p->id) : null; @endphp
                @if ($href)<a href="{{ $href }}" class="fin-list-row">@else<div class="fin-list-row">@endif
                    <i class="erp-dot erp-dot--{{ $d['tone'] }}"></i>
                    <span class="fin-grow"><b>{{ $p->number }}</b> · {{ $p->partyName() }}</span>
                    <span class="fin-muted erp-tone-{{ $d['tone'] }}" style="font-weight: 700;">{{ $d['overdue'] ? 'Vencida ' : 'Vence ' }}{{ $d['text'] }}</span>
                    <span class="fin-amt">${{ number_format($p->balance, 2) }}@if ($p->currency !== 'MXN') <span class="fin-cur">{{ $p->currency }}</span>@endif</span>
                @if ($href)</a>@else</div>@endif
            @empty
                <p class="fin-muted" style="margin: 0;">Sin pagos pendientes a proveedores.</p>
            @endforelse
        </x-erp.card>
    </div>

</div>
@endsection
