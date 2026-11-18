@extends('admin.layouts.master')
@section('title')
    Productos - Admin
@endsection
@section('content')
    @php
        // Catálogo de columnas configurables del listado normal (tabla +
        // tarjetas). Contrato coordinado con ProductController::index()
        // ($savedViews) y las rutas products.index-views.* — ver CLAUDE.md.
        // Descompone la cadena de ancestros de una categoría (requiere
        // 'category.parent.parent' precargado) para las columnas de
        // Categoría/Subcategoría/Categoría Hija — mismos 3 niveles que los
        // selects en cascada de crear/editar producto.
        $categoryChainLevel = function (?\App\Models\Category $category, int $level) {
            if (!$category) return null;
            $chain = [];
            $node = $category;
            while ($node) {
                array_unshift($chain, $node);
                $node = $node->parent;
            }
            return $chain[$level - 1]->name ?? null;
        };

        $indexColumns = [
            ['key' => 'sku', 'label' => 'SKU', 'group' => 'Básicos', 'showInGrid' => false, 'dg' => 'text'],
            ['key' => 'category_id', 'label' => 'Categoría', 'group' => 'Organización', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'category_sub', 'label' => 'Subcategoría', 'group' => 'Organización', 'showInGrid' => false, 'dg' => 'list'],
            ['key' => 'category_child', 'label' => 'Categoría Hija', 'group' => 'Organización', 'showInGrid' => false, 'dg' => 'list'],
            ['key' => 'model', 'label' => 'Modelo', 'group' => 'Básicos', 'showInGrid' => true, 'dg' => 'text'],
            ['key' => 'brand_id', 'label' => 'Marca', 'group' => 'Básicos', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'supplier_sku', 'label' => 'SKU Proveedor', 'group' => 'Básicos', 'showInGrid' => false, 'dg' => 'text'],
            ['key' => 'canonical_url', 'label' => 'URL Canónica', 'group' => 'Básicos', 'showInGrid' => false, 'dg' => 'text'],
            ['key' => 'price', 'label' => 'Precio', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'num'],
            ['key' => 'compare_price', 'label' => 'Precio Comp.', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'num'],
            ['key' => 'cost', 'label' => 'Costo', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'num'],
            ['key' => 'stock', 'label' => 'Stock', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'num', 'sum' => 'int'],
            ['key' => 'units_sold', 'label' => 'Vendidos', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'num', 'sum' => 'int'],
            ['key' => 'currency', 'label' => 'Moneda', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'availability', 'label' => 'Disponibilidad', 'group' => 'Precios / Inventario', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'is_active', 'label' => 'Estado', 'group' => 'Organización', 'showInGrid' => false, 'dg' => 'list'],
            ['key' => 'publish_on_website', 'label' => 'Publicado Web', 'group' => 'Organización', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'is_featured', 'label' => 'Destacado', 'group' => 'Organización', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'is_new', 'label' => 'Nuevo', 'group' => 'Organización', 'showInGrid' => true, 'dg' => 'list'],
            ['key' => 'is_recommended', 'label' => 'Recomendado', 'group' => 'Organización', 'showInGrid' => true, 'dg' => 'list'],
        ];
        $defaultVisibleIndexColumns = ['sku', 'category_id', 'price', 'cost', 'stock', 'is_active'];
        $indexColumnGroups = collect($indexColumns)->groupBy('group');
        $indexGridColumns = collect($indexColumns)->where('showInGrid', true);
        $availabilityLabels = ['available' => 'Disponible', 'out_of_stock' => 'Agotado', 'on_order' => 'Sobre pedido'];
        // "Vendidos" (ProductFilters::unitsSoldQuery): cotizaciones aceptadas + pedidos pagados de la tienda.
        $soldOf = fn ($p) => (int) ($unitsSold[$p->id] ?? 0);
        // Valor crudo de cada columna para la hoja de cálculo (data-dg-v: ordenar/filtrar/sumar en la página).
        $dgValue = function ($product, string $key) use ($categoryChainLevel, $availabilityLabels, $soldOf) {
            return match ($key) {
                'sku', 'model', 'supplier_sku', 'canonical_url', 'currency' => (string) ($product->{$key} ?? ''),
                'brand_id' => $product->brand->name ?? '',
                'category_id' => $categoryChainLevel($product->category, 1) ?? '',
                'category_sub' => $categoryChainLevel($product->category, 2) ?? '',
                'category_child' => $categoryChainLevel($product->category, 3) ?? '',
                'price', 'compare_price', 'cost' => $product->{$key} !== null ? (string) (float) $product->{$key} : '',
                'stock' => (string) (int) ($product->stock ?? 0),
                'units_sold' => (string) $soldOf($product),
                'availability' => $availabilityLabels[$product->availability] ?? '',
                'is_active' => $product->is_active ? 'Activo' : 'Inactivo',
                default => $product->{$key} ? 'Sí' : 'No',
            };
        };
        $prodHasPages = $products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages();
    @endphp
    <x-erp.datagrid-assets />
    <div class="prod-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Productos</h1>
                    <p class="prod-subtitle">Gestiona el catálogo de productos industriales</p>
                </div>
                <div class="prod-header-actions">
                    @php
                        // "Exportar" respeta los filtros activos (marca, categoría, búsqueda,
                        // proveedor…): con filtros exporta solo esos productos; sin filtros, todo.
                        // "sort" no es filtro: solo ordena el archivo (ProductFilters::applySort).
                        $prodExportFilters = collect(request()->except(['page', 'per_page', 'view', 'sort']))
                            ->filter(fn ($v) => is_array($v) ? count(array_filter($v, fn ($x) => $x !== null && $x !== '')) > 0 : ($v !== null && $v !== ''))
                            ->all();
                        $prodExportParams = $prodExportFilters + ($sortCurrent !== '' ? ['sort' => $sortCurrent] : []);
                    @endphp
                    <button type="button" class="prod-btn-outline" data-download-url="{{ route('admin.products.export', $prodExportParams) }}"
                        title="{{ $prodExportFilters ? 'Exporta los ' . $totalFiltered . ' productos que cumplen los filtros actuales' : 'Exporta todo el catálogo' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 15V3" />
                            <path d="m7 10 5 5 5-5" />
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        </svg>
                        {{ $prodExportFilters ? 'Exportar filtrados (' . $totalFiltered . ')' : 'Exportar' }}
                    </button>
                    <button class="prod-btn-outline" type="button" id="btnOpenImportModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v12" />
                            <path d="m7 8 5-5 5 5" />
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        </svg>
                        Importar Productos
                    </button>
                    @permiso('products','edit')
                    {{-- Lleva los filtros/búsqueda actuales (sin la página): el editor por lotes usa la
                         misma consulta filtrada (ProductFilters), así que abre justo el mismo conjunto. --}}
                    <a href="{{ route('admin.products.bulk-edit', request()->except(['page'])) }}" class="prod-btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                        Editar en lote
                    </a>
                    @endpermiso
                    @permiso('products','create')
                    <button class="prod-btn-new" type="button"
                        onclick="window.location.href='{{ route('admin.products.create') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Nuevo Producto
                    </button>
                    @endpermiso
                </div>
            </div>

            <form class="prod-toolbar" id="prodFilterForm" method="GET">
                @include('admin.products.partials._search_box', ['inputId' => 'prodSearch'])
                <select name="category_id" id="prodCategoryFilter" class="prod-filter-select">
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <select name="stock" id="prodStockFilter" class="prod-filter-select">
                    <option value="all">Todo el stock</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>En stock</option>
                    <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>Agotados</option>
                    <option value="low" @selected(request('stock') === 'low')>Stock bajo</option>
                </select>
                <select name="status" id="prodStatusFilter" class="prod-filter-select">
                    <option value="all">Todos los estados</option>
                    <option value="active" @selected(request('status') === 'active')>Activos</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
                </select>
                @include('admin.products.partials._advanced_filters', ['filtersKey' => 'prod'])
                {{-- Ordenar por: server-side (?sort=…, ProductFilters::SORTS). Viaja en el query string,
                     así lo conservan la paginación, los filtros y los filtros guardados. --}}
                <select name="sort" id="prodSortSelect" class="prod-filter-select" aria-label="Ordenar por" title="Ordenar por">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sortCurrent === $value)>{{ $value === '' ? 'Ordenar por: predeterminado' : 'Ordenar: ' . $label }}</option>
                    @endforeach
                </select>
                <select name="per_page" id="prodPerPage" class="prod-filter-select" aria-label="Productos por página" title="Productos por página">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPageCurrent === (string) $option)>{{ $option }} por página</option>
                    @endforeach
                    <option value="all" @selected($perPageCurrent === 'all')>Todos</option>
                </select>
                <div class="prod-bulk-views-wrap">
                    <select id="prodIndexViewSelect" class="prod-filter-select prod-bulk-view-select">
                        <option value="">Vista personalizada</option>
                        @foreach ($savedViews as $view)
                            <option value="{{ $view->id }}" data-columns="{{ json_encode($view->columns) }}">{{ $view->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="prod-bulk-columns-wrap">
                    <button type="button" class="prod-filter-select prod-bulk-columns-btn" id="prodIndexColumnsBtn">Columnas</button>
                    <div class="prod-bulk-columns-menu" id="prodIndexColumnsMenu">
                        @foreach ($indexColumnGroups as $group => $cols)
                            <div class="prod-bulk-columns-group">
                                <p class="prod-bulk-columns-group-title">{{ $group }}</p>
                                @foreach ($cols as $col)
                                    <label class="prod-bulk-columns-item">
                                        <input type="checkbox" class="prod-index-col-toggle" value="{{ $col['key'] }}"
                                            @checked(in_array($col['key'], $defaultVisibleIndexColumns, true))>
                                        {{ $col['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        <div class="prod-bulk-view-actions">
                            <div class="prod-bulk-view-save-row">
                                <input type="text" id="prodIndexViewNameInput" class="pform-input prod-bulk-view-name-input"
                                    placeholder="Nombre de la nueva vista" maxlength="60">
                                <button type="button" class="pform-btn primary" id="prodIndexViewSaveBtn">Guardar como vista nueva</button>
                            </div>
                            <div class="prod-bulk-view-manage-row" id="prodIndexViewManageRow" style="display:none">
                                <button type="button" class="button-secondary size-adjustment" id="prodIndexViewUpdateBtn">Actualizar vista actual</button>
                                <button type="button" class="button-secondary size-adjustment" id="prodIndexViewDeleteBtn">Eliminar vista actual</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="prod-view-toggle">
                    <button class="prod-view-btn" id="btnViewGrid" data-view="grid" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M3 9h18M3 15h18M9 3v18M15 3v18" />
                        </svg>
                    </button>
                    <button class="prod-view-btn active" id="btnViewList" data-view="list" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 12h.01M3 18h.01M3 6h.01M8 12h13M8 18h13M8 6h13" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="prod-content-area">

            {{-- List view --}}
            {{-- FIX BUG 12: wrapped the table in .products-table-wrapper so the
                 module CSS can scope the mobile card transformation and the
                 vertical scroll container without touching global styles. --}}
            <div class="prod-list-container" id="prodListView">
                {{-- Vista de hoja de cálculo (x-erp.datagrid-assets / erp-datagrid.js): nº de fila, Σ, encabezado fijo y
                     ordenar/filtrar por columna dentro de la página. data-dg-nocols: las columnas visibles las sigue
                     controlando el menú «Columnas» + vistas guardadas de esta pantalla (no el del componente). --}}
                <div class="products-table-wrapper prod-index-dg">
                <table class="prod-table" data-dg="productos-listado" data-dg-nocols data-dg-noun="productos"
                    data-dg-sum-label="{{ $prodHasPages ? 'Total de esta página' : 'Total general' }}">
                    <thead>
                        <tr>
                            <th data-dg-skip class="prod-th-check"><input type="checkbox" id="prodSelectAllList" class="prod-row-checkbox" aria-label="Seleccionar todos"></th>
                            <th data-dg-key="product" data-dg-type="text">Producto</th>
                            @foreach ($indexColumns as $col)
                                <th data-col="{{ $col['key'] }}" data-dg-key="{{ $col['key'] }}" data-dg-type="{{ $col['dg'] }}"
                                    @if (!empty($col['sum'])) data-dg-sum="{{ $col['sum'] }}" @endif
                                    @class(['prod-col-hidden' => !in_array($col['key'], $defaultVisibleIndexColumns, true), 'is-num' => $col['dg'] === 'num'])>{{ $col['label'] }}</th>
                            @endforeach
                            <th data-dg-skip>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="prodTableBody">
                        @forelse ($products as $product)
                            @php
                                $statusVal = $product->is_active ? 'active' : 'inactive';
                                $statusLabel = $product->is_active ? 'Activo' : 'Inactivo';
                                $statusClass = $product->is_active ? 'published' : 'draft';
                                $stockClass = $product->stock > 0 ? 'in-stock' : 'out-stock';
                                $thumbUrl = $product->cover_image_url ?? $product->images->first()?->url;
                            @endphp
                            <tr data-dg-row data-name="{{ strtolower($product->name) }}" data-sku="{{ strtolower($product->sku) }}"
                                data-status="{{ $statusVal }}">
                                <td data-label="" class="prod-td-check">
                                    <input type="checkbox" class="prod-row-checkbox" data-id="{{ $product->id }}">
                                </td>
                                <td data-label="Producto" data-dg-v="{{ $product->name }}">
                                    <div class="prod-cell-product">
                                        <div class="prod-thumb">
                                            @if ($thumbUrl)
                                                <img src="{{ $thumbUrl }}" alt="{{ $product->name }}"
                                                    style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                                                    <path d="M12 22V12" />
                                                    <polyline points="3.29 7 12 12 20.71 7" />
                                                    <path d="m7.5 4.27 9 5.15" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="prod-name">{{ $product->name }}</p>
                                            <p class="prod-category-label">SKU: {{ $product->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($indexColumns as $col)
                                    <td data-col="{{ $col['key'] }}" data-label="{{ $col['label'] }}" data-dg-v="{{ $dgValue($product, $col['key']) }}"
                                        @class(['prod-col-hidden' => !in_array($col['key'], $defaultVisibleIndexColumns, true), 'is-num' => $col['dg'] === 'num'])>
                                        @switch($col['key'])
                                            @case('sku') {{ $product->sku }} @break
                                            @case('model') {{ $product->model ?: '—' }} @break
                                            @case('brand_id') {{ $product->brand->name ?? '—' }} @break
                                            @case('supplier_sku') {{ $product->supplier_sku ?: '—' }} @break
                                            @case('canonical_url') {{ $product->canonical_url ?: '—' }} @break
                                            @case('category_id') {{ $categoryChainLevel($product->category, 1) ?? '—' }} @break
                                            @case('category_sub') {{ $categoryChainLevel($product->category, 2) ?? '—' }} @break
                                            @case('category_child') {{ $categoryChainLevel($product->category, 3) ?? '—' }} @break
                                            @case('price') <span class="prod-price">${{ number_format($product->price, 0) }}</span> @break
                                            @case('compare_price') {{ $product->compare_price ? '$'.number_format($product->compare_price, 0) : '—' }} @break
                                            @case('cost') <span class="prod-price">${{ number_format($product->cost ?? 0, 0) }}</span> @break
                                            @case('stock')
                                                <span class="prod-stock {{ $stockClass }}">{{ $product->stock ?? 0 }} unidades</span>
                                                @break
                                            @case('units_sold') {{ number_format($soldOf($product)) }} @break
                                            @case('currency') {{ $product->currency ?: '—' }} @break
                                            @case('availability')
                                                {{ $availabilityLabels[$product->availability] ?? '—' }}
                                                @break
                                            @case('is_active')
                                                <span class="prod-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                                @break
                                            @case('publish_on_website')
                                            @case('is_featured')
                                            @case('is_new')
                                            @case('is_recommended')
                                                <span class="prod-badge {{ $product->{$col['key']} ? 'published' : 'draft' }}">{{ $product->{$col['key']} ? 'Sí' : 'No' }}</span>
                                                @break
                                        @endswitch
                                    </td>
                                @endforeach
                                <td data-label="Acciones" class="prod-td-actions">
                                    <div class="prod-actions">
                                        @permiso('products','edit')
                                        <button class="prod-action-btn edit" type="button"
                                            onclick="window.location.href='{{ route('admin.products.edit', ['id' => $product->id, 'return_to' => request()->fullUrl()]) }}'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                            </svg>
                                        </button>
                                        @endpermiso
                                        @permiso('products','delete')
                                        <button class="prod-action-btn delete btn-delete-product" type="button"
                                            data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-sku="{{ $product->sku }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                <line x1="10" x2="10" y1="11" y2="17" />
                                                <line x1="14" x2="14" y1="11" y2="17" />
                                            </svg>
                                        </button>
                                        @endpermiso
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($indexColumns) + 3 }}">
                                    <p class="prod-empty">No se encontraron productos con los filtros actuales.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- Grid view --}}
            <label class="prod-grid-select-all" id="prodGridSelectAllBar">
                <input type="checkbox" id="prodSelectAllGrid" class="prod-row-checkbox">
                Seleccionar todos
            </label>
            <div class="prod-grid-container" id="prodGridView">
                @foreach ($products as $product)
                    @php
                        $statusVal = $product->is_active ? 'active' : 'inactive';
                        $statusClass = $product->is_active ? 'published' : 'draft';
                        $statusLabel = $product->is_active ? 'Activo' : 'Inactivo';
                    @endphp
                    <div class="prod-grid-card" data-name="{{ strtolower($product->name) }}"
                        data-sku="{{ strtolower($product->sku) }}" data-status="{{ $statusVal }}">
                        <input type="checkbox" class="prod-row-checkbox prod-grid-card-checkbox" data-id="{{ $product->id }}">
                        <div class="prod-grid-thumb">
                            @php $thumbUrl = $product->cover_image_url ?? $product->images->first()?->url; @endphp
                            @if ($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="{{ $product->name }}"
                                    style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                                    <path d="M12 22V12" />
                                    <polyline points="3.29 7 12 12 20.71 7" />
                                    <path d="m7.5 4.27 9 5.15" />
                                </svg>
                            @endif
                        </div>
                        <div class="prod-grid-info">
                            <p class="prod-grid-name">{{ $product->name }}</p>
                            <p class="prod-grid-cat">SKU: {{ $product->sku }}</p>
                        </div>
                        <div class="prod-grid-meta">
                            @foreach ($indexGridColumns as $col)
                                <div class="prod-grid-meta-row" data-col="{{ $col['key'] }}"
                                    @class(['prod-col-hidden' => !in_array($col['key'], $defaultVisibleIndexColumns, true)])>
                                    <span class="prod-grid-meta-label">{{ $col['label'] }}</span>
                                    <span class="prod-grid-meta-val {{ $col['key'] === 'price' ? 'price' : '' }}">
                                        @switch($col['key'])
                                            @case('category_id') {{ $categoryChainLevel($product->category, 1) ?? '—' }} @break
                                            @case('model') {{ $product->model ?: '—' }} @break
                                            @case('brand_id') {{ $product->brand->name ?? '—' }} @break
                                            @case('price') ${{ number_format($product->price, 0) }} @break
                                            @case('compare_price') {{ $product->compare_price ? '$'.number_format($product->compare_price, 0) : '—' }} @break
                                            @case('cost') ${{ number_format($product->cost ?? 0, 0) }} @break
                                            @case('stock')
                                                <span style="color:{{ ($product->stock ?? 0) > 0 ? '#16a34a' : '#dc2626' }}">{{ $product->stock ?? 0 }} unidades</span>
                                                @break
                                            @case('units_sold') {{ number_format($soldOf($product)) }} @break
                                            @case('currency') {{ $product->currency ?: '—' }} @break
                                            @case('availability')
                                                {{ ['available' => 'Disponible', 'out_of_stock' => 'Agotado', 'on_order' => 'Sobre pedido'][$product->availability] ?? '—' }}
                                                @break
                                            @case('publish_on_website')
                                            @case('is_featured')
                                            @case('is_new')
                                            @case('is_recommended')
                                                {{ $product->{$col['key']} ? 'Sí' : 'No' }}
                                                @break
                                        @endswitch
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="prod-grid-footer">
                            <span class="prod-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            <div class="prod-actions">
                                @permiso('products','edit')
                                <button class="prod-action-btn edit" type="button"
                                    onclick="window.location.href='{{ route('admin.products.edit', ['id' => $product->id, 'return_to' => request()->fullUrl()]) }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                    </svg>
                                </button>
                                @endpermiso
                                @permiso('products','delete')
                                <button class="prod-action-btn delete btn-delete-product" type="button"
                                    data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                    data-sku="{{ $product->sku }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                        <line x1="10" x2="10" y1="11" y2="17" />
                                        <line x1="14" x2="14" y1="11" y2="17" />
                                    </svg>
                                </button>
                                @endpermiso
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="prod-summary-bar">
                <span id="prodCountLabel">Mostrando {{ $products->count() }} de {{ $totalFiltered }} productos</span>
                @if ($sortCurrent !== '')
                    <span @if (in_array($sortCurrent, ['best_selling', 'least_selling'], true)) title="Vendidos = unidades en cotizaciones aceptadas + pedidos pagados de la tienda en línea" @endif>Orden: {{ $sortOptions[$sortCurrent] }}</span>
                @endif
                {{-- FIX BUG 11: stock_unit is now a real column (BUG 9), and the
                     controller selects it. --}}
                <span>Total en inventario: {{ $stockSum }} {{ $firstStockUnit ?? 'unidades' }}</span>
            </div>

            @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->links('admin.components.pagination') }}
            @endif
        </div>
    </div>
@endsection
@include('admin.products.partials._delete_modal')
@include('admin.products.partials._import_modal')
@include('admin.products.partials._bulk_action_bar')
@include('admin.products.partials._bulk_delete_modal')
@include('admin.components._download_format_modal')
@include('admin.products._scripts')

@push('scripts')
    <script src="{{ asset('js/admin/download-format.js') }}"></script>
    <script>
        initDownloadFormatModal();
    </script>
@endpush
