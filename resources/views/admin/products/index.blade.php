@extends('admin.layouts.master')
@section('title')
    Productos - Admin
@endsection
@section('content')
    <div class="prod-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Productos</h1>
                    <p class="prod-subtitle">Gestiona el catálogo de productos industriales</p>
                </div>
                <div class="prod-header-actions">
                    <a href="{{ route('admin.products.export') }}" class="prod-btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 15V3" />
                            <path d="m7 10 5 5 5-5" />
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        </svg>
                        Exportar
                    </a>
                    <button class="prod-btn-outline" type="button" id="btnOpenImportModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v12" />
                            <path d="m7 8 5-5 5 5" />
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        </svg>
                        Importar Productos
                    </button>
                    <button class="prod-btn-new" type="button"
                        onclick="window.location.href='{{ route('admin.products.create') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        Nuevo Producto
                    </button>
                </div>
            </div>

            <form class="prod-toolbar" id="prodFilterForm" method="GET">
                <div class="prod-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="text" name="search" id="prodSearch" class="prod-search-input"
                        placeholder="Buscar por nombre o SKU..." autocomplete="off"
                        value="{{ request('search') }}" />
                </div>
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
                </select>
                <select name="status" id="prodStatusFilter" class="prod-filter-select">
                    <option value="all">Todos los estados</option>
                    <option value="active" @selected(request('status') === 'active')>Activos</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
                </select>
                <select name="per_page" id="prodPerPage" class="prod-filter-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) request('per_page', 25) === (string) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                    <option value="all" @selected(request('per_page') === 'all')>Todos</option>
                </select>
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
                <div class="products-table-wrapper">
                <table class="prod-table">
                    <colgroup>
                        <col style="width:26%">
                        <col style="width:9%">
                        <col style="width:12%">
                        <col style="width:9%">
                        <col style="width:9%">
                        <col style="width:9%">
                        <col style="width:10%">
                        <col style="width:16%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Costo</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
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
                            <tr data-name="{{ strtolower($product->name) }}" data-sku="{{ strtolower($product->sku) }}"
                                data-status="{{ $statusVal }}">
                                <td data-label="Producto">
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
                                <td data-label="SKU">{{ $product->sku }}</td>
                                <td data-label="Categoría">{{ $product->category->name ?? '—' }}</td>
                                <td data-label="Precio"><span class="prod-price">${{ number_format($product->price, 0) }}</span></td>
                                <td data-label="Costo"><span class="prod-price">${{ number_format($product->cost ?? 0, 0) }}</span></td>
                                <td data-label="Stock">
                                    <span class="prod-stock {{ $stockClass }}">
                                        {{ $product->stock ?? 0 }} unidades
                                    </span>
                                </td>
                                <td data-label="Estado">
                                    <span class="prod-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td data-label="Acciones">
                                    <div class="prod-actions">
                                        <button class="prod-action-btn edit" type="button"
                                            onclick="window.location.href='{{ route('admin.products.edit', $product->id) }}'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                            </svg>
                                        </button>
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <p class="prod-empty">No se encontraron productos con los filtros actuales.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{-- Grid view --}}
            <div class="prod-grid-container" id="prodGridView">
                @foreach ($products as $product)
                    @php
                        $statusVal = $product->is_active ? 'active' : 'inactive';
                        $statusClass = $product->is_active ? 'published' : 'draft';
                        $statusLabel = $product->is_active ? 'Activo' : 'Inactivo';
                    @endphp
                    <div class="prod-grid-card" data-name="{{ strtolower($product->name) }}"
                        data-sku="{{ strtolower($product->sku) }}" data-status="{{ $statusVal }}">
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
                            <div class="prod-grid-meta-row">
                                <span class="prod-grid-meta-label">Categoría</span>
                                <span class="prod-grid-meta-val">{{ $product->category->name ?? '—' }}</span>
                            </div>
                            <div class="prod-grid-meta-row">
                                <span class="prod-grid-meta-label">Precio</span>
                                <span class="prod-grid-meta-val price">${{ number_format($product->price, 0) }}</span>
                            </div>
                            <div class="prod-grid-meta-row">
                                <span class="prod-grid-meta-label">Costo</span>
                                <span class="prod-grid-meta-val">${{ number_format($product->cost ?? 0, 0) }}</span>
                            </div>
                            <div class="prod-grid-meta-row">
                                <span class="prod-grid-meta-label">Stock</span>
                                <span class="prod-grid-meta-val"
                                    style="color:{{ ($product->stock ?? 0) > 0 ? '#16a34a' : '#dc2626' }}">
                                    {{ $product->stock ?? 0 }} unidades
                                </span>
                            </div>
                        </div>
                        <div class="prod-grid-footer">
                            <span class="prod-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            <div class="prod-actions">
                                <button class="prod-action-btn edit" type="button"
                                    onclick="window.location.href='{{ route('admin.products.edit', $product->id) }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                    </svg>
                                </button>
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
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="prod-summary-bar">
                <span id="prodCountLabel">Mostrando {{ $products->count() }} de {{ $totalFiltered }} productos</span>
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
@include('admin.products._scripts')
