@extends('admin.layouts.master')
@section('title')
    Editar en lote - Productos - Admin
@endsection
@section('content')
    <div class="prod-page prod-bulk-edit-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Editar en lote</h1>
                    <p class="prod-subtitle">Edita precio, costo, stock y otros campos de varios productos a la vez</p>
                </div>
                <div class="prod-header-actions">
                    <a href="{{ route('admin.products.index') }}" class="prod-btn-outline">
                        ← Volver a Productos
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
                <select name="category_id" id="bulkEditCategoryFilter" class="prod-filter-select">
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <select name="stock" id="bulkEditStockFilter" class="prod-filter-select">
                    <option value="all">Todo el stock</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>En stock</option>
                    <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>Agotados</option>
                </select>
                <select name="status" id="bulkEditStatusFilter" class="prod-filter-select">
                    <option value="all">Todos los estados</option>
                    <option value="active" @selected(request('status') === 'active')>Activos</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
                </select>
                <select name="per_page" id="bulkEditPerPage" class="prod-filter-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) request('per_page', 25) === (string) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>SKU</th>
                            <th>Precio</th>
                            <th>Precio Comp.</th>
                            <th>Costo</th>
                            <th>Stock</th>
                            <th>SKU Proveedor</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Activo</th>
                            <th>Publicar Web</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr data-row-id="{{ $product->id }}">
                                <td class="prod-bulk-readonly">{{ $product->name }}</td>
                                <td class="prod-bulk-readonly">{{ $product->sku }}</td>
                                <td>
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="price" value="{{ $product->price }}">
                                </td>
                                <td>
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="compare_price" value="{{ $product->compare_price }}">
                                </td>
                                <td>
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="cost" value="{{ $product->cost }}">
                                </td>
                                <td>
                                    <input type="number" min="0" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="stock" value="{{ $product->stock }}">
                                </td>
                                <td>
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="supplier_sku" value="{{ $product->supplier_sku }}">
                                </td>
                                <td>
                                    <select class="prod-bulk-input" data-id="{{ $product->id }}" data-field="category_id">
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($product->category_id == $category->id)>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="prod-bulk-input" data-id="{{ $product->id }}" data-field="brand_id">
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected($product->brand_id == $brand->id)>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="prod-bulk-checkbox-cell">
                                    <input type="checkbox" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="is_active" @checked($product->is_active)>
                                </td>
                                <td class="prod-bulk-checkbox-cell">
                                    <input type="checkbox" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="publish_on_website" @checked($product->publish_on_website)>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
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
@include('admin.products._bulk_edit_scripts')
