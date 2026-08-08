<?php
    // FIX: este partial se renderiza una vez por cada paquetería dentro de
    // @foreach($deliveries as $delivery) en index.blade.php, pero old()/
    // $errors son globales a la sesión — no saben a qué fila pertenecen. Sin
    // este chequeo, un fallo de validación al editar la paquetería #3
    // "contaminaría" con los mismos old()/@error los modales de TODAS las
    // demás filas (mismos nombres de campo: name, code, etc). "_delivery_id"
    // (hidden, viaja con el POST) identifica cuál fila fue la que falló.
    $isThisDeliveryError = $errors->any() && (string) old('_delivery_id') === (string) $delivery->id;
?>
<div class="delivery-modal-overlay {{ $isThisDeliveryError ? 'active' : '' }}" id="modalEditarOverlay{{ $delivery->id }}">
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
            <input type="hidden" name="_delivery_form" value="edit">
            <input type="hidden" name="_delivery_id" value="{{ $delivery->id }}">
            <div style="padding: 24px;">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Nombre *</label>
                    <input type="text" name="name" class="users-manager-input {{ $isThisDeliveryError && $errors->has('name') ? 'is-invalid' : '' }}" value="{{ $isThisDeliveryError ? old('name', $delivery->name) : $delivery->name }}" maxlength="120" required>
                    @if ($isThisDeliveryError)
                        @error('name')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Código *</label>
                    <input type="text" name="code" class="users-manager-input {{ $isThisDeliveryError && $errors->has('code') ? 'is-invalid' : '' }}" value="{{ $isThisDeliveryError ? old('code', $delivery->code) : $delivery->code }}" maxlength="50" required>
                    @if ($isThisDeliveryError)
                        @error('code')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">URL de Rastreo</label>
                    <input type="url" name="tracking_url_template" class="users-manager-input {{ $isThisDeliveryError && $errors->has('tracking_url_template') ? 'is-invalid' : '' }}" value="{{ $isThisDeliveryError ? old('tracking_url_template', $delivery->tracking_url_template) : $delivery->tracking_url_template }}">
                    @if ($isThisDeliveryError)
                        @error('tracking_url_template')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Teléfono</label>
                    <input type="text" name="phone" class="users-manager-input {{ $isThisDeliveryError && $errors->has('phone') ? 'is-invalid' : '' }}" value="{{ $isThisDeliveryError ? old('phone', $delivery->phone) : $delivery->phone }}" maxlength="30">
                    @if ($isThisDeliveryError)
                        @error('phone')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Sitio Web</label>
                    <input type="url" name="website" class="users-manager-input {{ $isThisDeliveryError && $errors->has('website') ? 'is-invalid' : '' }}" value="{{ $isThisDeliveryError ? old('website', $delivery->website) : $delivery->website }}">
                    @if ($isThisDeliveryError)
                        @error('website')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Estado *</label>
                    <select name="is_active" class="users-manager-select {{ $isThisDeliveryError && $errors->has('is_active') ? 'is-invalid' : '' }}" required>
                        <option value="1" {{ ($isThisDeliveryError ? old('is_active', $delivery->is_active) : $delivery->is_active) == 1 ? 'selected' : '' }}>Activa</option>
                        <option value="0" {{ ($isThisDeliveryError ? old('is_active', $delivery->is_active) : $delivery->is_active) == 0 ? 'selected' : '' }}>Inactiva</option>
                    </select>
                    @if ($isThisDeliveryError)
                        @error('is_active')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
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