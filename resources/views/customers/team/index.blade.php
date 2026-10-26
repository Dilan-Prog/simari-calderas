@extends('customers.layouts.master')

@section('title', 'Usuarios y roles')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.team._styles')
@endpush

@section('content')
@php
    $activeModal = (string) old('_modal');
    $errIn = fn (string $field, string $prefix) => str_starts_with($activeModal, $prefix) ? $errors->first($field) : null;
    $actionLabels = ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar'];
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Usuarios y roles</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Usuarios y roles</h1>
            <p class="ts-subtitle">Da acceso al portal a más personas de tu empresa y define qué puede hacer cada una. Todas ven la información de la misma cuenta.</p>
        </div>
        <button type="button" class="ts-btn ts-btn--primary" id="tm-primary-action" data-tm-open="user-create">+ Agregar usuario</button>
    </div>

    <div class="tm-tabs" role="tablist">
        <button type="button" class="tm-tab {{ $tab === 'usuarios' ? 'is-active' : '' }}" role="tab" data-tm-tab="usuarios" aria-selected="{{ $tab === 'usuarios' ? 'true' : 'false' }}">
            Usuarios <span class="tm-tab__count">{{ $users->count() + 1 }}</span>
        </button>
        <button type="button" class="tm-tab {{ $tab === 'roles' ? 'is-active' : '' }}" role="tab" data-tm-tab="roles" aria-selected="{{ $tab === 'roles' ? 'true' : 'false' }}">
            Roles <span class="tm-tab__count">{{ $roles->count() }}</span>
        </button>
    </div>

    {{-- ───────── Pestaña: Usuarios ───────── --}}
    <section class="tm-panel" id="tm-panel-usuarios" {{ $tab === 'usuarios' ? '' : 'hidden' }}>
        <div class="ts-table-wrap">
            <table class="ts-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Titular de la cuenta (no editable aquí) --}}
                    <tr>
                        <td>
                            <div class="tm-person">
                                <span class="tm-avatar tm-avatar--owner">{{ mb_strtoupper(mb_substr($owner->first_name ?: 'T', 0, 1)) }}</span>
                                <div>
                                    <div class="tm-person__name">{{ trim($owner->first_name . ' ' . $owner->last_name) }} <span class="tm-badge tm-badge--owner">Titular de la cuenta</span></div>
                                    <div class="tm-person__mail">{{ $owner->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>Acceso total</td>
                        <td><span class="tm-badge tm-badge--ok">Activo</span></td>
                        <td class="ts-table-date">—</td>
                        <td style="text-align:right"><span class="tm-muted">Se edita en Mi perfil</span></td>
                    </tr>

                    @foreach ($users as $member)
                        <tr>
                            <td>
                                <div class="tm-person">
                                    <span class="tm-avatar">{{ mb_strtoupper(mb_substr($member->first_name ?: 'U', 0, 1)) }}</span>
                                    <div>
                                        <div class="tm-person__name">{{ $member->full_name }}</div>
                                        <div class="tm-person__mail">{{ $member->email }}@if ($member->phone) · {{ $member->phone }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($member->role)
                                    {{ $member->role->name }}
                                @else
                                    <span class="tm-badge tm-badge--off">Sin rol (sin acceso)</span>
                                @endif
                            </td>
                            <td>
                                <span class="tm-badge {{ $member->isActive() ? 'tm-badge--ok' : 'tm-badge--off' }}">{{ $member->isActive() ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="ts-table-date">{{ $member->last_login_at ? $member->last_login_at->isoFormat('D MMM YYYY, HH:mm') : 'Nunca' }}</td>
                            <td>
                                <div class="tm-actions">
                                    <button type="button" class="ts-btn tm-btn-sm" data-tm-open="user-edit"
                                            data-id="{{ $member->id }}"
                                            data-user="{{ json_encode([
                                                'first_name' => $member->first_name,
                                                'last_name'  => $member->last_name,
                                                'email'      => $member->email,
                                                'phone'      => $member->phone,
                                                'role_id'    => $member->role_id,
                                            ], JSON_UNESCAPED_UNICODE) }}">Editar</button>
                                    <button type="button" class="ts-btn tm-btn-sm" data-tm-open="user-pass"
                                            data-id="{{ $member->id }}" data-name="{{ $member->full_name }}">Contraseña</button>
                                    <form method="POST" action="{{ route('customer.team.users.toggle', $member->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ts-btn tm-btn-sm {{ $member->isActive() ? 'ts-btn--warning' : 'ts-btn--success' }}">{{ $member->isActive() ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                    <button type="button" class="ts-btn ts-btn--danger tm-btn-sm" data-tm-open="user-delete"
                                            data-id="{{ $member->id }}" data-name="{{ $member->full_name }}">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->isEmpty())
            <p class="tm-hint">Aún no has agregado usuarios. Crea uno y asígnale un rol para que pueda entrar al portal con su propio correo y contraseña.</p>
        @endif
    </section>

    {{-- ───────── Pestaña: Roles ───────── --}}
    <section class="tm-panel" id="tm-panel-roles" {{ $tab === 'roles' ? '' : 'hidden' }}>
        <div class="ts-table-wrap">
            <table class="ts-table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Descripción</th>
                        <th>Módulos</th>
                        <th>Usuarios</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <strong>{{ $role->name }}</strong>
                                @if ($role->is_system)<span class="tm-badge tm-badge--sys">Predeterminado</span>@endif
                            </td>
                            <td class="tm-desc">{{ $role->description ?: '—' }}</td>
                            <td>{{ $role->permissions->where('action', 'view')->count() }} de {{ count($modules) }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <div class="tm-actions">
                                    <button type="button" class="ts-btn tm-btn-sm" data-tm-open="role-edit"
                                            data-id="{{ $role->id }}"
                                            data-role="{{ json_encode([
                                                'name'        => $role->name,
                                                'description' => $role->description,
                                                'permissions' => $role->permissionKeys(),
                                            ], JSON_UNESCAPED_UNICODE) }}">Editar</button>
                                    @if ($role->is_system)
                                        <button type="button" class="ts-btn tm-btn-sm" disabled title="Los roles predeterminados no se pueden eliminar">Eliminar</button>
                                    @elseif ($role->users_count > 0)
                                        <button type="button" class="ts-btn tm-btn-sm" disabled title="Tiene usuarios asignados">Eliminar</button>
                                    @else
                                        <button type="button" class="ts-btn ts-btn--danger tm-btn-sm" data-tm-open="role-delete"
                                                data-id="{{ $role->id }}" data-name="{{ $role->name }}">Eliminar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tm-hint">Los roles son propios de tu cuenta. La administración de usuarios y roles es exclusiva del titular y no se puede asignar a un rol.</p>
    </section>
