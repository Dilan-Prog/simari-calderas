{{--
    Modal grande (80vw x 80vh) de 2 columnas, reutilizado para crear Y
    editar pipelines (toggle por JS vía pipelines-index.js, mismo patrón
    que el modal actual). Todo el contenido dinámico (fila de etapas,
    preview del embudo, checklist de validaciones, impacto) se renderiza
    client-side desde un "draft" en memoria — este partial solo define el
    shell y los contenedores donde el JS inyecta HTML.
--}}
<div class="pp-modal-overlay" id="ppEditorOverlay">
    <div class="pp-modal pp-modal-xl" id="ppEditorModal">

        <div class="pp-modal-header">
            <div class="pp-modal-header-left">
                <div class="pp-modal-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18l-6 7v6l-6-2v-4z" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <div class="pp-modal-title" id="ppEditorTitle">Nuevo pipeline</div>
                    <div class="pp-modal-desc">Define el embudo, sus etapas, probabilidades y reglas de cierre.</div>
                </div>
            </div>
            <button type="button" class="pp-modal-close" id="ppEditorClose">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>

        <div class="pp-editor-body">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="pp-editor-col-left">

                <div class="pp-section-label">Datos generales</div>
                <div class="pp-field-grid">
                    <div class="pp-field">
                        <label class="pp-label">Nombre <span class="pp-required">*</span></label>
                        <input type="text" id="ppFieldName" class="pp-input" placeholder="Ej. Ventas directas" maxlength="150">
                        <div class="pp-field-hint" id="ppFieldNameHint">Como lo verá el equipo en los selectores de negocio.</div>
                    </div>
                    <div class="pp-field">
                        <label class="pp-label">Tipo de pipeline</label>
                        <select id="ppFieldChannel" class="pp-input">
                            <option value="deals">Negocios</option>
                            <option value="whatsapp">Chats</option>
                        </select>
                        <div class="pp-field-hint" id="ppFieldChannelHint">Define dónde aparece el embudo: negocios o chats.</div>
                    </div>
                </div>

                <div class="pp-toggle-grid">
                    <div class="pp-toggle-card" id="ppTogglePred" data-active="0">
                        <span class="pp-toggle-mark"></span>
                        <div>
                            <div class="pp-toggle-title">Pipeline predeterminado</div>
                            <div class="pp-toggle-sub">Se usa por defecto al crear un nuevo negocio.</div>
                        </div>
                    </div>
                    <div class="pp-toggle-card" id="ppToggleActive" data-active="1">
                        <span class="pp-toggle-mark"></span>
                        <div>
                            <div class="pp-toggle-title">Activo</div>
                            <div class="pp-toggle-sub">Los pipelines inactivos no aparecen al crear negocios nuevos.</div>
                        </div>
                    </div>
                </div>

                <div class="pp-stages-head">
                    <div class="pp-section-label" id="ppStagesCountLabel">Etapas · 0</div>
                    <div class="pp-templates-inline" id="ppTemplatesInline">
                        <span class="pp-templates-inline-label">Aplicar plantilla:</span>
                        {{-- JS inyecta un chip por plantilla (solo al crear) --}}
                    </div>
                </div>

                <div class="pp-stages-editor">
                    <div class="pp-stages-editor-head" id="ppStagesEditorHead">
                        <div>Orden</div><div>Nombre</div><div>Probabilidad</div><div>Tipo de cierre</div><div>Límite WIP</div><div></div>
                    </div>
                    <div id="ppStagesEditorBody">
                        {{-- JS inyecta las filas de etapas aquí (draggable) --}}
                    </div>
                    <div class="pp-stages-editor-footer">
                        <button type="button" class="pp-btn pp-btn-secondary" id="ppAddStage">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                            Agregar etapa
                        </button>
                        <div class="pp-stages-editor-note">Arrastra por el asa para reordenar. Las etapas se pueden editar después.</div>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: PREVIEW --}}
            <div class="pp-editor-col-right">
                <div class="pp-section-label">Vista previa del embudo</div>

                <div class="pp-preview-card">
                    <div class="pp-preview-name" id="ppPreviewName">Nuevo pipeline</div>
                    <div class="pp-preview-meta" id="ppPreviewMeta">Negocios · 0 etapas</div>
                    <div class="pp-preview-bars" id="ppPreviewBars"></div>
                </div>

                <div class="pp-side-card">
                    <div class="pp-side-card-title">Validaciones</div>
                    <div class="pp-checklist" id="ppChecklist"></div>
                </div>

                <div class="pp-side-card">
                    <div class="pp-side-card-title">Impacto</div>
                    <div class="pp-impact" id="ppImpact"></div>
                    <div class="pp-impact-note" id="ppImpactNote"></div>
                </div>
            </div>
        </div>

        <div class="pp-modal-footer">
            <div class="pp-footer-msg" id="ppFooterMsg">Revisa las validaciones pendientes del panel derecho.</div>
            <div class="pp-footer-actions">
                <button type="button" class="pp-btn pp-btn-secondary" id="ppEditorCancel">Cancelar</button>
                <button type="button" class="pp-btn pp-btn-primary" id="ppEditorSave" disabled>Crear pipeline</button>
            </div>
        </div>
    </div>
</div>
