@extends('admin.layouts.master')
{{-- Los estilos (home-sections.css) los carga partials/editor.blade.php --}}
@section('title')
    Secciones del Sitio - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    @php
        $page = $page ?? 'home';
        $isTemplatePage = $page === 'product_template';
        $usageCounts = $usageCounts ?? collect();

        $pageMeta = [
            'home' => [
                'title'    => 'Gestión de Secciones del Inicio',
                'subtitle' => 'Personaliza los carruseles, banners y el slider del sitio público',
                'new'      => '+ Nueva Sección',
            ],
            'product' => [
                'title'    => 'Secciones de la Página de Producto (globales)',
                'subtitle' => 'Secciones globales heredadas de la página de producto',
                'new'      => '+ Nueva Sección',
            ],
            'product_template' => [
                'title'    => 'Plantillas de producto',
                'subtitle' => 'Bloques reutilizables: se asignan a varios productos y editar la plantilla cambia todos',
                'new'      => '+ Nueva Plantilla',
            ],
            'collection' => [
                'title'    => 'Secciones de las Páginas de Colección',
                'subtitle' => 'Personaliza las secciones que aparecen debajo del listado en TODAS las páginas de colección',
                'new'      => '+ Nueva Sección',
            ],
        ];

        $typeLabels = [
            'hero_slider'      => 'Slider Principal',
            'banner'           => 'Banner',
            'dual_banner'      => 'Banner Doble',
            'product_carousel' => 'Carrusel de Productos',
            'product_carousel_banner' => 'Carrusel con Banner',
            'card_carousel'    => 'Carrusel de Fichas',
            'category_grid'    => 'Grid de Categorías',
            'brand_carousel'   => 'Carrusel de Marcas',
            'html_block'       => 'Bloque HTML',
            'faq'              => 'Preguntas Frecuentes',
        ];

        $zoneLabels = ['stack' => 'Pila', 'sidebar' => 'Barra lateral'];
        $colCount = $isTemplatePage ? 8 : 6;
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
        <div style="display:flex;align-items:flex-start;gap:8px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'home-sections.index',
                'columnDefs' => ['tipo' => 'Tipo', 'orden' => 'Orden', 'estado' => 'Estado'],
            ])
            @permiso('home-sections','create')
            <button type="button" class="button-primary size-adjustment" id="btnNewHomeSection"
                style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
                {{ $pageMeta[$page]['new'] }}
            </button>
            @endpermiso
        </div>
    </div>

    {{-- Tabs de página --}}
    <div class="hs-page-tabs">
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'inicio']) }}"
            class="hs-page-tab {{ $page === 'home' ? 'is-active' : '' }}">Inicio</a>
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'plantillas']) }}"
            class="hs-page-tab {{ $isTemplatePage ? 'is-active' : '' }}">Plantillas de producto</a>
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'colecciones']) }}"
            class="hs-page-tab {{ $page === 'collection' ? 'is-active' : '' }}">Colecciones</a>
        <a href="{{ route('admin.home-sections.index', ['pagina' => 'producto']) }}"
            class="hs-page-tab {{ $page === 'product' ? 'is-active' : '' }}">Página de Producto</a>
    </div>

    @if ($page === 'product' && empty($globalProductSectionsOn))
        <div class="hs-legacy-notice">
            Estas secciones globales están apagadas (config <code>shop.product_global_sections</code>):
            la página de producto ya no las muestra. Usa las
            <a href="{{ route('admin.home-sections.index', ['pagina' => 'plantillas']) }}">plantillas de producto</a>
            o las secciones propias de cada producto.
        </div>
    @endif

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    @if ($isTemplatePage)
                        <th>NOMBRE</th>
                        <th data-col="tipo">TIPO</th>
                        <th>ZONA</th>
                        <th>ENCABEZADO → DESTINO</th>
                        <th>EN USO</th>
                        <th data-col="estado">ESTADO</th>
                    @else
                        <th data-col="tipo">TIPO</th>
                        <th>TÍTULO</th>
                        <th data-col="orden">ORDEN</th>
                        <th data-col="estado">ESTADO</th>
                    @endif
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table" id="homeSectionsTable">
                <tbody id="homeSectionsTableBody">
                @forelse ($sections as $section)
                    @php
                        $rowLabel = $section->name ?: ($section->title ?: ($typeLabels[$section->type] ?? $section->type));
                    @endphp
                    <tr class="hs-row" data-id="{{ $section->id }}" @unless ($isTemplatePage) draggable="true" @endunless>
                        <td style="padding:12px 8px;text-align:center;color:#9ca3af;{{ $isTemplatePage ? '' : 'cursor:grab;' }}" class="{{ $isTemplatePage ? '' : 'hs-drag-handle' }}">
                            @unless ($isTemplatePage)
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                            @endunless
                        </td>

                        @if ($isTemplatePage)
                            <td style="padding:12px 16px;font-weight:500;">
                                {{ $section->name ?: '—' }}
                                @if ($section->title)
                                    <div class="hs-cell-sub">{{ $section->title }}</div>
                                @endif
                            </td>
                            <td data-col="tipo" style="padding:12px 16px;">
                                <span class="hs-type-badge" data-type="{{ $section->type }}">{{ $typeLabels[$section->type] ?? $section->type }}</span>
                            </td>
                            <td style="padding:12px 16px;">
                                <span class="hs-zone-badge hs-zone-badge--{{ $section->zone }}">{{ $zoneLabels[$section->zone] ?? $section->zone }}</span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;">
                                @if ($section->heading_link)
                                    {{ \App\Support\LinkTarget::label($section->heading_link) }}
                                    @if (\App\Support\LinkTarget::isBroken($section->heading_link))
                                        <span class="hs-broken-badge">enlace roto</span>
                                    @endif
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;color:#374151;">
                                {{ $usageCounts[$section->id] ?? 0 }} producto(s)
                            </td>
                        @else
                            <td data-col="tipo" style="padding:12px 16px;">
                                <span class="hs-type-badge" data-type="{{ $section->type }}">{{ $typeLabels[$section->type] ?? $section->type }}</span>
                            </td>
                            <td style="padding:12px 16px;font-weight:500;">{{ $section->title ?? '—' }}</td>
                            <td data-col="orden" style="padding:12px 16px;text-align:center;color:#374151;" class="hs-sort-order">{{ $section->sort_order }}</td>
                        @endif

                        <td data-col="estado" style="padding:12px 16px;">
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
                                @permiso('home-sections','edit')
                                <button type="button" class="table-users-manager-action-btn edit btn-edit-home-section" data-id="{{ $section->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </button>
                                @endpermiso
                                @permiso('home-sections','delete')
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-home-section" data-id="{{ $section->id }}" data-title="{{ e($rowLabel) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                                @endpermiso
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" style="padding:32px 16px;text-align:center;color:#9ca3af;">
                            @if ($page === 'product')
                                No hay secciones globales de la página de producto.
                            @elseif ($isTemplatePage)
                                No hay plantillas de producto todavía. Crea una con “Nueva Plantilla” y asígnala a productos desde Productos.
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

@include('admin.home-sections.partials.editor')
@include('admin.home-sections.partials._delete_modal')
@include('admin.home-sections.partials._scripts')
@include('admin.components.center-toast')
</div>
@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'home-sections.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
@endsection
