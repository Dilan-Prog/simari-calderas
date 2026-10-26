@extends('customers.layouts.master')

@section('title', 'Cotización ' . $quote->quote_number)

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .qt-badge { display:inline-flex; align-items:center; padding:.25rem .625rem; border-radius:9999px; font-size:.75rem; font-weight:600; border:1px solid transparent; white-space:nowrap; }
        .qt-badge--sent { background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE; }
        .qt-badge--accepted { background:#F0FDF4; color:#15803D; border-color:#BBF7D0; }
        .qt-badge--rejected { background:#FEF2F2; color:#B91C1C; border-color:#FECACA; }
        .qt-badge--expired { background:#F3F4F6; color:#4B5563; border-color:#D1D5DB; }

        .qt-actions { display:flex; flex-wrap:wrap; gap:.5rem; }
        .qt-note { font-size:.75rem; font-weight:600; margin-top:.125rem; }
        .qt-note--warn { color:#B45309; }
        .qt-note--late { color:#B91C1C; }

        .qt-items { width:100%; border-collapse:collapse; font-size:.8125rem; }
        .qt-items th { padding:.625rem .75rem; font-size:.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--ts-text-muted); text-align:left; border-bottom:1px solid var(--ts-border); white-space:nowrap; }
        .qt-items td { padding:.75rem; border-bottom:1px solid var(--ts-border-light); color:var(--ts-text-body); vertical-align:middle; }
        .qt-items tr:last-child td { border-bottom:none; }
        .qt-num { text-align:right !important; white-space:nowrap; }
        .qt-prod { display:flex; align-items:center; gap:.75rem; min-width:220px; }
        .qt-prod__img { width:48px; height:48px; flex-shrink:0; border-radius:6px; border:1px solid var(--ts-border-light); background:#F9FAFB; object-fit:cover; display:flex; align-items:center; justify-content:center; color:var(--ts-text-light); }
        .qt-prod__name { font-weight:600; color:var(--ts-text-title); }
        .qt-prod__desc { font-size:.75rem; color:var(--ts-text-muted); margin-top:.125rem; }
        .qt-sku { font-size:.75rem; color:var(--ts-text-muted); }

        .qt-totals { margin:1.25rem 0 0 auto; width:100%; max-width:340px; font-size:.875rem; }
        .qt-totals__row { display:flex; justify-content:space-between; gap:1rem; padding:.375rem 0; color:var(--ts-text-body); }
        .qt-totals__row--total { border-top:1px solid var(--ts-border); margin-top:.375rem; padding-top:.75rem; font-size:1.0625rem; font-weight:700; color:var(--ts-text-title); }
        .qt-totals__row--muted { font-size:.75rem; color:var(--ts-text-muted); }

        .qt-terms { font-size:.8125rem; color:var(--ts-text-body); line-height:1.6; }
        .qt-links { display:flex; flex-direction:column; gap:.5rem; }
        .qt-link { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; padding:.625rem .875rem; border:1px solid var(--ts-border-light); border-radius:8px; text-decoration:none; color:var(--ts-text-body); font-size:.8125rem; }
        .qt-link:hover { background:#FAFAFA; border-color:var(--ts-border); }
        .qt-link strong { color:var(--ts-primary); }
        .qt-link__tag { margin-left:auto; font-size:.6875rem; font-weight:600; padding:.125rem .5rem; border-radius:9999px; background:#F3F4F6; color:#4B5563; }

        @media (max-width: 768px) {
            .qt-items-wrap { overflow-x:auto; }
            .qt-items { min-width:640px; }
            .qt-totals { max-width:none; }
        }
    </style>
@endpush

@section('content')
@php
    $today    = now()->startOfDay();
    $lapsed   = $quote->status === 'sent' && $quote->valid_until && $quote->valid_until->lt($today);
    $key      = $lapsed ? 'expired' : $quote->status;
    $currency = $quote->currency ?: 'MXN';
    $money    = fn ($n) => '$' . number_format((float) $n, 2);

    $note = null; $noteClass = '';
    if ($quote->status === 'sent' && $quote->valid_until) {
        if ($lapsed) {
            $note = 'Vencida'; $noteClass = 'qt-note--late';
        } else {
            $days = (int) $today->diffInDays($quote->valid_until);
            $note = $days === 0 ? 'Vence hoy' : 'Vence en ' . $days . ' ' . ($days === 1 ? 'día' : 'días');
            $noteClass = $days <= 7 ? 'qt-note--warn' : '';
        }
    } elseif ($quote->status === 'expired') {
        $note = 'Vencida'; $noteClass = 'qt-note--late';
    }

    $discount = (float) $quote->discount_total;
    $isr      = (float) $quote->isr_retention_total;
@endphp
<div class="ts-page">

    <div class="ts-show-header">
        <div style="display:flex;align-items:flex-start;gap:0.75rem">
            <a href="{{ route('customer.quotes.index') }}" class="ts-show-back" title="Volver" aria-label="Volver a Cotizaciones">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7M19 12H5"/>
                </svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:0.25rem">
                    <span>Panel de Control</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <a href="{{ route('customer.quotes.index') }}" style="color:inherit;text-decoration:none">Cotizaciones</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="ts-breadcrumb__current">{{ $quote->quote_number }}</span>
                </div>
                <div class="ts-show-title-row">
                    <span class="ts-show-num">{{ $quote->quote_number }}</span>
                    <span class="qt-badge qt-badge--{{ $key }}">{{ \App\Models\Quote::statusLabel($key) }}</span>
                </div>
                <h1 class="ts-show-title">Total {{ $money($quote->total) }} {{ $currency }}</h1>
            </div>
        </div>
        <div class="qt-actions">
            <a href="{{ route('customer.quotes.pdf', $quote->id) }}" target="_blank" rel="noopener" class="ts-btn">Ver PDF</a>
            <a href="{{ route('customer.quotes.pdf', ['quote' => $quote->id, 'download' => 1]) }}" class="ts-btn ts-btn--primary">Descargar PDF</a>
        </div>
    </div>

    {{-- Fechas --}}
    <div class="ts-card">
        <h2 class="ts-card__title">Datos de la cotización</h2>
        <div class="ts-card__divider" style="margin-top:.75rem"></div>
        <div class="ts-detail-grid">
            <div class="ts-detail-field">
                <span class="ts-detail-label">Enviada</span>
                <span class="ts-detail-value">{{ ($quote->sent_at ?? $quote->created_at)?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="ts-detail-field">
                <span class="ts-detail-label">Vigencia</span>
                <span class="ts-detail-value">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}</span>
                @if($note)<span class="qt-note {{ $noteClass }}">{{ $note }}</span>@endif
            </div>
            @if($quote->accepted_at)
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Aceptada</span>
                    <span class="ts-detail-value">{{ $quote->accepted_at->format('d/m/Y') }}</span>
                </div>
            @endif
            @if($quote->rejected_at)
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Rechazada</span>
                    <span class="ts-detail-value">{{ $quote->rejected_at->format('d/m/Y') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Partidas --}}
    <div class="ts-card">
        <h2 class="ts-card__title">Partidas</h2>
        <div class="ts-card__divider" style="margin-top:.75rem"></div>

        <div class="qt-items-wrap">
            <table class="qt-items">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th class="qt-num">Cantidad</th>
                        <th class="qt-num">Precio unitario</th>
                        <th class="qt-num">Desc. %</th>
                        <th class="qt-num">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quote->items as $item)
                        @php $img = $item->product?->cover_image_url; @endphp
                        <tr>
                            <td>
                                <div class="qt-prod">
                                    @if($img)
                                        <img class="qt-prod__img" src="{{ $img }}" alt="{{ $item->product?->cover_image_alt ?: $item->product_name }}" loading="lazy">
                                    @endif
                                    <div>
                                        <div class="qt-prod__name">{{ $item->product_name }}</div>
                                        @if($item->notes)
                                            <div class="qt-prod__desc">{!! nl2br(e($item->notes)) !!}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="qt-sku">{{ $item->product_sku ?: '—' }}</td>
                            <td class="qt-num">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td class="qt-num">{{ $money($item->unit_price) }}</td>
                            <td class="qt-num">{{ (float) $item->discount_percent > 0 ? rtrim(rtrim(number_format((float) $item->discount_percent, 2), '0'), '.') . '%' : '—' }}</td>
                            <td class="qt-num"><strong>{{ $money($item->line_total) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;color:var(--ts-text-muted)">Esta cotización no tiene partidas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="qt-totals">
            <div class="qt-totals__row"><span>Subtotal</span><span>{{ $money($quote->subtotal) }}</span></div>
            @if($discount > 0)
                <div class="qt-totals__row"><span>Descuento</span><span>-{{ $money($discount) }}</span></div>
            @endif
            <div class="qt-totals__row">
                <span>IVA ({{ rtrim(rtrim(number_format((float) $quote->tax_rate, 2), '0'), '.') }}%)</span>
                <span>{{ $money($quote->tax_total) }}</span>
            </div>
            @if($isr > 0)
                <div class="qt-totals__row">
                    <span>Retención ISR ({{ rtrim(rtrim(number_format((float) $quote->isr_retention_rate, 2), '0'), '.') }}%)</span>
                    <span>-{{ $money($isr) }}</span>
                </div>
            @endif
            <div class="qt-totals__row qt-totals__row--total"><span>Total</span><span>{{ $money($quote->total) }} {{ $currency }}</span></div>
            @if($currency !== 'MXN')
                <div class="qt-totals__row qt-totals__row--muted">
                    <span>Tipo de cambio</span>
                    <span>1 {{ $currency }} = {{ number_format((float) $quote->exchange_rate, 4) }} MXN</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Términos y condiciones --}}
    @if(filled($quote->terms_conditions))
        <div class="ts-card">
            <h2 class="ts-card__title">Términos y condiciones</h2>
            <div class="ts-card__divider" style="margin-top:.75rem"></div>
            <div class="qt-terms">{!! nl2br(e($quote->terms_conditions)) !!}</div>
        </div>
    @endif

    {{-- Relacionados --}}
    @if($quote->salesOrders->isNotEmpty() || $services->isNotEmpty())
        <div class="ts-card">
            <h2 class="ts-card__title">Relacionado con esta cotización</h2>
            <div class="ts-card__divider" style="margin-top:.75rem"></div>
            <div class="qt-links">
                @foreach($quote->salesOrders as $order)
                    <a class="qt-link" href="{{ route('customer.deliveries.orders.show', $order) }}">
                        <span>Pedido</span>
                        <strong>{{ $order->order_number }}</strong>
                        <span class="qt-link__tag">{{ \App\Models\SalesOrder::statusLabel($order->status) }}</span>
                    </a>
                @endforeach
                @foreach($services as $service)
                    <a class="qt-link" href="{{ route('customer.technical-services.show', $service->id) }}">
                        <span>Servicio</span>
                        <strong>{{ $service->service_number }}</strong>
                        @if($service->serviceType?->name)<span>{{ $service->serviceType->name }}</span>@endif
                        @if($service->service_date)<span class="qt-link__tag">{{ $service->service_date->format('d/m/Y') }}</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('customer.quotes.index') }}" class="ts-btn ts-btn--ghost">← Volver a Cotizaciones</a>
</div>
@endsection
