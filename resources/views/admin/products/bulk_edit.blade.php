@extends('admin.layouts.master')
@section('title')
    Editar en lote - Productos - Admin
@endsection
@push('scripts')
    <script>window.PRODUCT_VARIABLES = @json(\App\Models\Products::VARIABLE_CATALOG);</script>
@endpush
@section('content')
    @php
        // Columnas configurables (todo lo editable de un producto salvo
        // imágenes, documentos, slug y dimensiones — ver el plan). Nombre y
        // SKU no viven aquí: van fijos en la tabla, no se pueden ocultar.
        $bulkEditColumns = [
            ['key' => 'model', 'label' => 'Modelo', 'group' => 'Básicos', 'type' => 'text', 'maxlength' => 100],
            ['key' => 'short_description', 'label' => 'Descripción Corta', 'group' => 'Básicos', 'type' => 'textarea', 'maxlength' => 500],
            ['key' => 'description', 'label' => 'Descripción Completa', 'group' => 'Básicos', 'type' => 'textarea'],

            ['key' => 'price', 'label' => 'Precio', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'price_includes_tax', 'label' => '¿Precio incluye IVA?', 'group' => 'Precios / Inventario', 'type' => 'checkbox'],
            ['key' => 'compare_price', 'label' => 'Precio Comp.', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'cost', 'label' => 'Costo', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'shipping_cost', 'label' => 'Costo de Envío', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'free_shipping_threshold', 'label' => 'Envío Gratis Desde', 'group' => 'Precios / Inventario', 'type' => 'text'],
            ['key' => 'stock', 'label' => 'Stock', 'group' => 'Precios / Inventario', 'type' => 'number'],
            ['key' => 'stock_unit', 'label' => 'Unidad Stock', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['pieza' => 'Pieza', 'juego' => 'Juego', 'kit' => 'Kit', 'metro' => 'Metro', 'kg' => 'Kg', 'litro' => 'Litro']],
            ['key' => 'currency', 'label' => 'Moneda', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['MXN' => 'MXN', 'USD' => 'USD']],
            ['key' => 'availability', 'label' => 'Disponibilidad', 'group' => 'Precios / Inventario', 'type' => 'select',
                'options' => ['available' => 'Disponible', 'out_of_stock' => 'Agotado', 'on_order' => 'Sobre pedido']],

            ['key' => 'category_id', 'label' => 'Categoría Principal', 'group' => 'Organización', 'type' => 'select-category-main'],
            ['key' => 'category_sub', 'label' => 'Subcategoría', 'group' => 'Organización', 'type' => 'select-category-sub'],
            ['key' => 'category_child', 'label' => 'Categoría Hija', 'group' => 'Organización', 'type' => 'select-category-child'],
            ['key' => 'brand_id', 'label' => 'Marca', 'group' => 'Organización', 'type' => 'select-brand'],
            ['key' => 'is_active', 'label' => 'Activo', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'publish_on_website', 'label' => 'Publicar Web', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_featured', 'label' => 'Destacado', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_new', 'label' => 'Nuevo', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'is_recommended', 'label' => 'Recomendado', 'group' => 'Organización', 'type' => 'checkbox'],
            ['key' => 'tags', 'label' => 'Tags', 'group' => 'Organización', 'type' => 'tags'],
            ['key' => 'specifications', 'label' => 'Especificaciones', 'group' => 'Organización', 'type' => 'specs'],

            ['key' => 'seo_title', 'label' => 'Título SEO', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 60],
            ['key' => 'seo_description', 'label' => 'Descripción SEO', 'group' => 'SEO / Social', 'type' => 'textarea', 'maxlength' => 160],
            ['key' => 'seo_keywords', 'label' => 'Keywords SEO', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'og_title', 'label' => 'Título Social', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'og_description', 'label' => 'Descripción Social', 'group' => 'SEO / Social', 'type' => 'textarea'],
            ['key' => 'og_image', 'label' => 'Imagen Social (URL)', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'canonical_url', 'label' => 'URL Canónica', 'group' => 'SEO / Social', 'type' => 'text', 'maxlength' => 255],
            ['key' => 'faqs', 'label' => 'FAQ', 'group' => 'SEO / Social', 'type' => 'faq'],
        ];
        // Columnas visibles por defecto la primera vez (antes de que exista
        // algo guardado en localStorage) — el set original + Nombre, para no
        // abrumar con ~27 columnas de entrada.
        $defaultVisibleColumns = ['price', 'compare_price', 'cost', 'stock', 'category_id', 'brand_id', 'is_active', 'publish_on_website'];
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

            <button type="button" class="pform-insert-variable-btn" data-variable-target="last-focused">{ } Insertar variable</button>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper prod-bulk-edit-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <colgroup>
                        <col data-col="_select">
                        <col data-col="name">
                        <col data-col="sku">
                        <col data-col="supplier_sku">
                        <col data-col="suppliers">
                        @foreach ($bulkEditColumns as $col)
                            <col data-col="{{ $col['key'] }}">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="prod-bulk-pinned-col" data-col="_select">
                                <input type="checkbox" id="prodBulkSelectAll" title="Seleccionar todos">
                                <span class="prod-bulk-resize-handle" data-resize-col="_select"></span>
                            </th>
                            <th class="prod-bulk-pinned-col" data-col="name">Nombre
                                <span class="prod-bulk-resize-handle" data-resize-col="name"></span>
                            </th>
                            <th class="prod-bulk-pinned-col" data-col="sku">SKU
                                <span class="prod-bulk-resize-handle" data-resize-col="sku"></span>
                            </th>
                            <th data-col="supplier_sku">SKU Proveedor (legacy)
                                <span class="prod-bulk-resize-handle" data-resize-col="supplier_sku"></span>
                            </th>
                            <th data-col="suppliers">Proveedores
                                <span class="prod-bulk-resize-handle" data-resize-col="suppliers"></span>
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
                            @php
                                // Misma lógica de create()/edit(): a partir de la
                                // categoría guardada (que puede ser de nivel 1, 2 o
                                // 3) se reconstruye cuál id va en cada uno de los 3
                                // selectores en cascada de esta fila.
                                $catInitialMainId = null;
                                $catInitialSubId = null;
                                $catInitialChildId = null;
                                if ($product->category) {
                                    $cat = $product->category;
                                    $level = $cat->level;
                                    if ($level === 1) {
                                        $catInitialMainId = $cat->id;
                                    } elseif ($level === 2) {
                                        $catInitialMainId = $cat->parent_id;
                                        $catInitialSubId = $cat->id;
                                    } else {
                                        $catInitialSubId = $cat->parent_id;
                                        $catInitialChildId = $cat->id;
                                        $catInitialMainId = $cat->parent?->parent_id;
                                    }
                                }
                            @endphp
                            <tr data-row-id="{{ $product->id }}">
                                <td class="prod-bulk-pinned-col" data-col="_select">
                                    <input type="checkbox" class="prod-bulk-row-select" value="{{ $product->id }}">
                                </td>
                                <td class="prod-bulk-pinned-col" data-col="name">
                                    <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                        data-field="name" value="{{ $product->name }}" maxlength="255">
                                </td>
                                <td class="prod-bulk-readonly prod-bulk-pinned-col" data-col="sku">{{ $product->sku }}</td>
                                <td class="prod-bulk-readonly" data-col="supplier_sku">{{ $product->supplier_sku }}</td>
                                <td class="prod-bulk-readonly prod-bulk-suppliers-cell" data-col="suppliers">
                                    @forelse ($product->suppliers as $supplier)
                                        <div>{{ $supplier->company_name }}@if ($supplier->pivot->sku) (SKU: {{ $supplier->pivot->sku }})@endif</div>
                                    @empty
                                        —
                                    @endforelse
                                </td>

                                @foreach ($bulkEditColumns as $col)
                                    <td data-col="{{ $col['key'] }}"
                                        @class(['prod-bulk-checkbox-cell' => $col['type'] === 'checkbox'])>
                                        @switch($col['type'])
                                            @case('text')
                                                <input type="text" class="prod-bulk-input" data-id="{{ $product->id }}"
                                                    data-field="{{ $col['key'] }}" value="{{ $product->{$col['key']} }}"
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

                                            @case('select-category-main')
                                                <select class="prod-bulk-input prod-bulk-cat-main" data-id="{{ $product->id }}"
                                                    data-field="category_id">
                                                    <option value="">Seleccionar...</option>
                                                    @foreach ($categoryTree as $rootCat)
                                                        <option value="{{ $rootCat->id }}" @selected($catInitialMainId == $rootCat->id)>
                                                            {{ $rootCat->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @break

                                            @case('select-category-sub')
                                                <select class="prod-bulk-input prod-bulk-cat-sub" data-id="{{ $product->id }}"
                                                    @if (!$catInitialMainId) disabled @endif>
                                                    <option value="">Seleccionar...</option>
                                                    @if ($catInitialMainId)
                                                        @foreach (($categoryTree->firstWhere('id', $catInitialMainId)?->children ?? []) as $subCat)
                                                            <option value="{{ $subCat->id }}" @selected($catInitialSubId == $subCat->id)>
                                                                {{ $subCat->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            @break

                                            @case('select-category-child')
                                                <select class="prod-bulk-input prod-bulk-cat-child" data-id="{{ $product->id }}"
                                                    @if (!$catInitialSubId) disabled @endif>
                                                    <option value="">Seleccionar...</option>
                                                    @if ($catInitialMainId && $catInitialSubId)
                                                        @foreach (($categoryTree->firstWhere('id', $catInitialMainId)?->children?->firstWhere('id', $catInitialSubId)?->children ?? []) as $childCat)
                                                            <option value="{{ $childCat->id }}" @selected($catInitialChildId == $childCat->id)>
                                                                {{ $childCat->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
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

                                            @case('tags')
                                                @php
                                                    $tagsList = is_array($product->tags) ? $product->tags : [];
                                                @endphp
                                                <button type="button" class="prod-bulk-popover-trigger"
                                                    data-id="{{ $product->id }}" data-field="tags"
                                                    data-tags="{{ json_encode($tagsList) }}">
                                                    Tags ({{ count($tagsList) }})
                                                </button>
                                            @break

                                            @case('faq')
                                                @php
                                                    $faqCount = count($product->faqs ?? []);
                                                @endphp
                                                <button type="button" class="prod-bulk-popover-trigger"
                                                    data-id="{{ $product->id }}" data-field="faqs"
                                                    data-faqs="{{ json_encode($product->faqs ?? []) }}">
                                                    FAQ ({{ $faqCount }})
                                                </button>
                                            @break
                                        @endswitch
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($bulkEditColumns) + 5 }}">
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

            {{-- FIX: wrapped left-aligned so it doesn't inherit .del-confirm-box's
                 text-align:center (which still centers the title/description
                 above), and switched from .primary (solid, full-bar looking)
                 to .outline to match the compact size of Cancelar/Guardar. --}}
            <div style="text-align:left; margin-top:14px">
                <button type="button" class="pform-btn outline" id="bulkSpecsAddBtn">
                    + Agregar campo
                </button>
            </div>

            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="bulkSpecsCancelBtn">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="bulkSpecsSaveBtn">Guardar</button>
            </div>
        </div>
    </div>

    {{-- Popover de FAQ — misma idea que el de Especificaciones: una sola
         instancia reutilizada por cualquier fila. --}}
    <div id="bulkFaqModal" class="del-confirm-overlay">
        <div class="del-confirm-box prod-bulk-specs-modal-box">
            <h2 class="del-confirm-title">Preguntas Frecuentes</h2>
            <p class="del-confirm-desc">Agrega o quita preguntas y respuestas para este producto. Puedes usar variables y enlaces hacia otras páginas del sitio.</p>

            <div class="pform-placeholder" id="bulkFaqEmpty">
                <p class="pform-placeholder-title">Sin preguntas todavía</p>
            </div>
            <div id="bulkFaqList" class="pform-spec-list" style="display:none"></div>

            <button type="button" class="pform-btn primary" id="bulkFaqAddBtn" style="margin-top:14px">
                + Agregar pregunta
            </button>

            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="bulkFaqCancelBtn">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="bulkFaqSaveBtn">Guardar</button>
            </div>
        </div>
    </div>

    {{-- Popover de Tags — mismo widget de chips + autocompletado ya usado
         en Crear/Editar producto, adaptado al patrón de popover reutilizable
         de esta pantalla (una sola instancia para cualquier fila). --}}
    <div id="bulkTagsModal" class="del-confirm-overlay">
        <div class="del-confirm-box prod-bulk-specs-modal-box">
            <h2 class="del-confirm-title">Tags</h2>
            <p class="del-confirm-desc">Busca entre tags ya usados en otros productos o escribe uno nuevo y presiona Enter.</p>

            <div class="pform-tag-row">
                <div class="pform-tag-input-wrap">
                    <input type="text" id="bulkTagInput" class="pform-input"
                        placeholder="Ejemplo: eficiente, industrial, premium..." autocomplete="off">
                    <ul class="pform-tag-suggestions" id="bulkTagSuggestions"></ul>
                </div>
                <button type="button" id="bulkTagAdd" class="pform-btn primary">Agregar</button>
            </div>
            <div class="pform-tag-chips" id="bulkTagList"></div>

            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="bulkTagsCancelBtn">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="bulkTagsSaveBtn">Guardar</button>
            </div>
        </div>
    </div>

    {{-- Confirmación de "Descartar cambios" — reemplaza el confirm() nativo
         del navegador por el mismo componente modal usado en el resto de
         esta pantalla. --}}
    <div id="bulkDiscardModal" class="del-confirm-overlay">
        <div class="del-confirm-box">
            <h2 class="del-confirm-title">¿Descartar cambios?</h2>
            <p class="del-confirm-desc" id="bulkDiscardDesc">Se perderán los cambios sin guardar.</p>
            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="bulkDiscardCancelBtn">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="bulkDiscardConfirmBtn">Descartar cambios</button>
            </div>
        </div>
    </div>

    <div id="prodBulkEditBar" class="prod-bulk-bar">
        <span class="prod-bulk-count"><span id="prodBulkEditCount">0</span> cambio(s) sin guardar</span>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditDiscardBtn">Descartar cambios</button>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditSaveBtn">Guardar cambios</button>
    </div>

    {{-- Barra de asignación masiva de proveedor — vincula de golpe los N
         productos seleccionados con 1 proveedor, llevándose su
         "SKU Proveedor (legacy)" como el SKU de ese proveedor. --}}
    <div id="prodSupplierAssignBar" class="prod-supplier-assign-bar">
        <span class="prod-bulk-count"><span id="prodSupplierAssignCount">0</span> producto(s) seleccionado(s)</span>
        <select id="prodSupplierAssignSelect">
            <option value="">Asignar a proveedor...</option>
            @foreach ($activeSuppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
            @endforeach
        </select>
        <button type="button" id="prodSupplierAssignBtn">Asignar</button>
    </div>
@endsection
@include('admin.products._bulk_edit_scripts')
