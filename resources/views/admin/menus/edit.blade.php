@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/menus.css')
@endpush
@section('title')
    Editar Menú: {{ $menu->name }} - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                <a href="{{ route('admin.menus.index') }}" style="color:inherit;text-decoration:none;">Panel de Control &gt; Menús</a>
                &gt; <strong>{{ $menu->name }}</strong>
            </p>
            <h1 style="margin:0 0 4px;">Editor de Menú: {{ $menu->name }}</h1>
            <p class="breadcrumb-clients-manager main">
                Ubicación: <span style="font-family:monospace;">{{ $menu->location }}</span>
                — arrastra los elementos para reordenarlos
            </p>
        </div>
        @permiso('menu', 'create')
        <button type="button" class="button-primary size-adjustment" id="btnNewRootItem"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            + Agregar elemento raíz
        </button>
        @endpermiso
    </div>

    <ul class="menu-tree" id="menuRootList" data-menu-id="{{ $menu->id }}">
        @forelse ($rootItems as $root)
            <li class="menu-tree-item" draggable="true" data-id="{{ $root->id }}">
                <div class="menu-tree-row">
                    <span class="menu-tree-drag-handle" title="Arrastrar para reordenar">⠿</span>
                    <div class="menu-tree-info">
                        <span class="menu-tree-title">{{ $root->title }}</span>
                        @if ($root->url)
                            <span class="menu-tree-url">{{ $root->url }}</span>
                        @endif
                    </div>
                    <span class="menu-tree-target">{{ $root->target }}</span>
                    @if ($root->is_active)
                        <span class="users-manager-badge status" data-status="active">Activo</span>
                    @else
                        <span class="users-manager-badge status-inactive" data-status="inactive">Inactivo</span>
                    @endif
                    <div class="header-right-user-manager menu-tree-actions">
                        @permiso('menu', 'create')
                        <button type="button" class="table-users-manager-action-btn add btn-add-child"
                            data-parent-id="{{ $root->id }}" data-parent-title="{{ $root->title }}"
                            title="Agregar elemento hijo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                        </button>
                        @endpermiso
                        @permiso('menu', 'edit')
                        <button type="button" class="table-users-manager-action-btn edit btn-edit-item"
                            data-id="{{ $root->id }}"
                            data-title="{{ $root->title }}"
                            data-url="{{ $root->url }}"
                            data-target="{{ $root->target }}"
                            data-parent-id=""
                            data-sort-order="{{ $root->sort_order }}"
                            data-active="{{ $root->is_active ? 1 : 0 }}"
                            title="Editar elemento">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                        </button>
                        @endpermiso
                        @permiso('menu', 'delete')
                        <button type="button" class="table-users-manager-action-btn delete btn-delete-item"
                            data-id="{{ $root->id }}" data-title="{{ $root->title }}"
                            title="Eliminar elemento">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                        @endpermiso
                    </div>
                </div>
                <ul class="menu-tree-children" data-parent-id="{{ $root->id }}">
                    @foreach ($childrenByParent->get($root->id, collect()) as $child)
                        <li class="menu-tree-item menu-tree-child" draggable="true" data-id="{{ $child->id }}">
                            <div class="menu-tree-row">
                                <span class="menu-tree-drag-handle" title="Arrastrar para reordenar">⠿</span>
                                <div class="menu-tree-info">
                                    <span class="menu-tree-title">{{ $child->title }}</span>
                                    @if ($child->url)
                                        <span class="menu-tree-url">{{ $child->url }}</span>
                                    @endif
                                </div>
                                <span class="menu-tree-target">{{ $child->target }}</span>
                                @if ($child->is_active)
                                    <span class="users-manager-badge status" data-status="active">Activo</span>
                                @else
                                    <span class="users-manager-badge status-inactive" data-status="inactive">Inactivo</span>
                                @endif
                                <div class="header-right-user-manager menu-tree-actions">
                                    @permiso('menu', 'edit')
                                    <button type="button" class="table-users-manager-action-btn edit btn-edit-item"
                                        data-id="{{ $child->id }}"
                                        data-title="{{ $child->title }}"
                                        data-url="{{ $child->url }}"
                                        data-target="{{ $child->target }}"
                                        data-parent-id="{{ $child->parent_id }}"
                                        data-sort-order="{{ $child->sort_order }}"
                                        data-active="{{ $child->is_active ? 1 : 0 }}"
                                        title="Editar elemento">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                    </button>
                                    @endpermiso
                                    @permiso('menu', 'delete')
                                    <button type="button" class="table-users-manager-action-btn delete btn-delete-item"
                                        data-id="{{ $child->id }}" data-title="{{ $child->title }}"
                                        title="Eliminar elemento">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                    @endpermiso
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </li>
        @empty
            <li class="menu-tree-empty">
                Este menú todavía no tiene elementos. Usa "Agregar elemento raíz" para comenzar.
            </li>
        @endforelse
    </ul>

</section>

{{-- Item create/edit modal --}}
<div id="menuItemModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="menuItemModalTitle">Nuevo Elemento</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeMenuItemModal">✕</button>
        </div>

        <div id="menu-item-modal-errors" class="user-manager-errors" style="display:none;"></div>

        <form class="user-manager-modal-body" id="menuItemForm">
            @csrf
            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Título <span style="color:red">*</span></label>
                <input type="text" class="users-manager-input" name="title" id="menuItemTitle"
                    placeholder="Ej: Calderas Industriales">
            </div>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">URL</label>
                <input type="text" class="users-manager-input" name="url" id="menuItemUrl"
                    placeholder="/ruta-existente o https://...">
            </div>

            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Destino</label>
                    <select class="users-manager-select" name="target" id="menuItemTarget">
                        <option value="_self">Misma pestaña</option>
                        <option value="_blank">Nueva pestaña</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Orden</label>
                    <input type="number" class="users-manager-input" name="sort_order" id="menuItemSortOrder" min="0" value="0">
                </div>
            </div>

            <div class="users-manager-email-camp" id="menuItemParentGroup">
                <label class="supliers-manager-slider-label">Elemento Padre</label>
                <select class="users-manager-select" name="parent_id" id="menuItemParent">
                    <option value="">Ninguno (elemento raíz)</option>
                    @foreach ($rootItems as $r)
                        <option value="{{ $r->id }}">{{ $r->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Estado <span style="color:red">*</span></label>
                    <select class="users-manager-select" name="is_active" id="menuItemIsActive">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelMenuItemModal" class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="menuItemSubmitBtn">Crear Elemento</button>
            </div>
        </form>
    </div>
</div>

{{-- Item delete modal --}}
<div id="deleteMenuItemModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <path d="M12 9v4"/><path d="M12 17h.01"/>
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar elemento?</h2>
        <p class="del-confirm-desc">Esta acción no se puede deshacer. Si tiene elementos hijos, quedarán como elementos raíz.</p>
        <div class="del-confirm-user-card">
            <div class="del-confirm-avatar" id="delMenuItemAvatar">E</div>
            <div>
                <p class="del-confirm-user-name" id="delMenuItemName">Nombre</p>
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="delMenuItemCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button" id="delMenuItemConfirm">Eliminar</button>
        </div>
    </div>
</div>

@include('admin.menus.partials._scripts')
</div>
@endsection
