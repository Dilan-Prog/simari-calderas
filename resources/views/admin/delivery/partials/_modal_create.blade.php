<?php
    // FIX: "name" y "code" son nombres de campo compartidos con el modal de
    // edición (_edit_modal.blade.php, uno por fila). Sin este marcador, un
    // fallo de validación en Editar Paquetería también "contaminaría" este
    // modal de Crear con los mismos old()/@error, porque old() y $errors son
    // globales a la sesión, no por formulario.
    $isCreateFormError = old('_delivery_form') === 'create';
?>
<div class="delivery-modal-overlay {{ $errors->any() && $isCreateFormError ? 'active' : '' }}" id="modalCrearOverlay">
    <div class="delivery-modal-content">

        {{-- Modal Header--}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border-color);">
            <h5 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--text-black);">Nueva Paquetería</h5>
            {{-- X button --}}
            <button type="button" onclick="document.getElementById('modalCrearOverlay').classList.remove('active')" style="background: transparent; border: none; font-size: 20px; cursor: pointer; color: var(--text-description-color);">
                ✕
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.deliveries.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_delivery_form" value="create">
            <div style="padding: 24px;">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Nombre *</label>
                    <input type="text" name="name" class="users-manager-input {{ $isCreateFormError && $errors->has('name') ? 'is-invalid' : '' }}" maxlength="120" value="{{ $isCreateFormError ? old('name') : '' }}" required>
                    @if ($isCreateFormError)
                        @error('name')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Código *</label>
                    <input type="text" name="code" class="users-manager-input {{ $isCreateFormError && $errors->has('code') ? 'is-invalid' : '' }}" maxlength="50" placeholder="Ej: DHL, FEDEX" value="{{ $isCreateFormError ? old('code') : '' }}" required>
                    @if ($isCreateFormError)
                        @error('code')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">URL de Rastreo</label>
                    <input type="url" name="tracking_url_template" class="users-manager-input {{ $isCreateFormError && $errors->has('tracking_url_template') ? 'is-invalid' : '' }}" placeholder="Ej: https://rastreo.com/?guia=" value="{{ $isCreateFormError ? old('tracking_url_template') : '' }}">
                    @if ($isCreateFormError)
                        @error('tracking_url_template')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Teléfono</label>
                    <input type="text" name="phone" class="users-manager-input {{ $isCreateFormError && $errors->has('phone') ? 'is-invalid' : '' }}" maxlength="30" value="{{ $isCreateFormError ? old('phone') : '' }}">
                    @if ($isCreateFormError)
                        @error('phone')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Sitio Web</label>
                    <input type="url" name="website" class="users-manager-input {{ $isCreateFormError && $errors->has('website') ? 'is-invalid' : '' }}" value="{{ $isCreateFormError ? old('website') : '' }}">
                    @if ($isCreateFormError)
                        @error('website')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-body); margin-bottom: 6px;">Estado *</label>
                    <select name="is_active" class="users-manager-select" required>
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>

            </div>

            {{-- Footer --}}
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