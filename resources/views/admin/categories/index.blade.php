@extends('admin.layouts.master')
@section('title')
    Categorías de Productos - Admin
@endsection
@section('content')
    <div class="container user-manager">

        {{-- Header --}}
        <section class="clients-manager-section">
            <header class="clients-manager-main">
                <div>
                    <h1>Categorías de Productos</h1>
                    <p class="breadcrumb-clients-manager main">Gestión jerárquica de categorías (3 niveles)</p>
                </div>
                <button type="button" class="button-primary size-adjustment" id="btnNewCategory">
                    + Nueva Categoría
                </button>
            </header>

            {{-- Filters --}}
            <section class="filters-clients-manager">
                <div class="filters-clients-manager-search">
                    <span class="search-icon-clients-manager">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </span>
                    <input type="text" id="categorySearch" placeholder="Buscar categorías...">
                </div>
                <div class="filters-clients-manager-select">
                    <select id="categoryLevelFilter">
                        <option value="all">Todos los niveles</option>
                        <option value="1">Categorías Principales</option>
                        <option value="2">Subcategorías</option>
                        <option value="3">Categorías Hijas</option>
                    </select>
                </div>
            </section>

            {{-- Categories list --}}
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table brand-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;"></th>
                                <th>CATEGORIA</th>
                                <th>NIVEL</th>
                                <th>SLUG</th>
                                <th>DESCRIPCIÓN</th>
                                <th>ORDEN</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="brandsTableBody">
                            @forelse ($categories as $category)
                                <tr class="brand-row" data-name="{{ strtolower($category->name) }}"
                                    data-slug="{{ strtolower($category->slug) }}"
                                    data-status="{{ $category->is_active ? 'active' : 'inactive' }}">

                                    <td class="logo-cell">
                                        @if ($category->logo_url)
                                            <img src="{{ $category->logo_url }}" alt="{{ $category->name }}"
                                                class="brand-logo-img">
                                        @else
                                            <div class="brand-logo-placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <rect width="18" height="18" x="3" y="3" rx="2"
                                                        ry="2" />
                                                    <circle cx="9" cy="9" r="2" />
                                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                                                </svg>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <p class="brand-name">{{ $category->name }}</p>
                                        <span class="brand-products-count">
                                            {{ $category->products_count }} productos
                                        </span>
                                    </td>

                                    <td>
                                        <span class="brand-slug-badge">
                                            {{ $category->slug }}
                                        </span>
                                    </td>

                                    <td>
                                        <p class="brand-description">
                                            {{ $category->description ? Str::limit($category->description, 60) : '—' }}
                                        </p>
                                    </td>

                                    <td>
                                        <span
                                            class="brand-status-badge {{ $category->is_active ? 'status-active' : 'status-inactive' }}"
                                            data-status="{{ $category->is_active ? 'active' : 'inactive' }}">
                                            {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions-container">
                                            <button type="button" class="action-btn btn-edit-brand"
                                                data-id="{{ $category->id }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="action-btn btn-delete-brand"
                                                data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                                data-slug="{{ $category->slug }}" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
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
                                    <td colspan="6" style="text-align:center; padding:40px; color:#6b7280;">
                                        No hay categorias registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>

            {{-- Pagination --}}
            {{-- <div
                style="display:flex;justify-content:space-between;align-items:center;
                    margin-top:16px;padding:0 4px;">
                <p class="breadcrumb-clients-manager">
                    Mostrando <strong>{{ $brands->firstItem() }}–{{ $brands->lastItem() }}</strong>
                    de <strong>{{ $brands->total() }}</strong> marcas
                </p>
                <div style="display:flex;gap:4px;align-items:center;"> --}}
                    {{-- First --}}
                    {{-- <a href="{{ $brands->url(1) }}"
                        class="table-users-manager-action-btn edit {{ $brands->onFirstPage() ? 'disabled' : '' }}"
                        style="font-size:12px;padding:6px 10px;">«</a>
                    <a href="{{ $brands->previousPageUrl() ?? '#' }}"
                        class="table-users-manager-action-btn edit {{ $brands->onFirstPage() ? 'disabled' : '' }}"
                        style="font-size:12px;padding:6px 10px;">‹</a>

                    @foreach ($brands->getUrlRange(max(1, $brands->currentPage() - 2), min($brands->lastPage(), $brands->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}"
                            class="{{ $page == $brands->currentPage() ? 'button-primary' : 'table-users-manager-action-btn edit' }}"
                            style="font-size:12px;padding:6px 10px;min-width:32px;text-align:center;">
                            {{ $page }}
                        </a>
                    @endforeach

                    <a href="{{ $brands->nextPageUrl() ?? '#' }}"
                        class="table-users-manager-action-btn edit {{ !$brands->hasMorePages() ? 'disabled' : '' }}"
                        style="font-size:12px;padding:6px 10px;">›</a>
                    <a href="{{ $brands->url($brands->lastPage()) }}"
                        class="table-users-manager-action-btn edit {{ !$brands->hasMorePages() ? 'disabled' : '' }}"
                        style="font-size:12px;padding:6px 10px;">»</a>
                </div>
            </div> --}}
        </section>

        </section>
        @include('admin.categories.partials._create_edit_modal')
        @include('admin.categories.partials._delete_modal')
        @include('admin.categories.partials._scripts')
    </div>
@endsection
