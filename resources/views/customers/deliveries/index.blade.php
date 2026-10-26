@extends('customers.layouts.master')

@section('title', 'Entregas de Material')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.deliveries._styles')
@endpush

@section('content')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $paginator = $tab === 'pedidos' ? $orders : $reports;
    $hasFilters = $status || $year || $search !== '';
@endphp
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Entregas de Material</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Entregas de Material</h1>
            <p class="ts-subtitle">Consulta tus pedidos de entrega y los reportes de entrega firmados</p>
        </div>
    </div>

    <div class="md-tabs" role="tablist">
        <a href="{{ route('customer.deliveries.index', ['tab' => 'pedidos']) }}"
           class="md-tab {{ $tab === 'pedidos' ? 'md-tab--active' : '' }}" role="tab" aria-selected="{{ $tab === 'pedidos' ? 'true' : 'false' }}">
            Pedidos <span class="md-tab__count">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('customer.deliveries.index', ['tab' => 'reportes']) }}"
           class="md-tab {{ $tab === 'reportes' ? 'md-tab--active' : '' }}" role="tab" aria-selected="{{ $tab === 'reportes' ? 'true' : 'false' }}">
            Reportes de entrega <span class="md-tab__count">{{ $reportsTotal }}</span>
        </a>
    </div>

    @if ($tab === 'pedidos')

        <div class="md-stats">
            <div class="md-stat"><p class="md-stat__label">Total de pedidos</p><p class="md-stat__value">{{ $stats['total'] }}</p></div>
            <div class="md-stat"><p class="md-stat__label">En preparación</p><p class="md-stat__value">{{ $stats['preparacion'] }}</p></div>
            <div class="md-stat"><p class="md-stat__label">Parcialmente entregados</p><p class="md-stat__value">{{ $stats['parcial'] }}</p></div>
            <div class="md-stat"><p class="md-stat__label">Entregados</p><p class="md-stat__value">{{ $stats['entregado'] }}</p></div>
        </div>

        <form method="GET" action="{{ route('customer.deliveries.index') }}" class="md-filters">
            <input type="hidden" name="tab" value="pedidos">
            <input type="search" name="q" class="md-input" value="{{ $search }}"
                   placeholder="Buscar por folio, producto o cotización" maxlength="100">
            <select name="status" class="md-select" aria-label="Estado">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ \App\Models\SalesOrder::statusLabel($s) }}</option>
                @endforeach
            </select>
            <select name="year" class="md-select" aria-label="Año">
                <option value="">Todos los años</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="md-btn">Filtrar</button>
            @if ($hasFilters)
                <a href="{{ route('customer.deliveries.index', ['tab' => 'pedidos']) }}" class="md-btn md-btn--ghost">Limpiar</a>
            @endif
        </form>

        <div class="md-card">
            @if ($orders->isEmpty())
                <div class="md-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    @if ($hasFilters)
                        <p>No encontramos pedidos con esos filtros.</p>
                    @else
                        <p>Aún no tienes pedidos de entrega de material.</p>
                    @endif
                </div>
            @else
                <div class="md-table-wrap">
                    <table class="md-table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Cotización</th>
                                <th class="md-num">Artículos</th>
                                <th>Avance de entrega</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $o)
                                @php
                                    $ordered   = (float) ($o->qty_ordered ?? 0);
                                    $delivered = (float) ($o->qty_delivered ?? 0);
                                    $pct       = $ordered > 0 ? (int) min(100, round($delivered / $ordered * 100)) : 0;
                                @endphp
                                <tr>
                                    <td><a href="{{ route('customer.deliveries.orders.show', $o->id) }}" class="md-folio">{{ $o->order_number }}</a></td>
                                    <td>{{ $o->created_at?->format('d/m/Y') }}</td>
                                    <td><span class="md-badge md-badge--{{ $o->status }}">{{ \App\Models\SalesOrder::statusLabel($o->status) }}</span></td>
                                    <td>{{ $o->quote?->quote_number ?? '—' }}</td>
                                    <td class="md-num">{{ $o->items_count }}</td>
                                    <td>
                                        <div class="md-progress">
                                            <div class="md-progress__bar"><div class="md-progress__fill {{ $pct >= 100 ? 'md-progress__fill--done' : '' }}" style="width:{{ $pct }}%"></div></div>
                                            <div class="md-progress__text">{{ $fmt($delivered) }} de {{ $fmt($ordered) }} piezas ({{ $pct }}%)</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    @else

        <div class="md-card">
            @if ($reports->isEmpty())
                <div class="md-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    <p>Aún no hay reportes de entrega firmados.</p>
                    <p class="md-sub">Cuando recibas material y firmes de conformidad, el reporte aparecerá aquí.</p>
                </div>
            @else
                <div class="md-table-wrap">
                    <table class="md-table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha de entrega</th>
                                <th>Pedido</th>
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
                                    <td>
                                        @if ($r->salesOrder)
                                            <a href="{{ route('customer.deliveries.orders.show', $r->salesOrder->id) }}" class="md-folio">{{ $r->salesOrder->order_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
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

    @endif

    @if ($paginator && $paginator->hasPages())
        <div class="md-card" style="margin-top:-.5rem;">
            <div class="md-pager" style="border-top:none;">
                <span>Mostrando <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong></span>
                <div class="md-pager__nav">
                    @php
                        $cur   = $paginator->currentPage();
                        $last  = $paginator->lastPage();
                        $start = max(1, $cur - 2);
                        $end   = min($last, $cur + 2);
                    @endphp
                    <a href="{{ $paginator->previousPageUrl() ?: '#' }}" class="md-page {{ $paginator->onFirstPage() ? 'md-page--off' : '' }}" aria-label="Página anterior">&lsaquo;</a>
                    @for ($p = $start; $p <= $end; $p++)
                        <a href="{{ $paginator->url($p) }}" class="md-page {{ $p === $cur ? 'md-page--active' : '' }}">{{ $p }}</a>
                    @endfor
                    <a href="{{ $paginator->nextPageUrl() ?: '#' }}" class="md-page {{ $paginator->hasMorePages() ? '' : 'md-page--off' }}" aria-label="Página siguiente">&rsaquo;</a>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
