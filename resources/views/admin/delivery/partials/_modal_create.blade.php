<div class="delivery-modal-overlay" id="modalCrearOverlay">
    <div class="delivery-modal-content">
        
        {{-- Header del Modal --}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border-color);">
            <h5 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--text-black);">Nueva Paquetería</h5>
            {{-- Botón X para cerrar --}}
            <button type="button" onclick="document.getElementById('modalCrearOverlay').classList.remove('active')" style="background: transparent; border: none; font-size: 20px; cursor: pointer; color: var(--text-description-color);">
                ✕
            </button>
        </div>

        {{-- Formulario --}}
        <form action="#" method="POST">
            @csrf
            <div style="padding: 24px;">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Nombre *</label>
                    <input type="text" name="nombre" class="users-manager-input" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">URL del Logo</label>
                    <input type="url" name="logo" class="users-manager-input">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Tiempo de Entrega *</label>
                    <input type="text" name="tiempo_entrega" class="users-manager-input" placeholder="Ej: 3-5 días" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Cobertura *</label>
                    <input type="text" name="cobertura" class="users-manager-input" placeholder="Ej: Nacional" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Estado *</label>
                    <select name="estado" class="users-manager-select">
                        <option value="Activa">Activa</option>
                        <option value="Inactiva">Inactiva</option>
                    </select>
                </div>

            </div>

            {{-- Footer del Modal --}}
            <div style="padding: 0 24px 24px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                {{-- Botón Cancelar cierra el modal quitándole la clase active --}}
                <button type="button" onclick="document.getElementById('modalCrearOverlay').classList.remove('active')" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-body); padding: 10px 20px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" style="background: var(--button-primary-color); border: none; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Crear
                </button>
            </div>
        </form>
        
    </div>
</div>