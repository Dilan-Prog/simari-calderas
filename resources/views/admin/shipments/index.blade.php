@extends('admin.layouts.master')

@section('title', 'Envíos - Admin')

@section('content')
<div class="shipments-page">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <div>
            <p class="shipments-eyebrow">Ecommerce · Envíos</p>
            <h1 class="shipments-title">Envíos</h1>
            <p class="shipments-subtitle" style="margin-bottom:0;">
                Rastrea la guía, paquetería y estatus de entrega de cada orden de tienda (<strong>StoreOrder</strong>)
                una vez que sale de almacén.
            </p>
        </div>
        <a href="{{ route('admin.shipments.export', request()->query()) }}" class="button-secondary size-adjustment">Exportar</a>
    </div>

    {{-- KPIs --}}
    @php
        $kpiCards = [
            ['label' => 'Envíos activos',        'color' => '#0f6fbd', 'value' => $kpis['activos'] ?? 0],
            ['label' => 'En tránsito',            'color' => '#6d28d9', 'value' => $kpis['en_transito'] ?? 0],
            ['label' => 'Con incidencia',         'color' => '#c81e1e', 'value' => $kpis['incidencia'] ?? 0],
            ['label' => 'Entregados este mes',    'color' => '#0f7a4f', 'value' => $kpis['entregados_mes'] ?? 0],
        ];
    @endphp
    <div class="shipments-kpi-grid">
        @foreach ($kpiCards as $card)
            <div class="shipments-kpi-card">
                <p class="shipments-kpi-label"><span class="shipments-kpi-dot" style="background:{{ $card['color'] }};"></span>{{ $card['label'] }}</p>
                <p class="shipments-kpi-value">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tabs de estatus. $tabs llega como array plano ['todos' => count,
         'preparando' => count, ...] (ShipmentController::index()) — las
         etiquetas se arman aquí desde ShipmentStatus::META, mismo patrón que
         $orderTabs en admin/orders/index.blade.php con StoreOrderStatus. --}}
    @php
        $shipmentTabLabels = ['todos' => 'Todos'] + collect(\App\Support\ShipmentStatus::META)->map(fn ($m) => $m['label'])->all();
    @endphp
    <div class="shipments-tabs">
        @foreach ($shipmentTabLabels as $tabKey => $tabLabel)
            @php
                $tabQuery = array_filter(
                    array_merge(request()->query(), ['tab' => $tabKey === 'todos' ? null : $tabKey]),
                    fn ($v) => $v !== null && $v !== ''
                );
                $tabActive = $tabKey === 'todos' ? empty($filters['tab'] ?? null) : ($filters['tab'] ?? null) === $tabKey;
            @endphp
            <a href="{{ route('admin.shipments.index', $tabQuery) }}" class="shipments-tab @if ($tabActive) is-active @endif">
                {{ $tabLabel }}
                <span class="shipments-tab-badge">{{ $tabs[$tabKey] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" class="shipments-filterbar">
        <div class="shipments-search">
            <span class="shipments-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por folio, cliente, guía o correo…">
        </div>

        @if (!empty($filters['tab']))
            <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
        @endif

        <select name="carrier_id" class="shipments-filter-select">
            <option value="">Todas las paqueterías</option>
            @foreach ($carriers as $carrier)
                <option value="{{ $carrier->id }}" @selected(($filters['carrier_id'] ?? null) == $carrier->id)>{{ $carrier->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="button-secondary size-adjustment">Filtrar</button>

        @include('admin.components._column_visibility_menu', [
            'tableKey' => 'shipments.index',
            'columnDefs' => [
                'cliente' => 'Cliente / destino',
                'transportista' => 'Transportista',
                'guia' => 'Guía',
                'estatus' => 'Estatus',
                'fecha' => 'Enviado / ETA',
            ],
        ])
    </form>

    {{-- Tabla --}}
    <div class="shipments-table">
        <div class="shipments-table-head">
            <span>Orden</span>
            <span data-col="cliente">Cliente / destino</span>
            <span data-col="transportista">Transportista</span>
            <span data-col="guia">Guía</span>
            <span data-col="estatus">Estatus</span>
            <span data-col="fecha">Enviado / ETA</span>
            <span></span>
        </div>

        @if ($shipments->isEmpty())
            <div class="shipments-table-empty">
                @if (empty(array_filter($filters ?? [])))
                    No hay envíos registrados todavía. Aparecerán aquí en cuanto se registre una guía desde el detalle de una orden.
                @else
                    No se encontraron envíos con estos filtros.
                    <div style="margin-top:12px;">
                        <a href="{{ route('admin.shipments.index') }}" class="button-secondary size-adjustment">Limpiar filtros</a>
                    </div>
                @endif
            </div>
        @else
            <div class="shipments-table-body">
                @foreach ($shipments as $shipment)
                    @php $rowMeta = $statusMeta[$shipment->status] ?? []; @endphp
                    <div class="shipments-table-row" onclick="window.location.href='{{ route('admin.orders.show', $shipment->store_order_id) }}'">
                        <span class="shipments-table-cell shipments-table-folio">{{ $shipment->storeOrder->order_number ?? '—' }}</span>

                        <span class="shipments-table-cell" data-col="cliente">
                            <div class="shipments-table-customer-name">{{ $shipment->storeOrder->contact_name ?? '—' }}</div>
                            <div class="shipments-table-customer-email">{{ $shipment->storeOrder->contact_email ?? '' }}</div>
                        </span>

                        <span class="shipments-table-cell" data-col="transportista">
                            @if ($shipment->carrier)
                                <span class="shipment-carrier-chip">
                                    <span class="shipment-carrier-chip-initials" style="background:{{ $shipment->carrier->bg_color ?? '#f1f2f4' }};color:{{ $shipment->carrier->text_color ?? '#6b7280' }};">{{ $shipment->carrier->initials }}</span>
                                    {{ $shipment->carrier->name }}
                                </span>
                            @else
                                <span style="color:#9ca3af;">Sin transportista</span>
                            @endif
                        </span>

                        <span class="shipments-table-cell" data-col="guia">{{ $shipment->guia ?? '—' }}</span>

                        <span class="shipments-table-cell" data-col="estatus">
                            <span class="shipments-status-pill" style="color:{{ $rowMeta['color'] ?? '#6b7280' }};background:{{ $rowMeta['bg'] ?? '#f1f2f4' }};">
                                <span class="shipments-status-pill-dot"></span>{{ $rowMeta['label'] ?? $shipment->status }}
                            </span>
                        </span>

                        <span class="shipments-table-cell" data-col="fecha">
                            {{ $shipment->fecha_envio ? $shipment->fecha_envio->format('d M Y') : '—' }}
                            @if ($shipment->eta)
                                <div style="font-size:11.5px;color:#9ca3af;">ETA {{ $shipment->eta->format('d M Y') }}</div>
                            @endif
                        </span>

                        <span class="shipments-table-cell shipments-table-actions">
                            <a href="{{ route('admin.orders.show', $shipment->store_order_id) }}" class="shipments-status-trigger" title="Ver envío" onclick="event.stopPropagation()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </span>
                    </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin:14px 4px 0;">
                <p style="margin:0;font-size:13px;color:#6b7280;">
                    Mostrando <strong>{{ $shipments->firstItem() }}–{{ $shipments->lastItem() }}</strong> de <strong>{{ $shipments->total() }}</strong> envíos
                </p>
            </div>
            {{ $shipments->links('admin.components.pagination') }}
        @endif
    </div>

    {{-- Leyenda de estatus --}}
    <div class="shipments-legend" style="margin-top:24px;">
        <p class="shipments-legend-title">Guía de estatus</p>
        <div class="shipments-legend-items">
            @foreach ($statusMeta as $statusKey => $meta)
                <div class="shipments-legend-item">
                    <span class="shipments-status-pill" style="align-self:flex-start;color:{{ $meta['color'] }};background:{{ $meta['bg'] }};">
                        <span class="shipments-status-pill-dot"></span>{{ $meta['label'] }}
                    </span>
                    <p class="shipments-legend-desc">{{ $meta['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/admin/pages/shipments.css'])
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        {{-- Mismo patrón que orders/index.blade.php: script clásico (no
             espera DOMContentLoaded) para que el wrap del menú de columnas
             ya exista en el DOM cuando shipments.js (módulo Vite) lo lee. --}}
        (function () {
            var wrap = document.querySelector('.col-vis-wrap[data-table-key="shipments.index"]');
            if (!wrap) return;
            var savedColumns = @json($visibleColumns);
            wrap.setAttribute('data-saved-columns', JSON.stringify(savedColumns));
            wrap.setAttribute('data-save-columns-url', @json(route('admin.column-preferences.update')));
        })();
    </script>
    @vite(['resources/js/admin/shipments.js'])
@endpush
