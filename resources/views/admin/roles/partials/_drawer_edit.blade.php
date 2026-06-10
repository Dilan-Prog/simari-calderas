<div id="roles-drawer-edit" class="roles-drawer">
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
        <form id="roles-edit-form" method="POST" action=""
            onsubmit="serializePermissions('roles-edit-form')">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-role-id" name="_role_id" value="">
            <div class="roles-field">
                <label class="roles-label" for="edit-name">Nombre del Rol <span class="roles-required">*</span></label>
                <input type="text" id="edit-name" name="name_role" class="roles-input" required>
            </div>
            <div class="roles-field">
                <label class="roles-label" for="edit-desc">Descripción</label>
                <textarea id="edit-desc" name="description_role" class="roles-textarea" rows="3"></textarea>
            </div>
            <div class="roles-modules-header">
                <span class="roles-label" style="margin:0">Módulos con acceso</span>
                <div class="roles-modules-actions">
                    <button type="button" class="roles-btn-link" onclick="selectAllModules('roles-edit-form')">Seleccionar todos</button>
                    <span class="roles-modules-sep">·</span>
                    <button type="button" class="roles-btn-link" onclick="clearAllModules('roles-edit-form')">Limpiar</button>
                </div>
            </div>
            <div class="roles-modules-grid" id="edit-modules-grid">
                @foreach($modules as $key => $module)
                <div class="roles-module-card" data-module="{{ $key }}" onclick="toggleModule(this)">
                    <span class="roles-module-name">{{ $module['name'] }}</span>
                    <div class="roles-toggle"></div>
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
