@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/usuarios.css')
    @vite('resources/css/admin/pages/usuarios-modals.css')
@endpush
@section('title')
    Gestor de usuarios - Admin
@endsection
@section('content')
    <div class="container user-manager">
        {{-- Main content --}}
        <section class="users-manager-section users-index">
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
            {{-- Barra de la tabla: buscador + filas por página (script inline al final) --}}
            <div class="users-toolbar">
                <label class="users-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="users-search" placeholder="Buscar por nombre, correo, rol o puesto…"
                        autocomplete="off" aria-label="Buscar usuarios">
                </label>
                <label class="users-per-page">
                    Mostrar
                    <select id="users-per-page" aria-label="Usuarios por página">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
            </div>
            <!-- TABLE -->
            <main class="table-container-users-manager">
                <div class="table-scroll">
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
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="table-row-user-manager"
                                    data-search="{{ mb_strtolower(trim($user->first_name . ' ' . $user->last_name . ' ' . $user->email . ' ' . ($user->role->name_role_es ?? '') . ' ' . $user->position . ' ' . $user->phone)) }}">
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
                                    <td class="users-cell-email" data-col="email" data-label="Email">
                                        <p class="breadcrumb-users-manager main users-email">{{ $user->email }}</p>
                                    </td>
                                    <td data-col="rol" data-label="Rol">
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
                                    <td data-col="estado" data-label="Estado">
                                        @if ($user->status == 'active')
                                            <span class="users-manager-badge status">Activo</span>
                                        @else
                                            <span class="users-manager-badge status-inactive">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="users-cell-actions">
                                        <div class="header-right-user-manager users-actions">
                                            {{-- view --}}
                                            <button class="table-users-manager-action-btn edit btn-show-user"
                                                type="button" title="Ver" aria-label="Ver usuario"
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
                                                title="Editar" aria-label="Editar usuario"
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
                                                title="Eliminar" aria-label="Eliminar usuario"
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
                            @empty
                                <tr class="users-empty-row">
                                    <td colspan="5" class="users-empty">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                            <tr class="users-no-results" hidden>
                                <td colspan="5" class="users-empty">Ningún usuario coincide con la búsqueda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
            {{-- Pie de la tabla: conteo + paginación --}}
            <div class="users-pager" id="users-pager">
                <span class="users-pager__info" id="users-pager-info"></span>
                <div class="users-pager__nav" id="users-pager-nav"></div>
            </div>
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
@push('scripts')
    <script>
        // Tabla de usuarios: buscador + paginación en el navegador (mismo patrón que roles.js).
        (function () {
            var searchInput = document.getElementById('users-search');
            var perPageSel  = document.getElementById('users-per-page');
            var pagerInfo   = document.getElementById('users-pager-info');
            var pagerNav    = document.getElementById('users-pager-nav');
            var noResults   = document.querySelector('.users-index .users-no-results');
            var allRows     = Array.prototype.slice.call(
                document.querySelectorAll('.users-index .users-manager-table tbody tr.table-row-user-manager'));
            var currentPage = 1;

            if (!pagerInfo || !pagerNav) return;

            function normalize(str) {
                return String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            function renderTable() {
                var term    = normalize(searchInput ? searchInput.value.trim() : '');
                var perPage = parseInt(perPageSel ? perPageSel.value : '10', 10) || 10;
                var matches = allRows.filter(function (tr) {
                    var hit = !term || normalize(tr.dataset.search).indexOf(term) !== -1;
                    tr.hidden = !hit;
                    return hit;
                });

                var pages = Math.max(1, Math.ceil(matches.length / perPage));
                currentPage = Math.min(Math.max(1, currentPage), pages);
                var from = (currentPage - 1) * perPage;
                var to   = Math.min(from + perPage, matches.length);
                matches.forEach(function (tr, i) { tr.classList.toggle('is-paged-out', i < from || i >= to); });

                if (noResults) noResults.hidden = !(allRows.length && !matches.length);
                pagerInfo.innerHTML = matches.length
                    ? 'Mostrando <strong>' + (from + 1) + '–' + to + '</strong> de <strong>' + matches.length + '</strong> ' + (matches.length === 1 ? 'usuario' : 'usuarios')
                    : (allRows.length ? 'Sin resultados' : '');

                var html = '';
                if (pages > 1) {
                    html += '<button type="button" class="users-pager__btn" data-page="' + (currentPage - 1) + '"' + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Página anterior">‹</button>';
                    for (var p = 1; p <= pages; p++) {
                        html += '<button type="button" class="users-pager__btn' + (p === currentPage ? ' is-current' : '') + '" data-page="' + p + '"' + (p === currentPage ? ' aria-current="page"' : '') + '>' + p + '</button>';
                    }
                    html += '<button type="button" class="users-pager__btn" data-page="' + (currentPage + 1) + '"' + (currentPage === pages ? ' disabled' : '') + ' aria-label="Página siguiente">›</button>';
                }
                pagerNav.innerHTML = html;
            }

            if (searchInput) searchInput.addEventListener('input', function () { currentPage = 1; renderTable(); });
            if (perPageSel)  perPageSel.addEventListener('change', function () { currentPage = 1; renderTable(); });
            pagerNav.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-page]');
                if (!btn || btn.disabled) return;
                currentPage = parseInt(btn.dataset.page, 10);
                renderTable();
                var bar = document.querySelector('.users-index .users-toolbar');
                if (bar) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            renderTable();
        })();
    </script>
@endpush
