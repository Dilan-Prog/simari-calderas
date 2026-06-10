@extends('admin.layouts.master')

@section('title', 'Gestión de Roles')

@push('styles')
    @vite('resources/css/admin/roles.css')
@endpush

@section('content')
<div class="roles-page"
    data-url-show="{{ url('admin/roles/mostrar-rol') }}"
    data-url-update="{{ url('admin/roles/editar-rol') }}"
    data-url-delete="{{ url('admin/roles/eliminar-rol') }}">

    {{-- Breadcrumb --}}
    <div class="roles-header">
        <div class="roles-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"/>
            </svg>
            <span class="roles-breadcrumb__current">Roles y Permisos</span>
        </div>
        <div class="roles-header__top">
            <div>
                <h1 class="roles-title">Gestión de Roles</h1>
                <p class="roles-subtitle">Administra los roles y permisos del sistema</p>
            </div>
            <button class="roles-btn-new" type="button" onclick="openCreateDrawer()">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Nuevo Rol
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="roles-stats">
        <div class="roles-stat-card">
            <div>
                <p class="roles-stat-label">Total Roles</p>
                <p class="roles-stat-value">{{ $totalRoles }}</p>
                <p class="roles-stat-sub">configurados</p>
            </div>
            <div class="roles-stat-icon roles-stat-icon--orange">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                </svg>
            </div>
        </div>
        <div class="roles-stat-card">
            <div>
                <p class="roles-stat-label">Total Usuarios</p>
                <p class="roles-stat-value">{{ $totalUsers }}</p>
                <p class="roles-stat-sub">con rol asignado</p>
            </div>
            <div class="roles-stat-icon roles-stat-icon--blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>
        <div class="roles-stat-card">
            <div>
                <p class="roles-stat-label">Módulos Activos</p>
                <p class="roles-stat-value">{{ $totalModules }}</p>
                <p class="roles-stat-sub">en el sistema</p>
            </div>
            <div class="roles-stat-icon roles-stat-icon--green">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/>
                    <path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/>
                    <path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="roles-table-wrap">
        <table class="roles-table">
            <thead>
                <tr>
                    <th>ROL</th>
                    <th>DESCRIPCIÓN</th>
                    <th>USUARIOS</th>
                    <th>PERMISOS</th>
                    <th>CREADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td>
                        <div class="roles-cell-role">
                            @if($role->isAdmin())
                            <div class="roles-role-icon roles-role-icon--orange">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                                </svg>
                            </div>
                            @else
                            <div class="roles-role-icon roles-role-icon--blue">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            @endif
                            <span class="roles-role-name">{{ $role->name_role }}</span>
                        </div>
                    </td>
                    <td class="roles-cell-desc">{{ $role->description_role }}</td>
                    <td>
                        <span class="roles-users-count">
                            {{ $role->users_count }} {{ $role->users_count === 1 ? 'usuario' : 'usuarios' }}
                        </span>
                    </td>
                    <td>
                        @if($role->isAdmin())
                            <span class="roles-badge roles-badge--admin">Todos los módulos</span>
                        @else
                            <span class="roles-badge roles-badge--default">{{ $role->permissions_count }} módulos</span>
                        @endif
                    </td>
                    <td class="roles-cell-date">
                        {{ $role->created_at ? \Carbon\Carbon::parse($role->created_at)->format('d M Y') : '—' }}
                    </td>
                    <td>
                        <div class="roles-actions">
                            <button class="roles-action-btn" type="button" title="Ver"
                                onclick="openShowModal({{ $role->id }})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <button class="roles-action-btn" type="button" title="Editar"
                                onclick="openEditDrawer({{ $role->id }}, '{{ addslashes($role->name_role) }}', '{{ addslashes($role->description_role) }}', {{ json_encode($role->permissions->pluck('module')->toArray()) }})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    <path d="m15 5 4 4"/>
                                </svg>
                            </button>
                            @if($role->isAdmin())
                            <button class="roles-action-btn roles-action-btn--disabled" type="button"
                                title="No se puede eliminar el rol administrador" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    <line x1="10" x2="10" y1="11" y2="17"/>
                                    <line x1="14" x2="14" y1="11" y2="17"/>
                                </svg>
                            </button>
                            @else
                            <button class="roles-action-btn roles-action-btn--danger" type="button" title="Eliminar"
                                onclick="openDeleteModal({{ $role->id }}, '{{ addslashes($role->name_role) }}', {{ $role->users_count }})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    <line x1="10" x2="10" y1="11" y2="17"/>
                                    <line x1="14" x2="14" y1="11" y2="17"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="roles-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                        </svg>
                        <p>No hay roles registrados</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Overlay --}}
<div id="roles-overlay" class="roles-overlay" onclick="closeAll()"></div>

@include('admin.roles.partials._drawer_create')
@include('admin.roles.partials._drawer_edit')
@include('admin.roles.partials._modal_show')
@include('admin.roles.partials._modal_delete')

@endsection

@push('scripts')
    @vite('resources/js/admin/roles.js')
@endpush
