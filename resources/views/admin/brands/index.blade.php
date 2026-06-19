@extends('admin.layouts.master')
@section('title')
    Gestión de Marcas - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Marcas
                    </p>
                    <h1>Gestión de Marcas</h1>
                    <p class="breadcrumb-clients-manager main">Administra las marcas de productos del catálogo</p>
                </div>
                <button type="button" class="button-primary size-adjustment" id="btnNewBrand">
                    + Nueva Marca
                </button>
            </header>

            {{-- Filters --}}
            <section class="filters-clients-manager" style="margin-bottom:20px;">
                <div class="filters-clients-manager-search">
                    <span class="search-icon-clients-manager">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </span>
                    <input type="text" id="brandSearch" placeholder="Buscar por nombre o slug...">
                </div>
                <div class="filters-clients-manager-select">
                    <select id="brandStatusFilter">
                        <option value="all">Todos los estados</option>
                        <option value="active">Activa</option>
                        <option value="inactive">Inactiva</option>
                    </select>
                </div>
            </section>

            {{-- Table --}}
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table brand-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">LOGO</th>
                                <th>MARCA</th>
                                <th>SLUG</th>
                                <th>DESCRIPCIÓN</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="brandsTableBody">
                            @forelse ($brands as $brand)
                                <tr class="brand-row" data-name="{{ strtolower($brand->name) }}"
                                    data-slug="{{ strtolower($brand->slug) }}"
                                    data-status="{{ $brand->is_active ? 'active' : 'inactive' }}">

                                    <td class="logo-cell">
                                        @if ($brand->logo_url)
                                            <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
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
                                        <p class="brand-name">{{ $brand->name }}</p>
                                        <span class="brand-products-count">
                                            {{ $brand->products_count }} productos
                                        </span>
                                    </td>

                                    <td>
                                        <span class="brand-slug-badge">
                                            {{ $brand->slug }}
                                        </span>
                                    </td>

                                    <td>
                                        <p class="brand-description">
                                            {{ $brand->description ? Str::limit($brand->description, 60) : '—' }}
                                        </p>
                                    </td>

                                    <td>
                                        <span
                                            class="brand-status-badge {{ $brand->is_active ? 'status-active' : 'status-inactive' }}"
                                            data-status="{{ $brand->is_active ? 'active' : 'inactive' }}">
                                            {{ $brand->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions-container">
                                            <button type="button" class="action-btn btn-edit-brand"
                                                data-id="{{ $brand->id }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="action-btn btn-delete-brand"
                                                data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                                data-slug="{{ $brand->slug }}" title="Eliminar">
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
                                        No hay marcas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>

            {{-- Pagination --}}
            <div
                style="display:flex;justify-content:space-between;align-items:center;
                    margin-top:16px;padding:0 4px;">
                <p class="breadcrumb-clients-manager">
                    Mostrando <strong>{{ $brands->firstItem() }}–{{ $brands->lastItem() }}</strong>
                    de <strong>{{ $brands->total() }}</strong> marcas
                </p>
                <div style="display:flex;gap:4px;align-items:center;">
                    {{-- First --}}
                    <a href="{{ $brands->url(1) }}"
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
            </div>
        </section>

        {{-- Create/Edit Modal --}}
        <div id="brandModal" class="user-manager-modal client-manage-modal">
            <div class="user-manager-modal-content client-modal-content">
                <div class="user-manager-modal-header">
                    <h2 id="brandModalTitle">Nueva Marca</h2>
                    <button type="button" class="table-users-manager-action-btn cancel" id="closeBrandModal">✕</button>
                </div>

                <div id="brand-modal-errors" class="user-manager-errors" style="display:none;"></div>

                <form class="user-manager-modal-body" id="brandForm">
                    @csrf

                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Nombre <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="name" id="brandName"
                                placeholder="Ej: SIMARI">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">
                                Slug <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="slug" id="brandSlug"
                                placeholder="simari">
                        </div>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">Descripción</label>
                        <textarea class="users-manager-input client-modal-textarea" name="description" id="brandDescription" rows="3"
                            placeholder="Descripción de la marca..."></textarea>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">URL del Logo</label>
                        <input type="text" class="users-manager-input" name="logo_url" id="brandLogoUrl"
                            placeholder="https://...">
                    </div>

                    <div class="user-manager-form" style="margin-top:12px;">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Estado <span style="color:red">*</span>
                            </label>
                            <select class="users-manager-select" name="is_active" id="brandIsActive">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="user-manager-modal-footer">
                        <button type="button" id="cancelBrandModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                        <button type="submit" class="button-primary size-adjustment" id="brandSubmitBtn">Crear
                            Marca</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete modal --}}
        <div id="deleteBrandModal" class="del-confirm-overlay">
            <div class="del-confirm-box">
                <div class="del-confirm-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                        fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                        <path d="M12 9v4" />
                        <path d="M12 17h.01" />
                    </svg>
                </div>
                <h2 class="del-confirm-title">¿Eliminar marca?</h2>
                <p class="del-confirm-desc">Esta acción no se puede deshacer. No puedes eliminar marcas con productos
                    asociados.</p>
                <div class="del-confirm-user-card">
                    <div class="del-confirm-avatar" id="delBrandAvatar">M</div>
                    <div>
                        <p class="del-confirm-user-name" id="delBrandName">Nombre</p>
                        <p class="del-confirm-user-email" id="delBrandSlug">/slug</p>
                    </div>
                </div>
                <div class="del-confirm-actions">
                    <button type="button" class="button-secondary size-adjustment" id="delBrandCancel">Cancelar</button>
                    <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                        id="delBrandConfirm">Eliminar</button>
                </div>
            </div>
        </div>

    </div>
@endsection
@include('admin.brands.partials._scripts')
