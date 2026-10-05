{{-- Modal "Plantillas de bloques" del editor por lotes: elegir plantillas +
     modo → vista previa (dry-run) → confirmar. Lógica en
     _bulk_blocks_scripts.blade.php; endpoints en ProductBlockController. --}}
<div id="bulkBlocksModal" class="del-confirm-overlay">
    <div class="del-confirm-box bbk-box">
        <h2 class="del-confirm-title">Plantillas de bloques</h2>
        <p class="del-confirm-desc" id="bbkSubtitle"></p>

        {{-- Paso 1: configuración --}}
        <div id="bbkStepConfig">
            <label class="bbk-label" for="bbkSearch">Plantillas</label>
            <input type="text" id="bbkSearch" class="pform-input" placeholder="Buscar plantilla por nombre o tipo..."
                autocomplete="off">
            <ul class="bbk-list" id="bbkList"></ul>
            <p class="bbk-selected-count" id="bbkSelectedCount">Ninguna plantilla elegida</p>

            <label class="bbk-label">Qué hacer con los productos seleccionados</label>
            <div class="bbk-modes">
                <label><input type="radio" name="bbkMode" value="append" checked> Agregar al final</label>
                <label><input type="radio" name="bbkMode" value="prepend"> Agregar al inicio</label>
                <label><input type="radio" name="bbkMode" value="replace"> Reemplazar plantillas
                    <span class="bbk-mode-hint">(quita todas sus plantillas actuales y pone estas; las secciones propias no se tocan)</span></label>
                <label><input type="radio" name="bbkMode" value="remove"> Quitar estas plantillas</label>
            </div>
        </div>

        {{-- Paso 2: vista previa --}}
        <div id="bbkStepPreview" style="display:none">
            <div class="bbk-preview-msg" id="bbkPreviewMsg"></div>
            <ul class="bbk-breakdown" id="bbkBreakdown"></ul>
            <p class="bbk-preview-note">Todavía no se guardó nada. Revisa y confirma para aplicar.</p>
        </div>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="bbkCancel">Cancelar</button>
            <button type="button" class="button-secondary size-adjustment" id="bbkBack" style="display:none">Volver</button>
            <button type="button" class="button-primary size-adjustment" id="bbkPreviewBtn" disabled>Vista previa</button>
            <button type="button" class="button-primary size-adjustment" id="bbkApplyBtn" style="display:none">Confirmar y aplicar</button>
        </div>
    </div>
</div>

<style>
    .bbk-box { max-width: 600px; text-align: left; }
    .bbk-box .del-confirm-title, .bbk-box .del-confirm-desc { text-align: left; }
    .bbk-label { display: block; margin: 14px 0 6px; font-size: 13px; font-weight: 600; color: #374151; }
    .bbk-list { list-style: none; margin: 10px 0 0; padding: 0; max-height: 220px; overflow-y: auto;
        border: 1px solid #e5e7eb; border-radius: 10px; }
    .bbk-list li { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; }
    .bbk-list li:last-child { border-bottom: none; }
    .bbk-list label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .bbk-list .bbk-name { font-size: 14px; font-weight: 600; color: #111827; }
    .bbk-list .bbk-sub { font-size: 12px; color: #6b7280; }
    .bbk-list .bbk-note { padding: 16px; text-align: center; color: #9ca3af; font-size: 13px; }
    .bbk-selected-count { margin: 6px 0 0; font-size: 12.5px; color: #6b7280; }
    .bbk-modes { display: flex; flex-direction: column; gap: 6px; font-size: 14px; color: #111827; }
    .bbk-modes label { display: flex; align-items: baseline; gap: 8px; cursor: pointer; }
    .bbk-mode-hint { font-size: 12px; color: #6b7280; }
    .bbk-preview-msg { padding: 14px 16px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe;
        color: #1e3a8a; font-size: 14px; line-height: 1.5; }
    .bbk-breakdown { list-style: none; margin: 12px 0 0; padding: 0; font-size: 13px; color: #374151; }
    .bbk-breakdown li { padding: 4px 0; }
    .bbk-preview-note { margin: 12px 0 0; font-size: 12.5px; color: #6b7280; }
    .prod-bulk-blocks-cell div { white-space: normal; }
</style>
