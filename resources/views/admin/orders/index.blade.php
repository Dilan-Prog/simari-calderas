@extends('admin.layouts.master')

@section('title', 'Órdenes - Admin')

@section('content')
<div class="orders-page">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <div>
            <p class="orders-eyebrow">Ventas · Tienda en línea</p>
            <h1 class="orders-title">Órdenes de tienda</h1>
            <p class="orders-subtitle" style="margin-bottom:0;">
                Pedidos generados por clientes desde el checkout público (folio <strong>PW-AAAA-XXXX</strong>).
                Para pedidos B2B nacidos de una cotización, ve a <a href="{{ route('admin.sales-orders.index') }}">Pedidos</a>.
            </p>
        </div>
        <a href="{{ route('admin.orders.export', request()->query()) }}" class="button-secondary size-adjustment">Exportar</a>
    </div>

    {{-- KPIs --}}
    @php
        $kpiCards = [
            ['key' => 'total',       'label' => 'Total de órdenes',   'color' => '#374151', 'status' => null],
            ['key' => 'pendientes',  'label' => 'Pendientes de pago', 'color' => '#6b7280', 'status' => 'pendiente_pago'],
            ['key' => 'pagadas',     'label' => 'Pagadas',            'color' => '#0f6fbd', 'status' => 'pagado'],
            ['key' => 'canceladas',  'label' => 'Canceladas',         'color' => '#c81e1e', 'status' => 'cancelado'],
        ];
    @endphp
    <div class="orders-kpi-grid">
        @foreach ($kpiCards as $card)
            @php
                $kpiQuery = array_filter(array_merge(request()->query(), ['status' => $card['status']]), fn ($v) => $v !== null && $v !== '');
            @endphp
            <a href="{{ route('admin.orders.index', $kpiQuery) }}" class="orders-kpi-card" style="text-decoration:none;">
                <p class="orders-kpi-label"><span class="orders-kpi-dot" style="background:{{ $card['color'] }};"></span>{{ $card['label'] }}</p>
                <p class="orders-kpi-value">{{ $kpis[$card['key']]['count'] }}</p>
                <p class="orders-kpi-sub">${{ number_format($kpis[$card['key']]['sum'], 2) }} MXN</p>
            </a>
        @endforeach
    </div>

    {{-- Tabs de estatus --}}
    @php
        $orderTabs = ['todos' => 'Todas'] + collect(\App\Support\StoreOrderStatus::META)->map(fn ($m) => $m['label'])->all();
    @endphp
    <div class="orders-tabs">
        @foreach ($orderTabs as $tabKey => $tabLabel)
            @php
                $tabQuery = array_filter(
                    array_merge(request()->query(), ['status' => $tabKey === 'todos' ? null : $tabKey]),
                    fn ($v) => $v !== null && $v !== ''
                );
                $tabActive = $tabKey === 'todos' ? empty($filters['status'] ?? null) : ($filters['status'] ?? null) === $tabKey;
            @endphp
            <a href="{{ route('admin.orders.index', $tabQuery) }}" class="orders-tab @if ($tabActive) is-active @endif">
                {{ $tabLabel }}
                <span class="orders-tab-badge">{{ $tabCounts[$tabKey] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" class="orders-filterbar">
        <div class="orders-search">
            <span class="orders-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por folio, cliente, correo o teléfono…">
        </div>

        <select name="status" class="orders-filter-select">
            <option value="">Todos los estatus</option>
            @foreach (\App\Support\StoreOrderStatus::META as $statusKey => $statusMeta)
                <option value="{{ $statusKey }}" @selected(($filters['status'] ?? null) === $statusKey)>{{ $statusMeta['label'] }}</option>
            @endforeach
        </select>

        <div class="orders-date-range">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            <span class="orders-date-sep">–</span>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>

        <button type="submit" class="button-secondary size-adjustment">Filtrar</button>

        @include('admin.components._column_visibility_menu', [
            'tableKey' => 'orders.index',
            'columnDefs' => [
                'cliente' => 'Cliente / contacto',
                'total' => 'Total',
                'metodo' => 'Método de pago',
                'estatus' => 'Estatus',
                'fecha' => 'Creada',
            ],
        ])
    </form>

    {{-- Tabla --}}
    <div class="orders-table">
        <div class="orders-table-head">
            <span>Folio</span>
            <span data-col="cliente">Cliente / contacto</span>
            <span data-col="total">Total</span>
            <span data-col="metodo">Método de pago</span>
            <span data-col="estatus">Estatus</span>
            <span data-col="fecha">Creada</span>
            <span></span>
        </div>

        @if ($orders->isEmpty())
            <div class="orders-table-empty">
                @if (empty(array_filter($filters ?? [])))
                    No hay órdenes registradas todavía. Aparecerán aquí en cuanto un cliente complete una compra en la tienda en línea.
                @else
                    No se encontraron órdenes con estos filtros.
                    <div style="margin-top:12px;">
                        <a href="{{ route('admin.orders.index') }}" class="button-secondary size-adjustment">Limpiar filtros</a>
                    </div>
                @endif
            </div>
        @else
            <div class="orders-table-body">
                @foreach ($orders as $order)
                    @php $rowMeta = \App\Support\StoreOrderStatus::meta($order->status); @endphp
                    <div class="orders-table-row" onclick="window.location.href='{{ route('admin.orders.show', $order) }}'">
                        <span class="orders-table-cell orders-table-folio">{{ $order->order_number }}</span>

                        <span class="orders-table-cell" data-col="cliente">
                            <div class="orders-table-customer-name">
                                {{ $order->customer ? trim($order->customer->first_name . ' ' . $order->customer->last_name) : $order->contact_name }}
                                <span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:6px;margin-left:4px;
                                    @if ($order->customer_id) background:#eef2ff;color:#4338ca; @else background:#f1f2f4;color:#6b7280; @endif">
                                    {{ $order->customer_id ? 'Cuenta' : 'Invitado' }}
                                </span>
                            </div>
                            <div class="orders-table-customer-email">{{ $order->contact_email }}</div>
                        </span>

                        <span class="orders-table-cell orders-table-total" data-col="total">${{ number_format($order->total, 2) }}</span>

                        <span class="orders-table-cell" data-col="metodo">{{ $order->paymentMethod?->name ?? '—' }}</span>

                        <span class="orders-table-cell" data-col="estatus">
                            <span class="orders-status-pill" style="color:{{ $rowMeta['color'] }};background:{{ $rowMeta['bg'] }};">
                                <span class="orders-status-pill-dot"></span>{{ $rowMeta['label'] }}
                            </span>
                        </span>

                        <span class="orders-table-cell" data-col="fecha">{{ $order->created_at->format('d M Y') }}</span>

                        <span class="orders-table-cell orders-table-actions" style="gap:6px;">
                            <a href="{{ route('admin.orders.show', $order) }}" class="orders-status-trigger" title="Ver detalle" onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <button type="button" class="js-open-status-modal orders-status-trigger" title="Cambiar estatus"
                                data-order-id="{{ $order->id }}"
                                data-folio="{{ $order->order_number }}"
                                data-current-status="{{ $order->status }}"
                                data-allowed='@json(\App\Support\StoreOrderStatus::allowedTransitions($order->status))'
                                data-update-url="{{ route('admin.orders.update-status', $order) }}"
                                onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                            </button>
                        </span>
                    </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin:14px 4px 0;">
                <p style="margin:0;font-size:13px;color:#6b7280;">
                    Mostrando <strong>{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</strong> de <strong>{{ $orders->total() }}</strong> órdenes
                </p>
            </div>
            {{ $orders->links('admin.components.pagination') }}
        @endif
    </div>

    {{-- Leyenda de estatus --}}
    <div class="orders-legend" style="margin-top:24px;">
        <p class="orders-legend-title">Guía de estatus</p>
        <div class="orders-legend-items">
            @foreach (\App\Support\StoreOrderStatus::META as $statusKey => $statusMeta)
                <div class="orders-legend-item">
                    <span class="orders-status-pill" style="align-self:flex-start;color:{{ $statusMeta['color'] }};background:{{ $statusMeta['bg'] }};">
                        <span class="orders-status-pill-dot"></span>{{ $statusMeta['label'] }}
                    </span>
                    <p class="orders-legend-desc">{{ $statusMeta['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @include('admin.orders.partials._status_modal')
</div>
@endsection

@push('styles')
    @vite(['resources/css/admin/pages/orders.css'])
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        {{-- Sin envolver en DOMContentLoaded a propósito: orders.js es un
             módulo Vite (defer implícito), corre después de que el HTML ya
             fue parseado pero ANTES de que se dispare DOMContentLoaded — si
             este script esperara ese evento, llegaría tarde y el wrap ya
             se habría leído sin data-saved-columns/data-save-columns-url.
             Como script clásico corre de inmediato al llegar aquí, cuando
             el wrap del menú (renderizado arriba, en @yield('content'))
             ya existe en el DOM. --}}
        (function () {
            var wrap = document.querySelector('.col-vis-wrap[data-table-key="orders.index"]');
            if (!wrap) return;
            var savedColumns = @json($visibleColumns);
            wrap.setAttribute('data-saved-columns', JSON.stringify(savedColumns));
            wrap.setAttribute('data-save-columns-url', @json(route('admin.column-preferences.update')));
        })();
    </script>
    @vite(['resources/js/admin/orders.js'])
@endpush
