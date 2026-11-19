@extends('admin.layouts.master')

@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush

@section('title')
    Editor en vivo - {{ $category->name }} - Admin
@endsection

@php
    // Ancestros (principal › subcategoría) para las migas, del más lejano al más cercano.
    $ancestors = [];
    $node = $category->parent;
    for ($i = 0; $node && $i < 3; $i++) {
        array_unshift($ancestors, $node);
        $node = $node->parent;
    }
    $publicPath = '/catalogo/' . $category->slug;
    $mapSection = fn ($s) => [
        'id' => $s->id,
        'type' => $s->type,
        'title' => $s->title,
        'config' => $s->config ?: (object) [],
        'is_active' => (bool) $s->is_active,
    ];
@endphp

@section('content')
<div class="container live-editor-page">

    <div class="live-editor-topbar">
        <p class="live-editor-breadcrumb">
            <a href="{{ route('admin.categories.index') }}">Categorías</a>
            @foreach ($ancestors as $ancestor)
                <span>&rsaquo;</span>
                <a href="{{ route('admin.categories.live-editor', $ancestor->id) }}" title="Abrir el editor en vivo de {{ $ancestor->name }}">{{ $ancestor->name }}</a>
            @endforeach
            <span>&rsaquo;</span> <span id="leCrumbCurrent">{{ $category->name }}</span>
            <span>&rsaquo;</span> Editor en vivo
        </p>

        <div class="live-editor-heading-row">
            <div class="live-editor-heading-row__left">
                <h1>{{ $category->name }}</h1>
                <span class="live-editor-badge" title="Nivel de la categoría">{{ mb_strtoupper($levelLabel) }}</span>
            </div>

            <div class="live-editor-heading-row__right">
                <div class="live-editor-viewport-toggle" id="leViewportToggle">
                    <button type="button" class="is-active" data-viewport="desktop">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                        Escritorio
                    </button>
                    <button type="button" data-viewport="mobile">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/></svg>
                        Móvil
                    </button>
                </div>

                <span class="live-editor-toolbar-divider"></span>

                <span id="leDirtyIndicator" class="live-editor-status is-saved">Guardado</span>

                <span class="live-editor-toolbar-divider"></span>

                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="live-editor-btn live-editor-btn--outline" id="leViewLiveLink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    Ver página pública
                </a>
                <button type="button" id="leSaveBtn" class="live-editor-btn live-editor-btn--solid">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
    <div class="live-editor-layout">

        {{-- Columna izquierda: info general + lista de bloques --}}
        <aside class="live-editor-col live-editor-col--blocks">
            <button type="button" id="leGeneralInfoBtn" class="live-editor-general-row">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">Información general</span>
                    <span class="live-editor-block-type">Nombre, imagen, SEO, FAQs</span>
                </span>
            </button>

            <div class="live-editor-sidebar-divider"></div>

            <div class="live-editor-col-title">Bloques de la página</div>
            <p class="hs-config-note" style="margin:0 0 8px;">Cada bloque se muestra arriba o debajo del catálogo de productos (se elige en el bloque).</p>
            <div id="leBlocksList" class="live-editor-blocks-list"></div>

            <div class="live-editor-add-block">
                {{-- El JS deja solo los tipos permitidos en categorías (sectionTypes). --}}
                <select id="leAddBlockType" class="users-manager-select">
                    <option value="product_grid">Productos en filas</option>
                    <option value="product_carousel">Carrusel de Productos</option>
                    <option value="product_carousel_banner">Carrusel con Banner</option>
                    <option value="banner">Banner</option>
                    <option value="dual_banner">Banner Doble</option>
                    <option value="category_grid">Grid de Categorías</option>
                    <option value="brand_carousel">Carrusel de Marcas</option>
                    <option value="brand_logos">Bloque de Marcas (logotipos propios)</option>
                    <option value="html_block">Bloque HTML</option>
                    <option value="rich_header">Encabezado enriquecido</option>
                    <option value="content_tabs">Descripción por secciones</option>
                    <option value="benefits_grid">Beneficios / características</option>
                    <option value="process_steps">Proceso / cómo funciona</option>
                    <option value="gallery_carousel">Galería / carrusel</option>
                    <option value="cta_final">CTA final</option>
                    <option value="button">Botón</option>
                    <option value="table_block">Tabla</option>
                </select>
                <button type="button" id="leAddBlockBtn" class="live-editor-btn live-editor-btn--outline live-editor-btn--block">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Agregar bloque
                </button>
            </div>
        </aside>

        {{-- Columna central: panel de edición del bloque seleccionado --}}
        <section class="live-editor-col live-editor-col--panel" id="leEditPanel">
            <div class="live-editor-panel-empty">
                Selecciona un bloque de la izquierda para editarlo aquí.
            </div>
        </section>

        {{-- Columna derecha: preview en iframe --}}
        <section class="live-editor-col live-editor-col--preview">
            <div class="live-editor-browser-bar">
                <span class="live-editor-browser-dot" style="background:#ff5f57;"></span>
                <span class="live-editor-browser-dot" style="background:#ffbd2e;"></span>
                <span class="live-editor-browser-dot" style="background:#28c840;"></span>
                <span class="live-editor-browser-url" id="leBrowserUrl">{{ request()->getHost() }}{{ $publicPath }}</span>
            </div>
            <div class="live-editor-iframe-wrap">
                <iframe id="leIframe" class="live-editor-iframe" title="Vista previa"></iframe>
            </div>
            <div class="live-editor-preview-caption">
                Click sobre cualquier bloque de la vista previa para editarlo
                <span>&middot;</span>
                <span id="leViewportCaption">Escritorio · 1440px</span>
            </div>
        </section>

    </div>

    {{-- Modal de confirmación al eliminar un bloque (mismo shell que el resto del admin). --}}
    <div id="leDeleteModal" class="del-confirm-overlay">
        <div class="del-confirm-box">
            <div class="del-confirm-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                    fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                </svg>
            </div>
            <h2 class="del-confirm-title">¿Eliminar este bloque?</h2>
            <p class="del-confirm-desc">Esta acción no se puede deshacer una vez que guardes los cambios.</p>
            <div class="del-confirm-user-card">
                <div class="del-confirm-avatar" id="leDeleteModalAvatar">B</div>
                <div>
                    <p class="del-confirm-user-name" id="leDeleteModalTitle">Bloque</p>
                </div>
            </div>
            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="leDeleteModalCancel">Cancelar</button>
                <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button" id="leDeleteModalConfirm">Eliminar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.service-pages.partials._live_editor_styles')
    <style>
        /* Aviso de bloques heredados (solo editor de Categorías). */
        .live-editor-inherit-banner {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            color: #7c2d12;
        }
        .live-editor-inherit-banner p { margin: 0 0 10px; line-height: 1.45; }
        .live-editor-inherit-banner__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
        }
        .live-editor-inherit-banner__link {
            color: #c2410c;
            font-weight: 600;
            text-decoration: underline;
            font-size: 12.5px;
        }
        .live-editor-inherit-banner__list {
            margin: 10px 0 0;
            padding-left: 18px;
            color: #9a3412;
            font-size: 12.5px;
        }
        .live-editor-inherit-banner__list small { color: #9ca3af; }
        @media (max-width: 640px) {
            .live-editor-page { padding: 16px; }
            .live-editor-breadcrumb { overflow-wrap: anywhere; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.__LIVE_EDITOR__ = {!! \Illuminate\Support\Js::from([
            'kind' => 'category',
            'previewUrl' => route('admin.categories.live-editor.preview', $category->id),
            'saveUrl' => route('admin.categories.live-editor.save', $category->id),
            'generalUrl' => route('admin.categories.live-editor.general', $category->id),
            'productsSearchUrl' => route('admin.categories.live-editor.products-search'),
            'linkSearchUrl' => route('admin.links.destinations'),
            'tagsSuggestUrl' => \Illuminate\Support\Facades\Route::has('admin.products.tags.suggestions') && auth()->user()?->hasPermission('products')
                ? route('admin.products.tags.suggestions')
                : null,
            'sectionTypes' => array_values($sectionTypes),
            'level' => $level,
            'general' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url,
                'is_active' => (bool) $category->is_active,
                'sort_order' => $category->sort_order,
                'seo_title' => $category->seo_title,
                'seo_description' => $category->seo_description,
                'faqs' => $category->faqs ?? [],
                'public_path' => $publicPath,
            ],
            'sections' => $ownSections->sortBy('sort_order')->map($mapSection)->values(),
            'inherited' => [
                'from' => $inheritedFrom ? [
                    'id' => $inheritedFrom->id,
                    'name' => $inheritedFrom->name,
                    'editorUrl' => route('admin.categories.live-editor', $inheritedFrom->id),
                ] : null,
                'sections' => collect($inheritedSections)->map($mapSection)->values(),
            ],
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id, 'image_url' => $c->image_url])->values(),
            'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'collections' => $collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ]) !!};
    </script>
    @vite('resources/js/admin/service-page-live-editor.js')
@endpush
