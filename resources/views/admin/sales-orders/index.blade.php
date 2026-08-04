@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@section('title')
    Pedidos - Admin
@endsection
@section('content')
<div class="container user-manager">
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
        <div style="display:flex; align-items:center; gap:10px;">
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
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
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
    <form method="GET" style="margin-bottom:16px;max-width:360px;">
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
            <table class="clients-manager-table">
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
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px;">
                            <p style="margin:0;font-weight:700;color:#ff6213;font-size:14px;">{{ $order->order_number }}</p>
                        </td>
                        <td style="padding:14px 16px;" data-col="cliente">
                            <p style="margin:0;font-weight:600;font-size:14px;color:#111827;">{{ $customerName }}</p>
                        </td>
                        <td style="padding:14px 16px;" data-col="cotizacion">
                            @if ($order->quote)
                                <a href="{{ route('admin.quotes.show', $order->quote) }}" style="font-size:13px;color:#ff6213;">{{ $order->quote->quote_number }}</a>
                            @else
                                <span style="font-size:13px;color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="padding:14px 16px;" data-col="productos">
                            <p style="margin:0;font-size:13px;color:#6b7280;">{{ $order->items_count }} producto{{ $order->items_count !== 1 ? 's' : '' }}</p>
                        </td>
                        <td style="padding:14px 16px;" data-col="estado">
                            <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;
                                background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['color'] }};">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td style="padding:14px 16px;" data-col="fecha">
                            <p style="margin:0;font-size:13px;color:#374151;">{{ $order->created_at->format('j M Y') }}</p>
                        </td>
                        <td style="padding:14px 16px;">
                            <a href="{{ route('admin.sales-orders.show', $order) }}" class="table-users-manager-action-btn edit" title="Ver">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            No hay pedidos registrados todavía. Se generan automáticamente al aceptar una cotización con productos.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
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
