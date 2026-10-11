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
    $cancelled = $doc->status === 'cancelada';
    $stepIdx = $doc->status === 'pagada' ? 2 : ((float) $doc->paid_amount > 0 ? 1 : 0);
    $steps = $isRec ? ['Por cobrar', 'Cobro parcial', 'Cobrada'] : ['Por pagar', 'Pago parcial', 'Pagada'];
    $dueText = $doc->due_date->format('d/m/Y') . ($doc->isOpen() && $doc->daysToDue() < 0 ? ' (vencida hace ' . abs($doc->daysToDue()) . ' d)' : '');
    $creator = $doc->createdBy ? trim($doc->createdBy->first_name . ' ' . $doc->createdBy->last_name) : null;
@endphp
<div class="erp-page fin-page">

    <x-erp.object-header
        :title="$doc->number"
        :eyebrow="($isRec ? 'Cuenta por cobrar' : 'Cuenta por pagar') . ' · ' . ($isRec ? 'FIN2' : 'FIN3')"
        :subtitle="$cfg['party_label'] . ': ' . $doc->partyName()"
        :status="$doc::statusLabel($doc->status)"
        :tone="$tone"
        :back="route($r . '.index')"
        :process="$isRec ? 'Order-to-Cash' : 'Procure-to-Pay'"
        :steps="$cancelled ? [] : $steps"
        :current="$stepIdx"
        :facts="[
            ['label' => 'Total', 'value' => '$' . number_format($doc->total, 2) . ' ' . $doc->currency],
            ['label' => 'Pagado', 'value' => '$' . number_format($doc->paid_amount, 2)],
            ['label' => 'Saldo', 'value' => '$' . number_format($doc->balance, 2)],
            ['label' => 'Vencimiento', 'value' => $dueText],
        ]">
        <a href="{{ route($r . '.statement', $doc->{$pc}) }}" class="erp-btn">Estado de cuenta</a>
        @if ($doc->isOpen() && $user->hasPermission('payments', 'create'))
            <a href="{{ route('admin.payments.create', ['direction' => $isRec ? 'in' : 'out', $pc => $doc->{$pc}, 'doc' => $doc->id]) }}" class="erp-btn erp-btn--primary">{{ $isRec ? 'Registrar cobro' : 'Registrar pago' }}</a>
        @endif
        @if (!$cancelled && $user->hasPermission($cfg['perm'], 'edit'))
            <a href="{{ route($r . '.edit', $doc->id) }}" class="erp-btn">Editar</a>
            @if ($applications->isEmpty())
                <form method="POST" action="{{ route($r . '.cancel', $doc->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Cancelar este documento?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="erp-btn erp-btn--danger">Cancelar</button>
                </form>
            @endif
        @endif
        @if ($applications->isEmpty() && $user->hasPermission($cfg['perm'], 'delete'))
            <form method="POST" action="{{ route($r . '.destroy', $doc->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Eliminar definitivamente este documento?');">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @php $flowHtml = trim(\Illuminate\Support\Facades\Blade::render('<x-erp.document-flow :document="$d" />', ['d' => $doc])); @endphp
    <div class="erp-split" @if ($flowHtml === '') style="display:block;" @endif>
        <section class="erp-card" data-erp-tabs>
            <x-erp.tab-nav :tabs="['resumen' => 'Resumen', 'pagos' => 'Pagos aplicados (' . $applications->count() . ')', 'historial' => 'Historial']" active="resumen" />

            <div class="erp-tabpanel is-active" data-panel="resumen">
                <x-erp.facts :items="array_values(array_filter([
                    ['label' => $cfg['party_label'], 'value' => $doc->partyName()],
                    ['label' => 'Emisión', 'value' => $doc->issue_date->format('d/m/Y')],
                    ['label' => 'Días de crédito', 'value' => $doc->credit_days],
                    ['label' => 'Moneda', 'value' => $doc->currency . ($doc->currency !== 'MXN' ? ' · TC ' . number_format($doc->exchange_rate, 4) : '')],
                    ['label' => 'Subtotal', 'value' => '$' . number_format($doc->subtotal, 2)],
                    ['label' => 'Impuestos', 'value' => '$' . number_format($doc->tax_total, 2)],
                    ['label' => 'Total', 'value' => '$' . number_format($doc->total, 2)],
                    $doc->currency !== 'MXN' ? ['label' => 'Saldo en MXN', 'value' => '$' . number_format($doc->balanceMxn(), 2)] : null,
                    $isRec ? null : ['label' => 'Factura prov.', 'value' => $doc->supplier_invoice ?: '—'],
                    ['label' => 'Creado por', 'value' => $creator ?: '—'],
                ]))" />

                <dl class="erp-facts" style="margin: 16px 0 0;">
                    <div>
                        <dt class="erp-fact-label">{{ $isRec ? 'Origen' : 'Orden de compra' }}</dt>
                        <dd class="erp-fact-value" style="margin: 2px 0 0;">
                            @if ($isRec)
                                @if ($source && $doc->source_type === 'sales_order')
                                    <a class="fin-link" href="{{ route('admin.sales-orders.show', $source->id) }}">{{ $source->order_number }}</a>
                                @elseif ($source && $doc->source_type === 'quote')
                                    <a class="fin-link" href="{{ route('admin.quotes.show', $source->id) }}">{{ $source->quote_number }}</a>
                                @elseif ($doc->source_type)
                                    {{ \App\Models\Receivable::sourceLabel($doc->source_type) }} #{{ $doc->source_id }}
                                @else
                                    Manual
                                @endif
                            @else
                                @if ($doc->purchaseOrder)
                                    <a class="fin-link" href="{{ route('admin.purchase-orders.show', $doc->purchaseOrder->id) }}">{{ $doc->purchaseOrder->po_number }}</a>
                                @else — @endif
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($doc->notes)
                    <p class="fin-note">{{ $doc->notes }}</p>
                @endif
            </div>

            <div class="erp-tabpanel" data-panel="pagos">
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Pago</th><th>Fecha</th><th>Método / referencia</th><th class="is-num">Aplicado ({{ $symbol }})</th><th>Estatus</th></tr></thead>
                        <tbody>
                        @forelse ($applications as $a)
                            @php $p = $a->payment; @endphp
                            <tr>
                                <td class="erp-cell-folio"><a href="{{ route('admin.payments.show', $p->id) }}">{{ $p->number }}</a></td>
                                <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                                <td>{{ $p->paymentMethod?->name ?? '—' }}@if ($p->reference)<div class="erp-cell-sub">{{ $p->reference }}</div>@endif</td>
                                <td class="is-num">${{ number_format($a->amount_applied, 2) }}
                                    @if ($p->currency !== $doc->currency)
                                        <div class="erp-cell-sub">de ${{ number_format($a->amount_payment, 2) }} {{ $p->currency }}</div>
                                    @endif
                                </td>
                                <td><x-erp.chip :tone="\App\Models\Payment::statusTone($p->status)" :label="\App\Models\Payment::statusLabel($p->status)" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 0;"><x-erp.empty title="Aún no hay pagos aplicados" text="Registra un pago y aplícalo a este documento para reducir su saldo." /></td></tr>
                        @endforelse
                        </tbody>
                        @if ($applications->isNotEmpty())
                            <tfoot><tr><td colspan="3">Total pagado</td><td class="is-num">${{ number_format($doc->paid_amount, 2) }}</td><td></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="erp-tabpanel" data-panel="historial">
                <ul class="fin-timeline">
                    <li>
                        <b>Documento creado</b>
                        <span>{{ $doc->created_at?->format('d/m/Y H:i') }}@if ($creator) · {{ $creator }}@endif</span>
                    </li>
                    @foreach ($applications->sortBy('created_at') as $a)
                        <li>
                            <b>{{ $isRec ? 'Cobro' : 'Pago' }} {{ $a->payment->number }} aplicado por ${{ number_format($a->amount_applied, 2) }} {{ $symbol }}</b>
                            <span>{{ $a->created_at?->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                    @if ($cancelled)
                        <li class="is-bad"><b>Documento cancelado</b><span>{{ $doc->updated_at?->format('d/m/Y H:i') }}</span></li>
                    @elseif ($doc->status === 'pagada')
                        <li><b>Documento liquidado</b><span>Saldo en $0.00</span></li>
                    @endif
                </ul>
            </div>
        </section>

        @if ($flowHtml !== '')<aside>
            {!! $flowHtml !!}
        </aside>@endif
    </div>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
