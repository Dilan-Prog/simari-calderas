{{-- Confirmación obligatoria antes de ejecutar cualquier sentencia de escritura --}}
<div id="devopsConfirmModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Ejecutar sentencia SQL de escritura?</h2>
        <p class="del-confirm-desc">Esta sentencia modificará la base de datos. Se creará un respaldo automático antes de ejecutarla — si el respaldo falla, no se ejecutará nada.</p>
        <pre id="devopsConfirmSqlPreview" class="devops-sql-preview"></pre>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="devopsConfirmCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button" id="devopsConfirmRun">Ejecutar</button>
        </div>
    </div>
</div>
