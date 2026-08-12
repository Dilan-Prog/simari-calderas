<?php
    // FIX: mismo razonamiento que en _drawer_create.blade.php — "_role_form"
    // distingue si el intento fallido pertenece a este formulario (Editar) y
    // no al de Crear, ya que ambos comparten nombres de campo (name_role,
    // description_role) y old()/$errors son globales a la sesión.
    $isEditFormError = old('_role_form') === 'edit';
    // "_role_id" viaja como hidden dentro del propio form (ver roles.js,
    // openEditDrawer) para poder reconstruir la action del form y
    // reabrir el drawer apuntando al rol correcto tras un redirect()->back().
    $editRoleId = old('_role_id');
?>
<div id="roles-drawer-edit" class="roles-drawer {{ $errors->any() && $isEditFormError ? 'is-open' : '' }}">
    <div class="roles-drawer__header">
        <div>
            <h2 class="roles-drawer__title">Editar Rol</h2>
            <p class="roles-drawer__subtitle">Modifica nombre, descripción y permisos</p>
        </div>
        <button class="roles-drawer__close" type="button" onclick="closeEditDrawer()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    </div>
    <div class="roles-drawer__body">
        <form id="roles-edit-form" method="POST"
            action="{{ $isEditFormError && $editRoleId ? route('admin.roles.update', $editRoleId) : '' }}"
            onsubmit="serializePermissions('roles-edit-form')">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-role-id" name="_role_id" value="{{ $editRoleId }}">
            <input type="hidden" name="_role_form" value="edit">
            <div class="roles-field">
                <label class="roles-label" for="edit-name">Nombre del Rol <span class="roles-required">*</span></label>
                <input type="text" id="edit-name" name="name_role"
                    class="roles-input {{ $isEditFormError && $errors->has('name_role') ? 'is-invalid' : '' }}"
                    value="{{ $isEditFormError ? old('name_role') : '' }}" required>
                @if ($isEditFormError)
                    @error('name_role')
                        <span class="field-error-msg">{{ $message }}</span>
                    @enderror
                @endif
            </div>
            <div class="roles-field">
                <label class="roles-label" for="edit-desc">Descripción</label>
                <textarea id="edit-desc" name="description_role"
                    class="roles-textarea {{ $isEditFormError && $errors->has('description_role') ? 'is-invalid' : '' }}"
                    rows="3">{{ $isEditFormError ? old('description_role') : '' }}</textarea>
                @if ($isEditFormError)
                    @error('description_role')
                        <span class="field-error-msg">{{ $message }}</span>
                    @enderror
                @endif
            </div>
            <div class="roles-modules-header">
                <span class="roles-label" style="margin:0">Módulos con acceso</span>
                <div class="roles-modules-actions">
                    <button type="button" class="roles-btn-link" onclick="selectAllModules('roles-edit-form')">Seleccionar todos</button>
                    <span class="roles-modules-sep">·</span>
                    <button type="button" class="roles-btn-link" onclick="clearAllModules('roles-edit-form')">Limpiar</button>
                </div>
            </div>
            <div class="roles-modules-container" id="edit-modules-grid">
                @foreach($moduleGroups as $groupLabel => $groupModuleKeys)
                    @php $groupModules = collect($groupModuleKeys)->filter(fn ($key) => isset($modules[$key])); @endphp
                    @continue($groupModules->isEmpty())
                    <div class="roles-module-group">
                        <div class="roles-module-group-title">{{ $groupLabel }}</div>
                        <div class="roles-modules-grid">
                            @foreach($groupModules as $key)
                            <div class="roles-module-card" data-module="{{ $key }}">
                                <span class="roles-module-name">{{ $modules[$key]['name'] }}</span>
                                <div class="roles-module-actions">
                                    <label class="roles-action-check"><input type="checkbox" class="roles-action-view"  data-action="view"   data-module="{{ $key }}"> Lectura</label>
                                    <label class="roles-action-check"><input type="checkbox" class="roles-action-other" data-action="create" data-module="{{ $key }}"> Crear</label>
                                    <label class="roles-action-check"><input type="checkbox" class="roles-action-other" data-action="edit"   data-module="{{ $key }}"> Editar</label>
                                    <label class="roles-action-check"><input type="checkbox" class="roles-action-other" data-action="delete" data-module="{{ $key }}"> Eliminar</label>
                                    <label class="roles-action-check"><input type="checkbox" class="roles-action-other" data-action="log"    data-module="{{ $key }}"> Bitácora</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div id="roles-edit-permissions-container"></div>
        </form>
    </div>
    <div class="roles-drawer__footer">
        <button type="button" class="roles-btn roles-btn--outline" onclick="closeEditDrawer()">Cancelar</button>
        <button type="submit" form="roles-edit-form" class="roles-btn roles-btn--primary">Guardar Cambios</button>
    </div>
</div>
