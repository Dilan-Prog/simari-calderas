@extends('admin.layouts.master')
@section('title')
    Gestor de usuarios - Admin
@endsection
@section('content')
    <div class="container user-manager">
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
                                        <span class="users-manager-badge role-admin">{{ $user->role->name_role_es }}</span>
                                    </td>
                                    <td>
                                        <span class="users-manager-badge status">
                                            @switch($user->status)
                                                @case('active')
                                                    Activo
                                                @break

                                                @case('inactive')
                                                    Inactivo
                                                @break

                                                @case('suspended')
                                                    Suspendido
                                                @break

                                                @default
                                                    Desconocido
                                            @endswitch
                                        </span>
                                    </td>
                                    <td>
                                        <div class="header-right-user-manager">
                                            <button class="table-users-manager-action-btn edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-pen">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button class="table-users-manager-action-btn delete">
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
                                                </svg></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            {{-- @for ($i = 0; $i <= 12; $i++)
                                <tr>
                                    <td class="user-manager-table-cell">
                                        <div class="avatar-user-manager">
                                            A
                                        </div>
                                        <div>
                                            <p class="users-manager-name-user">Ana Torres</p>
                                            <span class="users-manager-date-user">9 may 2024</span>
                                        </div>
                                    </td>

                                    <td>
                                        <p class="breadcrumb-users-manager main">ana.torres@simari.com</p>
                                    </td>

                                    <td>
                                        <span class="users-manager-badge role-employee">Empleado</span>
                                    </td>

                                    <td>
                                        <span class="users-manager-badge status">Activo</span>
                                    </td>

                                    <td>
                                        <div class="header-right-user-manager">
                                            <button class="table-users-manager-action-btn edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-pen">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button class="table-users-manager-action-btn delete">
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
                                                </svg></button>
                                        </div>
                                    </td>
                                </tr>
                            @endfor --}}
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
                    <div class="user-manager-form user-manager-form-3">
                        <div>
                            <label class="supliers-manager-slider-label email">Nombre del Contacto</label>
                            <input type="text" class="users-manager-input" name="emergency_contact_name"
                                placeholder="Ej: Juan Pérez">
                        </div>
                        {{-- revisar --}}
                        <div>
                            <label class="supliers-manager-slider-label email">Teléfono</label>
                            <input type="text" class="users-manager-input" name="emergency_phone"
                                placeholder="(449) 123-4567">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label email">Parentesco</label>
                            <input type="text" class="users-manager-input" name="relationship"
                                placeholder="Ej: Hermano/a, Esposo/a">
                        </div>
                        {{-- revisar --}}
                    </div>
                    <h3>Acceso al Sistema</h3>
                    <div class="user-manager-form access-sistem-form">
                        <div>
                            <label class="supliers-manager-slider-label">Role</label>
                            <select class="users-manager-select" name="role_id">
                                <option value="">Seleccionar</option>
                                <option value="1">Administrador</option>
                                <option value="2">Empleado</option>
                                <option value="3">Editor</option>
                                {{-- @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach --}}
                            </select>
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Estado*</label>
                            <select class="users-manager-select" name="status">
                                <option value="">Seleccionar</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Contraseña*</label>
                            <input class="users-manager-input password" type="password" name="password">
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
                            <label class="supliers-manager-slider-label">Confirmar Contraseña*</label>
                            <input class="users-manager-input password" type="password" name="password_confirmation"
                                id="password_confirmation">
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
                    {{-- Permissions  --}}
                    <h3>Permisos por Módulo</h3>
                    <div class="user-manager-permissions-grid">
                        <!-- ITEM -->
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-layout-dashboard text-gray-600">
                                    <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                                    <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                                    <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                                    <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-building2 lucide-building-2 text-gray-600">
                                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                    <path d="M10 6h4"></path>
                                    <path d="M10 10h4"></path>
                                    <path d="M10 14h4"></path>
                                    <path d="M10 18h4"></path>
                                </svg>
                                <span>Clientes</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-user-cog text-gray-600">
                                    <circle cx="18" cy="15" r="3"></circle>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M10 15H6a4 4 0 0 0-4 4v2"></path>
                                    <path d="m21.7 16.4-.9-.3"></path>
                                    <path d="m15.2 13.9-.9-.3"></path>
                                    <path d="m16.6 18.7.3-.9"></path>
                                    <path d="m19.1 12.2.3-.9"></path>
                                    <path d="m19.6 18.7-.4-1"></path>
                                    <path d="m16.8 12.3-.4-1"></path>
                                    <path d="m14.3 16.6 1-.4"></path>
                                    <path d="m20.7 13.8 1-.4"></path>
                                </svg>
                                <span>Proveedores</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-shopping-bag text-gray-600">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                    <path d="M3 6h18"></path>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                <span>Productos</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-folder-tree text-gray-600">
                                    <path
                                        d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z">
                                    </path>
                                    <path
                                        d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z">
                                    </path>
                                    <path d="M3 5a2 2 0 0 0 2 2h3"></path>
                                    <path d="M3 3v13a2 2 0 0 0 2 2h3"></path>
                                </svg>
                                <span>Categorías</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-shopping-cart text-gray-600">
                                    <circle cx="8" cy="21" r="1"></circle>
                                    <circle cx="19" cy="21" r="1"></circle>
                                    <path
                                        d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12">
                                    </path>
                                </svg>
                                <span>Órdenes</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-wrench text-gray-600">
                                    <path
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                                    </path>
                                </svg>
                                <span>Servicios Técnicos</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-archive text-gray-600">
                                    <rect width="20" height="5" x="2" y="3" rx="1"></rect>
                                    <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path>
                                    <path d="M10 12h4"></path>
                                </svg>
                                <span>Inventario</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-truck text-gray-600">
                                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path>
                                    <path d="M15 18H9"></path>
                                    <path
                                        d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14">
                                    </path>
                                    <circle cx="17" cy="18" r="2"></circle>
                                    <circle cx="7" cy="18" r="2"></circle>
                                </svg>
                                <span>Envíos</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-package text-gray-600">
                                    <path
                                        d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                    </path>
                                    <path d="M12 22V12"></path>
                                    <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                    <path d="m7.5 4.27 9 5.15"></path>
                                </svg>
                                <span>Paqueterías</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-credit-card text-gray-600">
                                    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                    <line x1="2" x2="22" y1="10" y2="10"></line>
                                </svg>
                                <span>Métodos de Pago</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-mail text-gray-600">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <span>Email Marketing</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-chart-column text-gray-600">
                                    <path d="M3 3v16a2 2 0 0 0 2 2h16"></path>
                                    <path d="M18 17V9"></path>
                                    <path d="M13 17V5"></path>
                                    <path d="M8 17v-3"></path>
                                </svg>
                                <span>Analíticas</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="user-manager-permission-item">
                            <div class="user-manager-permission-left-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-activity text-gray-600">
                                    <path
                                        d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2">
                                    </path>
                                </svg>
                                <span>Auditoría Sistema</span>
                            </div>
                            <label class="user-manager-switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="user-manager-modal-footer">
                        <button type="button" id="cancelModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                        <button type="submit" class="button-primary size-adjustment create-user">Guardar Usuario</button>
                    </div>

                </form>

            </div>

        </div>

        <script>
            const openBtn = document.querySelector('.button-primary');
            const modal = document.getElementById('userModal');
            const modalContent = modal.querySelector('.user-manager-modal-content');
            const closeBtn = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelModal');
            const openModal = () => {
                modal.style.display = 'flex';
            };
            const closeModalWithAnim = () => {
                modalContent.style.transform = 'translateX(100%)';
                modalContent.style.transition = 'transform 0.2s ease-in';
                modal.style.transition = 'opacity 0.2s ease-in';
                setTimeout(() => {
                    modal.style.display = 'none';
                    modalContent.style.transform = '';
                    modalContent.style.transition = '';
                    modal.style.transition = '';
                }, 300);
            };
            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModalWithAnim);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModalWithAnim);
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModalWithAnim();
                }
            });
        </script>
    </div>
@endsection
