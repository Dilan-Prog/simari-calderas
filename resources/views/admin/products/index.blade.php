@extends('admin.layouts.master')
@section('title')
    Productos - Admin
@endsection
@section('content')

<div class="prod-page">

    {{-- ── Page header + toolbar ── --}}
    <div class="prod-page-header">

        <div class="prod-header-top">
            <div>
                <h1 class="prod-title">Productos</h1>
                <p class="prod-subtitle">Gestiona el catálogo de productos industriales</p>
            </div>
            <button class="prod-btn-new" type="button" onclick="window.location.href='{{ route('admin.products.create') }}'">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Nuevo Producto
            </button>
        </div>

        <div class="prod-toolbar">

            {{-- Search --}}
            <div class="prod-search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    type="text"
                    id="prodSearch"
                    class="prod-search-input"
                    placeholder="Buscar por nombre o SKU..."
                    autocomplete="off"
                />
            </div>

            {{-- Category filter --}}
            <select id="prodCategoryFilter" class="prod-filter-select">
                <option value="all">Todas las categorías</option>
                <option value="calderas-industriales">Calderas Industriales</option>
                <option value="bombas-de-calor">Bombas de Calor</option>
                <option value="calentadores-solares">Calentadores Solares</option>
            </select>

            {{-- Status filter --}}
            <select id="prodStatusFilter" class="prod-filter-select">
                <option value="all">Todos los estados</option>
                <option value="published">Publicados</option>
                <option value="draft">Borradores</option>
                <option value="out-of-stock">Agotados</option>
            </select>

            {{-- View toggle --}}
            <div class="prod-view-toggle">
                <button class="prod-view-btn" id="btnViewGrid" data-view="grid" type="button" title="Vista cuadrícula">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2"/>
                        <path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>
                    </svg>
                </button>
                <button class="prod-view-btn active" id="btnViewList" data-view="list" type="button" title="Vista lista">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h.01M3 18h.01M3 6h.01M8 12h13M8 18h13M8 6h13"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- ── Scrollable content ── --}}
    <div class="prod-content-area">

        {{-- List view --}}
        <div class="prod-list-container" id="prodListView">
            <table class="prod-table">
                <colgroup>
                    <col style="width:30%">
                    <col style="width:10%">
                    <col style="width:16%">
                    <col style="width:11%">
                    <col style="width:11%">
                    <col style="width:12%">
                    <col style="width:10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th>Marca / Modelo</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="prodTableBody">

                    {{-- Row 1: Hyperion 500 --}}
                    <tr data-name="caldera industrial hyperion 500"
                        data-sku="hyp-500"
                        data-category="calderas-industriales"
                        data-status="published">
                        <td>
                            <div class="prod-cell-product">
                                <div class="prod-thumb">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                        <path d="M12 22V12"/>
                                        <polyline points="3.29 7 12 12 20.71 7"/>
                                        <path d="m7.5 4.27 9 5.15"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="prod-name">Caldera Industrial Hyperion 500</p>
                                    <p class="prod-category-label">Calderas Industriales</p>
                                </div>
                            </div>
                        </td>
                        <td>HYP-500</td>
                        <td>
                            <p class="prod-brand-name">SIMARI</p>
                            <p class="prod-brand-model">Hyperion 500</p>
                        </td>
                        <td><span class="prod-price">$45,000</span></td>
                        <td><span class="prod-stock in-stock">5 unidades</span></td>
                        <td><span class="prod-badge published">Publicado</span></td>
                        <td>
                            <div class="prod-actions">
                                <button class="prod-action-btn edit" type="button" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </button>
                                <button class="prod-action-btn delete" type="button" title="Eliminar"
                                    data-product-name="Caldera Industrial Hyperion 500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" x2="10" y1="11" y2="17"/>
                                        <line x1="14" x2="14" y1="11" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 2: Rinnai 20HP --}}
                    <tr data-name="bomba de calor rinnai 20hp"
                        data-sku="rin-bc-20"
                        data-category="bombas-de-calor"
                        data-status="published">
                        <td>
                            <div class="prod-cell-product">
                                <div class="prod-thumb">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                        <path d="M12 22V12"/>
                                        <polyline points="3.29 7 12 12 20.71 7"/>
                                        <path d="m7.5 4.27 9 5.15"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="prod-name">Bomba de Calor Rinnai 20HP</p>
                                    <p class="prod-category-label">Bombas de Calor</p>
                                </div>
                            </div>
                        </td>
                        <td>RIN-BC-20</td>
                        <td>
                            <p class="prod-brand-name">Rinnai</p>
                            <p class="prod-brand-model">BC-20HP</p>
                        </td>
                        <td><span class="prod-price">$32,000</span></td>
                        <td><span class="prod-stock in-stock">8 unidades</span></td>
                        <td><span class="prod-badge published">Publicado</span></td>
                        <td>
                            <div class="prod-actions">
                                <button class="prod-action-btn edit" type="button" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </button>
                                <button class="prod-action-btn delete" type="button" title="Eliminar"
                                    data-product-name="Bomba de Calor Rinnai 20HP">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" x2="10" y1="11" y2="17"/>
                                        <line x1="14" x2="14" y1="11" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Row 3: Solar 300L --}}
                    <tr data-name="calentador solar 300l"
                        data-sku="sol-300"
                        data-category="calentadores-solares"
                        data-status="out-of-stock">
                        <td>
                            <div class="prod-cell-product">
                                <div class="prod-thumb">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                        <path d="M12 22V12"/>
                                        <polyline points="3.29 7 12 12 20.71 7"/>
                                        <path d="m7.5 4.27 9 5.15"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="prod-name">Calentador Solar 300L</p>
                                    <p class="prod-category-label">Calentadores Solares</p>
                                </div>
                            </div>
                        </td>
                        <td>SOL-300</td>
                        <td>
                            <p class="prod-brand-name">SIMARI</p>
                            <p class="prod-brand-model">Solar-300</p>
                        </td>
                        <td><span class="prod-price">$15,000</span></td>
                        <td><span class="prod-stock out-stock">0 unidades</span></td>
                        <td><span class="prod-badge out-of-stock">Agotado</span></td>
                        <td>
                            <div class="prod-actions">
                                <button class="prod-action-btn edit" type="button" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                </button>
                                <button class="prod-action-btn delete" type="button" title="Eliminar"
                                    data-product-name="Calentador Solar 300L">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" x2="10" y1="11" y2="17"/>
                                        <line x1="14" x2="14" y1="11" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Empty state row (hidden by default) --}}
                    <tr id="prodEmptyRow" style="display:none;">
                        <td colspan="7">
                            <p class="prod-empty">No se encontraron productos con los filtros actuales.</p>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        {{-- Grid view (hidden by default) --}}
        <div class="prod-grid-container" id="prodGridView">

            {{-- Card 1 --}}
            <div class="prod-grid-card"
                data-name="caldera industrial hyperion 500"
                data-sku="hyp-500"
                data-category="calderas-industriales"
                data-status="published">
                <div class="prod-grid-thumb">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                        <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>
                    </svg>
                </div>
                <div class="prod-grid-info">
                    <p class="prod-grid-name">Caldera Industrial Hyperion 500</p>
                    <p class="prod-grid-cat">Calderas Industriales</p>
                </div>
                <div class="prod-grid-meta">
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">SKU</span>
                        <span class="prod-grid-meta-val">HYP-500</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Marca</span>
                        <span class="prod-grid-meta-val">SIMARI / Hyperion 500</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Precio</span>
                        <span class="prod-grid-meta-val price">$45,000</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Stock</span>
                        <span class="prod-grid-meta-val" style="color:#16a34a">5 unidades</span>
                    </div>
                </div>
                <div class="prod-grid-footer">
                    <span class="prod-badge published">Publicado</span>
                    <div class="prod-actions">
                        <button class="prod-action-btn edit" type="button" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                            </svg>
                        </button>
                        <button class="prod-action-btn delete" type="button" title="Eliminar"
                            data-product-name="Caldera Industrial Hyperion 500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Card 2 --}}
            <div class="prod-grid-card"
                data-name="bomba de calor rinnai 20hp"
                data-sku="rin-bc-20"
                data-category="bombas-de-calor"
                data-status="published">
                <div class="prod-grid-thumb">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                        <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>
                    </svg>
                </div>
                <div class="prod-grid-info">
                    <p class="prod-grid-name">Bomba de Calor Rinnai 20HP</p>
                    <p class="prod-grid-cat">Bombas de Calor</p>
                </div>
                <div class="prod-grid-meta">
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">SKU</span>
                        <span class="prod-grid-meta-val">RIN-BC-20</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Marca</span>
                        <span class="prod-grid-meta-val">Rinnai / BC-20HP</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Precio</span>
                        <span class="prod-grid-meta-val price">$32,000</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Stock</span>
                        <span class="prod-grid-meta-val" style="color:#16a34a">8 unidades</span>
                    </div>
                </div>
                <div class="prod-grid-footer">
                    <span class="prod-badge published">Publicado</span>
                    <div class="prod-actions">
                        <button class="prod-action-btn edit" type="button" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                            </svg>
                        </button>
                        <button class="prod-action-btn delete" type="button" title="Eliminar"
                            data-product-name="Bomba de Calor Rinnai 20HP">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Card 3 --}}
            <div class="prod-grid-card"
                data-name="calentador solar 300l"
                data-sku="sol-300"
                data-category="calentadores-solares"
                data-status="out-of-stock">
                <div class="prod-grid-thumb">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                        <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>
                    </svg>
                </div>
                <div class="prod-grid-info">
                    <p class="prod-grid-name">Calentador Solar 300L</p>
                    <p class="prod-grid-cat">Calentadores Solares</p>
                </div>
                <div class="prod-grid-meta">
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">SKU</span>
                        <span class="prod-grid-meta-val">SOL-300</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Marca</span>
                        <span class="prod-grid-meta-val">SIMARI / Solar-300</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Precio</span>
                        <span class="prod-grid-meta-val price">$15,000</span>
                    </div>
                    <div class="prod-grid-meta-row">
                        <span class="prod-grid-meta-label">Stock</span>
                        <span class="prod-grid-meta-val" style="color:#dc2626">0 unidades</span>
                    </div>
                </div>
                <div class="prod-grid-footer">
                    <span class="prod-badge out-of-stock">Agotado</span>
                    <div class="prod-actions">
                        <button class="prod-action-btn edit" type="button" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                            </svg>
                        </button>
                        <button class="prod-action-btn delete" type="button" title="Eliminar"
                            data-product-name="Calentador Solar 300L">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        {{-- Summary bar --}}
        <div class="prod-summary-bar">
            <span id="prodCountLabel">Mostrando 3 de 3 productos</span>
            <span>Total en inventario: 13 unidades</span>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const TOTAL = 3;

    const searchInput      = document.getElementById('prodSearch');
    const categoryFilter   = document.getElementById('prodCategoryFilter');
    const statusFilter     = document.getElementById('prodStatusFilter');
    const btnGrid          = document.getElementById('btnViewGrid');
    const btnList          = document.getElementById('btnViewList');
    const listView         = document.getElementById('prodListView');
    const gridView         = document.getElementById('prodGridView');
    const emptyRow         = document.getElementById('prodEmptyRow');
    const countLabel       = document.getElementById('prodCountLabel');

    const tableRows  = document.querySelectorAll('#prodTableBody tr[data-name]');
    const gridCards  = document.querySelectorAll('#prodGridView .prod-grid-card');

    /* ── Filtering ── */
    function applyFilters() {
        const query    = searchInput.value.trim().toLowerCase();
        const category = categoryFilter.value;
        const status   = statusFilter.value;
        let visible    = 0;

        tableRows.forEach((row, i) => {
            const card      = gridCards[i];
            const nameMatch = row.dataset.name.includes(query) || row.dataset.sku.includes(query);
            const catMatch  = category === 'all' || row.dataset.category === category;
            const stMatch   = status   === 'all' || row.dataset.status   === status;
            const show      = nameMatch && catMatch && stMatch;

            row.style.display  = show ? '' : 'none';
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        emptyRow.style.display = visible === 0 ? '' : 'none';
        countLabel.textContent = `Mostrando ${visible} de ${TOTAL} productos`;
    }

    searchInput.addEventListener('input', applyFilters);
    categoryFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    /* ── View toggle ── */
    function setView(view) {
        if (view === 'grid') {
            listView.style.display = 'none';
            gridView.classList.add('active');
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        } else {
            listView.style.display = '';
            gridView.classList.remove('active');
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        }
    }

    btnGrid.addEventListener('click', () => setView('grid'));
    btnList.addEventListener('click', () => setView('list'));

    /* ── Delete confirmation ── */
    document.querySelectorAll('.prod-action-btn.delete').forEach(btn => {
        btn.addEventListener('click', function () {
            const name = this.dataset.productName || 'este producto';
            if (confirm(`¿Eliminar "${name}"? Esta acción no se puede deshacer.`)) {
                /* placeholder: submit delete request here */
            }
        });
    });
})();
</script>
@endpush