</div>

{{-- Modal: agregar / editar usuario --}}
<div class="tm-modal" id="tm-modal-user" role="dialog" aria-modal="true" aria-labelledby="tm-user-title" aria-hidden="true">
    <div class="tm-modal__card">
        <form method="POST" action="{{ route('customer.team.users.store') }}" id="tm-user-form" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PUT" id="tm-user-method" disabled>
            <input type="hidden" name="_modal" value="{{ str_starts_with($activeModal, 'user-create') || str_starts_with($activeModal, 'user-edit') ? $activeModal : '' }}" id="tm-user-modal-key">

            <div class="tm-modal__head">
                <h2 class="tm-modal__title" id="tm-user-title">Agregar usuario</h2>
                <button type="button" class="tm-modal__close" data-tm-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="tm-modal__body">
                <div class="ts-field">
                    <label class="ts-label" for="tm-first-name">Nombre <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="tm-first-name" name="first_name" maxlength="100" required value="{{ str_starts_with($activeModal, 'user-') ? old('first_name') : '' }}">
                    @if ($e = $errIn('first_name', 'user-c') ?? $errIn('first_name', 'user-e'))<span class="tm-error">{{ $e }}</span>@endif
                </div>
                <div class="ts-field">
                    <label class="ts-label" for="tm-last-name">Apellido</label>
                    <input type="text" class="ts-input" id="tm-last-name" name="last_name" maxlength="100" value="{{ str_starts_with($activeModal, 'user-') ? old('last_name') : '' }}">
                    @if ($e = $errIn('last_name', 'user-c') ?? $errIn('last_name', 'user-e'))<span class="tm-error">{{ $e }}</span>@endif
                </div>
                <div class="ts-field tm-full">
                    <label class="ts-label" for="tm-email">Correo electrónico <span class="ts-label__req">*</span></label>
                    <input type="email" class="ts-input" id="tm-email" name="email" maxlength="255" required autocomplete="off" value="{{ str_starts_with($activeModal, 'user-') ? old('email') : '' }}">
                    @if ($e = $errIn('email', 'user-c') ?? $errIn('email', 'user-e'))<span class="tm-error">{{ $e }}</span>@else<span class="tm-hint-sm">Con este correo iniciará sesión en el portal.</span>@endif
                </div>
                <div class="ts-field">
                    <label class="ts-label" for="tm-phone">Teléfono</label>
                    <input type="tel" class="ts-input" id="tm-phone" name="phone" maxlength="30" value="{{ str_starts_with($activeModal, 'user-') ? old('phone') : '' }}">
                    @if ($e = $errIn('phone', 'user-c') ?? $errIn('phone', 'user-e'))<span class="tm-error">{{ $e }}</span>@endif
                </div>
                <div class="ts-field">
                    <label class="ts-label" for="tm-role">Rol <span class="ts-label__req">*</span></label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="tm-role" name="role_id" required>
                            <option value="">Selecciona un rol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id && str_starts_with($activeModal, 'user-') ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    @if ($e = $errIn('role_id', 'user-c') ?? $errIn('role_id', 'user-e'))<span class="tm-error">{{ $e }}</span>@endif
                </div>
                <div class="ts-field tm-full" id="tm-password-wrap">
                    <label class="ts-label" for="tm-password">Contraseña inicial <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="tm-password" name="password" minlength="8" autocomplete="new-password" value="">
                    @if ($e = $errIn('password', 'user-c'))<span class="tm-error">{{ $e }}</span>@else<span class="tm-hint-sm">Mínimo 8 caracteres. Compártela con la persona; podrá cambiarla desde Mi perfil.</span>@endif
                </div>
            </div>
            <div class="tm-modal__foot">
                <button type="button" class="ts-btn" data-tm-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar usuario</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: restablecer contraseña --}}
