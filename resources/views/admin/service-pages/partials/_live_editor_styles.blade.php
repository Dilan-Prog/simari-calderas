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
