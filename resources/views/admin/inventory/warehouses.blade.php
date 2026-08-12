@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/inventory.css')
@endpush
@section('title')
    Almacenes - Inventario - Admin
@endsection
@section('content')
    <div class="container user-manager inv-page">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Inventario &gt; Almacenes
                    </p>
                    <h1>Almacenes</h1>
                    <p class="breadcrumb-clients-manager main">Catálogo de ubicaciones de almacenamiento</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="{{ route('admin.inventory.index') }}" class="prod-btn-outline">
                        ← Volver a Movimientos
                    </a>
                    @permiso('inventory', 'create')
                    <button type="button" class="button-primary size-adjustment" id="btnNewWarehouse">
                        + Nuevo Almacén
                    </button>
                    @endpermiso
                </div>
            </header>

            {{-- Table --}}
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table inv-table">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>UBICACIÓN</th>
                                <th>MOVIMIENTOS</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($warehouses as $warehouse)
                                <tr>
                                    <td><p class="brand-name" style="margin:0;">{{ $warehouse->name }}</p></td>
                                    <td>{{ $warehouse->location ?: '—' }}</td>
                                    <td>{{ $warehouse->movements_count }}</td>
                                    <td>
                                        <span class="brand-status-badge {{ $warehouse->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-container">
                                            @permiso('inventory', 'edit')
                                            <button type="button" class="action-btn btn-edit-warehouse"
                                                data-id="{{ $warehouse->id }}" data-name="{{ $warehouse->name }}"
                                                data-location="{{ $warehouse->location }}"
                                                data-active="{{ $warehouse->is_active ? '1' : '0' }}" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                            @permiso('inventory', 'delete')
                                            <button type="button" class="action-btn btn-delete-warehouse"
                                                data-id="{{ $warehouse->id }}" data-name="{{ $warehouse->name }}"
                                                data-blocked="{{ $warehouse->movements_count > 0 ? '1' : '0' }}" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                    <line x1="10" x2="10" y1="11" y2="17" />
                                                    <line x1="14" x2="14" y1="11" y2="17" />
                                                </svg>
                                            </button>
                                            @endpermiso
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                        No hay almacenes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>
        </section>
    </div>

    @include('admin.inventory.partials._modal_warehouse')

    {{-- Delete confirmation modal --}}
    <div id="deleteWarehouseModal" class="del-confirm-overlay">
        <div class="del-confirm-box">
            <div class="del-confirm-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                    stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
            </div>
            <h2 class="del-confirm-title">¿Eliminar almacén?</h2>
            <p class="del-confirm-desc">Esta acción no se puede deshacer. No puedes eliminar almacenes con movimientos de inventario asociados.</p>
            <div class="del-confirm-user-card">
                <div class="del-confirm-avatar" id="delWarehouseAvatar">A</div>
                <div>
                    <p class="del-confirm-user-name" id="delWarehouseName">Nombre</p>
                </div>
            </div>
            <div class="del-confirm-actions">
                <button type="button" class="button-secondary size-adjustment" id="delWarehouseCancel">Cancelar</button>
                <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                    id="delWarehouseConfirm">Eliminar</button>
            </div>
        </div>
    </div>
@endsection
@include('admin.inventory.partials._scripts_warehouses')
