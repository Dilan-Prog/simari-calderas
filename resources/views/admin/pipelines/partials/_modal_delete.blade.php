{{--
    Modal de confirmación de borrado de pipeline. El <select> "Mover los
    negocios a…" solo se muestra (vía JS) cuando el pipeline a borrar tiene
    negocios/conversaciones > 0. El JS puebla las <option> en tiempo real
    desde el data-targets del botón de eliminar clicado (ya viene filtrado
    por el mismo channel y sin el propio pipeline — ver
    $pipeline->reassign_targets en PipelineController::index()).
--}}
<div class="pp-modal-overlay pp-modal-overlay-sm" id="ppDeleteOverlay">
    <div class="pp-modal pp-modal-sm" id="ppDeleteModal">
        <div class="pp-delete-body">
            <div class="pp-delete-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 8v5m0 3.5v.5" stroke-linecap="round"/><path d="M12 3l9 17H3z" stroke-linejoin="round"/></svg>
            </div>
            <div class="pp-delete-text">
                <div class="pp-delete-title">Eliminar “<span id="ppDeleteName"></span>”</div>
                <div class="pp-delete-desc" id="ppDeleteDesc"></div>

                <div class="pp-delete-move" id="ppDeleteMoveWrap" style="display:none;">
                    <label class="pp-label">Mover los negocios a <span class="pp-required">*</span></label>
                    <select class="pp-input" id="ppDeleteTarget">
                        <option value="">Selecciona un pipeline…</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="pp-modal-footer pp-modal-footer-plain">
            <button type="button" class="pp-btn pp-btn-secondary" id="ppDeleteCancel">Cancelar</button>
            <button type="button" class="pp-btn pp-btn-danger" id="ppDeleteConfirm" disabled>Eliminar pipeline</button>
        </div>
    </div>
</div>
