@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Estado de cuenta {{ $partyName }} - Admin
@endsection
@section('content')
@php $r = $cfg['route']; @endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager fin-noprint" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <a href="{{ route($r . '.index') }}">{{ $cfg['title'] }}</a> &gt; <strong>Estado de cuenta</strong></p>
            <h1 class="erp-title">Estado de cuenta</h1>
            <p class="erp-subtitle"><strong>{{ $partyName }}</strong> · emitido el {{ now()->format('d/m/Y') }}@if ($from || $to) · periodo {{ $from ?: 'inicio' }} a {{ $to ?: 'hoy' }}@endif</p>
        </div>
        <div class="fin-head-actions fin-noprint">
            <a href="{{ route($r . '.index', [$cfg['party_col'] => $partyId]) }}" class="fin-btn">Ver documentos</a>
            <button type="button" class="fin-btn fin-btn-primary" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    <form method="GET" class="fin-filters fin-noprint">
        <label>Desde <input type="date" name="from" class="users-manager-input" value="{{ $from }}"></label>
        <label>Hasta <input type="date" name="to" class="users-manager-input" value="{{ $to }}"></label>
        <button type="submit" class="fin-btn">Aplicar</button>
        @if ($from || $to)
            <a href="{{ route($r . '.statement', $partyId) }}" class="fin-chip-clear">Limpiar</a>
        @endif
    </form>

    @forelse ($groups as $currency => $g)
        <div class="fin-card">
            <h2>Moneda {{ $currency }}</h2>
            <div class="fin-scroll">
                <table class="fin-table">
                    <thead>
                        <tr><th>FECHA</th><th>DOCUMENTO</th><th>CONCEPTO</th><th class="num">CARGOS</th><th class="num">ABONOS</th><th class="num">SALDO</th></tr>
                    </thead>
                    <tbody>
                        @if ($from)
                            <tr><td colspan="5"><em>Saldo inicial</em></td><td class="num">${{ number_format($g['opening'], 2) }}</td></tr>
                        @endif
                        @forelse ($g['lines'] as $l)
                            <tr>
                                <td>{{ $l['date']->format('d/m/Y') }}</td>
                                <td><a class="fin-link" href="{{ route($l['route'], $l['id']) }}">{{ $l['number'] }}</a></td>
                                <td>{{ $l['concept'] }}</td>
                                <td class="num">{{ $l['charge'] > 0 ? '$' . number_format($l['charge'], 2) : '' }}</td>
                                <td class="num">{{ $l['credit'] > 0 ? '$' . number_format($l['credit'], 2) : '' }}</td>
                                <td class="num">${{ number_format($l['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="fin-empty">Sin movimientos en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Totales del periodo · saldo final</td>
                            <td class="num">${{ number_format($g['charges'], 2) }}</td>
                            <td class="num">${{ number_format($g['credits'], 2) }}</td>
                            <td class="num">${{ number_format($g['closing'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @empty
        <div class="fin-card fin-empty">Sin movimientos para {{ $partyName }}.</div>
    @endforelse

</section>
</div>
@endsection