<div class="tm-modal" id="tm-modal-pass" role="dialog" aria-modal="true" aria-labelledby="tm-pass-title" aria-hidden="true">
    <div class="tm-modal__card tm-modal__card--sm">
        <form method="POST" action="#" id="tm-pass-form" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="{{ str_starts_with($activeModal, 'user-pass') ? $activeModal : '' }}" id="tm-pass-modal-key">
            <div class="tm-modal__head">
                <h2 class="tm-modal__title" id="tm-pass-title">Nueva contraseña</h2>
                <button type="button" class="tm-modal__close" data-tm-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="tm-modal__body tm-modal__body--one">
                <p class="tm-hint-sm" style="margin:0">Define la contraseña de <strong id="tm-pass-name"></strong>. Compártela con la persona.</p>
                <div class="ts-field">
                    <label class="ts-label" for="tm-pass-input">Contraseña <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="tm-pass-input" name="password" minlength="8" autocomplete="new-password">
                    @if ($e = $errIn('password', 'user-pass'))<span class="tm-error">{{ $e }}</span>@else<span class="tm-hint-sm">Mínimo 8 caracteres.</span>@endif
                </div>
            </div>
            <div class="tm-modal__foot">
                <button type="button" class="ts-btn" data-tm-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar contraseña</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: eliminar usuario --}}
<div class="tm-modal" id="tm-modal-user-delete" role="alertdialog" aria-modal="true" aria-labelledby="tm-udel-title" aria-hidden="true">
    <div class="tm-modal__card tm-modal__card--sm">
        <form method="POST" action="#" id="tm-user-delete-form">
            @csrf
            @method('DELETE')
            <div class="tm-modal__head">
                <h2 class="tm-modal__title" id="tm-udel-title">¿Eliminar este usuario?</h2>
                <button type="button" class="tm-modal__close" data-tm-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="tm-confirm"><strong id="tm-udel-name"></strong> perderá el acceso al portal. Esta acción no se puede deshacer.</div>
            <div class="tm-modal__foot">
                <button type="button" class="ts-btn" data-tm-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: crear / editar rol (matriz de permisos) --}}
