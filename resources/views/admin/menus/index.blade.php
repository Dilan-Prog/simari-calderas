@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/menus.css')
@endpush
@section('title')
    Menús - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Menús</strong>
            </p>
            <h1 style="margin:0 0 4px;">Gestión de Menús</h1>
            <p class="breadcrumb-clients-manager main">Orden de la navegación principal, el mega-menú de Servicios y las columnas del footer</p>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'menus.index',
                'columnDefs' => ['ubicacion' => 'Ubicación', 'elementos' => 'Elementos', 'estado' => 'Estado'],
            ])
            <button type="button" class="button-primary size-adjustment" id="btnNewMenu"
                style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
                + Nuevo Menú
            </button>
        </div>
    </div>

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th data-col="ubicacion">UBICACIÓN</th>
                    <th data-col="elementos">ELEMENTOS</th>
                    <th data-col="estado">ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @forelse ($menus as $menu)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 16px;">
                            <a href="{{ route('admin.menus.edit', $menu->id) }}" style="font-weight:600;color:#141516;text-decoration:none;">
                                {{ $menu->name }}
                            </a>
                        </td>
                        <td data-col="ubicacion" style="padding:12px 16px;">
                            <span style="display:inline-block;padding:3px 10px;background:#f1f5f9;border-radius:6px;font-size:12px;font-family:monospace;color:#475569;">{{ $menu->location }}</span>
                        </td>
                        <td data-col="elementos" style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">{{ $menu->items_count }}</td>
                        <td data-col="estado" style="padding:12px 16px;">
                            @if ($menu->is_active)
                                <span class="users-manager-badge status" data-status="active">Activo</span>
                            @else
                                <span class="users-manager-badge status-inactive" data-status="inactive">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="header-right-user-manager">
                                <a href="{{ route('admin.menus.edit', $menu->id) }}"
                                    class="table-users-manager-action-btn edit" title="Editar árbol de elementos">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>
                                </a>
                                <button type="button" class="table-users-manager-action-btn edit btn-edit-menu"
                                    data-id="{{ $menu->id }}"
                                    data-name="{{ $menu->name }}"
                                    data-location="{{ $menu->location }}"
                                    data-active="{{ $menu->is_active ? 1 : 0 }}"
                                    title="Renombrar menú">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </button>
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-menu"
                                    data-id="{{ $menu->id }}" data-name="{{ $menu->name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:24px 16px;text-align:center;color:#6b7280;">
                            No hay menús registrados todavía.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

</section>

@include('admin.menus.partials._create_edit_modal')
@include('admin.menus.partials._delete_modal')
@include('admin.menus.partials._scripts')
</div>
@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'menus.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
@endsection
