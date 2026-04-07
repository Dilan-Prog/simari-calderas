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

                    <tbody>

                        {{-- Rows --}}
                        <tr class="table-row-user-manager">
                            <td class="user-manager-table-cell">
                                <div class="avatar-user-manager">
                                    M
                                </div>
                                <div>
                                    <p class="users-manager-name-user">María González</p>
                                    <span class="users-manager-date-user">9 feb 2024</span>
                                </div>
                            </td>

                            <td>
                                <p class="breadcrumb-users-manager main">maria.gonzalez@simari.com</p>
                            </td>

                            <td>
                                <span class="users-manager-badge role-admin">Administrador</span>
                            </td>

                            <td>
                                <span class="users-manager-badge status">Activo</span>
                            </td>

                            <td>
                                <div class="header-right-user-manager">
                                    <button class="table-users-manager-action-btn edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen">
                                            <path
                                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button class="table-users-manager-action-btn delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-trash2 lucide-trash-2">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            <line x1="10" x2="10" y1="11" y2="17"></line>
                                            <line x1="14" x2="14" y1="11" y2="17"></line>
                                        </svg></button>
                                </div>
                            </td>
                        </tr>


                        <tr>
                            <td class="user-manager-table-cell">
                                <div class="avatar-user-manager">
                                    D
                                </div>
                                <div>
                                    <p class="users-manager-name-user">Damian Lara</p>
                                    <span class="users-manager-date-user">14 feb 2024</span>
                                </div>
                            </td>

                            <td>
                                <p class="breadcrumb-users-manager main">damian.lara@simari.com</p>
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
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen">
                                            <path
                                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button class="table-users-manager-action-btn delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-trash2 lucide-trash-2">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            <line x1="10" x2="10" y1="11" y2="17"></line>
                                            <line x1="14" x2="14" y1="11" y2="17"></line>
                                        </svg></button>
                                </div>
                            </td>
                        </tr>

                        @for ($i = 0; $i <= 12; $i++)
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
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen">
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
                        @endfor

                    </tbody>

                </table>

            </main>

        </section>
    </div>
@endsection
