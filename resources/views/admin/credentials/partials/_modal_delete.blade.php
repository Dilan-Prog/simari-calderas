{{-- Delete modal --}}
<div id="deleteCredentialModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar credencial?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. Las automatizaciones que la usen dejarán de
            funcionar.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delCredentialAvatar">C</div>
            <div>
                <p class="del-confirm-user-name" id="delCredentialName">Nombre</p>
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delCredentialCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                id="delCredentialConfirm">Eliminar</button>
        </div>
    </div>
</div>
