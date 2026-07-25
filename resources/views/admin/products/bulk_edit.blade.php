@extends('admin.layouts.master')
@section('title')
    Editar en lote - Productos - Admin
@endsection
@section('content')
    @php
        // Columnas configurables (todo lo editable de un producto salvo
        // imágenes, documentos, slug y dimensiones — ver el plan). Nombre y
        // SKU no viven aquí: van fijos en la tabla, no se pueden ocultar.
        $bulkEditColumns = [
            ['key' => 'model', 'label' => 'Modelo', 'group' => 'Básicos', 'type' => 'text', 'maxlength' => 100],
            ['key' => 'supplier_sku', 'label' => 'SKU Proveedor', 'group' => 'Básicos', 'type' => 'text', 'maxlength' => 100],
            ['key' => 'short_description', 'label' => 'Descripción Corta', 'group' => 'Básicos', 'type' => 'textarea', 'maxlength' => 500],
            ['key' => 'description', 'label' => 'Descripción Completa', 'group' => 'Básicos', 'type' => 'textarea'],

            ['key' => 'price', 'label' => 'Precio', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'compare_price', 'label' => 'Precio Comp.', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'cost', 'label' => 'Costo', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'stock', 'label' => 'Stock', 'group' => 'Precios / Inventario', 'type' => 'number'],
            ['key' => 'stock_unit', 'label' => 'Unidad Stock', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['pieza' => 'Pieza', 'juego' => 'Juego', 'kit' => 'Kit', 'metro' => 'Metro', 'kg' => 'Kg', 'litro' => 'Litro']],
            ['key' => 'currency', 'label' => 'Moneda', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['MXN' => 'MXN', 'USD' => 'USD', 'EUR' => 'EUR']],
            ['key' => 'availability', 'label' => 'Disponibilidad', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['available' => 'Disponible', 'out_of_stock' => 'Agotado', 'on_order' => 'Sobre pedido']],

            ['key' => 'category_id', 'label' => 'Categoría', 'group' => 'Organización', 'type' => 'select-category'],
            ['key' => 'brand_id', 'label' => 'Marca', 'group' => 'Organización', 'type' => 'select-brand'],
            ['key' => 'is_active', 'label' => 'Activo', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'publish_on_website', 'label' => 'Publicar Web', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_featured', 'label' => 'Destacado', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_new', 'label' => 'Nuevo', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_recommended', 'label' => 'Recomendado', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'tags', 'label' => 'Tags', 'group' => 'Organización', 'type' => 'text'],
            ['key' => 'specifications', 'label' => 'Especificaciones', 'group' => 'Organización', 'type' => 'specs'],

            ['key' => 'seo_title', 'label' => 'Título SEO', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 60],
            ['key' => 'seo_description', 'label' => 'Descripción SEO', 'group' => 'SEO / Social', 'type' => 'textarea', 'maxlength' => 160],
            ['key' => 'seo_keywords', 'label' => 'Keywords SEO', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'og_title', 'label' => 'Título Social', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'og_description', 'label' => 'Descripción Social', 'group' => 'SEO / Social', 'type' => 'textarea'],
            ['key' => 'og_image', 'label' => 'Imagen Social (URL)', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
        ];
        // Columnas visibles por defecto la primera vez (antes de que exista
        // algo guardado en localStorage) — el set original + Nombre, para no
        // abrumar con ~27 columnas de entrada.
        $defaultVisibleColumns = ['price', 'compare_price', 'cost', 'stock', 'supplier_sku', 'category_id', 'brand_id', 'is_active', 'publish_on_website'];
        $columnGroups = collect($bulkEditColumns)->groupBy('group');
    @endphp

    <div class="prod-page prod-bulk-edit-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Editar en lote</h1>
                    <p class="prod-subtitle">Edita nombre, precio, costo, stock y (casi) toda la información de varios
                        productos a la vez</p>
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
                    </div>
                </div>
            </form>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper prod-bulk-edit-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <thead>
                        <tr>
                            <th class="prod-bulk-pinned-col">Nombre</th>
                            <th class="prod-bulk-pinned-col">SKU</th>
                            @foreach ($bulkEditColumns as $col)
                                <th data-col="{{ $col['key'] }}">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr data-row-id="{{ $product->id }}">
                                <td class="prod-bulk-pinned-col">
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="name" value="{{ $product->name }}" maxlength="255">
                                </td>
                                <td class="prod-bulk-readonly prod-bulk-pinned-col">{{ $product->sku }}</td>

                                @foreach ($bulkEditColumns as $col)
                                    <td data-col="{{ $col['key'] }}"
                                        @class(['prod-bulk-checkbox-cell' => $col['type'] === 'checkbox'])>
                                        @switch($col['type'])
                                            @case('text')
                                                @php
                                                    // 'tags' es un array (cast del modelo) — se muestra
                                                    // como texto separado por comas, igual que se guarda.
                                                    $rawVal = $product->{$col['key']};
                                                    $textVal = is_array($rawVal) ? implode(', ', $rawVal) : $rawVal;
                                                @endphp
                                                <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="{{ $col['key'] }}" value="{{ $textVal }}"
                                                    @if (!empty($col['maxlength'])) maxlength="{{ $col['maxlength'] }}" @endif>
                                            @break

                                            @case('number')
                                                <input type="number" min="0" class="prod-bulk-input"
                                                    data-id="{{ $product->id }}" data-field="{{ $col['key'] }}"
                                                    value="{{ $product->{$col['key']} }}">
                                            @break

                                            @case('textarea')
                                                <textarea class="prod-bulk-input prod-bulk-textarea-cell" data-id="{{ $product->id }}"
                                                    data-field="{{ $col['key'] }}"
                                                    @if (!empty($col['maxlength'])) maxlength="{{ $col['maxlength'] }}" @endif>{{ $product->{$col['key']} }}</textarea>
                                            @break

                                            @case('select')
                                                <select class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="{{ $col['key'] }}">
                                                    @foreach ($col['options'] as $val => $optLabel)
                                                        <option value="{{ $val }}" @selected($product->{$col['key']} === $val)>
                                                            {{ $optLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @break

                                            @case('select-category')
                                                <select class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="category_id">
                                                    @foreach ($categoryOptions as $opt)
                                                        <option value="{{ $opt['id'] }}" @selected($product->category_id == $opt['id'])>
                                                            {{ $opt['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @break

                                            @case('select-brand')
                                                <select class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="brand_id">
                                                    @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}" @selected($product->brand_id == $brand->id)>
                                                            {{ $brand->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @break

                                            @case('checkbox')
                                                <input type="checkbox" class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="{{ $col['key'] }}" @checked($product->{$col['key']})>
                                            @break

                                            @case('specs')
                                                @php
                                                    $specCount = count(json_decode($product->specifications ?? '[]', true) ?: []);
                                                @endphp
                                                <button type="button" class="prod-bulk-popover-trigger"
                                                    data-id="{{ $product->id }}" data-field="specifications"
                                                    data-specs="{{ $product->specifications ?? '[]' }}">
                                                    Especificaciones ({{ $specCount }})
                                                </button>
                                            @break
                                        @endswitch
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($bulkEditColumns) + 2 }}">
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

    {{-- Popover de Especificaciones — una sola instancia reutilizada por
         cualquier fila, no una por producto. --}}
    <div id="bulkSpecsModal" class="del-confirm-overlay">
        <div class="del-confirm-box prod-bulk-specs-modal-box">
            <h2 class="del-confirm-title">Especificaciones</h2>
            <p class="del-confirm-desc">Agrega o quita pares de nombre/valor técnicos para este producto.</p>

            <div class="pform-placeholder" id="bulkSpecsEmpty">
                <p class="pform-placeholder-title">Sin especificaciones todavía</p>
            </div>
            <div id="bulkSpecsList" class="pform-spec-list" style="display:none"></div>

            <button type="button" class="pform-btn primary" id="bulkSpecsAddBtn" style="margin-top:14px">
                + Agregar campo
            </button>

            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="bulkSpecsCancelBtn">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="bulkSpecsSaveBtn">Guardar</button>
            </div>
        </div>
    </div>

    <div id="prodBulkEditBar" class="prod-bulk-bar">
        <span class="prod-bulk-count"><span id="prodBulkEditCount">0</span> cambio(s) sin guardar</span>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditDiscardBtn">Descartar cambios</button>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditSaveBtn">Guardar cambios</button>
    </div>
@endsection
@include('admin.products._bulk_edit_scripts')
