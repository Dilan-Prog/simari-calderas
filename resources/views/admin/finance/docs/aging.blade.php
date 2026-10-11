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
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <a href="{{ route($r . '.index') }}">{{ $cfg['title'] }}</a> &gt; <strong>Antigüedad de saldos</strong></p>
            <h1 class="erp-title">Antigüedad de saldos</h1>
            <p class="erp-subtitle">Saldo abierto por {{ strtolower($cfg['party_label']) }} según días de vencimiento · MXN equivalente</p>
        </div>
        <div class="fin-head-actions">
            <button type="button" class="fin-btn fin-noprint" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    @include('admin.finance.docs._tabs', ['active' => 'aging'])

    <form method="GET" class="fin-filters fin-noprint">
        <select name="party" class="users-manager-input" onchange="this.form.submit()">
            <option value="">{{ $cfg['party_label'] }}: todos</option>
            @foreach ($parties as $id => $name)
                <option value="{{ $id }}" @selected($partyId === $id)>{{ $name }}</option>
            @endforeach
        </select>
        @if ($partyId)
            <a href="{{ route($r . '.aging') }}" class="fin-chip-clear">Limpiar</a>
        @endif
    </form>

    <div class="fin-kpis">
        @foreach ($buckets as $key => $label)
            <div class="fin-kpi {{ in_array($key, ['d90', 'd90p'], true) && $totals[$key] > 0 ? 'is-bad' : '' }}">
                <span>{{ $key === 'current' ? 'Vigente' : $label . ' días vencido' }}</span>
                <strong>${{ number_format($totals[$key], 2) }}</strong>
            </div>
        @endforeach
        <div class="fin-kpi"><span>Total</span><strong>${{ number_format($totals['total'], 2) }}</strong></div>
    </div>

    <main class="table-container-clients-manager">
        <div class="fin-scroll">
            <table class="fin-table fin-aging">
                <thead>
                    <tr>
                        <th>{{ strtoupper($cfg['party_label']) }}</th>
                        @foreach ($buckets as $key => $label)
                            <th class="num">{{ strtoupper($label) }}</th>
                        @endforeach
                        <th class="num">TOTAL</th>
                        <th class="fin-noprint"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['party'] }} <span class="fin-muted">{{ $row['count'] }} doc.</span></td>
                        @foreach ($buckets as $key => $label)
                            <td class="num b-{{ $key }} {{ $row[$key] <= 0 ? 'zero' : '' }}">{{ $row[$key] > 0 ? '$' . number_format($row[$key], 2) : '—' }}</td>
                        @endforeach
                        <td class="num"><strong>${{ number_format($row['total'], 2) }}</strong></td>
                        <td class="fin-noprint"><a class="fin-btn fin-btn-sm" href="{{ route($r . '.statement', $row['party_id']) }}">Estado de cuenta</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($buckets) + 3 }}" class="fin-empty">No hay saldos abiertos.</td></tr>
                @endforelse
                </tbody>
                @if (count($report['rows']))
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            @foreach ($buckets as $key => $label)
                                <td class="num">${{ number_format($totals[$key], 2) }}</td>
                            @endforeach
                            <td class="num">${{ number_format($totals['total'], 2) }}</td>
                            <td class="fin-noprint"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </main>
    <p class="fin-muted" style="margin-top:10px;">Las columnas indican días transcurridos desde el vencimiento; "Vigente" son saldos que aún no vencen.</p>

</section>
</div>
@endsection
