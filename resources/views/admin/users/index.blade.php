@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/usuarios.css')
@endpush
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
                <div class="users-header-actions">
                    @include('admin.components._column_visibility_menu', [
                        'tableKey' => 'users.index',
                        'columnDefs' => ['email' => 'Email', 'rol' => 'Rol', 'estado' => 'Estado'],
                    ])
                    @permiso('users', 'create')
                    <button class="button-primary size-adjustment" id="btnNewUser">
                        + Nuevo Usuario
                    </button>
                    @endpermiso
                </div>
            </header>
            <!-- TABLE -->
            <main class="table-container-users-manager">
                <table class="users-manager-table">
                    <thead>
                        <tr>
                            <th>USUARIO</th>
                            <th data-col="email">EMAIL</th>
                            <th data-col="rol">ROL</th>
                            <th data-col="estado">ESTADO</th>
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
                                        {{-- FIX QA: avatar letter and join date were hardcoded
                                             ("M" / "9 feb 2024") for every row regardless of the
                                             actual user. --}}
                                        <div class="avatar-user-manager">
                                            {{ strtoupper(substr($user->first_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="users-manager-name-user">{{ $user->first_name }}
                                                {{ $user->last_name }}</p>
                                            {{-- ->locale('es') is a per-instance Carbon override, not a
                                                 global config change — the app's default locale is 'en'
                                                 (config/app.php, shared/out of this module's scope). --}}
                                            <span class="users-manager-date-user">{{ $user->created_at?->locale('es')->translatedFormat('j M Y') }}</span>
                                        </div>
                                    </td>
                                    <td data-col="email">
                                        <p class="breadcrumb-users-manager main">{{ $user->email }}</p>
                                    </td>
                                    <td data-col="rol">
                                        @php
                                            $roleClass = '';
                                            $roleName  = strtolower($user->role->name_role_es ?? '');

                                            if ($roleName === 'administrador' || $roleName === 'admin') {
                                                $roleClass = 'role-admin';
                                            } else {
                                                $roleClass = 'role-employee';
                                            }
                                        @endphp

                                        <span class="users-manager-badge {{ $roleClass }}">
                                            {{ $user->role->name_role_es ?? 'Sin rol' }}
                                        </span>
                                    </td>
                                    <td data-col="estado">
                                        @if ($user->status == 'active')
                                            <span class="users-manager-badge status">Activo</span>
                                        @else
                                            <span class="users-manager-badge status-inactive">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="header-right-user-manager">
                                            {{-- view --}}
                                            <button class="table-users-manager-action-btn edit btn-show-user"
                                                data-id="{{ $user->id }}">
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
                                            @permiso('users', 'edit')
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
                                            @endpermiso
                                            {{-- delete --}}
                                            @permiso('users', 'delete')
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
                                            @endpermiso
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
    @include('admin.users.partials._modal_show')
@endsection
@push('scripts')
@include('admin.users.partials._scripts')
@endpush
@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'users.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
