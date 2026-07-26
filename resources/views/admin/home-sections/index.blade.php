@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush
@section('title')
    Secciones del Sitio - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    @php
        $page = $page ?? 'home';
        $isProductPage = $page === 'product';

        $pageMeta = [
            'home' => [
                'title'    => 'Gestión de Secciones del Inicio',
                'subtitle' => 'Personaliza los carruseles, banners y el slider del sitio público',
            ],
            'product' => [
                'title'    => 'Secciones de la Página de Producto',
                'subtitle' => 'Personaliza las secciones que aparecen debajo de la información del producto',
            ],
            'collection' => [
                'title'    => 'Secciones de las Páginas de Colección',
                'subtitle' => 'Personaliza las secciones que aparecen debajo del listado en TODAS las páginas de colección',
            ],
        ];
    @endphp

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Secciones del Sitio</strong>
            </p>
            <h1 style="margin:0 0 4px;">{{ $pageMeta[$page]['title'] }}</h1>
            <p class="breadcrumb-clients-manager main">{{ $pageMeta[$page]['subtitle'] }}</p>
        </div>
        <button type="button" class="button-primary size-adjustment" id="btnNewHomeSection"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            + Nueva Sección
        </button>
    </div>

    {{-- Tabs de página --}}
    <div class="hs-page-tabs">
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'inicio']) }}"
            class="hs-page-tab {{ $page === 'home' ? 'is-active' : '' }}">Inicio</a>
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'producto']) }}"
            class="hs-page-tab {{ $page === 'product' ? 'is-active' : '' }}">Página de Producto</a>
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'colecciones']) }}"
            class="hs-page-tab {{ $page === 'collection' ? 'is-active' : '' }}">Colecciones</a>
    </div>

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    <th>TIPO</th>
                    <th>TÍTULO</th>
                    <th>ORDEN</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table" id="homeSectionsTable">
                <tbody id="homeSectionsTableBody">
                @php
                    $typeLabels = [
                        'hero_slider'      => 'Slider Principal',
                        'banner'           => 'Banner',
                        'dual_banner'      => 'Banner Doble',
                        'product_carousel' => 'Carrusel de Productos',
                        'product_carousel_banner' => 'Carrusel con Banner',
                        'category_grid'    => 'Grid de Categorías',
                        'brand_carousel'   => 'Carrusel de Marcas',
                        'html_block'       => 'Bloque HTML',
                        'faq'              => 'Preguntas Frecuentes',
                    ];
                @endphp
                @forelse ($sections as $section)
                    <tr class="hs-row" data-id="{{ $section->id }}" draggable="true">
                        <td style="padding:12px 8px;text-align:center;color:#9ca3af;cursor:grab;" class="hs-drag-handle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="hs-type-badge" data-type="{{ $section->type }}">{{ $typeLabels[$section->type] ?? $section->type }}</span>
                        </td>
                        <td style="padding:12px 16px;font-weight:500;">{{ $section->title ?? '—' }}</td>
                        <td style="padding:12px 16px;text-align:center;color:#374151;" class="hs-sort-order">{{ $section->sort_order }}</td>
                        <td style="padding:12px 16px;">
                            <span class="users-manager-badge {{ $section->is_active ? 'status' : 'status-inactive' }}">
                                {{ $section->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="header-right-user-manager">
                                @if ($section->type === 'hero_slider')
                                    <a href="{{ route('admin.home-sections.slides.view', $section->id) }}"
                                        class="table-users-manager-action-btn" title="Gestionar slides">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                    </a>
                                @endif
                                <button type="button" class="table-users-manager-action-btn edit btn-edit-home-section" data-id="{{ $section->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </button>
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-home-section" data-id="{{ $section->id }}" data-title="{{ e($section->title ?? ($typeLabels[$section->type] ?? $section->type)) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:32px 16px;text-align:center;color:#9ca3af;">
                            @if ($page === 'product')
                                No hay secciones configuradas para la página de producto todavía.
                            @elseif ($page === 'collection')
                                No hay secciones configuradas para las páginas de colección todavía.
                            @else
                                No hay secciones configuradas todavía.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

</section>

@include('admin.home-sections.partials._create_edit_modal')
@include('admin.home-sections.partials._delete_modal')
@include('admin.home-sections.partials._scripts')
@include('admin.components.center-toast')
</div>
@endsection
