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
            <div class="card-information-show">
                @forelse ($categories as $category)
                    @php
                        $level = $category->level;
                        $levelLabel = match ($level) {
                            1 => 'Principal',
                            2 => 'Subcategoría',
                            3 => 'Hija',
                            default => 'Principal',
                        };
                        $indent = ($level - 1) * 24;
                    @endphp
                    <div class="category-row" data-name="{{ strtolower($category->name) }}" data-level="{{ $level }}">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke=#3b82f6 stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-folder-tree text-[#0054ff]">
                                <path
                                    d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z">
                                </path>
                                <path
                                    d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z">
                                </path>
                                <path d="M3 5a2 2 0 0 0 2 2h3"></path>
                                <path d="M3 3v13a2 2 0 0 0 2 2h3"></path>
                            </svg>
                            <div>
                                <p style="font-weight:600;font-size:14px;margin:0;">
                                    {{ $category->name }}
                                    <span class="users-manager-badge status" style="margin-left:8px;font-size:11px;">
                                        {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </p>
                                <p class="breadcrumb-clients-manager" style="margin:2px 0 0;">
                                    /{{ $category->slug }}
                                </p>
                            </div>
                        </div>
                        <div class="header-right-user-manager">
                            <button type="button" class="table-users-manager-action-btn edit btn-edit-category"
                                data-id="{{ $category->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                </svg>
                            </button>
                            <button type="button" class="table-users-manager-action-btn delete btn-delete-category"
                                data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                data-slug="{{ $category->slug }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M3 6h18" />
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                    <line x1="10" x2="10" y1="11" y2="17" />
                                    <line x1="14" x2="14" y1="11" y2="17" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="padding:40px;text-align:center;">
                        <p class="breadcrumb-clients-manager">No hay categorías registradas.</p>
                    </div>
                @endforelse
            </div>

        </section>
        @include('admin.categories.partials._create_edit_modal')
        @include('admin.categories.partials._delete_modal')
        @include('admin.categories.partials._scripts')
    </div>
@endsection
