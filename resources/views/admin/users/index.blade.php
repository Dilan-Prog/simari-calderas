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
                <button class="toast-close"
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
                                            {{-- view --}}
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
                                                data-id="{{ $user->id }}">
                                                {{-- edit svg --}}
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
                                                {{-- delete svg --}}
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
        @include('admin.users.partials._modal_create')
        @include('admin.users.partials._modal_edit')
    </div>
    @include('admin.users.partials._modal_delete')
@endsection

@include('admin.users.partials._scripts')
