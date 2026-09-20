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
    <style>
        /* ── Editor en vivo de Páginas de Servicio (BETA) ──
           Layout de 3 columnas específico de esta pantalla. Reusa clases de
           home-sections.css / ui-kit.css (button-primary, button-secondary,
           users-manager-select, users-manager-input, hs-config-note, etc.)
           para los controles; aquí solo van reglas de estructura. */

        .live-editor-page {
            max-width: none;
            padding: 24px 32px;
        }

        .live-editor-topbar {
            margin-bottom: 20px;
        }

        .live-editor-breadcrumb {
            font-size: 12.5px;
            color: #9ca3af;
            margin: 0 0 6px;
        }

        .live-editor-breadcrumb a {
            color: #9ca3af;
            text-decoration: none;
        }

        .live-editor-breadcrumb a:hover {
            color: #6b7280;
        }

        .live-editor-heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .live-editor-heading-row__left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .live-editor-heading-row__right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .live-editor-heading-row h1 {
            margin: 0;
            font-size: 22px;
        }

        .live-editor-badge {
            display: inline-block;
            background: rgba(255, 98, 19, .12);
            color: #ff6213;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .02em;
            border-radius: 4px;
            padding: 3px 7px;
            cursor: default;
        }

        .live-editor-toolbar-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }

        .live-editor-toolbar-divider {
            width: 1px;
            height: 20px;
            background: #e5e7eb;
        }

        .live-editor-actions-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .live-editor-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 10px;
            padding: 10px 16px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .live-editor-btn--outline {
            background: #fff;
            color: #4b5563;
            border-color: #dfe2e6;
        }

        .live-editor-btn--outline:hover {
            border-color: #ff6213;
            color: #ff6213;
        }

        .live-editor-btn--solid {
            background: #ff6213;
            color: #fff;
            border-color: #ff6213;
        }

        .live-editor-btn--solid:hover {
            background: #de4a00;
        }

        .live-editor-btn--solid:disabled {
            opacity: .6;
            cursor: default;
        }

        .live-editor-btn--block {
            width: 100%;
            justify-content: center;
        }

        .live-editor-viewport-toggle {
            display: inline-flex;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
        }

        .live-editor-viewport-toggle button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            background: #fff;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            cursor: pointer;
        }

        .live-editor-viewport-toggle button.is-active {
            background: #ff6213;
            color: #fff;
        }

        .live-editor-status {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .live-editor-status.is-dirty {
            color: #d97706;
        }

        .live-editor-status.is-saved {
            color: #16a34a;
        }

        .live-editor-status.is-saving {
            color: #6b7280;
        }

        .live-editor-layout {
            display: grid;
            grid-template-columns: 280px 360px 1fr;
            gap: 16px;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .live-editor-layout {
                grid-template-columns: 1fr;
            }
        }

        .live-editor-col {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            box-sizing: border-box;
        }

        .live-editor-col-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 10px;
        }

        .live-editor-general-row {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            border: 1px solid rgba(0,0,0,.09);
            border-radius: 9px;
            padding: 9px 10px;
            background: #fff;
            cursor: pointer;
            text-align: left;
            margin-bottom: 6px;
            font: inherit;
        }

        .live-editor-general-row:hover {
            border-color: #d1d5db;
        }

        .live-editor-general-row.is-selected {
            border-color: #ff6213;
            background: rgba(255, 98, 19, .06);
        }

        .live-editor-general-row.is-selected .live-editor-block-icon {
            background: rgba(255, 98, 19, .14);
            color: #ff6213;
        }

        .live-editor-more-link {
            margin: 0 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .live-editor-more-link a {
            font-size: 12px;
            color: #6b7280;
            text-decoration: none;
        }

        .live-editor-more-link a:hover {
            color: #ff6213;
        }

        /* Separador entre los 3 botones "Información general / Galería /
           Reseñas" y la lista de bloques -- mismas medidas que
           .live-editor-more-link, que ya no envuelve ningún enlace desde que
           Galería/Reseñas/Rating se portaron al editor en vivo. */
        .live-editor-sidebar-divider {
            margin: 0 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* La columna entera desplaza junto con la lista de bloques (mismo
           criterio ya usado en .live-editor-col--panel) -- antes solo
           .live-editor-blocks-list tenía su propio scroll, pero el resto de
           la columna (Información general, el link Galería/FAQ, "Agregar
           bloque") no estaba contemplado en ese cálculo, así que con varios
           bloques la columna completa se desbordaba de la página sin forma
           de llegar al selector de "Agregar bloque". */
        .live-editor-col--blocks {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        /* ── Columna de bloques ── */
        .live-editor-blocks-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .live-editor-block-row {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(0,0,0,.09);
            border-radius: 9px;
            padding: 9px 10px;
            background: #fff;
            cursor: pointer;
            transition: opacity 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
        }

        .live-editor-block-row:hover {
            border-color: #d1d5db;
        }

        .live-editor-block-row.is-selected {
            border-color: #ff6213;
            background: rgba(255, 98, 19, .06);
        }

        .live-editor-block-icon {
            flex-shrink: 0;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #f3f4f6;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .live-editor-block-row.is-selected .live-editor-block-icon {
            background: rgba(255, 98, 19, .14);
            color: #ff6213;
        }

        /* Clases de SortableJS (ver renderBlocksList() en el JS) — el
           elemento "fantasma" que marca dónde caerá el bloque, y el
           elemento real mientras se arrastra. */
        .live-editor-block-row--ghost {
            opacity: 0.4;
        }

        .live-editor-block-row--dragging {
            opacity: 0.7;
        }

        .live-editor-block-row.is-inactive {
            opacity: 0.55;
        }

        .live-editor-block-drag-handle {
            cursor: grab;
            color: #9ca3af;
            flex-shrink: 0;
            display: flex;
        }

        .live-editor-block-info {
            flex: 1;
            min-width: 0;
        }

        .live-editor-block-name {
            font-size: 13px;
            font-weight: 700;
            color: #141516;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }

        .live-editor-block-type {
            font-size: 11.5px;
            color: #9ca3af;
            display: block;
        }

        .live-editor-block-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .live-editor-toggle-active {
            width: 30px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #e5e7eb;
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
            padding: 0;
        }

        .live-editor-toggle-active::after {
            content: '';
            position: absolute;
            top: 1px;
            left: 1px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            transition: transform 0.15s ease;
        }

        .live-editor-toggle-active.is-on {
            background: #22c55e;
            border-color: #22c55e;
        }

        .live-editor-toggle-active.is-on::after {
            transform: translateX(12px);
        }

        .live-editor-block-delete {
            width: 24px;
            height: 24px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .live-editor-block-delete:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        .live-editor-blocks-empty {
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            padding: 24px 8px;
        }

        .live-editor-add-block {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        /* ── Columna central (panel de edición) ── */
        .live-editor-col--panel {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .live-editor-panel-empty {
            color: #9ca3af;
            font-size: 13px;
            text-align: center;
            padding: 32px 8px;
        }

        .live-editor-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .live-editor-panel-header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .live-editor-panel-header-info strong {
            display: block;
            font-size: 14px;
            color: #141516;
        }

        .live-editor-panel-header-info small {
            display: block;
            font-size: 11.5px;
            color: #9ca3af;
            font-weight: 400;
        }

        .live-editor-panel-close {
            border: none;
            background: none;
            color: #6b7280;
            font-size: 16px;
            cursor: pointer;
            line-height: 1;
        }

        .live-editor-field {
            margin-bottom: 12px;
        }

        .live-editor-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .live-editor-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        /* ── Columna derecha (preview) ── */
        .live-editor-col--preview {
            padding: 0;
            overflow: hidden;
        }

        .live-editor-browser-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 12px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }

        .live-editor-browser-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        .live-editor-browser-url {
            margin-left: 10px;
            font-size: 12px;
            color: #6b7280;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px 10px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .live-editor-iframe-wrap {
            display: flex;
            justify-content: center;
            background: #e5e7eb;
            height: calc(100vh - 232px);
            overflow: auto;
        }

        .live-editor-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #fff;
            transition: width 0.2s ease;
        }

        .live-editor-iframe.is-mobile {
            width: 375px;
        }

        .live-editor-preview-caption {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 11.5px;
            color: #9ca3af;
            padding: 8px 12px;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
        }

        /* ── Controles de tipografía (mountTypographyFields/mountColorPicker) ── */
        .le-align-toggle {
            display: inline-flex;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
        }
        .le-align-toggle button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: none;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
        }
        .le-align-toggle button + button {
            border-left: 1px solid #d1d5db;
        }
        .le-align-toggle button.is-active {
            background: #ff6213;
            color: #fff;
        }

        .le-color-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .le-color-swatch {
            position: relative;
            width: 22px;
            height: 22px;
            border-radius: 5px;
            border: 1px solid rgba(0,0,0,.12);
            cursor: pointer;
            padding: 0;
        }
        .le-color-swatch.is-active {
            outline: 2px solid #ff6213;
            outline-offset: 2px;
        }
        .le-color-swatch-remove {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #141516;
            color: #fff;
            font-size: 9px;
            line-height: 14px;
            text-align: center;
            box-shadow: 0 0 0 1px #fff;
        }
        .le-color-custom-label {
            font-size: 11px;
            color: #9ca3af;
            margin: 8px 0 4px;
        }
        .le-color-custom-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }
        .le-color-native {
            width: 30px;
            height: 30px;
            padding: 0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .le-color-hex {
            flex: 1;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.__LIVE_EDITOR__ = {!! \Illuminate\Support\Js::from([
            'previewUrl' => route('admin.service-pages.live-editor.preview', $servicePage),
            'saveUrl' => route('admin.service-pages.live-editor.save', $servicePage),
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
