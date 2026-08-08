<?php
    // FIX: name_role/name_role_es/description_role son nombres de campo
    // compartidos con el drawer de edición (_drawer_edit.blade.php). Sin este
    // marcador, un fallo de validación en Editar Rol también "contaminaría"
    // este formulario de Crear con los mismos old()/@error, porque old() y
    // $errors son globales a la sesión, no por formulario. El hidden
    // "_role_form" identifica a qué formulario pertenece el intento fallido.
    $isCreateFormError = old('_role_form') === 'create';
?>
<div id="roles-drawer-create" class="roles-drawer {{ $errors->any() && $isCreateFormError ? 'is-open' : '' }}">
    <div class="roles-drawer__header">
        <div>
            <h2 class="roles-drawer__title">Nuevo Rol</h2>
            <p class="roles-drawer__subtitle">Define nombre, descripción y permisos</p>
        </div>
        <button class="roles-drawer__close" type="button" onclick="closeCreateDrawer()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    </div>
    <div class="roles-drawer__body">
        <form id="roles-create-form" method="POST" action="{{ route('admin.roles.store') }}"
            onsubmit="serializePermissions('roles-create-form')">
            @csrf
            <input type="hidden" name="_role_form" value="create">
            <div class="roles-field">
                <label class="roles-label" for="create-name">Nombre del Rol <span class="roles-required">*</span></label>
                <input type="text" id="create-name" name="name_role"
                    class="roles-input {{ $isCreateFormError && $errors->has('name_role') ? 'is-invalid' : '' }}"
                    placeholder="Ej: Supervisor de Ventas" value="{{ $isCreateFormError ? old('name_role') : '' }}" required>
                @if ($isCreateFormError)
                    @error('name_role')
                        <span class="field-error-msg">{{ $message }}</span>
                    @enderror
                @endif
            </div>
            <div class="roles-field">
                <label class="roles-label" for="create-name-es">Nombre en español</label>
                <input type="text" id="create-name-es" name="name_role_es"
                    class="roles-input {{ $isCreateFormError && $errors->has('name_role_es') ? 'is-invalid' : '' }}"
                    placeholder="Ej: Supervisor de Ventas" value="{{ $isCreateFormError ? old('name_role_es') : '' }}">
                <small class="roles-field-hint">Si se deja vacío se usará el nombre del rol</small>
                @if ($isCreateFormError)
                    @error('name_role_es')
                        <span class="field-error-msg">{{ $message }}</span>
                    @enderror
                @endif
            </div>
            <div class="roles-field">
                <label class="roles-label" for="create-desc">Descripción</label>
                <textarea id="create-desc" name="description_role"
                    class="roles-textarea {{ $isCreateFormError && $errors->has('description_role') ? 'is-invalid' : '' }}"
                    rows="3"
                    placeholder="Describe las responsabilidades de este rol">{{ $isCreateFormError ? old('description_role') : '' }}</textarea>
                @if ($isCreateFormError)
                    @error('description_role')
                        <span class="field-error-msg">{{ $message }}</span>
                    @enderror
                @endif
            </div>
            <div class="roles-modules-header">
                <span class="roles-label" style="margin:0">Módulos con acceso</span>
                <div class="roles-modules-actions">
                    <button type="button" class="roles-btn-link" onclick="selectAllModules('roles-create-form')">Seleccionar todos</button>
                    <span class="roles-modules-sep">·</span>
                    <button type="button" class="roles-btn-link" onclick="clearAllModules('roles-create-form')">Limpiar</button>
                </div>
            </div>
            <div class="roles-modules-grid" id="create-modules-grid">
                @foreach($modules as $key => $module)
                <div class="roles-module-card" data-module="{{ $key }}" onclick="toggleModule(this)">
                    <span class="roles-module-name">{{ $module['name'] }}</span>
                    <div class="roles-toggle"></div>
                </div>
                @endforeach
            </div>
            <div id="roles-create-permissions-container"></div>
        </form>
    </div>
    <div class="roles-drawer__footer">
        <button type="button" class="roles-btn roles-btn--outline" onclick="closeCreateDrawer()">Cancelar</button>
        <button type="submit" form="roles-create-form" class="roles-btn roles-btn--primary">Guardar Rol</button>
    </div>
</div>
