<div class="delivery-modal-overlay" id="modalEditarOverlay{{ $delivery->id }}">
    <div class="delivery-modal-content">
        
        {{-- Header --}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border-color);">
            <h5 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--text-black);">Editar Paquetería</h5>
            {{-- Button X --}}
            <button type="button" onclick="document.getElementById('modalEditarOverlay{{ $delivery->id }}').classList.remove('active')" style="background: transparent; border: none; font-size: 20px; cursor: pointer; color: var(--text-description-color);">
                ✕
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.deliveries.update', $delivery->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div style="padding: 24px;">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Nombre *</label>
                    <input type="text" name="name" class="users-manager-input" value="{{ $delivery->name }}" maxlength="120" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Código *</label>
                    <input type="text" name="code" class="users-manager-input" value="{{ $delivery->code }}" maxlength="50" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">URL de Rastreo</label>
                    <input type="url" name="tracking_url_template" class="users-manager-input" value="{{ $delivery->tracking_url_template }}">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Teléfono</label>
                    <input type="text" name="phone" class="users-manager-input" value="{{ $delivery->phone }}" maxlength="30">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Sitio Web</label>
                    <input type="url" name="website" class="users-manager-input" value="{{ $delivery->website }}">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Estado *</label>
                    <select name="is_active" class="users-manager-select" required>
                        <option value="1" {{ $delivery->is_active == 1 ? 'selected' : '' }}>Activa</option>
                        <option value="0" {{ $delivery->is_active == 0 ? 'selected' : '' }}>Inactiva</option>
                    </select>
                </div>

            </div>

            {{-- Footer --}}
            <div style="padding: 0 24px 24px 24px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="document.getElementById('modalEditarOverlay{{ $delivery->id }}').classList.remove('active')" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-body); padding: 10px 20px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" style="background: var(--button-primary-color); border: none; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 500; font-family: var(--font-family); cursor: pointer;">
                    Actualizar
                </button>
            </div>
        </form>
        
    </div>
</div>