@extends('customers.layouts.master')

@section('title', 'Reporte ' . $report->report_number)

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.deliveries._styles')
@endpush

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
    $hasReceiver = $report->received_by_name || $report->received_by_position || $report->received_by_phone;
@endphp
<div class="ts-page">

    <div class="md-head">
        <div class="md-head__left">
            <a href="{{ route('customer.deliveries.index', ['tab' => 'reportes']) }}" class="md-back" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:.25rem">
                    <span>Panel de Control</span>
                    {!! $chevron !!}
                    <a href="{{ route('customer.deliveries.index', ['tab' => 'reportes']) }}" style="color:inherit;text-decoration:none">Entregas de Material</a>
                    {!! $chevron !!}
                    <span class="ts-breadcrumb__current">{{ $report->report_number }}</span>
                </div>
                <div class="md-head__row">
                    <h1 class="md-head__num">{{ $report->report_number }}</h1>
                    <span class="md-badge md-badge--signed">Firmado</span>
                </div>
                <p class="md-head__sub">Reporte de entrega de material</p>
            </div>
        </div>
        <div class="md-actions">
            <a href="{{ route('customer.deliveries.reports.pdf', $report->id) }}" target="_blank" rel="noopener" class="md-btn md-btn--ghost">Ver PDF</a>
            <a href="{{ route('customer.deliveries.reports.pdf', ['report' => $report->id, 'download' => 1]) }}" class="md-btn">Descargar PDF</a>
        </div>
    </div>

    <div class="md-card">
        <div class="md-card__head">Datos de la entrega</div>
        <div class="md-card__body">
            <div class="md-grid">
                <div class="md-kv">
                    <p class="md-kv__k">Fecha de entrega</p>
                    <p class="md-kv__v">{{ $report->delivery_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div class="md-kv">
                    <p class="md-kv__k">Lugar de entrega</p>
                    <p class="md-kv__v">{{ $report->delivery_location ?: '—' }}</p>
                </div>
                <div class="md-kv">
                    <p class="md-kv__k">Pedido</p>
                    <p class="md-kv__v">
                        @if ($report->salesOrder)
                            <a href="{{ route('customer.deliveries.orders.show', $report->salesOrder->id) }}" class="md-folio">{{ $report->salesOrder->order_number }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div class="md-kv">
                    <p class="md-kv__k">Firmado el</p>
                    <p class="md-kv__v">{{ $report->signed_at?->format('d/m/Y · H:i') ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if ($hasReceiver)
        <div class="md-card">
            <div class="md-card__head">Recibió</div>
            <div class="md-card__body">
                <div class="md-grid">
                    <div class="md-kv"><p class="md-kv__k">Nombre</p><p class="md-kv__v">{{ $report->received_by_name ?: '—' }}</p></div>
                    <div class="md-kv"><p class="md-kv__k">Cargo</p><p class="md-kv__v">{{ $report->received_by_position ?: '—' }}</p></div>
                    <div class="md-kv"><p class="md-kv__k">Teléfono</p><p class="md-kv__v">{{ $report->received_by_phone ?: '—' }}</p></div>
                </div>
            </div>
        </div>
    @endif

    <div class="md-card">
        <div class="md-card__head">Material entregado</div>
        @if ($report->items->isEmpty())
            <div class="md-empty"><p>Este reporte no tiene artículos registrados.</p></div>
        @else
            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Unidad</th>
                            <th class="md-num">Cantidad entregada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->items as $item)
                            <tr>
                                <td style="font-weight:500;color:var(--ts-text-title);">{{ $item->product_name }}</td>
                                <td class="md-muted">{{ $item->product_sku ?: '—' }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td class="md-num" style="font-weight:600;">{{ $fmt($item->quantity_delivered_in_event) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if (filled($report->observations))
        <div class="md-card">
            <div class="md-card__head">Observaciones</div>
            <div class="md-card__body">
                <p class="md-obs">{!! nl2br(e($report->observations)) !!}</p>
            </div>
        </div>
    @endif

    @if ($report->images->isNotEmpty())
        <div class="md-card">
            <div class="md-card__head">Evidencia fotográfica ({{ $report->images->count() }})</div>
            <div class="md-card__body">
                <div class="md-photos">
                    @foreach ($report->images as $img)
                        <a href="{{ $img->url }}" target="_blank" rel="noopener" class="md-photo" title="{{ $img->caption ?: 'Abrir imagen completa' }}">
                            <img src="{{ $img->url }}" alt="{{ $img->caption ?: 'Evidencia de entrega' }}" loading="lazy" decoding="async">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($signature)
        <div class="md-card">
            <div class="md-card__head">Firma de quien recibe</div>
            <div class="md-card__body">
                <div class="md-sign"><img src="{{ $signature }}" alt="Firma de {{ $report->received_by_name ?: 'quien recibe' }}"></div>
            </div>
        </div>
    @endif

</div>
@endsection
