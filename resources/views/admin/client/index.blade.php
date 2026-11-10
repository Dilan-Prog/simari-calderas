@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/clientes.css')
    @vite('resources/css/admin/pages/clientes-modals.css')
@endpush

@section('title')
    Gestión de clientes - Admin
@endsection

@section('content')
    <div class="container user-manager">

        {{-- Main content --}}

        {{-- Text --}}
        <section class="clients-manager-section clients-index">

            {{-- clients manager section --}}
            <header class="clients-manager-main">
                <div>
                    <h1>Gestión de Clientes</h1>

                    <p class="breadcrumb-clients-manager main">
                        Administra la información de tus clientes B2B
                    </p>
                </div>

                <div class="clients-header-actions">
                    @include('admin.components._column_visibility_menu', [
                        'tableKey' => 'clients.index',
                        'columnDefs' => ['cliente' => 'Cliente', 'contacto' => 'Contacto', 'rfc' => 'RFC', 'estado' => 'Estado'],
                    ])
                    @permiso('clients','create')
                    <button class="button-primary size-adjustment clients">
                        + Nuevo Cliente
                    </button>
                    @endpermiso
                </div>
            </header>

            {{-- Barra de la tabla: buscador + estado + filas por página (script inline al final).
                 Los ids clientSearch / clientStatusFilter los usa también partials/_scripts. --}}
            <div class="clients-toolbar">
                <label class="clients-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="clientSearch" placeholder="Buscar por nombre, empresa, correo o RFC…"
                        autocomplete="off" aria-label="Buscar clientes">
                </label>
                <div class="clients-toolbar__filters">
                    <select id="clientStatusFilter" class="clients-status-filter" aria-label="Filtrar por estado">
                        <option value="all">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                    <label class="clients-per-page">
                        Mostrar
                        <select id="clients-per-page" aria-label="Clientes por página">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
            </div>

            <!-- TABLE -->
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table">
                        <thead>
                            <tr>
                                <th>EMPRESA</th>
                                <th data-col="cliente">CLIENTE</th>
                                <th data-col="contacto">CONTACTO</th>
                                <th data-col="rfc">RFC</th>
                                <th data-col="estado">ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Rows. El orden de las celdas importa: partials/_scripts
                                 (updateClientTableRow) las ubica por td:first-child / nth-child. --}}
                            @forelse ($customers as $customer)
                                @php
                                    $address = $customer->customer_addresses->first();
                                    $fullName = trim($customer->first_name . ' ' . $customer->last_name);
                                    $statusLabel = match ($customer->status) {
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'suspended' => 'Suspendido',
                                        default => $customer->status,
                                    };
                                    $statusClass = match ($customer->status) {
                                        'active' => 'status',
                                        'inactive' => 'status-inactive',
                                        'suspended' => 'status-suspended',
                                        default => 'status',
                                    };
                                    $hasAccess = !empty($customer->password_hash);
                                @endphp
                                <tr class="clients-row"
                                    data-search="{{ mb_strtolower(trim($customer->company . ' ' . $fullName . ' ' . $customer->email . ' ' . $customer->phone . ' ' . $customer->rfc . ' ' . ($address ? $address->city . ' ' . $address->state : ''))) }}">
                                    <td class="clients-manager-table-cell clients-cell-company">
                                        <span class="clients-company-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-building2 lucide-building-2 text-gray-600">
                                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                                <path d="M10 6h4"></path>
                                                <path d="M10 10h4"></path>
                                                <path d="M10 14h4"></path>
                                                <path d="M10 18h4"></path>
                                            </svg>
                                        </span>
                                        <div class="clients-company-text">
                                            <p class="clients-manager-name-client">{{ $customer->company ?: '—' }}</p>
                                            <span class="clients-manager-ubication-client">
                                                {{ $address ? $address->city . ', ' . $address->state : '—' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="clients-cell-client" data-col="cliente" data-label="Cliente">
                                        <div class="clients-client-inner">
                                            {{-- FIX: la inicial estaba fija en "D" para todas las filas. --}}
                                            <div class="avatar-user-manager">
                                                {{ mb_strtoupper(mb_substr($customer->first_name ?: ($customer->company ?: 'C'), 0, 1)) }}
                                            </div>
                                            <p class="breadcrumb-clients-manager">{{ $fullName }}</p>
                                        </div>
                                    </td>

                                    <td class="clients-cell-contact" data-col="contacto" data-label="Contacto">
                                        <p class="breadcrumb-clients-manager">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-mail text-gray-400">
                                                <rect width="20" height="16" x="2" y="4" rx="2">
                                                </rect>
                                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                            </svg> {{ $customer->email ?? 'Sin correo' }}
                                        </p>
                                        <p class="breadcrumb-clients-manager">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-phone text-gray-400">
                                                <path
                                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                                </path>
                                            </svg> {{ $customer->phone ?: '—' }}
                                        </p>
                                    </td>

                                    <td class="clients-cell-rfc" data-col="rfc" data-label="RFC">
                                        <span class="breadcrumb-clients-manager client-rfc">{{ $customer->rfc ?? '—' }}</span>
                                    </td>

                                    <td class="clients-cell-status" data-col="estado" data-label="Estado">
                                        <span class="users-manager-badge {{ $statusClass }}"
                                            id="statusBadge-{{ $customer->id }}"
                                            data-status="{{ $customer->status }}">{{ $statusLabel }}</span>
                                    </td>

                                    <td class="clients-cell-actions">
                                        <div class="header-right-user-manager buttons clients-actions">
                                            <button type="button"
                                                class="table-users-manager-action-btn access_panel {{ $hasAccess ? 'access-granted' : '' }}"
                                                title="{{ $hasAccess ? 'Restablecer contraseña del portal' : 'Otorgar acceso al portal' }}"
                                                aria-label="{{ $hasAccess ? 'Restablecer contraseña del portal' : 'Otorgar acceso al portal' }}"
                                                data-customer-id="{{ $customer->id }}"
                                                data-has-access="{{ $hasAccess ? 'true' : 'false' }}"
                                                data-portal-access="{{ $customer->portal_access ? 'true' : 'false' }}"
                                                data-portal-requested="{{ $customer->portalRequest?->completed_at ? 'true' : 'false' }}"
                                                onclick="openAccessModal({{ $customer->id }}, '{{ addslashes($customer->first_name . ' ' . $customer->last_name) }}', '{{ addslashes($customer->email ?? '') }}', {{ $hasAccess ? 'true' : 'false' }}, '{{ $customer->status }}', this.dataset.portalAccess === 'true', this.dataset.portalRequested === 'true')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-lock">
                                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="table-users-manager-action-btn edit"
                                                title="Ver ficha" aria-label="Ver ficha del cliente"
                                                onclick="window.location='{{ route('admin.clients.information', $customer->id) }}'">
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
                                            @permiso('clients','edit')
                                            <button type="button"
                                                class="table-users-manager-action-btn edit btn-edit-client"
                                                title="Editar" aria-label="Editar cliente"
                                                data-id="{{ $customer->id }}">
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
                                            @permiso('clients','delete')
                                            <button type="button"
                                                class="table-users-manager-action-btn delete btn-delete-client"
                                                title="Eliminar" aria-label="Eliminar cliente"
                                                data-id="{{ $customer->id }}"
                                                data-name="{{ $customer->first_name }} {{ $customer->last_name }}"
                                                data-email="{{ $customer->email ?? '' }}"
                                                data-initial="{{ strtoupper(substr($customer->first_name, 0, 1)) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-trash2 lucide-trash-2">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                                </svg>
                                            </button>
                                            @endpermiso
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="clients-empty-row">
                                    <td colspan="6" class="clients-empty">No hay clientes registrados.</td>
                                </tr>
                            @endforelse
                            <tr class="clients-no-results" hidden>
                                <td colspan="6" class="clients-empty">Ningún cliente coincide con la búsqueda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
            {{-- Pie de la tabla: conteo + paginación --}}
            <div class="clients-pager" id="clients-pager">
                <span class="clients-pager__info" id="clients-pager-info"></span>
                <div class="clients-pager__nav" id="clients-pager-nav"></div>
            </div>
            @include('admin.client.partials._access_panel_modal')
            @include('admin.client.partials._modal_create')
            @include('admin.client.partials._edit_modal')
            @include('admin.client.partials._scripts')
        </section>
        @include('admin.client.partials._modal_delete')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
    <script>
        initColumnVisibility({
            tableKey: 'clients.index',
            savedColumns: @json($visibleColumns),
            saveUrl: '{{ route('admin.column-preferences.update') }}',
        });
    </script>
@endpush
@push('scripts')
    <script>
        // Tabla de clientes: buscador + filtro de estado + paginación en el navegador
        // (mismo patrón que users/index). partials/_scripts también escucha
        // #clientSearch / #clientStatusFilter y pone style.display en las filas; este
        // bloque se registra en DOMContentLoaded DESPUÉS que aquél, así que corre
        // último, limpia ese display en línea y decide la visibilidad con
        // [hidden] / .is-paged-out.
        (function () {
            function init() {
                var searchInput = document.getElementById('clientSearch');
                var statusSel   = document.getElementById('clientStatusFilter');
                var perPageSel  = document.getElementById('clients-per-page');
                var pagerInfo   = document.getElementById('clients-pager-info');
                var pagerNav    = document.getElementById('clients-pager-nav');
                var tbody       = document.querySelector('.clients-index .clients-manager-table tbody');
                var currentPage = 1;

                if (!pagerInfo || !pagerNav || !tbody) return;

                function normalize(str) {
                    return String(str || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
                }

                function renderTable() {
                    // Se vuelve a consultar en cada render: al eliminar un cliente, _scripts quita su <tr>.
                    var allRows  = Array.prototype.slice.call(tbody.querySelectorAll('tr.clients-row'));
                    var noResults = tbody.querySelector('tr.clients-no-results');
                    var term     = normalize(searchInput ? searchInput.value.trim() : '');
                    var status   = statusSel ? statusSel.value : 'all';
                    var perPage  = parseInt(perPageSel ? perPageSel.value : '10', 10) || 10;

                    Array.prototype.forEach.call(tbody.rows, function (tr) { tr.style.display = ''; });

                    var matches = allRows.filter(function (tr) {
                        // data-search + texto visible (queda al día si se edita la fila sin recargar)
                        var haystack = normalize((tr.dataset.search || '') + ' ' + tr.textContent);
                        var badge = tr.querySelector('.users-manager-badge');
                        var hit = (!term || haystack.indexOf(term) !== -1) &&
                            (status === 'all' || (badge && badge.dataset.status === status));
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
                        ? 'Mostrando <strong>' + (from + 1) + '–' + to + '</strong> de <strong>' + matches.length + '</strong> ' + (matches.length === 1 ? 'cliente' : 'clientes')
                        : (allRows.length ? 'Sin resultados' : '');

                    var html = '';
                    if (pages > 1) {
                        html += '<button type="button" class="clients-pager__btn" data-page="' + (currentPage - 1) + '"' + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Página anterior">‹</button>';
                        for (var p = 1; p <= pages; p++) {
                            html += '<button type="button" class="clients-pager__btn' + (p === currentPage ? ' is-current' : '') + '" data-page="' + p + '"' + (p === currentPage ? ' aria-current="page"' : '') + '>' + p + '</button>';
                        }
                        html += '<button type="button" class="clients-pager__btn" data-page="' + (currentPage + 1) + '"' + (currentPage === pages ? ' disabled' : '') + ' aria-label="Página siguiente">›</button>';
                    }
                    pagerNav.innerHTML = html;
                }

                if (searchInput) searchInput.addEventListener('input', function () { currentPage = 1; renderTable(); });
                if (statusSel)   statusSel.addEventListener('change', function () { currentPage = 1; renderTable(); });
                if (perPageSel)  perPageSel.addEventListener('change', function () { currentPage = 1; renderTable(); });
                pagerNav.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-page]');
                    if (!btn || btn.disabled) return;
                    currentPage = parseInt(btn.dataset.page, 10);
                    renderTable();
                    var bar = document.querySelector('.clients-index .clients-toolbar');
                    if (bar) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
                // Fila eliminada por _scripts -> recalcular conteo y páginas.
                if (window.MutationObserver) {
                    new MutationObserver(function () { renderTable(); }).observe(tbody, { childList: true });
                }
                renderTable();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endpush
