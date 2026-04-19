{{-- Create user --}}
<div id="userModal" class="user-manager-modal">
    <div class="user-manager-modal-content">
        <!-- Header -->
        <div class="user-manager-modal-header">
            <h2>Nuevo Usuario</h2>
            <button class="table-users-manager-action-btn cancel" id="closeModal">x</button>
        </div>
        @if ($errors->any())
            <div
                style="background:#fee2e2;border:1px solid #f87171;border-radius:6px;padding:12px 16px;margin:12px 16px 0;">
                <ul style="margin:0;padding-left:16px;color:#b91c1c;font-size:0.875rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="user-manager-photo-container">
            <div class="user-manager-icon-container">
            </div>
        </div>




        <!-- Form Body -->
        <form class="user-manager-modal-body" action="{{ route('admin.users.store') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <h3>Información Personal</h3>
            <div class="user-manager-avatar-upload-container">
                <div class="user-manager-avatar-upload">
                    <span class="user-manager-avatar-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-camera text-gray-400">
                            <path
                                d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z">
                            </path>
                            <circle cx="12" cy="13" r="3"></circle>
                        </svg>
                    </span>
                </div>
                <button type="button" class="user-manager-avatar-upload-btn">+</button>
            </div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre</label>
                    <input class="users-manager-input" type="text" name="first_name" value="{{ old('first_name') }}">
                </div>

                <div>
                    <label class="supliers-manager-slider-label">Apellidos*</label>
                    <input class="users-manager-input" type="text" name="last_name" value="{{ old('last_name') }}">
                </div>

                <div>
                    <label class="supliers-manager-slider-label">Fecha de Nacimiento</label>
                    <input class="users-manager-input" type="date" name="birthdate" value="{{ old('birthdate') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC</label>
                    <input class="users-manager-input" type="text" placeholder="XAXX010101000"
                        name="rfc" value="{{ old('rfc') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">CURP</label>
                    <input class="users-manager-input" type="text" placeholder="XEXX010101HNEXXXA4"
                        name="curp" value="{{ old('curp') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Número de Seguridad Social</label>
                    <input class="users-manager-input" type="text" placeholder="12345678901"
                        name="social_segurity_number" value="{{ old('social_segurity_number') }}">
                </div>
            </div>
            <h3>Contacto</h3>
            <div class="user-manager-form">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label email">Email*</label>
                    <input class="users-manager-input" type="email" name="email" value="{{ old('email') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono</label>
                    <input class="users-manager-input" type="text" placeholder="(049) 123-4567"
                        name="phone" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Puesto / Cargo</label>
                    <input class="users-manager-input" type="text" placeholder="Ej. Técnico de Mantenimiento"
                        name="position" value="{{ old('position') }}">
                </div>
            </div>
            <h3>Contacto de Emergencia</h3>

            <div id="emergency-contacts-container"></div>
            <div id="emergency-buttons-container">
                <button type="button" id="addEmergencyContact" class="emergency-add-btn">
                    <span class="icon">+</span>
                    <span id="emergencyText">Agregar otro contacto de emergencia (1/5)</span>
                </button>
            </div>

            <div class="user-manager-form access-sistem-form">
                <div>
                    <label class="supliers-manager-slider-label">Role</label>
                    <select class="users-manager-select" style="text-transform: capitalize;" name="role_id"
                        id="edit_role_id">
                        <option value="">Seleccionar</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name_role_es }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado*</label>
                    <select class="users-manager-select" name="status" id="edit_status">
                        <option value="">Seleccionar</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspendido</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Nueva Contraseña</label>
                    <input class="users-manager-input password" type="password" name="password"
                        id="edit_password">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-eye">
                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                        </path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Confirmar Contraseña</label>
                    <input class="users-manager-input password" type="password" name="password_confirmation"
                        id="edit_password_confirmation">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-eye">
                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                        </path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
            </div>

            <!-- BUTTON -->
            <div class="user-manager-modal-footer">
                <button type="button" id="cancelModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment create-user">Guardar
                    Usuario</button>
            </div>
        </form>

    </div>

</div>
