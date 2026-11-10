@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@push('styles')
<style>
/* ── Teléfono (≤768px): todo acotado a .so-index; escritorio sin cambios ── */
@media (max-width: 768px) {
    .so-index { max-width: 100%; overflow-x: clip; }
    .so-index * { box-sizing: border-box; }
    .so-index .clients-manager-section { padding: 12px; }

    /* Encabezado */
    .so-index .header-of-purchase-orders-section { flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 16px; }
    .so-index .content-of-header-of-purchase-orders-section { min-width: 0; }
    .so-index .content-of-header-of-purchase-orders-section h1 { margin: 6px 0; font-size: 22px; line-height: 1.2; }
    .so-index .breadcrumb-clients-manager { overflow-wrap: anywhere; }
    .so-index .so-head-tools { width: 100%; }
    .so-index .so-head-tools .col-vis-wrap { width: 100%; }
    .so-index .so-head-tools .col-vis-btn { width: 100%; min-height: 44px; justify-content: center; font-size: 14px; }

    /* Tarjetas de estadísticas: 2 × 2 */
    .so-index .so-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 10px !important; margin-bottom: 16px !important; }
    .so-index .so-stats .stat-card-of-purchase-orders-section { padding: 12px; gap: 10px; min-width: 0; align-items: flex-start; }
    .so-index .so-stats .stat-card-of-purchase-orders-section > div:first-child { width: 36px !important; height: 36px !important; border-radius: 10px !important; }
    .so-index .so-stats .stat-card-of-purchase-orders-section > div:last-child { min-width: 0; }
    .so-index .so-stats h2 { font-size: 1.4rem !important; line-height: 1.1; }
    .so-index .so-stats p { font-size: 12.5px !important; line-height: 1.3; overflow-wrap: anywhere; }

    /* Búsqueda */
    .so-index .so-search { max-width: none !important; width: 100%; margin-bottom: 12px !important; }
    .so-index .so-search input { width: 100%; font-size: 16px; min-height: 44px; }

    /* Tabla → tarjetas */
    .so-index .table-container-clients-manager { border: 0; box-shadow: none; border-radius: 0; overflow: visible; }
    .so-index .clients-manager-table.brand-table { display: none; }
    .so-index .table-scroll { max-height: none; overflow: visible; }
    .so-index .so-table, .so-index .so-table tbody { display: block; width: 100%; table-layout: auto; }
    .so-index .so-table tbody tr.so-row {
        position: relative; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px 12px;
        grid-template-areas: "folio status" "cliente cliente" "cotizacion productos" "fecha ver";
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid #e5e7eb !important; border-radius: 12px;
        background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
    }
    .so-index .so-table tbody tr.so-row:last-child { margin-bottom: 0; }
    .so-index .so-table tbody tr.so-row:active { background: #fafbfc; }
    .so-index .so-table tbody tr.so-row > td { display: block; padding: 0 !important; border: 0; min-width: 0; font-size: 13px; overflow-wrap: anywhere; }
    .so-index .so-row td[data-label]::before {
        content: attr(data-label); display: block; margin-bottom: 3px;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af;
    }
    .so-index .so-c-folio { grid-area: folio; align-self: center; }
    .so-index .so-c-folio p { font-size: 15px !important; }
    .so-index .so-c-status { grid-area: status; justify-self: end; align-self: center; text-align: right; max-width: 100%; }
    .so-index .so-c-status span { white-space: normal; line-height: 1.35; }
    .so-index .so-c-cliente { grid-area: cliente; }
    .so-index .so-c-cliente p { font-size: 15px !important; line-height: 1.3; }
    .so-index .so-c-cotizacion { grid-area: cotizacion; padding-top: 10px !important; border-top: 1px solid #f1f5f9 !important; }
    .so-index .so-c-productos { grid-area: productos; padding-top: 10px !important; border-top: 1px solid #f1f5f9 !important; }
    .so-index .so-c-fecha { grid-area: fecha; align-self: end; }
    .so-index .so-c-ver { grid-area: ver; justify-self: end; align-self: end; }
    /* Enlace de la cotización por encima del enlace que cubre toda la tarjeta */
    .so-index .so-c-cotizacion a { position: relative; z-index: 2; display: inline-block; padding: 6px 0; }
    /* "Ver": botón táctil y toda la tarjeta navegable */
    .so-index .so-c-ver .table-users-manager-action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; position: static; overflow: visible; width: auto; height: auto; min-width: 44px; min-height: 44px; padding: 0 14px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #ff6213; }
    .so-index .so-c-ver .table-users-manager-action-btn svg { margin: 0; }
    .so-index .so-c-ver .table-users-manager-action-btn::after { content: ""; position: absolute; inset: 0; border-radius: 12px; z-index: 1; }
    .so-index .so-ver-text { display: inline; }
    /* Columnas ocultas por el menú de columnas: siguen ocultas en tarjeta */
    .so-index .so-row > td.col-hidden { display: none !important; }

    /* Estado vacío */
    .so-index .so-table tbody tr.so-empty { display: block; }
    .so-index .so-table tbody tr.so-empty > td { display: block; padding: 32px 16px !important; border: 1px dashed #e5e7eb; border-radius: 12px; }

    /* Pie: conteo + paginación apilados */
    .so-index .so-count { flex-direction: column; justify-content: center !important; text-align: center; padding: 0 !important; margin-top: 12px !important; }
    .so-index .admin-pagination { justify-content: center; margin-top: 10px; }
    .so-index .admin-pagination-list { flex-wrap: wrap; justify-content: center; gap: 4px; }
    .so-index .admin-pagination-btn { min-width: 40px; min-height: 40px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; }
    .so-index .admin-pagination-dots { min-width: auto; }
}
</style>
@endpush
@section('title')
    Pedidos - Admin
@endsection
@section('content')
<div class="container user-manager so-index">
<section class="clients-manager-section">

    {{-- Header --}}
    <div class="header-of-purchase-orders-section">
        <div class="content-of-header-of-purchase-orders-section">
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Pedidos</strong>
            </p>
            <h1>Pedidos</h1>
            <p class="breadcrumb-clients-manager">Generados automáticamente al aceptar cotizaciones de productos</p>
        </div>
        <div class="so-head-tools" style="display:flex; align-items:center; gap:10px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'sales-orders.index',
                'columnDefs' => [
                    'cliente' => 'Cliente',
                    'cotizacion' => 'Cotización',
                    'productos' => 'Productos',
                    'estado' => 'Estado',
                    'fecha' => 'Fecha',
                ],
            ])
        </div>
    </div>

    {{-- Stats --}}
    <div class="so-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        @php
            $statCards = [
                ['color' => '#ff6213', 'bg' => '#fff3ec', 'value' => $stats['total'],     'label' => 'Total pedidos'],
                ['color' => '#d97706', 'bg' => '#fffbeb', 'value' => $stats['pendiente'], 'label' => 'Pendientes'],
                ['color' => '#2563eb', 'bg' => '#eff6ff', 'value' => $stats['parcial'],   'label' => 'Parcialmente entregados'],
                ['color' => '#16a34a', 'bg' => '#f0fdf4', 'value' => $stats['entregado'], 'label' => 'Entregados'],
            ];
        @endphp
        @foreach ($statCards as $card)
        <div class="stat-card-of-purchase-orders-section">
            <div style="width:48px;height:48px;background:{{ $card['bg'] }};border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;
                border:1px solid {{ $card['color'] }}30;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="{{ $card['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;color:#111827;">{{ $card['value'] }}</h2>
                <p style="margin:0;font-size:14px;font-weight:600;color:#374151;">{{ $card['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" class="so-search" style="margin-bottom:16px;max-width:360px;">
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio o cliente..." value="{{ request('search') }}">
    </form>

    {{-- Table --}}
    <main class="table-container-clients-manager">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>FOLIO</th>
                    <th data-col="cliente">CLIENTE</th>
                    <th data-col="cotizacion">COTIZACIÓN</th>
                    <th data-col="productos">PRODUCTOS</th>
                    <th data-col="estado">ESTADO</th>
                    <th data-col="fecha">FECHA</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table so-table">
                <tbody>
                @forelse ($orders as $order)
                    @php
                        $statusConfig = match($order->status) {
                            'pendiente'              => ['label' => 'Pendiente',              'color' => '#d97706', 'bg' => '#fffbeb'],
                            'en_preparacion'         => ['label' => 'En preparación',          'color' => '#2563eb', 'bg' => '#eff6ff'],
                            'parcialmente_entregado' => ['label' => 'Parcialmente entregado',  'color' => '#2563eb', 'bg' => '#eff6ff'],
                            'entregado'              => ['label' => 'Entregado',               'color' => '#16a34a', 'bg' => '#f0fdf4'],
                            'cancelado'              => ['label' => 'Cancelado',               'color' => '#dc2626', 'bg' => '#fef2f2'],
                            default                  => ['label' => $order->status,            'color' => '#6b7280', 'bg' => '#f3f4f6'],
                        };
                        $customerName = $order->customer
                            ? trim($order->customer->first_name . ' ' . $order->customer->last_name)
                            : '—';
                    @endphp
                    <tr class="so-row" style="border-bottom:1px solid #f1f5f9;">
                        <td class="so-c-folio" style="padding:14px 16px;">
                            <p style="margin:0;font-weight:700;color:#ff6213;font-size:14px;">{{ $order->order_number }}</p>
                        </td>
                        <td class="so-c-cliente" style="padding:14px 16px;" data-col="cliente" data-label="Cliente">
                            <p style="margin:0;font-weight:600;font-size:14px;color:#111827;">{{ $customerName }}</p>
                        </td>
                        <td class="so-c-cotizacion" style="padding:14px 16px;" data-col="cotizacion" data-label="Cotización">
                            @if ($order->quote)
                                <a href="{{ route('admin.quotes.show', $order->quote) }}" style="font-size:13px;color:#ff6213;">{{ $order->quote->quote_number }}</a>
                            @else
                                <span style="font-size:13px;color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td class="so-c-productos" style="padding:14px 16px;" data-col="productos" data-label="Productos">
                            <p style="margin:0;font-size:13px;color:#6b7280;">{{ $order->items_count }} producto{{ $order->items_count !== 1 ? 's' : '' }}</p>
                        </td>
                        <td class="so-c-status" style="padding:14px 16px;" data-col="estado">
                            <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;
                                background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['color'] }};">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td class="so-c-fecha" style="padding:14px 16px;" data-col="fecha" data-label="Fecha">
                            <p style="margin:0;font-size:13px;color:#374151;">{{ $order->created_at->format('j M Y') }}</p>
                        </td>
                        <td class="so-c-ver" style="padding:14px 16px;">
                            <a href="{{ route('admin.sales-orders.show', $order) }}" class="table-users-manager-action-btn edit" title="Ver" aria-label="Ver pedido {{ $order->order_number }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                <span class="so-ver-text" hidden>Ver</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="so-empty">
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            No hay pedidos registrados todavía. Se generan automáticamente al aceptar una cotización con productos.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="so-count" style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
                <p style="margin:0;font-size:13px;color:#6b7280;">
                    Mostrando <strong>{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</strong>
                    de <strong>{{ $orders->total() }}</strong> pedidos
                </p>
            </div>
            {{ $orders->links('admin.components.pagination') }}
        @endif
    </main>
</section>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'sales-orders.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
