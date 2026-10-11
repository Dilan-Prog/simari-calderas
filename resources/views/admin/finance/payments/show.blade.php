@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    {{ $payment->number }} - Pagos - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $isIn = $payment->isIncoming();
    $docRoute = $isIn ? 'admin.receivables.show' : 'admin.payables.show';
    $canEdit = $user->hasPermission('payments', 'edit');
    $editable = $canEdit && !in_array($payment->status, ['cancelado', 'conciliado'], true);
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <x-erp.object-header
        :title="$payment->number"
        :subtitle="$payment->directionLabel() . ': ' . $payment->partyName()"
        :status="\App\Models\Payment::statusLabel($payment->status)"
        :tone="\App\Models\Payment::statusTone($payment->status)"
        :back="route('admin.payments.index')"
        :facts="[
            ['label' => 'Monto', 'value' => '$' . number_format($payment->amount, 2) . ' ' . $payment->currency],
            ['label' => 'Aplicado', 'value' => '$' . number_format($payment->applied_amount, 2)],
            ['label' => 'Sin aplicar', 'value' => $payment->status === 'cancelado' ? '—' : '$' . number_format($payment->unappliedAmount(), 2)],
            ['label' => 'Fecha', 'value' => $payment->payment_date->format('d/m/Y')],
        ]">
        @if ($canEdit && $payment->status === 'aplicado')
            <form method="POST" action="{{ route('admin.payments.reconcile', $payment->id) }}" class="fin-inline-form">
                @csrf @method('PATCH')
                <button type="submit" class="fin-btn fin-btn-primary">Marcar conciliado</button>
            </form>
        @endif
        @if ($canEdit && $payment->status === 'conciliado')
            <form method="POST" action="{{ route('admin.payments.unreconcile', $payment->id) }}" class="fin-inline-form">
                @csrf @method('PATCH')
                <button type="submit" class="fin-btn">Quitar conciliación</button>
            </form>
        @endif
        @if ($canEdit && in_array($payment->status, ['pendiente', 'aplicado'], true))
            <form method="POST" action="{{ route('admin.payments.cancel', $payment->id) }}" class="fin-inline-form" onsubmit="return confirm('Se cancelará el pago y se revertirán sus aplicaciones. ¿Continuar?');">
                @csrf @method('PATCH')
                <button type="submit" class="fin-btn fin-btn-danger">Cancelar pago</button>
            </form>
        @endif
        @if (in_array($payment->status, ['pendiente', 'cancelado'], true) && $applications->isEmpty() && $user->hasPermission('payments', 'delete'))
            <form method="POST" action="{{ route('admin.payments.destroy', $payment->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Eliminar este pago?');">
                @csrf @method('DELETE')
                <button type="submit" class="fin-btn fin-btn-danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.document-flow :document="$payment" />

    <div class="fin-grid">
        <div>
            <div class="fin-card">
                <h2>Aplicaciones</h2>
                <div class="fin-scroll">
                    <table class="fin-table">
                        <thead><tr><th>DOCUMENTO</th><th>VENCE</th><th class="num">APLICADO (MONEDA DOC.)</th><th class="num">CONSUMIDO DEL PAGO</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($applications as $a)
                            @php $d = $a->doc; @endphp
                            <tr>
                                <td>
                                    @if ($d)<a class="fin-link" href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a>@else — @endif
                                    @if ($d)<div class="fin-muted">{{ $d->partyName() }}</div>@endif
                                </td>
                                <td>{{ $d?->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="num">${{ number_format($a->amount_applied, 2) }} <span class="fin-muted">{{ $d?->currency }}</span></td>
                                <td class="num">${{ number_format($a->amount_payment, 2) }} <span class="fin-muted">{{ $payment->currency }}</span></td>
                                <td class="num">
                                    @if ($editable)
                                        <form method="POST" action="{{ route('admin.payments.unapply', [$payment->id, $a->id]) }}" class="fin-inline-form" onsubmit="return confirm('¿Revertir esta aplicación?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="fin-btn fin-btn-sm fin-btn-danger">Desaplicar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="fin-empty">Este pago aún no se ha aplicado a ningún documento.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($editable && $payment->unappliedAmount() > 0)
                <div class="fin-card">
                    <h2>Aplicar saldo disponible ({{ number_format($payment->unappliedAmount(), 2) }} {{ $payment->currency }})</h2>
                    @if ($openDocs->isEmpty())
                        <p class="fin-muted">La contraparte no tiene documentos abiertos con saldo.</p>
                    @else
                        <form method="POST" action="{{ route('admin.payments.apply', $payment->id) }}" id="finApplyForm"
                              data-free="{{ $payment->unappliedAmount() }}" data-rate="{{ (float) $payment->exchange_rate }}" data-currency="{{ $payment->currency }}">
                            @csrf
                            <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:10px;">
                                <input type="checkbox" name="fifo" value="1" id="finFifo">
                                Distribuir automáticamente (FIFO)
                            </label>
                            <div class="fin-scroll">
                                <table class="fin-table">
                                    <thead><tr><th>DOCUMENTO</th><th>VENCE</th><th>MONEDA</th><th class="num">SALDO</th><th class="num">APLICAR</th></tr></thead>
                                    <tbody>
                                    @foreach ($openDocs as $d)
                                        <tr>
                                            <td><a class="fin-link" href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a></td>
                                            <td><span class="fin-due fin-due-{{ $d->dueTone() }}"><i></i>{{ $d->due_date->format('d/m/Y') }}</span></td>
                                            <td>{{ $d->currency }}</td>
                                            <td class="num">${{ number_format($d->balance, 2) }}</td>
                                            <td class="num"><input type="number" step="0.01" min="0" max="{{ $d->balance }}" name="alloc[{{ $d->id }}]" class="users-manager-input fin-apply-input" data-rate="{{ (float) $d->exchange_rate }}" data-balance="{{ $d->balance }}"></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="fin-apply-summary">
                                <div>Disponible: <strong id="finSumFree">${{ number_format($payment->unappliedAmount(), 2) }}</strong></div>
                                <div>A aplicar: <strong id="finSumApplied">$0.00</strong></div>
                            </div>
                            <div class="fin-form-actions"><button type="submit" class="fin-btn fin-btn-primary">Aplicar</button></div>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <aside>
            <div class="fin-card">
                <h2>Detalle</h2>
                <dl class="fin-dl">
                    <dt>{{ $isIn ? 'Cliente' : 'Proveedor' }}</dt><dd>{{ $payment->partyName() }}</dd>
                    <dt>Fecha de pago</dt><dd>{{ $payment->payment_date->format('d/m/Y') }}</dd>
                    <dt>Método</dt><dd>{{ $payment->paymentMethod?->name ?? '—' }}</dd>
                    <dt>Referencia</dt><dd>{{ $payment->reference ?: '—' }}</dd>
                    <dt>Moneda</dt><dd>{{ $payment->currency }}@if ($payment->currency !== 'MXN') · TC {{ number_format($payment->exchange_rate, 4) }}@endif</dd>
                    <dt>Conciliado</dt><dd>{{ $payment->reconciled_at ? $payment->reconciled_at->format('d/m/Y H:i') : '—' }}</dd>
                    <dt>Comprobante</dt>
                    <dd>@if ($payment->proof_path)<a class="fin-link" href="{{ route('admin.payments.proof', $payment->id) }}" target="_blank" rel="noopener">Ver comprobante</a>@else — @endif</dd>
                    <dt>Registrado por</dt><dd>{{ $payment->createdBy ? trim($payment->createdBy->first_name . ' ' . $payment->createdBy->last_name) : '—' }}</dd>
                </dl>
                @if ($payment->notes)
                    <p style="margin:14px 0 0;font-size:13px;color:#4b5563;white-space:pre-line;">{{ $payment->notes }}</p>
                @endif
            </div>
        </aside>
    </div>

</section>
</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
