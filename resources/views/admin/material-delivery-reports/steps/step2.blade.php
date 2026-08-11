@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 2')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step2">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 2</span>
    </div>

    {{-- Progress --}}
    <div class="mdr-progress">
        @php $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 2 ? 'done' : ($n === 2 ? 'active' : ''); @endphp
            <div class="mdr-step-item {{ $cls }}">
                <div class="mdr-step-circle">
                    @if($n < 2) ✓ @else {{ $n }} @endif
                </div>
                <span class="mdr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="mdr-form-card">
        <div class="mdr-form-header">
            <h2>Paso 2 — Líneas Entregadas</h2>
            <p>Captura la cantidad entregada en este evento para cada línea del pedido</p>
        </div>

        <form method="POST" action="{{ route('admin.material-delivery-reports.save-step', [$report, 2]) }}" id="step2Form">
            @csrf
            <div class="mdr-form-body">

                <p class="mdr-lines-hint">La columna "Entregado (total)" es informativa y refleja lo entregado acumulado en todos los eventos de este pedido. La columna "Pendiente" ya descuenta ese acumulado.</p>

                @php $mdrLines = $salesOrderItems ?? ($report->salesOrder->items ?? collect()); @endphp

                @if($mdrLines->isEmpty())
                    <div class="mdr-lines-empty">Este pedido no tiene líneas disponibles.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="mdr-lines-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Unidad</th>
                                    <th>Cant. Pedida</th>
                                    <th>Entregado (total)</th>
                                    <th>Pendiente</th>
                                    <th>Entregado en este evento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mdrLines as $idx => $line)
                                    @php
                                        $existingLine = $report->items->firstWhere('sales_order_item_id', $line->id) ?? null;
                                        $lineValue = old("items.$idx.quantity_delivered_in_event", $existingLine->quantity_delivered_in_event ?? '');
                                    @endphp
                                    <tr data-idx="{{ $idx }}" data-pending="{{ $line->pending }}">
                                        <td class="mdr-line-product" data-label="Producto">{{ $line->product_name }}</td>
                                        <td class="mdr-line-sku" data-label="SKU">{{ $line->product_sku ?? '—' }}</td>
                                        <td data-label="Unidad">{{ $line->unit ?? '—' }}</td>
                                        <td class="mdr-line-info" data-label="Cant. Pedida">{{ $line->quantity_ordered }}</td>
                                        <td class="mdr-line-info" data-label="Entregado (total)">{{ $line->quantity_delivered }}</td>
                                        <td class="mdr-line-pending" data-label="Pendiente">{{ $line->pending }}</td>
                                        <td data-label="Entregado en este evento">
                                            <input type="hidden" name="items[{{ $idx }}][sales_order_item_id]" value="{{ $line->id }}">
                                            <input type="number" step="any" min="0" max="{{ $line->pending }}"
                                                   name="items[{{ $idx }}][quantity_delivered_in_event]"
                                                   class="mdr-input mdr-qty-input"
                                                   value="{{ $lineValue }}"
                                                   placeholder="0">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>

            <div class="mdr-form-footer">
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 1]) }}" class="mdr-btn-outline">← Anterior</a>
                <button type="submit" class="mdr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // UX only — the backend re-validates that quantity_delivered_in_event
    // never exceeds the line's pending amount.
    document.querySelectorAll('.mdr-qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const max = parseFloat(this.getAttribute('max'));
            const val = parseFloat(this.value);
            if (!isNaN(max) && !isNaN(val) && val > max) {
                this.value = max;
            }
            if (!isNaN(val) && val < 0) {
                this.value = 0;
            }
        });
    });
})();
</script>
@endpush
