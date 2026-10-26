@extends('customers.layouts.master')

@section('title', 'Pedido ' . $order->order_number)

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.deliveries._styles')
@endpush

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $totalPct = $qtyOrdered > 0 ? (int) min(100, round($qtyDelivered / $qtyOrdered * 100)) : 0;
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
@endphp
<div class="ts-page">

    <div class="md-head">
        <div class="md-head__left">
            <a href="{{ route('customer.deliveries.index', ['tab' => 'pedidos']) }}" class="md-back" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:.25rem">
                    <span>Panel de Control</span>
                    {!! $chevron !!}
                    <a href="{{ route('customer.deliveries.index', ['tab' => 'pedidos']) }}" style="color:inherit;text-decoration:none">Entregas de Material</a>
                    {!! $chevron !!}
                    <span class="ts-breadcrumb__current">{{ $order->order_number }}</span>
                </div>
                <div class="md-head__row">
                    <h1 class="md-head__num">{{ $order->order_number }}</h1>
                    <span class="md-badge md-badge--{{ $order->status }}">{{ \App\Models\SalesOrder::statusLabel($order->status) }}</span>
                </div>
                <p class="md-head__sub">
                    Creado el {{ $order->created_at?->format('d/m/Y') }}
                    @if ($order->updated_at && $order->updated_at->format('Y-m-d') !== $order->created_at?->format('Y-m-d'))
                        · Última actualización {{ $order->updated_at->format('d/m/Y') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="md-card">
        <div class="md-card__body">
            <div class="md-grid">
                <div class="md-kv">
                    <p class="md-kv__k">Cotización relacionada</p>
                    <p class="md-kv__v">
                        @if ($order->quote)
                            @if ($quoteLinkable)
                                <a href="{{ route('customer.quotes.show', $order->quote->id) }}" class="md-folio">{{ $order->quote->quote_number }}</a>
                            @else
                                {{ $order->quote->quote_number }}
                            @endif
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div class="md-kv">
                    <p class="md-kv__k">Artículos</p>
                    <p class="md-kv__v">{{ $order->items->count() }}</p>
                </div>
                <div class="md-kv" style="grid-column:span 2;">
                    <p class="md-kv__k">Avance de entrega</p>
                    <div class="md-progress" style="max-width:360px;">
                        <div class="md-progress__bar"><div class="md-progress__fill {{ $totalPct >= 100 ? 'md-progress__fill--done' : '' }}" style="width:{{ $totalPct }}%"></div></div>
                        <div class="md-progress__text">{{ $fmt($qtyDelivered) }} de {{ $fmt($qtyOrdered) }} piezas ({{ $totalPct }}%)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="md-card">
        <div class="md-card__head">Material del pedido</div>
        @if ($order->items->isEmpty())
            <div class="md-empty"><p>Este pedido no tiene artículos registrados.</p></div>
        @else
            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Unidad</th>
                            <th class="md-num">Pedido</th>
                            <th class="md-num">Entregado</th>
                            <th class="md-num">Pendiente</th>
                            <th>Avance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @php
                                $ord  = (float) $item->quantity_ordered;
                                $del  = (float) $item->quantity_delivered;
                                $pend = max(0, $ord - $del);
                                $pct  = $ord > 0 ? (int) min(100, round($del / $ord * 100)) : 0;
                            @endphp
                            <tr>
                                <td style="font-weight:500;color:var(--ts-text-title);">{{ $item->product_name }}</td>
                                <td class="md-muted">{{ $item->product_sku ?: '—' }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td class="md-num">{{ $fmt($ord) }}</td>
                                <td class="md-num">{{ $fmt($del) }}</td>
                                <td class="md-num">{{ $fmt($pend) }}</td>
                                <td>
                                    <div class="md-progress" style="min-width:110px;">
                                        <div class="md-progress__bar"><div class="md-progress__fill {{ $pct >= 100 ? 'md-progress__fill--done' : '' }}" style="width:{{ $pct }}%"></div></div>
                                        <div class="md-progress__text">{{ $pct }}%</div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="md-card">
        <div class="md-card__head">Reportes de entrega firmados</div>
        @if ($reports->isEmpty())
            <div class="md-empty"><p>Aún no hay reportes de entrega firmados para este pedido.</p></div>
        @else
            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha de entrega</th>
                            <th>Recibió</th>
                            <th class="md-num">Artículos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $r)
                            <tr>
                                <td><a href="{{ route('customer.deliveries.reports.show', $r->id) }}" class="md-folio">{{ $r->report_number }}</a></td>
                                <td>{{ $r->delivery_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $r->received_by_name ?: '—' }}</td>
                                <td class="md-num">{{ $r->items_count }}</td>
                                <td>
                                    <div class="md-actions">
                                        <a href="{{ route('customer.deliveries.reports.show', $r->id) }}" class="md-act">Ver</a>
                                        <a href="{{ route('customer.deliveries.reports.pdf', $r->id) }}" target="_blank" rel="noopener" class="md-act md-act--primary">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
