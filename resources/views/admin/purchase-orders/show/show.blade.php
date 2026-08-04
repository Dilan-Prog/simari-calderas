@extends('admin.layouts.master')
@section('title', 'Ver Orden de Compra - Admin')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@section('content')
    @php
        $statusConfig = match ($order->status) {
            'pending' => ['label' => 'Pendiente', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#d9780640'],
            'accepted' => ['label' => 'Aceptada', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#16a34a40'],
            'rejected' => ['label' => 'Rechazada', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#dc262640'],
            default => ['label' => 'Pendiente', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#d9780640'],
        };
    @endphp
    <div class="po-page">

        {{-- Header --}}
        <header class="po-header">
            <nav class="po-breadcrumb">
                <a href="{{ route('admin.purchase-orders.index') }}">Órdenes de Compra</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
                <span>{{ $order->po_number }}</span>
            </nav>
            <div class="po-header__title-row">
                <a href="{{ route('admin.purchase-orders.index') }}" class="po-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7" />
                        <path d="M19 12H5" />
                    </svg>
                </a>
                <h1 class="po-header__title">Orden {{ $order->po_number }}</h1>
            </div>
        </header>

        <div class="po-layout">

            {{-- ── LEFT COLUMN ── --}}
            <div class="po-main">

                {{-- Datos del Proveedor --}}
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                            <line x1="3" x2="21" y1="6" y2="6" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        Datos del Proveedor
                    </h2>

                    <div class="po-supplier-card" style="margin-bottom:20px;">
                        <p class="po-supplier-card__name">
                            {{ strtoupper($order->supplier->company_name ?? '—') }}
                        </p>
                        <div class="po-supplier-card__grid">
                            <span class="po-supplier-card__label">Contacto:</span>
                            <span>{{ $order->supplier->contact_name ?? '—' }}</span>
                            <span class="po-supplier-card__label">Email:</span>
                            <span>{{ $order->supplier->email ?? '—' }}</span>
                            <span class="po-supplier-card__label">Teléfono:</span>
                            <span>{{ $order->supplier->phone ?? '—' }}</span>
                            <span class="po-supplier-card__label">RFC:</span>
                            <span>{{ $order->supplier->rfc ?? '—' }}</span>
                            <span class="po-supplier-card__label">Términos:</span>
                            <span>{{ $order->supplier->payment_terms ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="po-form-grid">
                        <div class="po-form-group">
                            <label class="po-label">FECHA DE LA ORDEN</label>
                            <p class="po-info-row__val" style="margin:0;">
                                {{ $order->order_date->format('d M Y') }}
                            </p>
                        </div>
                        <div class="po-form-group">
                            <label class="po-label">FECHA DE ENTREGA ESPERADA</label>
                            <p class="po-info-row__val" style="margin:0;">
                                {{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="po-form-group">
                        <label class="po-label">REFERENCIA INTERNA</label>
                        <p class="po-info-row__val" style="margin:0;">
                            {{ $order->internal_reference ?? '—' }}
                        </p>
                    </div>

                    <div class="po-form-group">
                        <label class="po-label">NOTAS AL PROVEEDOR</label>
                        <p style="margin:0;font-size:14px;color:#374151;">
                            {{ $order->notes ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Productos / Servicios --}}
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                            <path d="M12 22V12" />
                            <polyline points="3.29 7 12 12 20.71 7" />
                        </svg>
                        Productos / Servicios
                    </h2>

                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th class="po-col-num">#</th>
                                    <th class="po-col-name">PRODUCTO / DESCRIPCIÓN</th>
                                    <th class="po-col-sku">SKU</th>
                                    <th class="po-col-qty">CANT.</th>
                                    <th class="po-col-price">PRECIO UNIT.</th>
                                    <th class="po-col-disc">DESC. %</th>
                                    <th class="po-col-total">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($order->items as $index => $item)
                                    <tr>
                                        <td class="po-col-num">{{ $index + 1 }}</td>
                                        <td class="po-col-name">
                                            <p class="po-item-name">{{ $item->product_name }}</p>
                                            @if ($item->comment)
                                                <p style="margin:0;font-size:12px;color:#9ca3af;">
                                                    {{ $item->comment }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="po-col-sku">{{ $item->product_sku ?? '—' }}</td>
                                        <td class="po-col-qty">{{ $item->quantity }}</td>
                                        <td class="po-col-price">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="po-col-disc">{{ number_format($item->discount_percent, 2) }}%</td>
                                        <td class="po-col-total">
                                            <span class="po-item-total">${{ number_format($item->line_total, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="po-table__empty">
                                        <td colspan="7">Esta orden no tiene productos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT SIDEBAR ── --}}
            <aside class="po-sidebar">

                {{-- Resumen --}}
                <div class="po-card">
                    <h2 class="po-card__header">Resumen de la Orden</h2>
                    <div class="po-summary">
                        <div class="po-summary__row">
                            <span>Subtotal</span>
                            <span class="po-summary__val">${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="po-summary__row">
                            <span>Descuento global</span>
                            <span class="po-summary__val po-summary__val--red">
                                -${{ number_format($order->discount_total, 2) }}
                            </span>
                        </div>
                        <div class="po-summary__row">
                            <span>Base gravable</span>
                            <span class="po-summary__val">
                                ${{ number_format($order->subtotal - $order->discount_total, 2) }}
                            </span>
                        </div>
                        <div class="po-summary__row">
                            <span>IVA ({{ number_format($order->tax_rate, 2) }}%)</span>
                            <span class="po-summary__val">${{ number_format($order->tax_total, 2) }}</span>
                        </div>
                        @if ($order->exchange_rate !== null)
                            <div class="po-summary__row">
                                <span>Tipo de cambio (USD → MXN)</span>
                                <span class="po-summary__val">
                                    {{ $order->currency }} · {{ number_format($order->exchange_rate, 4) }}
                                </span>
                            </div>
                        @endif
                        <div class="po-summary__total">
                            <span>TOTAL</span>
                            <span class="po-summary__total-val">${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="po-card">
                    <h2 class="po-card__header">Estado de la Orden</h2>
                    <span style="display:inline-block;padding:6px 16px;border-radius:20px;
                        font-size:13px;font-weight:600;
                        background:{{ $statusConfig['bg'] }};
                        color:{{ $statusConfig['color'] }};
                        border:1px solid {{ $statusConfig['border'] }};">
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                {{-- Info adicional --}}
                <div class="po-card">
                    <h2 class="po-card__header">Información adicional</h2>
                    <div class="po-info-row">
                        <span class="po-info-row__label">Creada por</span>
                        <span class="po-info-row__val">
                            {{ $order->createdBy->first_name ?? 'Admin' }}
                            {{ $order->createdBy->last_name ?? '' }}
                        </span>
                    </div>
                    <div class="po-info-row">
                        <span class="po-info-row__label">Folio</span>
                        <span class="po-info-row__val po-info-row__val--orange">
                            {{ $order->po_number }}
                        </span>
                    </div>
                    <div class="po-info-row">
                        <span class="po-info-row__label">Creada el</span>
                        <span class="po-info-row__val">
                            {{ $order->created_at->format('d M Y') }}
                        </span>
                    </div>
                </div>

                {{-- Actions --}}
                <a href="{{ route('admin.purchase-orders.edit', $order->id) }}" class="po-btn-save"
                    style="display:block;text-align:center;text-decoration:none;">
                    Editar Orden
                </a>
                <a href="{{ route('admin.purchase-orders.pdf', $order->id) }}" class="po-btn-draft"
                    style="display:block;text-align:center;text-decoration:none;">
                    Exportar PDF
                </a>
                <a href="{{ route('admin.purchase-orders.index') }}" class="po-btn-draft"
                    style="display:block;text-align:center;text-decoration:none;">
                    Volver
                </a>

            </aside>
        </div>
    </div>
@endsection
