@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Antigüedad de saldos - {{ $cfg['title'] }} - Admin
@endsection
@section('content')
@php
    $r = $cfg['route'];
    $totals = $report['totals'];
    $colors = ['current' => '#16a34a', 'd30' => '#f59e0b', 'd60' => '#ea580c', 'd90' => '#dc2626', 'd90p' => '#991b1b'];
    $segLabel = fn ($key, $label) => $key === 'current' ? 'Vigente' : $label . ' días';
    $segments = collect($buckets)->map(fn ($label, $key) => [
        'label' => $segLabel($key, $label), 'value' => (float) $totals[$key],
        'amount' => \App\Support\ErpUi::money($totals[$key]), 'color' => $colors[$key],
    ])->values()->all();
    $overdueTotal = round($totals['total'] - $totals['current'], 2);
@endphp
<div class="erp-page fin-page">

    <x-erp.page-header
        title="Antigüedad de saldos"
        :code="$cfg['kind'] === 'receivable' ? 'FIN2' : 'FIN3'"
        :desc="'Saldo abierto por ' . strtolower($cfg['party_label']) . ' según días de vencimiento · MXN equivalente'"
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], [$cfg['title'], route($r . '.index')], ['Antigüedad de saldos', null]]">
        <button type="button" class="erp-btn fin-noprint" onclick="window.print()">Imprimir</button>
    </x-erp.page-header>

    @include('admin.finance.docs._tabs', ['active' => 'aging'])

    <div class="erp-tiles fin-tiles" style="margin-top: 16px;">
        <x-erp.kpi-tile title="Saldo total" :value="\App\Support\ErpUi::money($totals['total'])" tone="info" caption="MXN equivalente" />
        <x-erp.kpi-tile title="Vigente" :value="\App\Support\ErpUi::money($totals['current'])" tone="ok" caption="aún no vence" />
        <x-erp.kpi-tile title="Vencido" :value="\App\Support\ErpUi::money($overdueTotal)" :tone="$overdueTotal > 0 ? 'bad' : 'ok'"
            :caption="$totals['total'] > 0 ? round($overdueTotal / $totals['total'] * 100) . '% de la cartera' : 'sin saldos'" />
        <x-erp.kpi-tile title="Más de 90 días" :value="\App\Support\ErpUi::money($totals['d90p'])" :tone="$totals['d90p'] > 0 ? 'bad' : 'neu'" caption="cartera crítica" />
    </div>

    <x-erp.card title="Distribución por antigüedad" :sub="'Total ' . \App\Support\ErpUi::money($totals['total']) . ' MXN'">
        <x-erp.aging-bar :segments="$segments" :total="$totals['total']" :cols="count($segments)" />
    </x-erp.card>

    <x-erp.worklist>
        <x-slot:toolbar>
            <form method="GET" class="fin-filterform fin-noprint">
                <select name="party" class="erp-select" onchange="this.form.submit()" aria-label="{{ $cfg['party_label'] }}">
                    <option value="">{{ $cfg['party_label'] }}: todos</option>
                    @foreach ($parties as $id => $name)
                        <option value="{{ $id }}" @selected($partyId === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @if ($partyId)
                    <a href="{{ route($r . '.aging') }}" class="erp-pill erp-pill--ghost">Limpiar</a>
                @endif
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table fin-aging">
                <thead>
                    <tr>
                        <th>{{ $cfg['party_label'] }}</th>
                        @foreach ($buckets as $key => $label)
                            <th class="is-num">{{ $segLabel($key, $label) }}</th>
                        @endforeach
                        <th class="is-num">Total</th>
                        <th>Estado</th>
                        <th class="fin-noprint"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($report['rows'] as $row)
                    @php
                        $worst = $row['d90p'] > 0 ? ['bad', '+90 días'] : ($row['d90'] > 0 ? ['bad', 'Vencido 61–90'] : ($row['d60'] > 0 ? ['warn', 'Vencido 31–60'] : ($row['d30'] > 0 ? ['warn', 'Vencido 1–30'] : ['ok', 'Al corriente'])));
                    @endphp
                    <tr>
                        <td>
                            <div class="erp-cell-main">{{ $row['party'] }}</div>
                            <div class="erp-cell-sub">{{ $row['count'] }} {{ $row['count'] === 1 ? 'documento' : 'documentos' }}</div>
                        </td>
                        @foreach ($buckets as $key => $label)
                            <td class="is-num b-{{ $key }} {{ $row[$key] <= 0 ? 'is-zero' : '' }}">{{ $row[$key] > 0 ? '$' . number_format($row[$key], 2) : '—' }}</td>
                        @endforeach
                        <td class="is-num"><strong>${{ number_format($row['total'], 2) }}</strong></td>
                        <td><x-erp.chip :tone="$worst[0]" :label="$worst[1]" /></td>
                        <td class="fin-noprint"><a class="erp-btn erp-btn--sm" href="{{ route($r . '.statement', $row['party_id']) }}">Estado de cuenta</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($buckets) + 4 }}" style="padding: 0;"><x-erp.empty title="No hay saldos abiertos" text="Cuando existan documentos con saldo aparecerán aquí por antigüedad." /></td></tr>
                @endforelse
                </tbody>
                @if (count($report['rows']))
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            @foreach ($buckets as $key => $label)
                                <td class="is-num">${{ number_format($totals[$key], 2) }}</td>
                            @endforeach
                            <td class="is-num">${{ number_format($totals['total'], 2) }}</td>
                            <td></td>
                            <td class="fin-noprint"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <x-slot:footer>
            <span>Las columnas indican días transcurridos desde el vencimiento; «Vigente» son saldos que aún no vencen.</span>
        </x-slot:footer>
    </x-erp.worklist>

</div>
@endsection
