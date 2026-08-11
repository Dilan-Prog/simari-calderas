@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/inventory.css')
@endpush
@section('title')
    Edición masiva de stock - Inventario - Admin
@endsection
@section('content')
    @php
        // Columnas configurables — 'name', 'sku' y 'quantity' van fijas/
        // pinneadas en la tabla (mismo patrón que 'name' en Marcas), el resto
        // son informativas y ocultables.
        $bulkEditColumns = [
            ['key' => 'category', 'label' => 'Categoría', 'group' => 'Info'],
            ['key' => 'brand', 'label' => 'Marca', 'group' => 'Info'],
        ];
        $defaultVisibleColumns = collect($bulkEditColumns)->pluck('key')->all();
        $columnGroups = collect($bulkEditColumns)->groupBy('group');
    @endphp

    <div class="prod-page prod-bulk-edit-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Edición masiva de stock</h1>
                    <p class="prod-subtitle">Ajusta el stock de varios productos a la vez para un almacén — cada cambio genera un movimiento de tipo "ajuste"</p>
                </div>
                <div class="prod-header-actions">
                    <a href="{{ route('admin.inventory.index') }}" class="prod-btn-outline">
                        ← Volver a Inventario
                    </a>
                </div>
            </div>

            <form class="prod-toolbar" id="bulkEditFilterForm" method="GET">
                <div class="prod-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="text" name="search" id="bulkEditSearch" class="prod-search-input"
                        placeholder="Buscar por nombre o SKU..." autocomplete="off"
                        value="{{ request('search') }}" />
                </div>
                <select name="warehouse_id" id="bulkEditWarehouseFilter" class="prod-filter-select">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected($warehouseId === $warehouse->id)>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                <select name="per_page" id="bulkEditPerPage" class="prod-filter-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) request('per_page', 25) === (string) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>

                <div class="prod-bulk-views-wrap">
                    <select id="bulkEditViewSelect" class="prod-filter-select prod-bulk-view-select">
                        <option value="">Vista personalizada</option>
                        @foreach ($savedViews as $view)
                            <option value="{{ $view->id }}" data-columns="{{ json_encode($view->columns) }}" data-widths="{{ json_encode($view->widths) }}">
                                {{ $view->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prod-bulk-columns-wrap">
                    <button type="button" class="prod-filter-select prod-bulk-columns-btn" id="bulkEditColumnsBtn">
                        Columnas
                    </button>
                    <div class="prod-bulk-columns-menu" id="bulkEditColumnsMenu">
                        @foreach ($columnGroups as $group => $cols)
                            <div class="prod-bulk-columns-group">
                                <p class="prod-bulk-columns-group-title">{{ $group }}</p>
                                @foreach ($cols as $col)
                                    <label class="prod-bulk-columns-item">
                                        <input type="checkbox" class="prod-bulk-col-toggle" value="{{ $col['key'] }}"
                                            @checked(in_array($col['key'], $defaultVisibleColumns, true))>
                                        {{ $col['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        <div class="prod-bulk-view-actions">
                            <div class="prod-bulk-view-save-row">
                                <input type="text" id="bulkEditViewNameInput" class="pform-input prod-bulk-view-name-input"
                                    placeholder="Nombre de la nueva vista (máx. {{ $savedViews->count() >= 10 ? '10 alcanzado' : '10' }})"
                                    maxlength="60">
                                <button type="button" class="pform-btn primary" id="bulkEditViewSaveBtn">
                                    Guardar como vista nueva
                                </button>
                            </div>
                            <div class="prod-bulk-view-manage-row" id="bulkEditViewManageRow" style="display:none">
                                <button type="button" class="button-secondary size-adjustment" id="bulkEditViewUpdateBtn">
                                    Actualizar vista actual
                                </button>
                                <button type="button" class="button-secondary size-adjustment" id="bulkEditViewDeleteBtn">
                                    Eliminar vista actual
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper prod-bulk-edit-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <colgroup>
                        <col data-col="name">
                        <col data-col="sku">
                        <col data-col="quantity">
                        @foreach ($bulkEditColumns as $col)
                            <col data-col="{{ $col['key'] }}">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="prod-bulk-pinned-col" data-col="name">Producto
                                <span class="prod-bulk-resize-handle" data-resize-col="name"></span>
                            </th>
                            <th data-col="sku">SKU
                                <span class="prod-bulk-resize-handle" data-resize-col="sku"></span>
                            </th>
                            <th data-col="quantity">Stock ({{ $warehouses->firstWhere('id', $warehouseId)?->name }})
                                <span class="prod-bulk-resize-handle" data-resize-col="quantity"></span>
                            </th>
                            @foreach ($bulkEditColumns as $col)
                                <th data-col="{{ $col['key'] }}">{{ $col['label'] }}
                                    <span class="prod-bulk-resize-handle" data-resize-col="{{ $col['key'] }}"></span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr data-row-id="{{ $product->id }}">
                                <td class="prod-bulk-pinned-col" data-col="name">{{ $product->name }}</td>
                                <td data-col="sku">{{ $product->sku }}</td>
                                <td data-col="quantity">
                                    <input type="number" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="quantity" min="0" step="1"
                                        value="{{ $stockMap[$product->id] ?? 0 }}">
                                </td>
                                @foreach ($bulkEditColumns as $col)
                                    <td data-col="{{ $col['key'] }}">
                                        {{ $col['key'] === 'category' ? ($product->category->name ?? '—') : ($product->brand->name ?? '—') }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($bulkEditColumns) + 3 }}">
                                    <p class="prod-empty">No se encontraron productos con los filtros actuales.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="prod-summary-bar">
                <span>Mostrando {{ $products->count() }} de {{ $totalFiltered }} productos</span>
            </div>

            @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->links('admin.components.pagination') }}
            @endif
        </div>
    </div>

    <div id="prodBulkEditBar" class="prod-bulk-bar">
        <span class="prod-bulk-count"><span id="prodBulkEditCount">0</span> cambio(s) sin guardar</span>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditDiscardBtn">Descartar cambios</button>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditSaveBtn">Guardar cambios</button>
    </div>
@endsection
@include('admin.inventory._bulk_edit_scripts')
