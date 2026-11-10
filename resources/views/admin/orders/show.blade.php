@extends('admin.layouts.master')

@section('title', $order->order_number . ' - Órdenes - Admin')

@section('content')
<div class="orders-page ordshow-m">

    <p class="ordshow-crumb" style="margin:0 0 14px;font-size:12.5px;color:#6b7280;">
        <a href="{{ route('admin.orders.index') }}" style="color:#6b7280;">Órdenes de tienda</a> / {{ $order->order_number }}
    </p>

    @php
        $meta = \App\Support\StoreOrderStatus::meta($order->status);
        $isCancelledBranch = in_array($order->status, ['cancelado', 'reembolsado'], true);
        $currentFlowIndex = array_search($order->status, \App\Support\StoreOrderStatus::FLOW, true);
        // StoreOrderController::show() no pasa una variable $shipment
        // separada al view — carga "shipment.carrier" / "shipment.statusLogs.
        // changedBy" como relación eager-loaded sobre $order (ver
        // $order->load([...]) en el controller) y no la incluye en el
        // compact(). Se deriva aquí para que _shipment_card.blade.php y
        // _shipment_status_modal.blade.php reciban $shipment como
        // contemplaba el contrato original.
        $shipment = $order->shipment;
    @endphp

    {{-- Header --}}
    <div class="orders-detail-header">
        <div class="orders-detail-header-left">
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span class="orders-detail-folio">{{ $order->order_number }}</span>
                    <span class="orders-status-pill" style="color:{{ $meta['color'] }};background:{{ $meta['bg'] }};">
                        <span class="orders-status-pill-dot"></span>{{ $meta['label'] }}
                    </span>
                </div>
                <p class="orders-detail-date">
                    Creada el {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                    · {{ $order->items->count() }} {{ Str::plural('partida', $order->items->count()) }}
                    · Canal: Tienda en línea
                </p>
            </div>
        </div>
        <div class="orders-detail-actions">
            <a href="{{ route('admin.orders.index') }}" class="button-secondary size-adjustment">Volver</a>
            <button type="button" class="button-secondary size-adjustment" onclick="window.print()">Imprimir</button>
            <button type="button" class="js-open-status-modal orders-status-trigger" title="Cambiar estatus"
                data-order-id="{{ $order->id }}"
                data-folio="{{ $order->order_number }}"
                data-current-status="{{ $order->status }}"
                data-allowed='@json($allowedTransitions)'
                data-update-url="{{ route('admin.orders.update-status', $order) }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                <span class="ordshow-btn-label">Cambiar estatus</span>
            </button>
        </div>
    </div>

    {{-- Banner de cancelación / reembolso --}}
    @if ($isCancelledBranch)
        @php $cancelLog = $order->statusLogs->firstWhere('to_status', $order->status); @endphp
        <div class="orders-cancel-banner">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            <div>
                <strong>{{ $meta['label'] }}.</strong>
                @if ($cancelLog?->motivo)
                    Motivo: {{ \App\Support\StoreOrderStatus::MOTIVOS[$cancelLog->motivo] ?? $cancelLog->motivo }}.
                @endif
                @if ($cancelLog?->note)
                    {{ $cancelLog->note }}
                @endif
            </div>
        </div>
    @endif

    {{-- Timeline --}}
    <div class="orders-timeline">
        @foreach (\App\Support\StoreOrderStatus::FLOW as $flowIndex => $flowStatus)
            @php
                $flowMeta = \App\Support\StoreOrderStatus::meta($flowStatus);
                $stepClass = '';
                if (!$isCancelledBranch) {
                    if ($flowIndex < $currentFlowIndex) {
                        $stepClass = 'is-done';
                    } elseif ($flowIndex === $currentFlowIndex) {
                        $stepClass = 'is-current';
                    }
                }
            @endphp
            <div class="orders-timeline-step {{ $stepClass }}" @if ($stepClass === 'is-current') style="--orders-current-color:{{ $flowMeta['color'] }};" @endif>
                <div class="orders-timeline-circle">
                    @if ($stepClass === 'is-done')
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    @else
                        {{ $flowIndex + 1 }}
                    @endif
                </div>
                <span class="orders-timeline-label">{{ $flowMeta['label'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="orders-detail-grid">
        {{-- Columna principal --}}
        <div class="orders-detail-main">

            <div class="orders-card ordshow-sec-items">
                <div class="orders-card-header">Productos de la orden</div>
                <div class="orders-card-body">
                    @foreach ($order->items as $item)
                        <div class="orders-item-row">
                            @if ($item->product?->cover_image_url)
                                <img class="orders-item-thumb ordshow-thumb" src="{{ $item->product->cover_image_url }}" alt="" loading="lazy">
                            @else
                                <span class="orders-item-thumb ordshow-thumb" aria-hidden="true"></span>
                            @endif
                            <div class="orders-item-info">
                                <p class="orders-item-name">{{ $item->product_name }}</p>
                                <p class="orders-item-sku">SKU: {{ $item->product_sku ?? '—' }}</p>
                            </div>
                            <span class="orders-item-qty">{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</span>
                            <span class="orders-item-total">${{ number_format($item->line_total, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="orders-card ordshow-sec-address">
                <div class="orders-card-header">Dirección de envío</div>
                <div class="orders-card-body">
                    <p style="margin:0 0 4px;font-size:13px;color:#111827;">{{ $order->shipping_address_line1 }}</p>
                    @if ($order->shipping_address_line2)
                        <p style="margin:0 0 4px;font-size:13px;color:#111827;">{{ $order->shipping_address_line2 }}</p>
                    @endif
                    <p style="margin:0 0 12px;font-size:13px;color:#111827;">
                        {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
                    </p>
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Recibe</span>
                        <span class="orders-card-row-value">{{ $order->contact_name }}</span>
                    </div>
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Teléfono</span>
                        <span class="orders-card-row-value">{{ $order->contact_phone }}</span>
                    </div>
                </div>
            </div>

            @include('admin.orders.partials._shipment_card', [
                'order' => $order,
                'shipment' => $shipment,
                'shipmentCarriers' => $shipmentCarriers,
                'shipmentUsers' => $shipmentUsers,
                'shipmentAllowedTransitions' => $shipmentAllowedTransitions,
                'shipmentStatusMeta' => $shipmentStatusMeta,
                'shipmentMotivos' => $shipmentMotivos,
            ])

            @if ($order->requires_invoice)
                <div class="orders-card ordshow-sec-billing">
                    <div class="orders-card-header">
                        Datos de facturación (CFDI)
                        <button type="button" class="js-copy-cfdi button-secondary size-adjustment"
                            data-copy-text="{{ $order->razon_social }} · RFC {{ $order->rfc }}">Copiar datos</button>
                    </div>
                    <div class="orders-card-body">
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">Razón social</span>
                            <span class="orders-card-row-value">{{ $order->razon_social }}</span>
                        </div>
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">RFC</span>
                            <span class="orders-card-row-value">{{ $order->rfc }}</span>
                        </div>
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">Régimen fiscal</span>
                            <span class="orders-card-row-value">{{ $order->regimen_fiscal }}</span>
                        </div>
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">Uso de CFDI</span>
                            <span class="orders-card-row-value">{{ $order->uso_cfdi }}</span>
                        </div>
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">CP fiscal</span>
                            <span class="orders-card-row-value">{{ $order->cp_fiscal }}</span>
                        </div>
                        <div class="orders-card-row">
                            <span class="orders-card-row-label">Correo</span>
                            <span class="orders-card-row-value">{{ $order->contact_email }}</span>
                        </div>
                        @if ($order->tax_certificate_path)
                            <p style="margin-top:12px;">
                                <a href="{{ route('admin.orders.tax-certificate', $order) }}">Ver constancia fiscal</a>
                            </p>
                        @endif
                    </div>
                </div>
            @else
                <div class="orders-card ordshow-sec-billing">
                    <div class="orders-card-body" style="text-align:center;color:#9ca3af;font-size:13px;padding:24px 18px;">
                        Esta orden no requiere factura.
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="orders-detail-side">

            <div class="orders-card ordshow-sec-client">
                <div class="orders-card-header">Cliente</div>
                <div class="orders-card-body">
                    <p style="margin:0 0 6px;font-weight:700;font-size:14px;color:#111827;">{{ $order->contact_name }}</p>
                    <span style="display:inline-block;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;margin-bottom:12px;
                        @if ($order->customer_id) background:#eef2ff;color:#4338ca; @else background:#f1f2f4;color:#6b7280; @endif">
                        {{ $order->customer_id ? 'Cliente con cuenta' : 'Compra como invitado' }}
                    </span>
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Correo</span>
                        <span class="orders-card-row-value">{{ $order->contact_email }}</span>
                    </div>
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Teléfono</span>
                        <span class="orders-card-row-value">{{ $order->contact_phone }}</span>
                    </div>
                    @if ($order->customer_id)
                        <p style="margin-top:10px;">
                            <a href="{{ route('admin.clients.information', $order->customer_id) }}">Ver ficha de cliente →</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="orders-card ordshow-sec-payment">
                <div class="orders-card-header">Pago</div>
                <div class="orders-card-body">
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Método</span>
                        <span class="orders-card-row-value">{{ $order->paymentMethod?->name ?? '—' }}</span>
                    </div>
                    <div class="orders-card-row">
                        <span class="orders-card-row-label">Estado</span>
                        <span class="orders-card-row-value">
                            @if ($order->status === 'pendiente_pago')
                                Pago no confirmado
                            @elseif ($order->status === 'cancelado')
                                Sin cobro
                            @else
                                Pago confirmado
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="orders-card ordshow-sec-summary">
                <div class="orders-card-header">Resumen</div>
                <div class="orders-card-body">
                    <div class="orders-summary-row"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
                    <div class="orders-summary-row"><span>Envío</span><span>${{ number_format($order->shipping_total, 2) }}@if ($order->shipping_pending_quote) <strong style="color:#c2410c;">+ por cotizar</strong>@endif</span></div>
                    @if ($order->shipping_pending_quote)
                        <div class="orders-summary-row" style="color:#c2410c;font-size:12.5px;"><span>Este pedido incluye productos con envío que se cotiza aparte: cotiza la guía a la dirección del cliente y cóbrasela por separado.</span></div>
                    @endif
                    @if ($order->discount_total > 0)
                        <div class="orders-summary-row"><span>Descuento</span><span>-${{ number_format($order->discount_total, 2) }}</span></div>
                    @endif
                    <div class="orders-summary-row is-total"><span>Total</span><span>${{ number_format($order->total, 2) }}</span></div>
                    <p class="orders-legend-desc" style="margin-top:10px;">IVA incluido en los precios mostrados.</p>
                </div>
            </div>

            <div class="orders-card ordshow-sec-activity">
                <div class="orders-card-header">Actividad</div>
                <div class="orders-card-body">
                    @foreach ($order->statusLogs as $log)
                        @php $logMeta = \App\Support\StoreOrderStatus::meta($log->to_status); @endphp
                        <div class="orders-activity-item">
                            <span class="orders-activity-dot" style="background:{{ $logMeta['color'] ?? '#9ca3af' }};"></span>
                            <div>
                                <p class="orders-activity-text">
                                    Estatus actualizado a <strong>{{ $logMeta['label'] ?? $log->to_status }}</strong>
                                    @if ($log->motivo)
                                        — {{ \App\Support\StoreOrderStatus::MOTIVOS[$log->motivo] ?? $log->motivo }}
                                    @endif
                                </p>
                                @if ($log->note)
                                    <p class="orders-activity-text" style="color:#6b7280;">{{ $log->note }}</p>
                                @endif
                                <p class="orders-activity-meta">
                                    {{ $log->changedBy->name ?? 'Sistema' }} · {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                    <div class="orders-activity-item">
                        <span class="orders-activity-dot" style="background:#9ca3af;"></span>
                        <div>
                            <p class="orders-activity-text">Orden creada</p>
                            <p class="orders-activity-meta">{{ $order->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.orders.partials._status_modal')
    @include('admin.orders.partials._shipment_status_modal', [
        'shipment' => $shipment,
        'shipmentAllowedTransitions' => $shipmentAllowedTransitions,
        'shipmentStatusMeta' => $shipmentStatusMeta,
        'shipmentMotivos' => $shipmentMotivos,
    ])
</div>
@endsection

@push('styles')
    @vite(['resources/css/admin/pages/orders.css'])
    @vite(['resources/css/admin/pages/shipments.css'])
@endpush

@push('styles')
<style>
/* ── Detalle de orden de tienda: adaptación a teléfono (solo ≤768px / ≤640px; escritorio sin cambios) ── */
.ordshow-btn-label,
.ordshow-thumb { display: none; }
@media (max-width: 768px) {
    .ordshow-m { padding: 16px 16px 48px; max-width: 100%; overflow-x: clip; }
    .ordshow-m .ordshow-crumb { overflow-wrap: anywhere; }

    /* Header: folio + estatus envuelven; acciones en grid táctil */
    .ordshow-m .orders-detail-header { flex-direction: column; align-items: stretch; gap: 14px; padding: 16px; }
    .ordshow-m .orders-detail-header-left,
    .ordshow-m .orders-detail-header-left > div { min-width: 0; }
    .ordshow-m .orders-detail-folio { font-size: 20px; overflow-wrap: anywhere; }
    .ordshow-m .orders-detail-date { line-height: 1.45; }
    .ordshow-m .orders-detail-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; width: 100%; }
    .ordshow-m .orders-detail-actions > * { width: 100%; min-width: 0; min-height: 44px; margin: 0; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; text-align: center; font-size: 14px; }
    .ordshow-m .orders-detail-actions .js-open-status-modal { order: -1; grid-column: 1 / -1; height: auto; gap: 8px; padding: 10px 14px; font-weight: 700; color: #374151; }
    .ordshow-m .ordshow-btn-label { display: inline; }

    .ordshow-m .orders-cancel-banner { overflow-wrap: anywhere; }

    /* Timeline de la orden: compacto, desplazable dentro de su tarjeta */
    .ordshow-m .orders-timeline { overflow-x: auto; padding: 16px 8px 14px; -webkit-overflow-scrolling: touch; }
    .ordshow-m .orders-timeline-step { min-width: 68px; }
    .ordshow-m .orders-timeline-label { font-size: 11px; line-height: 1.3; padding: 0 2px; }

    /* Columnas → una sola, en orden: productos, resumen, cliente, dirección,
       facturación, pago, envío, actividad */
    .ordshow-m .orders-detail-grid { display: flex; flex-direction: column; gap: 16px; }
    .ordshow-m .orders-detail-main,
    .ordshow-m .orders-detail-side { display: contents; }
    .ordshow-m .ordshow-sec-items    { order: 1; }
    .ordshow-m .ordshow-sec-summary  { order: 2; }
    .ordshow-m .ordshow-sec-client   { order: 3; }
    .ordshow-m .ordshow-sec-address  { order: 4; }
    .ordshow-m .ordshow-sec-billing  { order: 5; }
    .ordshow-m .ordshow-sec-payment  { order: 6; }
    .ordshow-m .ordshow-sec-shipment { order: 7; }
    .ordshow-m .ordshow-sec-activity { order: 8; }
    .ordshow-m .orders-card { min-width: 0; }

    /* Cards: textos largos (correos, direcciones, RFC, guías, URLs) envuelven */
    .ordshow-m .orders-card-header { flex-wrap: wrap; padding: 12px 16px; min-height: 44px; box-sizing: border-box; }
    .ordshow-m .orders-card-header .button-secondary { min-height: 40px; font-size: 13.5px; }
    .ordshow-m .orders-card-body { padding: 14px 16px; overflow-wrap: anywhere; }
    .ordshow-m .orders-card-body a { overflow-wrap: anywhere; }
    .ordshow-m .orders-card-body p > a { display: inline-flex; align-items: center; min-height: 40px; }
    .ordshow-m .orders-card-row { font-size: 13.5px; padding: 8px 0; }
    .ordshow-m .orders-card-row-label { flex: 0 0 auto; max-width: 45%; }
    .ordshow-m .orders-card-row-value { flex: 1 1 auto; min-width: 0; overflow-wrap: anywhere; word-break: normal; }

    /* Productos: filas → tarjetas (imagen, nombre/SKU, cantidad × precio, total) */
    .ordshow-m .ordshow-sec-items .orders-card-body { padding: 12px; }
    .ordshow-m .orders-item-row { display: grid; grid-template-columns: 52px minmax(0, 1fr) auto; grid-template-areas: "thumb info info" "qty qty total"; align-items: center; gap: 10px 0; padding: 12px; margin-bottom: 10px; border: 1px solid #e5e7eb; border-radius: 10px; }
    .ordshow-m .orders-item-row:last-child { margin-bottom: 0; border-bottom: 1px solid #e5e7eb; }
    .ordshow-m .ordshow-thumb { display: block; grid-area: thumb; width: 52px; height: 52px; }
    .ordshow-m .orders-item-info { grid-area: info; padding-left: 12px; }
    .ordshow-m .orders-item-name { white-space: normal; overflow: visible; text-overflow: clip; overflow-wrap: anywhere; font-size: 14px; line-height: 1.35; }
    .ordshow-m .orders-item-sku { font-size: 12px; overflow-wrap: anywhere; }
    .ordshow-m .orders-item-qty,
    .ordshow-m .orders-item-total { padding-top: 10px; border-top: 1px dashed #e5e7eb; }
    .ordshow-m .orders-item-qty { grid-area: qty; min-width: 0; font-size: 13px; white-space: normal; overflow-wrap: anywhere; }
    .ordshow-m .orders-item-total { grid-area: total; text-align: right; font-size: 15px; padding-left: 12px; }

    /* Resumen */
    .ordshow-m .orders-summary-row { gap: 12px; font-size: 14px; padding: 6px 0; }
    .ordshow-m .orders-summary-row > span:first-child:not(:last-child) { flex-shrink: 0; }
    .ordshow-m .orders-summary-row > span:last-child:not(:first-child) { min-width: 0; text-align: right; overflow-wrap: anywhere; }
    .ordshow-m .orders-summary-row.is-total { font-size: 17px; padding-top: 12px; }

    /* Actividad */
    .ordshow-m .orders-activity-item > div { min-width: 0; overflow-wrap: anywhere; }
    .ordshow-m .orders-activity-text { font-size: 13px; }

    /* Card de envío (estilos base en shipments.css, compartido: se sobrescribe aquí) */
    .ordshow-m .ordshow-sec-shipment .orders-status-trigger { width: 44px; height: 44px; }
    .ordshow-m .shipment-carrier-block > div { min-width: 0; overflow-wrap: anywhere; }
    .ordshow-m .shipment-guia-row { flex-direction: column; align-items: stretch; }
    .ordshow-m .shipment-guia-row > div:first-child { min-width: 0; }
    .ordshow-m .shipment-guia-value { overflow-wrap: anywhere; word-break: break-all; }
    .ordshow-m .shipment-guia-row > div:last-child { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .ordshow-m .shipment-guia-row > div:last-child > * { width: 100%; min-width: 0; min-height: 44px; margin: 0; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; text-align: center; font-size: 14px; white-space: normal; }
    .ordshow-m .shipment-guia-row > div:last-child > :only-child { grid-column: 1 / -1; }
    .ordshow-m .shipment-facts-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ordshow-m .shipment-fact { min-width: 0; overflow-wrap: anywhere; }
    .ordshow-m .shipment-timeline { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .ordshow-m .shipment-timeline-step { min-width: 64px; }
    .ordshow-m .shipment-timeline-label { font-size: 11px; line-height: 1.3; padding: 0 2px; }
    .ordshow-m .shipment-incidencia-banner { overflow-wrap: anywhere; }
    .ordshow-m .shipment-activity-item > div { min-width: 0; overflow-wrap: anywhere; }
    .ordshow-m #shipmentEmptyState .button-primary,
    .ordshow-m .ordshow-sec-shipment form .button-secondary,
    .ordshow-m .ordshow-sec-shipment form .button-primary { min-height: 44px; font-size: 14px; }
    .ordshow-m .shipment-form-grid { grid-template-columns: minmax(0, 1fr); }
    .ordshow-m .shipment-form-field input,
    .ordshow-m .shipment-form-field select { width: 100%; min-height: 44px; font-size: 16px; box-sizing: border-box; }
    .ordshow-m .shipment-form-checkbox { min-height: 44px; font-size: 14px; }
    .ordshow-m .shipment-form-checkbox input { width: 20px; height: 20px; flex-shrink: 0; }
    .ordshow-m #shipmentRegisterForm > div:last-child { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

/* Modales de estatus (orden y envío): bottom sheet en teléfono */
@media (max-width: 640px) {
    .ordshow-m .orders-status-modal-overlay { align-items: flex-end; justify-content: stretch; padding: 0; }
    .ordshow-m .orders-status-modal { display: flex; flex-direction: column; max-width: none; width: 100%; max-height: 92vh; max-height: 92dvh; overflow: hidden; border-radius: 16px 16px 0 0; }
    .ordshow-m .orders-status-modal-header { flex-shrink: 0; align-items: center; padding: 10px 8px 10px 16px; }
    .ordshow-m .orders-status-modal-header > div { min-width: 0; overflow-wrap: anywhere; }
    .ordshow-m .orders-status-modal-title { font-size: 16px; }
    .ordshow-m .orders-status-modal-sub { font-size: 13px; line-height: 1.45; }
    .ordshow-m .orders-status-modal-close { width: 44px; height: 44px; }
    .ordshow-m .orders-status-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 14px 16px; }
    .ordshow-m .orders-status-option { min-height: 48px; flex-wrap: wrap; padding: 10px 12px; box-sizing: border-box; }
    .ordshow-m .orders-status-option-desc { flex: 1 1 100%; padding-left: 26px; font-size: 12.5px; color: #6b7280; line-height: 1.4; }
    .ordshow-m .orders-status-field label { font-size: 13px; }
    .ordshow-m .orders-status-field select,
    .ordshow-m .orders-status-field textarea { min-height: 44px; font-size: 16px; }
    .ordshow-m .orders-status-field textarea { min-height: 96px; }
    .ordshow-m .orders-status-field-hint { font-size: 12.5px; line-height: 1.45; }
    .ordshow-m .orders-status-modal-footer { flex-shrink: 0; gap: 10px; padding: 10px 12px; padding-bottom: calc(10px + env(safe-area-inset-bottom)); background: #fff; }
    .ordshow-m .orders-status-modal-footer > button { flex: 1 1 0; min-width: 0; min-height: 46px; font-size: 15px; font-weight: 700; justify-content: center; }
}
</style>
@endpush

@push('scripts')
    @vite(['resources/js/admin/orders.js'])
    @vite(['resources/js/admin/shipments.js'])
@endpush

@push('scripts')
<script>
/* Bloqueo del scroll de fondo en teléfono mientras un modal de estatus está
   abierto. Solo observa la clase .is-open que ya ponen/quitan orders.js y
   shipments.js; no toca su lógica de apertura/cierre. */
(function () {
    var mq = window.matchMedia ? window.matchMedia('(max-width: 640px)') : null;
    var modals = ['orderStatusModalOverlay', 'shipmentStatusModalOverlay']
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);
    if (!mq || !modals.length || !window.MutationObserver) return;
    var locked = false, savedY = 0;
    function anyOpen() {
        return modals.some(function (m) { return m.classList.contains('is-open'); });
    }
    function sync() {
        var want = mq.matches && anyOpen();
        var b = document.body.style;
        if (want && !locked) {
            savedY = window.scrollY || window.pageYOffset || 0;
            b.position = 'fixed'; b.top = (-savedY) + 'px'; b.left = '0'; b.right = '0'; b.width = '100%';
            locked = true;
        } else if (!want && locked) {
            b.position = ''; b.top = ''; b.left = ''; b.right = ''; b.width = '';
            locked = false;
            window.scrollTo(0, savedY);
        }
    }
    var mo = new MutationObserver(sync);
    modals.forEach(function (m) { mo.observe(m, { attributes: true, attributeFilter: ['class'] }); });
    if (mq.addEventListener) mq.addEventListener('change', sync); else if (mq.addListener) mq.addListener(sync);
    sync();
})();
</script>
@endpush
