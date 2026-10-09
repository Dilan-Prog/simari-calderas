{{-- Modal de FILTROS TÉCNICOS por categoría (catálogo público). Centrado al
     80vw×80vh; el contenido se pinta por JS (_filters_scripts.blade.php). --}}
<div id="categoryFiltersModal" class="user-manager-modal client-manage-modal" aria-hidden="true">
    <div class="user-manager-modal-content client-modal-content cf-content" role="dialog" aria-modal="true"
        aria-labelledby="cfTitle">
        <div class="user-manager-modal-header cf-header">
            <div class="cf-header__text">
                <h2 id="cfTitle">Filtros técnicos</h2>
                <p id="cfPath" class="cf-path"></p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="cfClose"
                aria-label="Cerrar" title="Cerrar">✕</button>
        </div>

        <div class="cf-body" id="cfBody">
            <div class="cf-body__inner" id="cfInner">
                <p class="cf-intro">
                    Cada opción apunta a una <strong>etiqueta de producto</strong>; los productos con esa etiqueta
                    aparecerán al elegirla en el catálogo. Los grupos aplican a esta categoría y a sus subcategorías.
                    Las etiquetas se asignan a los productos desde su ficha o con el editor masivo (Tags por lote).
                </p>

                <div id="cfAlert" class="cf-alert" role="alert" hidden></div>
                <div id="cfWarnings" class="cf-warnings" role="status" hidden></div>
                <div id="cfLoading" class="cf-loading" hidden>Cargando filtros…</div>

                <div id="cfContent" hidden>
                    <details id="cfInherited" class="cf-inherited" hidden></details>

                    <div id="cfGroups" class="cf-groups"></div>

                    <div class="cf-add-group">
                        <button type="button" class="cf-btn cf-btn--add" id="cfAddGroup" data-action="add-group">
                            + Agregar grupo
                        </button>
                        <span class="cf-hint" id="cfGroupLimit"></span>
                    </div>

                    <section class="cf-suggest" id="cfSuggest" aria-labelledby="cfSuggestTitle" hidden>
                        <h3 id="cfSuggestTitle">Etiquetas sugeridas</h3>
                        <p class="cf-hint">Etiquetas que ya usan los productos de esta categoría y aún no tienen un filtro.
                            Haz clic en una para agregarla como opción.</p>
                        <div id="cfSuggestChips" class="cf-chips"></div>
                    </section>
                </div>
            </div>
        </div>

        <div class="cf-footer">
            <span id="cfStatus" class="cf-status" role="status" aria-live="polite"></span>
            <div class="cf-footer__actions">
                <button type="button" class="button-secondary size-adjustment" id="cfCancel">Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="cfSave" disabled
                    style="background:#ff6213;border-color:#ff6213;">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>
