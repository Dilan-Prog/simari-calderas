@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Estado de cuenta {{ $partyName }} - Admin
@endsection
@section('content')
@php $r = $cfg['route']; @endphp
<div class="erp-page fin-page fin-statement">

    <x-erp.page-header
        title="Estado de cuenta"
        :desc="$partyName . ' · emitido el ' . now()->format('d/m/Y') . (($from || $to) ? ' · periodo ' . ($from ?: 'inicio') . ' a ' . ($to ?: 'hoy') : '')"
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], [$cfg['title'], route($r . '.index')], ['Estado de cuenta', null]]">
        <a href="{{ route($r . '.index', [$cfg['party_col'] => $partyId]) }}" class="erp-btn fin-noprint">Ver documentos</a>
        <button type="button" class="erp-btn erp-btn--primary fin-noprint" onclick="window.print()">Imprimir</button>
    </x-erp.page-header>

    <x-erp.card class="fin-noprint">
        <form method="GET" class="fin-filterform">
            <label>Desde <input type="date" name="from" class="erp-input" value="{{ $from }}"></label>
            <label>Hasta <input type="date" name="to" class="erp-input" value="{{ $to }}"></label>
            <button type="submit" class="erp-btn">Aplicar</button>
            @if ($from || $to)
                <a href="{{ route($r . '.statement', $partyId) }}" class="erp-pill erp-pill--ghost">Limpiar</a>
            @endif
        </form>
    </x-erp.card>

    @forelse ($groups as $currency => $g)
        <x-erp.card :title="'Moneda ' . $currency" :sub="'Saldo final $' . number_format($g['closing'], 2)">
            <div class="erp-table-wrap">
                <table class="erp-table fin-stmt-table">
                    <thead>
                        <tr><th>Fecha</th><th>Documento</th><th>Concepto</th><th class="is-num">Cargos</th><th class="is-num">Abonos</th><th class="is-num">Saldo</th></tr>
                    </thead>
                    <tbody>
                        @if ($from)
                            <tr class="fin-stmt-opening"><td colspan="5"><em>Saldo inicial</em></td><td class="is-num" data-label="Saldo inicial">${{ number_format($g['opening'], 2) }}</td></tr>
                        @endif
                        @forelse ($g['lines'] as $l)
                            <tr class="fin-stmt-row">
                                <td class="fin-s-date">{{ $l['date']->format('d/m/Y') }}</td>
                                <td class="erp-cell-folio fin-s-doc"><a href="{{ route($l['route'], $l['id']) }}">{{ $l['number'] }}</a></td>
                                <td class="fin-s-concept">{{ $l['concept'] }}</td>
                                <td class="is-num fin-s-charge {{ $l['charge'] > 0 ? '' : 'is-blank' }}" data-label="Cargo">{{ $l['charge'] > 0 ? '$' . number_format($l['charge'], 2) : '' }}</td>
                                <td class="is-num fin-s-credit {{ $l['credit'] > 0 ? '' : 'is-blank' }}" data-label="Abono">{{ $l['credit'] > 0 ? '$' . number_format($l['credit'], 2) : '' }}</td>
                                <td class="is-num fin-s-bal" data-label="Saldo"><strong>${{ number_format($l['balance'], 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr class="fin-row-empty"><td colspan="6" style="padding: 0;"><x-erp.empty title="Sin movimientos en el periodo" /></td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fin-stmt-total">
                            <td colspan="3" class="fin-s-tlabel">Totales del periodo · saldo final</td>
                            <td class="is-num" data-label="Cargos">${{ number_format($g['charges'], 2) }}</td>
                            <td class="is-num" data-label="Abonos">${{ number_format($g['credits'], 2) }}</td>
                            <td class="is-num fin-s-closing" data-label="Saldo final">${{ number_format($g['closing'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp.card>
    @empty
        <x-erp.empty :title="'Sin movimientos para ' . $partyName" text="Cuando existan documentos o pagos de esta contraparte aparecerán aquí." />
    @endforelse

</div>
@endsection
