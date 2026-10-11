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
    $cancelled = $payment->status === 'cancelado';
    $stepIdx = match ($payment->status) { 'conciliado' => 2, 'aplicado' => 1, default => 0 };
    $creator = $payment->createdBy ? trim($payment->createdBy->first_name . ' ' . $payment->createdBy->last_name) : null;
    $showApply = $editable && $payment->unappliedAmount() > 0;
    $tabs = ['resumen' => 'Resumen', 'aplicacion' => 'Aplicación (' . $applications->count() . ')', 'historial' => 'Historial'];
@endphp
<div class="erp-page fin-page">

    <x-erp.object-header
        :title="$payment->number"
        :eyebrow="($isIn ? 'Cobro recibido' : 'Pago emitido') . ' · FIN4'"
        :subtitle="$payment->directionLabel() . ': ' . $payment->partyName()"
        :status="\App\Models\Payment::statusLabel($payment->status)"
        :tone="\App\Models\Payment::statusTone($payment->status)"
        :back="route('admin.payments.index')"
        process="Aplicación y conciliación"
        :steps="$cancelled ? [] : ['Registrado', 'Aplicado', 'Conciliado']"
        :current="$stepIdx"
        :facts="[
            ['label' => 'Monto', 'value' => '$' . number_format($payment->amount, 2) . ' ' . $payment->currency],
            ['label' => 'Aplicado', 'value' => '$' . number_format($payment->applied_amount, 2)],
            ['label' => 'Sin aplicar', 'value' => $cancelled ? '—' : '$' . number_format($payment->unappliedAmount(), 2)],
            ['label' => 'Fecha', 'value' => $payment->payment_date->format('d/m/Y')],
        ]">
        @if ($payment->proof_path)
            <a href="{{ route('admin.payments.proof', $payment->id) }}" class="erp-btn" target="_blank" rel="noopener">Ver comprobante</a>
        @endif
        @if ($canEdit && $payment->status === 'aplicado')
            <form method="POST" action="{{ route('admin.payments.reconcile', $payment->id) }}" class="fin-inline-form">
                @csrf @method('PATCH')
                <button type="submit" class="erp-btn erp-btn--primary">Marcar conciliado</button>
            </form>
        @endif
        @if ($canEdit && $payment->status === 'conciliado')
            <form method="POST" action="{{ route('admin.payments.unreconcile', $payment->id) }}" class="fin-inline-form">
                @csrf @method('PATCH')
                <button type="submit" class="erp-btn">Quitar conciliación</button>
            </form>
        @endif
        @if ($canEdit && in_array($payment->status, ['pendiente', 'aplicado'], true))
            <form method="POST" action="{{ route('admin.payments.cancel', $payment->id) }}" class="fin-inline-form" onsubmit="return confirm('Se cancelará el pago y se revertirán sus aplicaciones. ¿Continuar?');">
                @csrf @method('PATCH')
                <button type="submit" class="erp-btn erp-btn--danger">Cancelar pago</button>
            </form>
        @endif
        @if (in_array($payment->status, ['pendiente', 'cancelado'], true) && $applications->isEmpty() && $user->hasPermission('payments', 'delete'))
            <form method="POST" action="{{ route('admin.payments.destroy', $payment->id) }}" class="fin-inline-form" onsubmit="return confirm('¿Eliminar este pago?');">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @php $flowHtml = trim(\Illuminate\Support\Facades\Blade::render('<x-erp.document-flow :document="$d" />', ['d' => $payment])); @endphp
    <div class="erp-split" @if ($flowHtml === '') style="display:block;" @endif>
        <section class="erp-card" data-erp-tabs>
            <x-erp.tab-nav :tabs="$tabs" :active="$showApply ? 'aplicacion' : 'resumen'" />

            <div class="erp-tabpanel {{ $showApply ? '' : 'is-active' }}" data-panel="resumen">
                <x-erp.facts :items="[
                    ['label' => $isIn ? 'Cliente' : 'Proveedor', 'value' => $payment->partyName()],
                    ['label' => 'Fecha de pago', 'value' => $payment->payment_date->format('d/m/Y')],
                    ['label' => 'Método', 'value' => $payment->paymentMethod?->name ?? '—'],
                    ['label' => 'Referencia', 'value' => $payment->reference ?: '—'],
                    ['label' => 'Moneda', 'value' => $payment->currency . ($payment->currency !== 'MXN' ? ' · TC ' . number_format($payment->exchange_rate, 4) : '')],
                    ['label' => 'Conciliado', 'value' => $payment->reconciled_at ? $payment->reconciled_at->format('d/m/Y H:i') : '—'],
                    ['label' => 'Registrado por', 'value' => $creator ?: '—'],
                ]" />
                @if ($payment->proof_path)
                    <p class="fin-note"><a class="fin-link" href="{{ route('admin.payments.proof', $payment->id) }}" target="_blank" rel="noopener">Ver comprobante</a></p>
                @endif
                @if ($payment->notes)
                    <p class="fin-note">{{ $payment->notes }}</p>
                @endif
            </div>

            <div class="erp-tabpanel {{ $showApply ? 'is-active' : '' }}" data-panel="aplicacion">
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Documento</th><th>Vence</th><th class="is-num">Aplicado (moneda doc.)</th><th class="is-num">Consumido del pago</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($applications as $a)
                            @php $d = $a->doc; @endphp
                            <tr>
                                <td>
                                    @if ($d)<span class="erp-cell-folio"><a href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a></span>@else — @endif
                                    @if ($d)<div class="erp-cell-sub">{{ $d->partyName() }}</div>@endif
                                </td>
                                <td>{{ $d?->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="is-num">${{ number_format($a->amount_applied, 2) }}<span class="fin-cur">{{ $d?->currency }}</span></td>
                                <td class="is-num">${{ number_format($a->amount_payment, 2) }}<span class="fin-cur">{{ $payment->currency }}</span></td>
                                <td class="is-num">
                                    @if ($editable)
                                        <form method="POST" action="{{ route('admin.payments.unapply', [$payment->id, $a->id]) }}" class="fin-inline-form" onsubmit="return confirm('¿Revertir esta aplicación?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="erp-btn erp-btn--sm erp-btn--danger">Desaplicar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 0;"><x-erp.empty title="Este pago aún no se ha aplicado" text="Aplícalo a uno o varios documentos abiertos para reducir sus saldos." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($showApply)
                    <h3 class="erp-card-title" style="margin: 22px 0 8px;">Aplicar saldo disponible ({{ number_format($payment->unappliedAmount(), 2) }} {{ $payment->currency }})</h3>
                    @if ($openDocs->isEmpty())
                        <p class="fin-muted">La contraparte no tiene documentos abiertos con saldo.</p>
                    @else
                        <form method="POST" action="{{ route('admin.payments.apply', $payment->id) }}" id="finApplyForm"
                              data-free="{{ $payment->unappliedAmount() }}" data-rate="{{ (float) $payment->exchange_rate }}" data-currency="{{ $payment->currency }}">
                            @csrf
                            <label class="fin-check-line">
                                <input type="checkbox" name="fifo" value="1" id="finFifo">
                                Distribuir automáticamente (FIFO)
                            </label>
                            <div class="erp-table-wrap">
                                <table class="erp-table">
                                    <thead><tr><th>Documento</th><th>Vence</th><th>Moneda</th><th class="is-num">Saldo</th><th class="is-num">Aplicar</th></tr></thead>
                                    <tbody>
                                    @foreach ($openDocs as $d)
                                        <tr>
                                            <td class="erp-cell-folio"><a href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a></td>
                                            <td><x-erp.due :date="$d->due_date" /></td>
                                            <td>{{ $d->currency }}</td>
                                            <td class="is-num">${{ number_format($d->balance, 2) }}</td>
                                            <td class="is-num"><input type="number" step="0.01" min="0" max="{{ $d->balance }}" name="alloc[{{ $d->id }}]" class="erp-input fin-apply-input" aria-label="Monto a aplicar a {{ $d->number }}" data-rate="{{ (float) $d->exchange_rate }}" data-balance="{{ $d->balance }}"></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="fin-apply-summary">
                                <div>Disponible: <strong id="finSumFree">${{ number_format($payment->unappliedAmount(), 2) }}</strong></div>
                                <div>A aplicar: <strong id="finSumApplied">$0.00</strong></div>
                            </div>
                            <div class="erp-form-actions"><button type="submit" class="erp-btn erp-btn--primary">Aplicar</button></div>
                        </form>
                    @endif
                @endif
            </div>

            <div class="erp-tabpanel" data-panel="historial">
                <ul class="fin-timeline">
                    <li><b>Pago registrado</b><span>{{ $payment->created_at?->format('d/m/Y H:i') }}@if ($creator) · {{ $creator }}@endif</span></li>
                    @foreach ($applications->sortBy('created_at') as $a)
                        <li>
                            <b>Aplicado ${{ number_format($a->amount_applied, 2) }} {{ $a->doc?->currency }} a {{ $a->doc?->number ?? 'documento' }}</b>
                            <span>{{ $a->created_at?->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                    @if ($payment->reconciled_at)
                        <li><b>Conciliado</b><span>{{ $payment->reconciled_at->format('d/m/Y H:i') }}</span></li>
                    @endif
                    @if ($cancelled)
                        <li class="is-bad"><b>Pago cancelado</b><span>{{ $payment->updated_at?->format('d/m/Y H:i') }}</span></li>
                    @endif
                </ul>
            </div>
        </section>

        @if ($flowHtml !== '')
            <aside>{!! $flowHtml !!}</aside>
        @endif
    </div>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