<div class="tm-modal" id="tm-modal-role" role="dialog" aria-modal="true" aria-labelledby="tm-role-title" aria-hidden="true">
    <div class="tm-modal__card tm-modal__card--lg">
        <form method="POST" action="{{ route('customer.team.roles.store') }}" id="tm-role-form" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PUT" id="tm-role-method" disabled>
            <input type="hidden" name="_modal" value="{{ str_starts_with($activeModal, 'role-') && $activeModal !== 'role-delete' ? $activeModal : '' }}" id="tm-role-modal-key">

            <div class="tm-modal__head">
                <h2 class="tm-modal__title" id="tm-role-title">Crear rol</h2>
                <button type="button" class="tm-modal__close" data-tm-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="tm-modal__body">
                <div class="ts-field">
                    <label class="ts-label" for="tm-role-name">Nombre del rol <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="tm-role-name" name="name" maxlength="100" required value="{{ str_starts_with($activeModal, 'role-') ? old('name') : '' }}">
                    @if ($e = $errIn('name', 'role-'))<span class="tm-error">{{ $e }}</span>@endif
                </div>
                <div class="ts-field">
                    <label class="ts-label" for="tm-role-desc">Descripción</label>
                    <input type="text" class="ts-input" id="tm-role-desc" name="description" maxlength="255" value="{{ str_starts_with($activeModal, 'role-') ? old('description') : '' }}">
                    @if ($e = $errIn('description', 'role-'))<span class="tm-error">{{ $e }}</span>@endif
                </div>

                <div class="tm-full">
                    <div class="tm-matrix-head">
                        <span class="ts-label" style="margin:0">Permisos por módulo</span>
                        <span>
                            <button type="button" class="tm-link" data-tm-matrix="all">Marcar todo</button>
                            <button type="button" class="tm-link" data-tm-matrix="none">Quitar todo</button>
                        </span>
                    </div>
                    <div class="tm-matrix-wrap">
                        <table class="tm-matrix">
                            <thead>
                                <tr>
                                    <th>Módulo</th>
                                    @foreach ($actionLabels as $label)<th>{{ $label }}</th>@endforeach
                                </tr>
                            </thead>
                            @foreach ($moduleGroups as $groupName => $keys)
                                <tbody>
                                    <tr class="tm-matrix__group"><th colspan="5">{{ $groupName }}</th></tr>
                                    @foreach ($keys as $key)
                                        @php $allowed = $modules[$key]['actions'] ?? ['view']; @endphp
                                        <tr data-module="{{ $key }}">
                                            <td>{{ $modules[$key]['name'] }}</td>
                                            @foreach ($actionLabels as $action => $label)
                                                <td>
                                                    @if (in_array($action, $allowed, true))
                                                        <input type="checkbox" name="permissions[]" value="{{ $key }}:{{ $action }}"
                                                               data-action="{{ $action }}" aria-label="{{ $label }} — {{ $modules[$key]['name'] }}"
                                                               {{ str_starts_with($activeModal, 'role-') && in_array($key . ':' . $action, (array) old('permissions', []), true) ? 'checked' : '' }}>
                                                    @else
                                                        <span class="tm-muted">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="tm-modal__foot">
                <button type="button" class="ts-btn" data-tm-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar rol</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: eliminar rol --}}
<div class="tm-modal" id="tm-modal-role-delete" role="alertdialog" aria-modal="true" aria-labelledby="tm-rdel-title" aria-hidden="true">
    <div class="tm-modal__card tm-modal__card--sm">
        <form method="POST" action="#" id="tm-role-delete-form">
            @csrf
            @method('DELETE')
            <div class="tm-modal__head">
                <h2 class="tm-modal__title" id="tm-rdel-title">¿Eliminar este rol?</h2>
                <button type="button" class="tm-modal__close" data-tm-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="tm-confirm">El rol <strong id="tm-rdel-name"></strong> se eliminará. Esta acción no se puede deshacer.</div>
            <div class="tm-modal__foot">
                <button type="button" class="ts-btn" data-tm-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @include('customers.team._scripts', ['activeModal' => $activeModal])
@endpush
