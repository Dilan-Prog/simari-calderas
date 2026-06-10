<div id="roles-modal-delete" class="roles-modal" style="display:none">
    <div class="roles-modal__box roles-modal__box--sm">
        <div class="roles-modal__header">
            <h3 class="roles-modal__title">Eliminar Rol</h3>
            <button class="roles-drawer__close" type="button" onclick="closeDeleteModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>
        <div class="roles-modal__body">
            <div class="roles-delete-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    <line x1="10" x2="10" y1="11" y2="17"/>
                    <line x1="14" x2="14" y1="11" y2="17"/>
                </svg>
            </div>
            <p class="roles-delete-title">¿Eliminar rol <strong id="roles-delete-name"></strong>?</p>
            <p class="roles-delete-warning" id="roles-delete-warning"></p>
        </div>
        <form id="roles-delete-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="roles-modal__footer">
                <button type="button" class="roles-btn roles-btn--outline" onclick="closeDeleteModal()">Cancelar</button>
                <button type="submit" class="roles-btn roles-btn--danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
