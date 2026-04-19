@extends('admin.layouts.master')
@section('title')
    Gestor de usuarios - Admin
@endsection
@section('content')
    <div class="container user-manager">
        {{-- Toast notifications --}}
        @if (session('success'))
            <div class="toast-notification toast-success" id="toastNotification">
                <div class="toast-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>
                </div>
                <div class="toast-body">
                    <p class="toast-title">Accion realizada</p>
                    <p class="toast-message">{{ session('success') }}</p>
                </div>
                <button class="toast-close"
                    onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>
            </div>
        @endif

        @if (session('error'))
            <div class="toast-notification toast-error" id="toastNotification">
                <div class="toast-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" x2="12" y1="8" y2="12"></line>
                        <line x1="12" x2="12.01" y1="16" y2="16"></line>
                    </svg>
                </div>
                <div class="toast-body">
                    <p class="toast-title">Error</p>
                    <p class="toast-message">{{ session('error') }}</p>
                </div>
                <button class="toast-close" <button class="toast-close"
                    onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>
            </div>
        @endif
        {{-- Main content --}}
        <section class="users-manager-section">
            {{-- User manager section --}}
            <header class="users-manager-main">
                <div>
                    <h1>Gestión de Usuarios</h1>
                    <p class="breadcrumb-users-manager main">
                        Administrar roles y permisos del sistema
                    </p>
                </div>
                <button class="button-primary size-adjustment">
                    + Nuevo Usuario
                </button>
            </header>
            <!-- TABLE -->
            <main class="table-container-users-manager">
                <table class="users-manager-table">
                    <thead>
                        <tr>
                            <th>USUARIO</th>
                            <th>EMAIL</th>
                            <th>ROL</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
                <div class="table-scroll">
                    <table class="users-manager-table">
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="table-row-user-manager">
                                    <td class="user-manager-table-cell">
                                        <div class="avatar-user-manager">
                                            M
                                        </div>
                                        <div>
                                            <p class="users-manager-name-user">{{ $user->first_name }}
                                                {{ $user->last_name }}</p>
                                            <span class="users-manager-date-user">9 feb 2024</span>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="breadcrumb-users-manager main">{{ $user->email }}</p>
                                    </td>
                                    <td>
                                        @php
                                            $roleClass = '';
                                            $roleName = strtolower($user->role->name_role_es);

                                            if ($roleName == 'administrador' || $roleName == 'admin') {
                                                $roleClass = 'role-admin';
                                            } else {
                                                $roleClass = 'role-employee';
                                            }
                                        @endphp

                                        <span class="users-manager-badge {{ $roleClass }}">
                                            {{ $user->role->name_role_es }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($user->status == 'active')
                                            <span class="users-manager-badge status">Activo</span>
                                        @else
                                            <span class="users-manager-badge status-inactive">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="header-right-user-manager">
                                            {{-- edit --}}
                                            @php $ec = $user->contactEmergency->first(); @endphp
                                            <button class="table-users-manager-action-btn edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-eye">
                                                    <path
                                                        d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                                                    </path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>

                                            {{-- Edit --}}
                                            <button type="button" class="table-users-manager-action-btn edit btn-edit-user"
                                                data-id="{{ $user->id }}" data-first-name="{{ $user->first_name }}"
                                                data-last-name="{{ $user->last_name }}" data-email="{{ $user->email }}"
                                                data-phone="{{ $user->phone }}" data-position="{{ $user->position }}"
                                                data-birthdate="{{ $user->birthdate }}" data-rfc="{{ $user->rfc }}"
                                                data-curp="{{ $user->curp }}"
                                                data-ssn="{{ $user->social_segurity_number }}"
                                                data-role-id="{{ $user->role_id }}" data-status="{{ $user->status }}"
                                                @foreach ($user->contactEmergency as $i => $ec)
                                                data-ec-name-{{ $i }}="{{ $ec->name }}"
                                                data-ec-phone-{{ $i }}="{{ $ec->phone }}"
                                                data-ec-rel-{{ $i }}="{{ $ec->relationship }}" @endforeach
                                                data-ec-count="{{ $user->contactEmergency->count() }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-pen">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                    </path>
                                                </svg>
                                            </button>
                                            {{-- delete --}}
                                            <button type="button"
                                                class="table-users-manager-action-btn delete btn-delete-user"
                                                data-id="{{ $user->id }}"
                                                data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                data-email="{{ $user->email }}"
                                                data-initial="{{ strtoupper(substr($user->first_name, 0, 1)) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-trash2 lucide-trash-2">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17">
                                                    </line>
                                                    <line x1="14" x2="14" y1="11" y2="17">
                                                    </line>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </main>
        </section>

        {{-- Create user --}}
        <div id="userModal" class="user-manager-modal">
            <div class="user-manager-modal-content">
                @if ($errors->any())
                    <div class="alert alert-danger" style="margin-bottom: 12px;">
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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
                            <input class="users-manager-input" type="text" name="first_name">
                        </div>

                        <div>
                            <label class="supliers-manager-slider-label">Apellidos*</label>
                            <input class="users-manager-input" type="text" name="last_name">
                        </div>

                        <div>
                            <label class="supliers-manager-slider-label">Fecha de Nacimiento</label>
                            <input class="users-manager-input" type="date" name="birthdate">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">RFC</label>
                            <input class="users-manager-input" type="text" placeholder="XAXX010101000"
                                name="rfc">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">CURP</label>
                            <input class="users-manager-input" type="text" placeholder="XEXX010101HNEXXXA4"
                                name="curp">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Número de Seguridad Social</label>
                            <input class="users-manager-input" type="text" placeholder="12345678901"
                                name="social_segurity_number">
                        </div>
                    </div>
                    <h3>Contacto</h3>
                    <div class="user-manager-form">
                        <div class="users-manager-email-camp">
                            <label class="supliers-manager-slider-label email">Email*</label>
                            <input class="users-manager-input" type="email" name="email">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Teléfono</label>
                            <input class="users-manager-input" type="text" placeholder="(049) 123-4567"
                                name="phone">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Puesto / Cargo</label>
                            <input class="users-manager-input" type="text" placeholder="Ej. Técnico de Mantenimiento"
                                name="position">
                        </div>
                    </div>
                    <h3>Contacto de Emergencia</h3>

                    <div id="emergency-contacts-container">

                        <div class="user-manager-form user-manager-form-3 emergency-contact-item">
                            <div>
                                <label class="supliers-manager-slider-label email">Nombre del Contacto</label>
                                <input type="text" class="users-manager-input" name="emergency_contact_name[]"
                                    placeholder="Ej: Juan Pérez">
                            </div>
                            <div>
                                <label class="supliers-manager-slider-label email">Teléfono</label>
                                <input type="text" class="users-manager-input" name="emergency_phone[]"
                                    placeholder="(449) 123-4567">
                            </div>
                            <div>
                                <label class="supliers-manager-slider-label email">Parentesco</label>
                                <input type="text" class="users-manager-input" name="relationship[]"
                                    placeholder="Ej: Hermano/a, Esposo/a">
                            </div>

                            <button type="button" class="table-users-manager-action-btn delete delete-emergency-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-trash2 lucide-trash-2">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                    <line x1="10" x2="10" y1="11" y2="17">
                                    </line>
                                    <line x1="14" x2="14" y1="11" y2="17">
                                    </line>
                                </svg>
                            </button>
                        </div>
                    </div>
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
                                    <option value="{{ $role->id }}">{{ $role->name_role_es }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Estado*</label>
                            <select class="users-manager-select" name="status" id="edit_status">
                                <option value="">Seleccionar</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
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


















        {{-- Edit user --}}
        <div id="editUserModal" class="user-manager-modal">
            <div class="user-manager-modal-content">
                <!-- Header -->
                <div class="user-manager-modal-header">
                    <h2>Editar Usuario</h2>
                    <button class="table-users-manager-action-btn cancel" id="closeEditModal">x</button>
                </div>
                <div class="user-manager-photo-container">
                    <div class="user-manager-icon-container"></div>
                </div>

                <!-- Form Body -->
                <form id="editUserForm" class="user-manager-modal-body" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
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
                            <input class="users-manager-input" type="text" name="first_name" id="edit_first_name">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Apellidos*</label>
                            <input class="users-manager-input" type="text" name="last_name" id="edit_last_name">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Fecha de Nacimiento</label>
                            <input class="users-manager-input" type="date" name="birthdate" id="edit_birthdate">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">RFC</label>
                            <input class="users-manager-input" type="text" placeholder="XAXX010101000" name="rfc"
                                id="edit_rfc">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">CURP</label>
                            <input class="users-manager-input" type="text" placeholder="XEXX010101HNEXXXA4"
                                name="curp" id="edit_curp">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Número de Seguridad Social</label>
                            <input class="users-manager-input" type="text" placeholder="12345678901"
                                name="social_segurity_number" id="edit_social_segurity_number">
                        </div>
                    </div>
                    <h3>Contacto</h3>
                    <div class="user-manager-form">
                        <div class="users-manager-email-camp">
                            <label class="supliers-manager-slider-label email">Email*</label>
                            <input class="users-manager-input" type="email" name="email" id="edit_email">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Teléfono</label>
                            <input class="users-manager-input" type="text" placeholder="(049) 123-4567"
                                name="phone" id="edit_phone">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Puesto / Cargo</label>
                            <input class="users-manager-input" type="text" placeholder="Ej. Técnico de Mantenimiento"
                                name="position" id="edit_position">
                        </div>
                    </div>
                    <h3>Contacto de Emergencia</h3>
                    <div id="edit-emergency-contacts-container">
                    </div>
                    <div id="emergency-buttons-container">
                        <button type="button" id="editAddEmergencyContact" class="emergency-add-btn">
                            <span class="icon">+</span>
                            <span id="editEmergencyText">Agregar otro contacto de emergencia (1/5)</span>
                        </button>
                    </div>
                    <h3>Acceso al Sistema</h3>
                    <div class="user-manager-form access-sistem-form">
                        <div>
                            <label class="supliers-manager-slider-label">Role</label>
                            <select class="users-manager-select" style="text-transform: capitalize;" name="role_id"
                                id="edit_role_id">
                                <option value="">Seleccionar</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name_role_es }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Estado*</label>
                            <select class="users-manager-select" name="status" id="edit_status">
                                <option value="">Seleccionar</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
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
                    <div class="user-manager-modal-footer">
                        <button type="button" id="cancelEditModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                        <button type="submit" class="button-primary size-adjustment update-user">Actualizar
                            Usuario</button>
                    </div>
                </form>
            </div>
        </div>



        {{-- Delete confirmation modal --}}
        <div id="deleteUserModal" class="del-confirm-overlay">
            <div class="del-confirm-box">
                <div class="del-confirm-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                        fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <h2 class="del-confirm-title">¿Eliminar usuario?</h2>
                <p class="del-confirm-desc">Esta acción no se puede deshacer. El usuario perderá acceso al sistema
                    permanentemente.</p>
                <div class="del-confirm-user-card">
                    <div class="del-confirm-avatar" id="delConfirmAvatar">U</div>
                    <div>
                        <p class="del-confirm-user-name" id="delConfirmName">Nombre Usuario</p>
                        <p class="del-confirm-user-email" id="delConfirmEmail">email@ejemplo.com</p>
                    </div>
                </div>
                <div class="del-confirm-actions">
                    <button type="button" class="button-secondary size-adjustment"
                        id="delConfirmCancel">Cancelar</button>
                    <form id="deleteUserForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="del-confirm-btn-delete">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
        <script>
            // --- Helpers ---
            const closeModalWithAnim = (m) => {
                const mc = m.querySelector('.user-manager-modal-content');
                if (mc) {
                    mc.style.transform = 'translateX(100%)';
                    mc.style.transition = 'transform 0.2s ease-in';
                }
                m.style.transition = 'opacity 0.2s ease-in';
                setTimeout(() => {
                    m.style.display = 'none';
                    if (mc) {
                        mc.style.transform = '';
                        mc.style.transition = '';
                    }
                    m.style.transition = '';
                }, 300);
            };

            // --- Create modal ---
            const openBtn = document.querySelector('.button-primary.size-adjustment');
            const modal = document.getElementById('userModal');
            const closeBtn = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelModal');

            if (openBtn) openBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
            });
            if (closeBtn) closeBtn.addEventListener('click', () => closeModalWithAnim(modal));
            if (cancelBtn) cancelBtn.addEventListener('click', () => closeModalWithAnim(modal));

            let clickStartedOutside = false;
            window.addEventListener('mousedown', (e) => {
                clickStartedOutside = (e.target === modal || e.target === editModal);
            });
            window.addEventListener('mouseup', (e) => {
                if (clickStartedOutside && (e.target === modal || e.target === editModal)) {
                    if (e.target === modal) closeModalWithAnim(modal);
                    if (e.target === editModal) closeModalWithAnim(editModal);
                }
                clickStartedOutside = false;
            });

            // --- Edit modal ---
            const editModal = document.getElementById('editUserModal');
            const closeEditBtn = document.getElementById('closeEditModal');
            const cancelEditBtn = document.getElementById('cancelEditModal');
            const editForm = document.getElementById('editUserForm');
            const editBaseUrl = '{{ url('admin/usuarios') }}';

            // Contenedor de contactos del modal editar
            const editEcContainer = document.getElementById('edit-emergency-contacts-container');
            const editAddBtn = document.getElementById('editAddEmergencyContact');
            const editEcText = document.getElementById('editEmergencyText');
            const maxContacts = 5;

            function buildEditContactRow(data = {}) {
                const div = document.createElement('div');
                div.className = 'user-manager-form user-manager-form-3 emergency-contact-item';
                div.innerHTML = `
            <div>
                <label class="supliers-manager-slider-label email">Nombre del Contacto</label>
                <input type="text" class="users-manager-input" name="emergency_contact_name[]"
                    placeholder="Ej: Juan Pérez" value="${data.name || ''}">
            </div>
            <div>
                <label class="supliers-manager-slider-label email">Teléfono</label>
                <input type="text" class="users-manager-input" name="emergency_phone[]"
                    placeholder="(449) 123-4567" value="${data.phone || ''}">
            </div>
            <div>
                <label class="supliers-manager-slider-label email">Parentesco</label>
                <input type="text" class="users-manager-input" name="relationship[]"
                    placeholder="Ej: Hermano/a, Esposo/a" value="${data.relationship || ''}">
            </div>
            <button type="button" class="table-users-manager-action-btn delete edit-delete-emergency-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    <line x1="10" x2="10" y1="11" y2="17"></line>
                    <line x1="14" x2="14" y1="11" y2="17"></line>
                </svg>
            </button>`;
                div.querySelector('.edit-delete-emergency-btn').addEventListener('click', () => {
                    div.remove();
                    updateEditUI();
                });
                return div;
            }

            function updateEditUI() {
                const total = editEcContainer.querySelectorAll('.emergency-contact-item').length;
                editEcText.textContent = `Agregar otro contacto de emergencia (${total}/${maxContacts})`;
                editAddBtn.style.display = total >= maxContacts ? 'none' : 'flex';
            }

            editAddBtn.addEventListener('click', () => {
                const total = editEcContainer.querySelectorAll('.emergency-contact-item').length;
                if (total >= maxContacts) return;
                editEcContainer.appendChild(buildEditContactRow());
                updateEditUI();
            });

            const openEditModal = (d, btn) => {

                const roleSelect = document.getElementById('edit_role_id');
                const statusSelect = document.getElementById('edit_status');
                editForm.action = editBaseUrl + '/' + d.id;

                document.getElementById('edit_first_name').value = d.firstName || '';
                document.getElementById('edit_last_name').value = d.lastName || '';
                document.getElementById('edit_birthdate').value = d.birthdate || '';
                document.getElementById('edit_rfc').value = d.rfc || '';
                document.getElementById('edit_curp').value = d.curp || '';
                document.getElementById('edit_social_segurity_number').value = d.ssn || '';
                document.getElementById('edit_email').value = d.email || '';
                document.getElementById('edit_phone').value = d.phone || '';
                document.getElementById('edit_position').value = d.position || '';
                Array.from(roleSelect.options).forEach(opt => {
                    opt.selected = opt.value == d.roleId;
                });
                Array.from(statusSelect.options).forEach(opt => {
                    opt.selected = opt.value == d.status;
                });
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirmation').value = '';

                editEcContainer.innerHTML = '';
                const count = parseInt(btn.getAttribute('data-ec-count') || '0');

                if (count === 0) {
                    editEcContainer.appendChild(buildEditContactRow());
                } else {
                    for (let i = 0; i < count; i++) {
                        editEcContainer.appendChild(buildEditContactRow({
                            name: btn.getAttribute('data-ec-name-' + i) || '',
                            phone: btn.getAttribute('data-ec-phone-' + i) || '',
                            relationship: btn.getAttribute('data-ec-rel-' + i) || ''
                        }));
                    }
                }
                updateEditUI();

                editModal.style.display = 'flex';
            };

            document.querySelectorAll('.btn-edit-user').forEach(btn => {
                btn.addEventListener('click', () => openEditModal(btn.dataset, btn));
            });

            if (closeEditBtn) closeEditBtn.addEventListener('click', () => closeModalWithAnim(editModal));
            if (cancelEditBtn) cancelEditBtn.addEventListener('click', () => closeModalWithAnim(editModal));

            // --- Create modal emergency contacts ---
            const addBtn = document.getElementById('addEmergencyContact');
            const container = document.getElementById('emergency-contacts-container');
            const textCounter = document.getElementById('emergencyText');

            function updateUI() {
                const contacts = container.querySelectorAll('.emergency-contact-item');
                const total = contacts.length;
                textCounter.textContent = `Agregar otro contacto de emergencia (${total}/${maxContacts})`;
                contacts.forEach(contact => {
                    contact.querySelector('.delete-emergency-btn').style.display = total > 1 ? 'block' :
                        'none';
                });
                addBtn.style.display = total >= maxContacts ? 'none' : 'flex';
            }

            addBtn.addEventListener('click', () => {
                if (container.querySelectorAll('.emergency-contact-item').length >= maxContacts) return;
                const newContact = container.querySelector('.emergency-contact-item').cloneNode(true);
                newContact.querySelectorAll('input').forEach(i => i.value = '');
                newContact.querySelector('.delete-emergency-btn').addEventListener('click', () => {
                    newContact.remove();
                    updateUI();
                });
                container.appendChild(newContact);
                updateUI();
            });

            container.querySelectorAll('.delete-emergency-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.emergency-contact-item').remove();
                    updateUI();
                });
            });

            updateUI();

            // --- Delete confirmation modal ---
            const deleteModal = document.getElementById('deleteUserModal');
            const deleteForm = document.getElementById('deleteUserForm');
            const delCancelBtn = document.getElementById('delConfirmCancel');

            document.querySelectorAll('.btn-delete-user').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const name = btn.dataset.name;
                    const email = btn.dataset.email;
                    const initial = btn.dataset.initial;

                    document.getElementById('delConfirmName').textContent = name;
                    document.getElementById('delConfirmEmail').textContent = email;
                    document.getElementById('delConfirmAvatar').textContent = initial;

                    deleteForm.action = "{{ url('admin/usuarios') }}/" + id;
                    deleteModal.classList.add('active');
                });
            });

            delCancelBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
            deleteModal
                .addEventListener('click', (e) => {
                    if (e.target === deleteModal) deleteModal.classList.remove('active');
                });

            document.querySelectorAll('.toast-notification').forEach(toast => {
                setTimeout(() => {
                    toast.style.animation = 'toastOut 0.3s ease forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            });
        </script>
    </div>
@endsection
