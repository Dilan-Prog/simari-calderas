<div id="modalEliminarOverlay{{ $paquete->id }}" class="del-confirm-overlay">
    <div class="del-confirm-box" style="font-family: var(--font-family);">
        
        {{-- Ícono de Alerta Rojo --}}
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
            </svg>
        </div>

        {{-- Textos principales --}}
        <h2 class="del-confirm-title">¿Eliminar Paquetería?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. Se borrará del sistema de forma permanente.</p>
        
        {{-- Tarjeta de resumen de la paquetería --}}
        <div class="del-confirm-user-card" style="justify-content: center; text-align: center;">
            <div>
                <p class="del-confirm-user-name" style="font-size: 16px;">{{ $paquete->nombre }}</p>
                <p class="del-confirm-user-email">Cobertura: {{ $paquete->cobertura }}</p>
            </div>
        </div>

        {{-- Botones de Acción --}}
        <div class="del-confirm-actions">
            {{-- Botón Cancelar (Usa onclick para remover la clase active) --}}
            <button type="button" class="button-secondary size-adjustment" 
                onclick="document.getElementById('modalEliminarOverlay{{ $paquete->id }}').classList.remove('active')">
                Cancelar
            </button>
            
            {{-- Formulario de eliminación --}}
            <form action="#" method="POST" id="deleteDeliveryForm{{ $paquete->id }}">
                @csrf
                @method('DELETE')
                {{-- Botón Eliminar (Rojo) --}}
                <button type="submit" class="button-primary size-adjustment delete-confirmation-modal-button" style="background-color: var(--button-primary-color-rinnai);">
                    Eliminar
                </button>
            </form>
        </div>

    </div>
</div>