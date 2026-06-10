{{-- Delete modal --}}
<div id="deleteBrandModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar marca?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. No puedes eliminar marcas con productos
            asociados.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delBrandAvatar">M</div>
            <div>
                <p class="del-confirm-user-name" id="delBrandName">Nombre</p>
                <p class="del-confirm-user-email" id="delBrandSlug">/slug</p>
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delBrandCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                id="delBrandConfirm">Eliminar</button>
        </div>
    </div>
</div>
