@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@push('styles')
<style>
/* Pagos · detalle — teléfono (≤768px). Escritorio sin cambios: fuera del @media solo se oculta .pay-m-only
   (botón «Aplicar saldo» por documento). */
.pay-show .pay-m-only { display: none; }
@media (max-width: 768px) {
    .pay-show { padding: 12px; overflow-x: clip; }
    .pay-show * { box-sizing: border-box; }

    /* Cabecera */
    .pay-show .erp-objhead { padding: 14px; }
    .pay-show .erp-objhead-top { flex-direction: column; gap: 12px; }
    .pay-show .erp-objhead-main { min-width: 0; }
    .pay-show .erp-objhead-back { display: inline-flex; align-items: center; min-height: 36px; padding: 4px 12px; margin-bottom: 6px; }
    .pay-show .erp-eyebrow { display: block; overflow-wrap: anywhere; line-height: 1.4; }
    .pay-show .erp-objhead-title { font-size: 22px; overflow-wrap: anywhere; }
    .pay-show .erp-objhead-sub { overflow-wrap: anywhere; }
    .pay-show .erp-objhead-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; width: 100%; }
    .pay-show .erp-objhead-actions:empty { display: none; }
    .pay-show .erp-objhead-actions > .fin-inline-form { display: block; margin: 0; min-width: 0; }
    .pay-show .erp-objhead-actions .erp-btn { width: 100%; min-height: 44px; justify-content: center; font-size: 14px; text-align: center; white-space: normal; }
    .pay-show .erp-objhead-actions > .fin-inline-form:has(.erp-btn--primary) { grid-column: 1 / -1; order: -1; }
    .pay-show .erp-objhead-actions > :only-child { grid-column: 1 / -1; }
    .pay-show .erp-objhead-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 14px; }
    .pay-show .erp-objhead-facts > div { min-width: 0; }
    .pay-show .erp-objhead-facts dd { font-size: 13.5px; overflow-wrap: anywhere; }
    .pay-show .erp-objhead-process { min-width: 0; }
    .pay-show .erp-chevrons { max-width: 100%; }
    .pay-show .erp-chevron { flex: 1 1 0; min-width: 0; padding: 6px 8px 6px 16px; font-size: 10.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pay-show .erp-chevron:first-child { padding-left: 10px; }
    .pay-show .erp-split { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .pay-show .erp-split > * { min-width: 0; }

    /* Pestañas y paneles */
    .pay-show .erp-tabnav { padding: 0 6px; scrollbar-width: none; }
    .pay-show .erp-tabnav::-webkit-scrollbar { display: none; }
    .pay-show .erp-tab { min-height: 44px; padding: 10px 12px; font-size: 13.5px; }
    .pay-show .erp-tabpanel { padding-left: 14px; padding-right: 14px; }
    .pay-show .erp-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px; }
    .pay-show .erp-facts > div { min-width: 0; }
    .pay-show .erp-fact-value { font-size: 13.5px; overflow-wrap: anywhere; }
    .pay-show .fin-note { overflow-wrap: anywhere; }
    .pay-show .fin-note .fin-link { display: inline-flex; align-items: center; min-height: 44px; }

    /* Tablas → tarjetas */
    .pay-show .erp-table-wrap { overflow-x: visible; }
    .pay-show .pay-apps-table, .pay-show .pay-apps-table tbody, .pay-show .pay-alloc-table, .pay-show .pay-alloc-table tbody { display: block; width: 100%; }
    .pay-show .pay-apps-table thead, .pay-show .pay-alloc-table thead { display: none; }
    .pay-show .erp-table tbody tr.pay-row-empty, .pay-show .erp-table tbody tr.pay-row-empty > td { display: block; width: 100%; }
    .pay-show .erp-empty { text-align: center; }
    .pay-show .erp-table tbody tr.pay-app, .pay-show .erp-table tbody tr.pay-doc {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px 12px;
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid var(--erp-line); border-radius: 10px; background: #fff;
    }
    .pay-show .erp-table tbody tr.pay-app:hover, .pay-show .erp-table tbody tr.pay-doc:hover { background: #fff; }
    .pay-show .erp-table tbody tr.pay-app > td, .pay-show .erp-table tbody tr.pay-doc > td { display: block; width: auto; padding: 0; border: 0; min-width: 0; text-align: left; font-size: 13px; overflow-wrap: anywhere; }
    .pay-show .pay-app td[data-label]::before, .pay-show .pay-doc td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 3px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--erp-faint); }
    /* Aplicaciones */
    .pay-show .pay-app { grid-template-areas: "doc due" "applied used" "action action"; }
    .pay-show .pay-a-doc { grid-area: doc; font-size: 15px !important; }
    .pay-show .pay-a-doc .erp-cell-folio a { display: inline-flex; align-items: center; min-height: 32px; }
    .pay-show .pay-a-due { grid-area: due; justify-self: end; text-align: right !important; }
    .pay-show .pay-a-applied { grid-area: applied; padding: 8px 10px !important; border-radius: 8px; background: #f6f7f9; font-weight: 800; }
    .pay-show .pay-a-used { grid-area: used; padding: 8px 10px !important; border-radius: 8px; background: #f6f7f9; }
    .pay-show .pay-a-action { grid-area: action; }
    .pay-show .pay-a-action.is-empty { display: none !important; }
    .pay-show .pay-a-action .fin-inline-form { display: block; }
    .pay-show .pay-a-action .erp-btn { width: 100%; min-height: 44px; justify-content: center; font-size: 14px; }
    /* Aplicar saldo disponible */
    .pay-show .pay-doc { grid-template-areas: "doc cur" "due bal" "apply apply"; }
    .pay-show .pay-doc.is-filled { border-color: var(--erp-accent); box-shadow: 0 0 0 1px var(--erp-accent) inset; }
    .pay-show .pay-doc > td:nth-child(1) { grid-area: doc; font-size: 15px !important; }
    .pay-show .pay-doc > td:nth-child(1) a { display: inline-flex; align-items: center; min-height: 32px; }
    .pay-show .pay-doc > td:nth-child(2) { grid-area: due; }
    .pay-show .pay-doc > td:nth-child(2) .erp-due { white-space: normal; }
    .pay-show .pay-doc > td:nth-child(3) { grid-area: cur; justify-self: end; text-align: right !important; }
    .pay-show .pay-doc > td:nth-child(4) { grid-area: bal; justify-self: end; text-align: right !important; font-size: 15px !important; font-weight: 800; color: var(--erp-ink); }
    .pay-show .pay-doc > td:nth-child(5) { grid-area: apply; display: grid !important; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: end; padding-top: 10px !important; border-top: 1px solid var(--erp-line-soft) !important; }
    .pay-show .pay-doc > td:nth-child(5)::before { grid-column: 1 / -1; margin-bottom: 0 !important; }
    .pay-show .pay-alloc-table .fin-apply-input { width: 100%; min-height: 44px; font-size: 16px; text-align: right; }
    .pay-show .pay-m-only.pay-fill { display: inline-flex; min-height: 44px; padding: 8px 12px; font-size: 14px; justify-content: center; white-space: nowrap; }
    .pay-show #finApplyForm .fin-check-line { min-height: 44px; gap: 10px; font-size: 14px; cursor: pointer; }
    .pay-show #finApplyForm .fin-check-line input { width: 20px; height: 20px; margin: 0; }
    .pay-show .erp-card-title { overflow-wrap: anywhere; }
    /* Barra fija: disponible / a aplicar + Aplicar */
    .pay-show .pay-apply-bar {
        position: sticky; bottom: 0; z-index: 30;
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;
        margin: 12px -14px 0; padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
        background: #fff; border-top: 1px solid var(--erp-line); box-shadow: 0 -6px 16px rgba(16, 24, 40, .08);
    }
    .pay-show .pay-apply-bar .fin-apply-summary, .pay-show .pay-apply-bar .erp-form-actions { display: contents; }
    .pay-show .pay-apply-bar .fin-apply-summary > div { min-width: 0; padding: 6px 8px; border-radius: 8px; background: #f6f7f9; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--erp-muted); line-height: 1.25; }
    .pay-show .pay-apply-bar .fin-apply-summary strong { display: block; margin-top: 2px; font-size: 14px; letter-spacing: 0; color: var(--erp-ink); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .pay-show .pay-apply-bar .fin-apply-summary strong.is-bad { color: var(--erp-bad); }
    .pay-show .pay-apply-bar .erp-btn--primary { grid-column: 1 / -1; width: 100%; min-height: 48px; justify-content: center; font-size: 15px; }

    /* Historial y flujo de documentos */
    .pay-show .fin-timeline b, .pay-show .fin-timeline span { overflow-wrap: anywhere; }
}
</style>
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
<div class="erp-page fin-page pay-show">

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
                    <table class="erp-table pay-apps-table">
                        <thead><tr><th>Documento</th><th>Vence</th><th class="is-num">Aplicado (moneda doc.)</th><th class="is-num">Consumido del pago</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($applications as $a)
                            @php $d = $a->doc; @endphp
                            <tr class="pay-app">
                                <td class="pay-a-doc">
                                    @if ($d)<span class="erp-cell-folio"><a href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a></span>@else — @endif
                                    @if ($d)<div class="erp-cell-sub">{{ $d->partyName() }}</div>@endif
                                </td>
                                <td class="pay-a-due" data-label="Vence">{{ $d?->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="is-num pay-a-applied" data-label="Aplicado (moneda doc.)">${{ number_format($a->amount_applied, 2) }}<span class="fin-cur">{{ $d?->currency }}</span></td>
                                <td class="is-num pay-a-used" data-label="Consumido del pago">${{ number_format($a->amount_payment, 2) }}<span class="fin-cur">{{ $payment->currency }}</span></td>
                                <td class="is-num pay-a-action {{ $editable ? '' : 'is-empty' }}">
                                    @if ($editable)
                                        <form method="POST" action="{{ route('admin.payments.unapply', [$payment->id, $a->id]) }}" class="fin-inline-form" onsubmit="return confirm('¿Revertir esta aplicación?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="erp-btn erp-btn--sm erp-btn--danger">Desaplicar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="pay-row-empty"><td colspan="5" style="padding: 0;"><x-erp.empty title="Este pago aún no se ha aplicado" text="Aplícalo a uno o varios documentos abiertos para reducir sus saldos." /></td></tr>
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
                                <table class="erp-table pay-alloc-table">
                                    <thead><tr><th>Documento</th><th>Vence</th><th>Moneda</th><th class="is-num">Saldo</th><th class="is-num">Aplicar</th></tr></thead>
                                    <tbody>
                                    @foreach ($openDocs as $d)
                                        <tr class="pay-doc">
                                            <td class="erp-cell-folio" data-label="Documento"><a href="{{ route($docRoute, $d->id) }}">{{ $d->number }}</a></td>
                                            <td data-label="Vence"><x-erp.due :date="$d->due_date" /></td>
                                            <td data-label="Moneda">{{ $d->currency }}</td>
                                            <td class="is-num" data-label="Saldo">${{ number_format($d->balance, 2) }}</td>
                                            <td class="is-num" data-label="Aplicar"><input type="number" step="0.01" min="0" max="{{ $d->balance }}" name="alloc[{{ $d->id }}]" inputmode="decimal" class="erp-input fin-apply-input" aria-label="Monto a aplicar a {{ $d->number }}" data-rate="{{ (float) $d->exchange_rate }}" data-balance="{{ $d->balance }}"><button type="button" class="erp-btn pay-m-only pay-fill" aria-label="Aplicar saldo completo a {{ $d->number }}">Aplicar saldo</button></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="pay-apply-bar">
                            <div class="fin-apply-summary">
                                <div>Disponible: <strong id="finSumFree">${{ number_format($payment->unappliedAmount(), 2) }}</strong></div>
                                <div>A aplicar: <strong id="finSumApplied">$0.00</strong></div>
                            </div>
                            <div class="erp-form-actions"><button type="submit" class="erp-btn erp-btn--primary">Aplicar</button></div>
                            </div>
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
@push('scripts')
<script>
/* Móvil: «Aplicar saldo» rellena el saldo del documento y avisa al resumen de erp-finance.js. */
(function () {
    var form = document.getElementById('finApplyForm');
    if (!form) return;
    var inputs = form.querySelectorAll('input[name^="alloc["]');
    var mark = function (input) {
        var tr = input.closest('tr');
        if (tr) tr.classList.toggle('is-filled', (parseFloat(input.value) || 0) > 0);
    };
    inputs.forEach(function (i) { i.addEventListener('input', function () { mark(i); }); });
    form.addEventListener('change', function (e) { if (e.target.id === 'finFifo') setTimeout(function () { inputs.forEach(mark); }, 0); });
    form.addEventListener('click', function (e) {
        var btn = e.target.closest('.pay-fill');
        if (!btn) return;
        var input = btn.parentNode.querySelector('input[name^="alloc["]');
        if (!input || input.disabled) return;
        input.value = (parseFloat(input.dataset.balance) || 0).toFixed(2);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
})();
</script>
@endpush
