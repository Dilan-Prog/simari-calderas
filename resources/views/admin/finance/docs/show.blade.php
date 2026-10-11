@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    {{ $doc->number }} - {{ $cfg['title'] }} - Admin
@endsection
@section('content')
@php
    $r = $cfg['route'];
    $pc = $cfg['party_col'];
    $isRec = $cfg['kind'] === 'receivable';
    $user = auth()->user();
    $tone = $doc::statusTone($doc->status);
    $symbol = $doc->currency;
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <x-erp.object-header
        :title="$doc->number"
        :subtitle="$cfg['party_label'] . ': ' . $doc->partyName()"
        :status="$doc::statusLabel($doc->status)"
        :tone="$tone"
        :back="route($r . '.index')"
        :facts="[
            ['label' => 'Total', 'value' => '$' . number_format($doc->total, 2) . ' ' . $doc->currency],
            ['label' => 'Pagado', 'value' => '$' . number_format($doc->paid_amount, 2)],
            ['label' => 'Saldo', 'value' => '$' . number_format($doc->balance, 2)],
            ['label' => 'Vencimiento', 'value' => $doc->due_date->format('d/m/Y') . ($doc->isOpen() ? ($doc->daysToDue() < 0 ? ' (vencida hace ' . abs($doc->daysToDue()) . ' d)' : '') : '')],
        ]">
        <a href="{{ route($r . '.statement', $doc->{$pc}) }}" class="fin-btn">Estado de cuenta</a>
        @if ($doc->isOpen() && $user->hasPermission('payments', 'create'))
            <a href="{{ route('admin.payments.create', ['direction' => $isRec ? 'in' : 'out', $pc => $doc->{$pc}, 'doc' => $doc->id]) }}" class="fin-btn fin-btn-primary">{{ $isRec ? 'Registrar cobro' : 'Registrar pago' }}</a>
        @endif
        @if ($doc->status !== 'cancelada' && $user->hasPermission($cfg['perm'], 'edit'))
            <a href="{{ route($r . '.edit', $doc->id) }}" class="fin-btn">Editar</a>
            @if ($applications->isEmpty())
                <form method="POST" action="{{ route($r . '.cancel', $doc->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Cancelar este documento?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="fin-btn fin-btn-danger">Cancelar</button>
                </form>
            @endif
        @endif
        @if ($applications->isEmpty() && $user->hasPermission($cfg['perm'], 'delete'))
            <form method="POST" action="{{ route($r . '.destroy', $doc->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Eliminar definitivamente este documento?');">
                @csrf @method('DELETE')
                <button type="submit" class="fin-btn fin-btn-danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.document-flow :document="$doc" />

    <div class="fin-grid">
        <div>
            <div class="fin-card">
                <h2>Pagos aplicados</h2>
                <div class="fin-scroll">
                    <table class="fin-table">
                        <thead><tr><th>PAGO</th><th>FECHA</th><th>MÉTODO / REFERENCIA</th><th class="num">APLICADO ({{ $symbol }})</th><th>ESTATUS</th></tr></thead>
                        <tbody>
                        @forelse ($applications as $a)
                            @php $p = $a->payment; @endphp
                            <tr>
                                <td><a class="fin-link" href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                                <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                                <td>{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference) <div class="fin-muted">{{ $p->reference }}</div>@endif</td>
                                <td class="num">${{ number_format($a->amount_applied, 2) }}
                                    @if ($p->currency !== $doc->currency)
                                        <div class="fin-muted">de ${{ number_format($a->amount_payment, 2) }} {{ $p->currency }}</div>
                                    @endif
                                </td>
                                <td><span class="erp-chip erp-tone-{{ \App\Models\Payment::statusTone($p->status) }}">{{ \App\Models\Payment::statusLabel($p->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="fin-empty">Aún no hay pagos aplicados.</td></tr>
                        @endforelse
                        </tbody>
                        @if ($applications->isNotEmpty())
                            <tfoot><tr><td colspan="3">Total pagado</td><td class="num">${{ number_format($doc->paid_amount, 2) }}</td><td></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <aside>
            <div class="fin-card">
                <h2>Detalle</h2>
                <dl class="fin-dl">
                    <dt>{{ $cfg['party_label'] }}</dt><dd>{{ $doc->partyName() }}</dd>
                    <dt>Emisión</dt><dd>{{ $doc->issue_date->format('d/m/Y') }}</dd>
                    <dt>Días de crédito</dt><dd>{{ $doc->credit_days }}</dd>
                    <dt>Moneda</dt><dd>{{ $doc->currency }}@if ($doc->currency !== 'MXN') · TC {{ number_format($doc->exchange_rate, 4) }}@endif</dd>
                    <dt>Subtotal</dt><dd>${{ number_format($doc->subtotal, 2) }}</dd>
                    <dt>Impuestos</dt><dd>${{ number_format($doc->tax_total, 2) }}</dd>
                    <dt>Total</dt><dd>${{ number_format($doc->total, 2) }}</dd>
                    @if ($doc->currency !== 'MXN')
                        <dt>Saldo en MXN</dt><dd>${{ number_format($doc->balanceMxn(), 2) }}</dd>
                    @endif
                    @if ($isRec)
                        <dt>Origen</dt>
                        <dd>
                            @if ($source && $doc->source_type === 'sales_order')
                                <a class="fin-link" href="{{ route('admin.sales-orders.show', $source->id) }}">{{ $source->order_number }}</a>
                            @elseif ($source && $doc->source_type === 'quote')
                                <a class="fin-link" href="{{ route('admin.quotes.show', $source->id) }}">{{ $source->quote_number }}</a>
                            @elseif ($doc->source_type)
                                {{ \App\Models\Receivable::sourceLabel($doc->source_type) }} #{{ $doc->source_id }}
                            @else
                                Manual
                            @endif
                        </dd>
                    @else
                        <dt>Orden de compra</dt>
                        <dd>
                            @if ($doc->purchaseOrder)
                                <a class="fin-link" href="{{ route('admin.purchase-orders.show', $doc->purchaseOrder->id) }}">{{ $doc->purchaseOrder->po_number }}</a>
                            @else — @endif
                        </dd>
                        <dt>Factura prov.</dt><dd>{{ $doc->supplier_invoice ?: '—' }}</dd>
                    @endif
                    <dt>Creado por</dt><dd>{{ $doc->createdBy ? trim($doc->createdBy->first_name . ' ' . $doc->createdBy->last_name) : '—' }}</dd>
                </dl>
                @if ($doc->notes)
                    <p style="margin:14px 0 0;font-size:13px;color:#4b5563;white-space:pre-line;">{{ $doc->notes }}</p>
                @endif
            </div>
        </aside>
    </div>

</section>
</div>
@endsection
