{{-- Crear/editar pipeline — un solo modal reutilizado para ambos modos
     (el JS de _scripts.blade.php decide qué mostrar/enviar), mismo shell
     .del-confirm-overlay/.del-confirm-box + .ap-modal-* que el resto del
     admin (ver payment-methods/partials/_modal_form.blade.php). El builder
     de etapas iniciales solo aplica al crear — update() del controlador no
     acepta "stages", así que se oculta en modo edición. --}}
<div id="pipelineModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box pipeline-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3v18h18" />
                    <path d="m19 9-5 5-4-4-3 3" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="pipelineModalTitle">Nuevo Pipeline</h2>
                <p class="del-confirm-desc">Define el embudo de ventas y sus etapas iniciales.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closePipelineModal">✕</button>
        </div>

        <div id="pipeline-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="pipelineForm">
            @csrf

            {{-- Sección 1 — Datos generales --}}
            <div class="pm-section">
                <h3 class="pm-section-title">Datos generales</h3>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Nombre <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="name" id="pipelineName" maxlength="150"
                        placeholder="Ej: Ventas directas">
                </div>

                <label class="ap-primary-toggle" for="pipelineIsDefault" style="margin-top:12px;">
                    <input type="checkbox" id="pipelineIsDefault" name="is_default">
                    <div>
                        <strong>Pipeline predeterminado</strong>
                        <span>Se usa por defecto al crear un nuevo negocio.</span>
                    </div>
                </label>

                <label class="ap-primary-toggle" for="pipelineIsActive" style="margin-top:12px;">
                    <input type="checkbox" id="pipelineIsActive" name="is_active" checked>
                    <div>
                        <strong>Activo</strong>
                        <span>Los pipelines inactivos no aparecen al crear negocios nuevos.</span>
                    </div>
                </label>
            </div>

            {{-- Sección 2 — Etapas iniciales (solo al crear) --}}
            <div class="pm-section" id="pipelineStagesBuilderSection">
                <h3 class="pm-section-title">Etapas iniciales</h3>
                <div id="pipelineStagesBuilder"></div>
                <button type="button" class="button-secondary size-adjustment" id="btnAddPipelineStage"
                    style="margin-top:10px;">
                    + Agregar etapa
                </button>
                <p class="ap-readonly-note">Las etapas se pueden reordenar o eliminar más adelante desde el panel de
                    cada pipeline.</p>
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelPipelineModal" class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="pipelineSubmitBtn">Crear
                    Pipeline</button>
            </div>
        </form>
    </div>
</div>

{{-- Plantilla de una fila de etapa del builder — clonada por JS vanilla,
     nunca se renderiza directamente (queda oculta con .pipeline-stage-row-template). --}}
<template id="pipelineStageRowTemplate">
    <div class="pipeline-stage-row">
        <div class="ap-field-group pipeline-stage-row-name">
            <label class="supliers-manager-slider-label">Nombre</label>
            <input type="text" class="users-manager-input pipeline-stage-name" maxlength="150"
                placeholder="Ej: Contactado">
        </div>
        <div class="ap-field-group pipeline-stage-row-prob">
            <label class="supliers-manager-slider-label">Probabilidad %</label>
            <input type="number" class="users-manager-input pipeline-stage-probability" min="0" max="100"
                placeholder="0">
        </div>
        <div class="ap-field-group pipeline-stage-row-flags">
            <label class="supliers-manager-slider-label">&nbsp;</label>
            <div class="pipeline-stage-flags-inline">
                <label class="pipeline-stage-flag-label">
                    <input type="checkbox" class="pipeline-stage-is-won"> Ganada
                </label>
                <label class="pipeline-stage-flag-label">
                    <input type="checkbox" class="pipeline-stage-is-lost"> Perdida
                </label>
            </div>
        </div>
        <button type="button" class="action-btn btn-remove-pipeline-stage" title="Quitar etapa">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>
</template>

{{-- Modal de confirmación de eliminación (reutilizado para pipelines y etapas) --}}
<div id="deletePipelineModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        </div>
        <h2 class="del-confirm-title" id="deletePipelineTitle">¿Eliminar pipeline?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delPipelineAvatar">P</div>
            <div>
                <p class="del-confirm-user-name" id="delPipelineName">Nombre</p>
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delPipelineCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                id="delPipelineConfirm">Eliminar</button>
        </div>
    </div>
</div>
