<div class="delivery-modal-overlay" id="modalEditarOverlay{{ $paquete->id }}">
    <div class="delivery-modal-content">
        
        {{-- Header --}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border-color);">
            <h5 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--text-black);">Editar Paquetería</h5>
            {{-- Botón X para cerrar --}}
            <button type="button" onclick="document.getElementById('modalEditarOverlay{{ $paquete->id }}').classList.remove('active')" style="background: transparent; border: none; font-size: 20px; cursor: pointer; color: var(--text-description-color);">
                ✕
            </button>
        </div>

        {{-- Formulario --}}
        <form action="#" method="POST">
            @csrf
            @method('PUT')
            <div style="padding: 24px;">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Nombre *</label>
                    <input type="text" name="nombre" class="users-manager-input" value="{{ $paquete->nombre }}" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">URL del Logo</label>
                    <input type="url" name="logo" class="users-manager-input" value="https://images.unsplash.com/photo-1707596830261-9c6138...">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Tiempo de Entrega *</label>
                    <input type="text" name="tiempo_entrega" class="users-manager-input" value="{{ $paquete->tiempo_entrega }}" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Cobertura *</label>
                    <input type="text" name="cobertura" class="users-manager-input" value="{{ $paquete->cobertura }}" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Estado *</label>
                    <select name="estado" class="users-manager-select">
                        <option value="Activa" {{ $paquete->estado == 'Activa' ? 'selected' : '' }}>Activa</option>
                        <option value="Inactiva" {{ $paquete->estado == 'Inactiva' ? 'selected' : '' }}>Inactiva</option>
                    </select>
                </div>

            </div>

            {{-- Footer --}}
            <div style="padding: 0 24px 24px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="document.getElementById('modalEditarOverlay{{ $paquete->id }}').classList.remove('active')" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-body); padding: 10px 20px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" style="background: var(--button-primary-color); border: none; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Actualizar
                </button>
            </div>
        </form>
        
    </div>
</div>