@extends('admin.layouts.master')

@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush

@section('title')
    Editor en vivo - {{ $servicePage->name }} - Admin
@endsection

@section('content')
<div class="container live-editor-page">

    <div class="live-editor-topbar">
        <p class="live-editor-breadcrumb">
            <a href="{{ route('admin.service-pages.index') }}">Servicios</a>
            <span>&rsaquo;</span> Editor en vivo
        </p>

        <div class="live-editor-heading-row">
            <div class="live-editor-heading-row__left">
                <h1>{{ $servicePage->name }}</h1>
                <span class="live-editor-badge" title="El editor en vivo está disponible solo en el módulo Servicios por ahora.">BETA · SOLO SERVICIOS</span>
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

                <a href="{{ url($servicePage->publicPath()) }}" target="_blank" rel="noopener" class="live-editor-btn live-editor-btn--outline" id="leViewLiveLink">
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
                    <span class="live-editor-block-type">Nombre, slug, precio, SEO, rating</span>
                </span>
            </button>

            <button type="button" id="leGalleryBtn" class="live-editor-general-row">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">Galería</span>
                    <span class="live-editor-block-type">Imágenes del servicio</span>
                </span>
            </button>

            <button type="button" id="leReviewsBtn" class="live-editor-general-row">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">Reseñas</span>
                    <span class="live-editor-block-type">Reseñas capturadas</span>
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
                    <option value="rich_header">Encabezado enriquecido</option>
                    <option value="content_tabs">Descripción por secciones</option>
                    <option value="benefits_grid">Beneficios / características</option>
                    <option value="process_steps">Proceso / cómo funciona</option>
                    <option value="gallery_carousel">Galería / carrusel</option>
                    <option value="rating_reviews">Rating y reseñas</option>
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
                <span class="live-editor-browser-url" id="leBrowserUrl">equitermindustries.com.mx{{ $servicePage->publicPath() }}</span>
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
            'previewUrl' => route('admin.service-pages.live-editor.preview', $servicePage),
            'saveUrl' => route('admin.service-pages.live-editor.save', $servicePage),
            'linkSearchUrl' => route('admin.links.destinations'),
            'generalUrl' => route('admin.service-pages.update-general', $servicePage),
            'productsSearchUrl' => route('admin.service-pages.products.search'),
            'general' => [
                'name' => $servicePage->name,
                'slug' => $servicePage->slug,
                'page_type' => $servicePage->page_type,
                'parent_id' => $servicePage->parent_id,
                'sort_order' => $servicePage->sort_order,
                'short_description' => $servicePage->short_description,
                'price' => $servicePage->price,
                'currency' => $servicePage->currency ?: 'MXN',
                'show_price' => (bool) $servicePage->show_price,
                'background_color' => $servicePage->background_color,
                'seo_title' => $servicePage->seo_title,
                'seo_description' => $servicePage->seo_description,
                'is_active' => (bool) $servicePage->is_active,
                'public_path' => $servicePage->publicPath(),
                'faqs' => $servicePage->faqs ?? [],
                'canonical_url' => $servicePage->canonical_url,
                // Estadísticas de marketing (pestaña "Rating y reseñas —
                // Promedio mostrado" del formulario clásico) -- portadas al
                // panel "Información general" del editor en vivo. Nunca
                // alimentan el JSON-LD, ver ServicePageController::fillRatingStats().
                'rating_average_displayed' => $servicePage->rating_average_displayed,
                'rating_total_rated' => $servicePage->rating_total_rated,
                'rating_recommend_percent' => $servicePage->rating_recommend_percent,
                'rating_punctuality_average' => $servicePage->rating_punctuality_average,
                'rating_recurring_clients' => $servicePage->rating_recurring_clients,
                'rating_since_year' => $servicePage->rating_since_year,
                'rating_distribution' => $servicePage->rating_distribution,
            ],
            'eligibleParents' => $eligibleParents->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'page_type' => $p->page_type,
            ])->values(),
            'sections' => $servicePage->sections->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'title' => $s->title,
                'config' => $s->config ?: (object) [],
                'is_active' => (bool) $s->is_active,
            ])->values(),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'collections' => $collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'images' => $servicePage->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'alt_text' => $img->alt_text,
            ])->values(),
            // Panel "Reseñas" del editor en vivo -- se pasa el set completo de
            // columnas (igual criterio que 'sections'/'images') para no
            // depender de reviews.edit (GET) al abrir el formulario de edición.
            'reviews' => $servicePage->reviews->map(fn ($r) => [
                'id' => $r->id,
                'customer_name' => $r->customer_name,
                'customer_role' => $r->customer_role,
                'customer_company' => $r->customer_company,
                'customer_city' => $r->customer_city,
                'customer_state' => $r->customer_state,
                'review_date' => $r->review_date?->format('Y-m-d'),
                'rating' => $r->rating,
                'comment' => $r->comment,
                'categories' => $r->categories ?? [],
                'is_verified' => (bool) $r->is_verified,
                'is_visible' => (bool) $r->is_visible,
                'business_response' => $r->business_response,
                'business_response_date' => $r->business_response_date?->format('Y-m-d'),
            ])->values(),
            'reviewCategories' => \App\Models\ServicePageReview::CATEGORIES,
            'imagesStoreUrl' => route('admin.service-pages.images.store', $servicePage),
            'imagesReorderUrl' => route('admin.service-pages.images.reorder', $servicePage),
            // Placeholders sustituidos en JS (String.replace) -- mismo truco
            // que url() con un parámetro de ruta que todavía no se conoce en
            // el momento de generar la URL (el id de imagen/reseña se sabe
            // solo hasta que el usuario hace click en un item ya persistido).
            'imageUpdateUrlTemplate' => route('admin.service-pages.images.update', [$servicePage, '__IMAGE_ID__']),
            'imageDestroyUrlTemplate' => route('admin.service-pages.images.destroy', [$servicePage, '__IMAGE_ID__']),
            'reviewsStoreUrl' => route('admin.service-pages.reviews.store', $servicePage),
            'reviewsReorderUrl' => route('admin.service-pages.reviews.reorder', $servicePage),
            'reviewUpdateUrlTemplate' => route('admin.service-pages.reviews.update', [$servicePage, '__REVIEW_ID__']),
            'reviewDestroyUrlTemplate' => route('admin.service-pages.reviews.destroy', [$servicePage, '__REVIEW_ID__']),
        ]) !!};
    </script>
    @vite('resources/js/admin/service-page-live-editor.js')
@endpush
