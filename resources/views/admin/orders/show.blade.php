@extends('admin.layouts.master')

@section('title', $order->order_number . ' - Órdenes - Admin')

@section('content')
<div class="orders-page">

    <p style="margin:0 0 14px;font-size:12.5px;color:#6b7280;">
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

            <div class="orders-card">
                <div class="orders-card-header">Productos de la orden</div>
                <div class="orders-card-body">
                    @foreach ($order->items as $item)
                        <div class="orders-item-row">
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

            <div class="orders-card">
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
                <div class="orders-card">
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
                <div class="orders-card">
                    <div class="orders-card-body" style="text-align:center;color:#9ca3af;font-size:13px;padding:24px 18px;">
                        Esta orden no requiere factura.
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="orders-detail-side">

            <div class="orders-card">
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

            <div class="orders-card">
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

            <div class="orders-card">
                <div class="orders-card-header">Resumen</div>
                <div class="orders-card-body">
                    <div class="orders-summary-row"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
                    <div class="orders-summary-row"><span>Envío</span><span>${{ number_format($order->shipping_total, 2) }}</span></div>
                    @if ($order->discount_total > 0)
                        <div class="orders-summary-row"><span>Descuento</span><span>-${{ number_format($order->discount_total, 2) }}</span></div>
                    @endif
                    <div class="orders-summary-row is-total"><span>Total</span><span>${{ number_format($order->total, 2) }}</span></div>
                    <p class="orders-legend-desc" style="margin-top:10px;">IVA incluido en los precios mostrados.</p>
                </div>
            </div>

            <div class="orders-card">
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

@push('scripts')
    @vite(['resources/js/admin/orders.js'])
    @vite(['resources/js/admin/shipments.js'])
@endpush
