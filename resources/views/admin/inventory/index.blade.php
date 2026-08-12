@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/inventory.css')
@endpush
@section('title')
    Inventario - Admin
@endsection
@section('content')
    <div class="container user-manager inv-page">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Inventario
                    </p>
                    <h1>Inventario</h1>
                    <p class="breadcrumb-clients-manager main">Movimientos de entrada, salida y ajuste de stock por almacén</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    @include('admin.components._column_visibility_menu', [
                        'tableKey' => 'inventory-movements.index',
                        'columnDefs' => [
                            'reference' => 'Referencia',
                            'notes' => 'Notas',
                        ],
                    ])
                    <a href="{{ route('admin.inventory.warehouses.index') }}" class="prod-btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="5" x="2" y="3" rx="1" />
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                        Almacenes
                    </a>
                    @permiso('inventory', 'edit')
                    <a href="{{ route('admin.inventory.bulk-edit') }}" class="prod-btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                        Edición masiva de stock
                    </a>
                    @endpermiso
                    @permiso('inventory', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewMovement">
                        + Registrar movimiento
                    </button>
                    @endpermiso
                </div>
            </header>

            {{-- Filters --}}
            <form class="filters-clients-manager inv-filters" id="invFilterForm" method="GET" style="margin-bottom:20px; flex-wrap:wrap;">
                <div class="filters-clients-manager-select">
                    <select name="warehouse_id">
                        <option value="">Todos los almacenes</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filters-clients-manager-select">
                    <select name="movement_type">
                        <option value="">Todos los tipos</option>
                        <option value="entrada" @selected(request('movement_type') === 'entrada')>Entrada</option>
                        <option value="salida" @selected(request('movement_type') === 'salida')>Salida</option>
                        <option value="ajuste" @selected(request('movement_type') === 'ajuste')>Ajuste</option>
                    </select>
                </div>
                <div class="filters-clients-manager-search inv-product-filter-wrap">
                    <input type="text" id="invProductFilterSearch" autocomplete="off"
                        placeholder="Filtrar por producto (nombre o SKU)..."
                        value="{{ request('product_id') ? collect($products)->firstWhere('id', (int) request('product_id'))?->name : '' }}">
                    <input type="hidden" name="product_id" id="invProductFilterId" value="{{ request('product_id') }}">
                    <div class="inv-product-dropdown" id="invProductFilterDropdown"></div>
                </div>
                <input type="text" name="reference_type" class="pform-input" placeholder="Tipo de referencia..."
                    value="{{ request('reference_type') }}" style="max-width:180px;">
                <input type="date" name="date_from" class="pform-input" value="{{ request('date_from') }}" style="max-width:160px;">
                <input type="date" name="date_to" class="pform-input" value="{{ request('date_to') }}" style="max-width:160px;">
                <button type="submit" class="button-secondary size-adjustment">Filtrar</button>
                @if (request()->anyFilled(['warehouse_id', 'movement_type', 'product_id', 'reference_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.inventory.index') }}" class="button-secondary size-adjustment">Limpiar</a>
                @endif
            </form>

            {{-- Table --}}
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table inv-table">
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>ALMACÉN</th>
                                <th>PRODUCTO</th>
                                <th>TIPO</th>
                                <th>CANTIDAD</th>
                                <th data-col="reference">REFERENCIA</th>
                                <th data-col="notes">NOTAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td>{{ optional($movement->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $movement->warehouse->name ?? '—' }}</td>
                                    <td>
                                        <p class="brand-name" style="margin:0;">{{ $movement->product->name ?? '—' }}</p>
                                        @if ($movement->product?->sku)
                                            <span class="brand-products-count">{{ $movement->product->sku }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="inv-type-badge inv-type-{{ $movement->movement_type }}">
                                            {{ ucfirst($movement->movement_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $movement->quantity }}</td>
                                    <td data-col="reference">
                                        {{ $movement->reference_type ? $movement->reference_type . ($movement->reference_id ? ' #' . $movement->reference_id : '') : '—' }}
                                    </td>
                                    <td data-col="notes">{{ $movement->notes ? Str::limit($movement->notes, 60) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:40px; color:#6b7280;">
                                        No hay movimientos de inventario con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>

            {{-- Pagination --}}
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
                <p class="breadcrumb-clients-manager">
                    Mostrando <strong>{{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }}</strong>
                    de <strong>{{ $movements->total() }}</strong> movimientos
                </p>
            </div>
            {{ $movements->links('admin.components.pagination') }}
        </section>
    </div>

    @include('admin.inventory.partials._modal_movement')

@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'inventory-movements.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
@endsection
@include('admin.inventory.partials._scripts_index')
