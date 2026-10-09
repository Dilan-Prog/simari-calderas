@extends('admin.layouts.master')

@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush

@section('title')
    Editor en vivo - {{ $collection->name }} - Admin
@endsection

@section('content')
<div class="container live-editor-page">

    <div class="live-editor-topbar">
        <p class="live-editor-breadcrumb">
            <a href="{{ route('admin.collections.index') }}">Colecciones</a>
            <span>&rsaquo;</span> Editor en vivo
        </p>

        <div class="live-editor-heading-row">
            <div class="live-editor-heading-row__left">
                <h1>{{ $collection->name }}</h1>
                <span class="live-editor-badge" title="El editor en vivo de colecciones está en beta.">BETA · COLECCIONES</span>
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

                <a href="{{ url('/coleccion/'.$collection->slug) }}" target="_blank" rel="noopener" class="live-editor-btn live-editor-btn--outline" id="leViewLiveLink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    Ver página en vivo
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
                    <span class="live-editor-block-type">Nombre, slug, SEO, FAQs</span>
                </span>
            </button>

            <button type="button" id="leProductsBtn" class="live-editor-general-row">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">Productos y tipo</span>
                    <span class="live-editor-block-type">Manual o automática, reglas</span>
                </span>
            </button>

            <div class="live-editor-sidebar-divider"></div>

            <div class="live-editor-col-title">Bloques de la página</div>
            <div id="leBlocksList" class="live-editor-blocks-list"></div>

            <div class="live-editor-add-block">
                <select id="leAddBlockType" class="users-manager-select">
                    <option value="banner">Banner</option>
                    <option value="dual_banner">Banner Doble</option>
                    <option value="product_carousel">Carrusel de Productos</option>
                    <option value="product_carousel_banner">Carrusel con Banner</option>
                    <option value="category_grid">Grid de Categorías</option>
                    <option value="brand_carousel">Carrusel de Marcas</option>
                    <option value="brand_logos">Bloque de Marcas (logotipos propios)</option>
                    <option value="html_block">Bloque HTML</option>
                    <option value="faq">Preguntas Frecuentes</option>
                    <option value="content_tabs">Descripción por secciones</option>
                    <option value="benefits_grid">Beneficios / características</option>
                    <option value="process_steps">Proceso / cómo funciona</option>
                    <option value="gallery_carousel">Galería / carrusel</option>
                    <option value="cta_final">CTA final</option>
                    <option value="button">Botón</option>
                    <option value="table_block">Tabla</option>
                    <option value="pool_calculator">Calculadora de alberca</option>
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
                <span class="live-editor-browser-url" id="leBrowserUrl">equitermindustries.com.mx/coleccion/{{ $collection->slug }}</span>
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

    {{-- Modal de confirmación al eliminar un bloque — mismo shell reutilizado
         en todo el admin (.del-confirm-overlay/.del-confirm-box, ver
         resources/views/admin/service-pages/partials/_delete_section_modal.blade.php). --}}
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
@endpush

@push('scripts')
    <script>
        window.__LIVE_EDITOR__ = {!! \Illuminate\Support\Js::from([
            'kind' => 'collection',
            'previewUrl' => route('admin.collections.live-editor.preview', $collection),
            'saveUrl' => route('admin.collections.live-editor.save', $collection),
            'generalUrl' => route('admin.collections.live-editor.general', $collection),
            'settingsUrl' => route('admin.collections.live-editor.settings', $collection),
            'productsSearchUrl' => route('admin.collections.products.search'),
            'productsAddUrl' => route('admin.collections.products.add', $collection),
            'productsRemoveUrlTemplate' => route('admin.collections.products.remove', [$collection, '__ID__']),
            'productsReorderUrl' => route('admin.collections.products.reorder', $collection),
            'linkSearchUrl' => route('admin.links.destinations'),
            'tagsSuggestUrl' => route('admin.collections.tags.suggestions'),
            'ruleOptions' => [
                'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            ],
            'general' => [
                'name' => $collection->name,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'image_url' => $collection->image_url,
                'sort_order' => $collection->sort_order,
                'is_active' => (bool) $collection->is_active,
                'seo_title' => $collection->seo_title,
                'seo_description' => $collection->seo_description,
                'og_image_url' => $collection->og_image_url,
                'faqs' => $collection->faqs ?? [],
                'public_path' => '/coleccion/' . $collection->slug,
            ],
            'collectionState' => [
                'type' => $collection->type,
                'match_type' => $collection->match_type,
                'rules' => $collection->rules->map(fn ($r) => [
                    'field' => $r->field, 'operator' => $r->operator, 'value' => $r->value,
                ])->values(),
                'products' => $collection->manualProducts->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'thumb' => $p->cover_image_url,
                ])->values(),
            ],
            'sections' => $collection->sections->sortBy('sort_order')->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'title' => $s->title,
                'config' => $s->config ?: (object) [],
                'is_active' => (bool) $s->is_active,
            ])->values(),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'collections' => $collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ]) !!};
    </script>
    @vite('resources/js/admin/service-page-live-editor.js')
@endpush
