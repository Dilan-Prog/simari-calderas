@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/proveedores.css')
@endpush
@section('title')
    Gestor de Proveedores - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section suppliers-index">
            <header class="clients-manager-main">
                <div>
                    <h1>Gestión de Proveedores</h1>
                    <p class="breadcrumb-clients-manager main">Administra tus proveedores y sus productos</p>
                </div>
                <div class="supplier-header-actions">
                    @include('admin.components._column_visibility_menu', [
                        'tableKey' => 'suppliers.index',
                        'columnDefs' => [
                            'contacto' => 'Contacto',
                            'contacto_info' => 'Email / Teléfono',
                            'condiciones_pago' => 'Condiciones de Pago',
                            'calidad' => 'Calidad',
                            'cumplimiento' => 'Cumplimiento',
                            'estado' => 'Estado',
                        ],
                    ])
                    <button type="button" class="button-secondary size-adjustment sup-header-btn" data-download-url="{{ route('admin.suppliers.products.export') }}">
                        Exportar Proveedor-Producto
                    </button>
                    <button type="button" id="btnOpenSupplierProductImportModal" class="button-secondary size-adjustment sup-header-btn">
                        Importar Proveedor-Producto
                    </button>
                    @permiso('suppliers','edit')
                    <a href="{{ route('admin.suppliers.products.bulk-edit.index') }}" class="button-secondary size-adjustment sup-header-btn">
                        Editar Proveedor-Producto en lote
                    </a>
                    @endpermiso
                    @permiso('suppliers','create')
                    <button type="button" class="button-primary size-adjustment new-supplier-btn sup-header-btn">+ Nuevo Proveedor</button>
                    @endpermiso
                </div>
            </header>

            {{-- Stats --}}
            <div class="grid-supplier-header">
                <div class="stat-card-supplier-index">
                    <p class="breadcrumb-users-manager">Total Proveedores</p>
                    <h2>{{ $suppliers->count() }}</h2>
                </div>
                <div class="stat-card-supplier-index">
                    <p class="breadcrumb-users-manager">Activos</p>
                    <h2 style="color:#16a34a;">{{ $suppliers->where('status', 'active')->count() }}</h2>
                </div>
                <div class="stat-card-supplier-index">
                    <p class="breadcrumb-users-manager">Inactivos</p>
                    <h2>{{ $suppliers->where('status', 'inactive')->count() }}</h2>
                </div>
                <div class="stat-card-supplier-index">
                    <p class="breadcrumb-users-manager">Bloqueados</p>
                    <h2 style="color:#dc2626;">{{ $suppliers->where('status', 'suspended')->count() }}</h2>
                </div>
            </div>

            {{-- Barra de la tabla: buscador + estado + condiciones de pago + filas por página.
                 Los ids supplierSearch / supplierStatusFilter / supplierPaymentFilter los usa
                 partials/_scripts (filterSuppliers); la paginación vive en el script inline de abajo. --}}
            <div class="suppliers-toolbar">
                <label class="suppliers-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="search" id="supplierSearch" autocomplete="off" aria-label="Buscar proveedores"
                        placeholder="Buscar por empresa, contacto, RFC, email o teléfono…">
                </label>
                <div class="suppliers-toolbar__filters">
                    <select id="supplierStatusFilter" class="suppliers-filter-select" aria-label="Filtrar por estado">
                        <option value="all">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                    <select id="supplierPaymentFilter" class="suppliers-filter-select" aria-label="Filtrar por condiciones de pago">
                        <option value="all">Todas las condiciones de pago</option>
                        <option value="15 días">15 días</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                        <option value="60 días">60 días</option>
                        <option value="contado">Contado</option>
                    </select>
                    <label class="suppliers-per-page">
                        Mostrar
                        <select id="suppliers-per-page" aria-label="Proveedores por página">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
            </div>

            {{-- Slider rating --}}
            <section class="supliers-manager-slider-section">
                <label class="supliers-manager-slider-label" id="rating-label">Rating mínimo: 0/5</label>
                <div class="supliers-manager-custom-slider" id="slider-container">
                    <div class="supliers-manager-slider-track-fill" id="slider-fill"></div>
                    <div class="supliers-manager-slider-handle" id="slider-handle"></div>
                </div>
            </section>

            {{-- Table --}}
            {{-- Una sola tabla (antes eran dos: encabezado y cuerpo por separado). En celular
                 (≤768px) cada fila se vuelve tarjeta (proveedores.css, bloque .suppliers-index). --}}
            <main class="table-container-clients-manager">
                <div class="table-scroll">
                    <table class="clients-manager-table suppliers-table" id="suppliersTable">
                        <thead>
                            <tr>
                                <th>EMPRESA</th>
                                <th data-col="contacto">CONTACTO</th>
                                <th data-col="contacto_info">EMAIL / TELÉFONO</th>
                                <th data-col="condiciones_pago">CONDICIONES DE PAGO</th>
                                <th data-col="calidad">CALIDAD</th>
                                <th data-col="cumplimiento">CUMPLIMIENTO</th>
                                <th data-col="estado">ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                                @forelse ($suppliers as $supplier)
                                    @php
                                        $statusLabel = match ($supplier->status) {
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            'suspended' => 'Suspendido',
                                            default => $supplier->status,
                                        };
                                        $statusClass = $supplier->status === 'active' ? 'status' : 'status-inactive';
                                    @endphp
                                    <tr class="supplier-row"
                                        data-search="{{ mb_strtolower(trim($supplier->company_name . ' ' . $supplier->contact_name . ' ' . $supplier->rfc . ' ' . $supplier->email . ' ' . $supplier->phone . ' ' . $supplier->payment_terms)) }}">
                                        <td class="clients-manager-table-cell sup-cell-company">
                                            <div class="avatar-user-manager sup-avatar" style="background:#3b82f6;color:white;">
                                                {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                            </div>
                                            <div class="sup-company-text">
                                                <p class="clients-manager-name-client supplier-company">
                                                    {{ $supplier->company_name }}</p>
                                                <span
                                                    class="clients-manager-ubication-client supplier-rfc">{{ $supplier->rfc ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="sup-cell-contact" data-col="contacto" data-label="Contacto">
                                            <p class="breadcrumb-clients-manager supplier-contact">
                                                {{ $supplier->contact_name ?? '—' }}</p>
                                        </td>
                                        <td class="sup-cell-info" data-col="contacto_info" data-label="Email / Teléfono">
                                            <p class="breadcrumb-clients-manager supplier-email">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                                </svg>
                                                {{ $supplier->email ?? '—' }}
                                            </p>
                                            <p class="breadcrumb-clients-manager supplier-phone">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                                    </path>
                                                </svg>
                                                {{ $supplier->phone ?? '—' }}
                                            </p>
                                        </td>
                                        <td class="sup-cell-payment" data-col="condiciones_pago" data-label="Condiciones de pago">
                                            <span class="users-manager-badge role-employee supplier-payment"
                                                data-payment="{{ $supplier->payment_terms }}">
                                                {{ $supplier->payment_terms ?? '—' }}
                                            </span>
                                        </td>
                                        {{-- Quality stars --}}
                                        <td class="sup-cell-quality" data-col="calidad" data-label="Calidad">
                                            <span class="supplier-stars-svg"
                                                data-rating="{{ $supplier->rating_quality ?? 0 }}">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24"
                                                        fill="{{ $s <= ($supplier->rating_quality ?? 0) ? '#facc15' : 'none' }}"
                                                        stroke="{{ $s <= ($supplier->rating_quality ?? 0) ? '#facc15' : '#d1d5db' }}"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                                                        </path>
                                                    </svg>
                                                @endfor
                                            </span>
                                        </td>
                                        {{-- Compliance stars --}}
                                        <td class="sup-cell-compliance" data-col="cumplimiento" data-label="Cumplimiento">
                                            <span class="supplier-stars-svg"
                                                data-rating="{{ $supplier->rating_compliance ?? 0 }}">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24"
                                                        fill="{{ $s <= ($supplier->rating_compliance ?? 0) ? '#facc15' : 'none' }}"
                                                        stroke="{{ $s <= ($supplier->rating_compliance ?? 0) ? '#facc15' : '#d1d5db' }}"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                                                        </path>
                                                    </svg>
                                                @endfor
                                            </span>
                                        </td>
                                        <td class="sup-cell-status" data-col="estado" data-label="Estado">
                                            <span class="users-manager-badge {{ $statusClass }} supplier-status-badge"
                                                data-status="{{ $supplier->status }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="sup-cell-actions">
                                            <div class="header-right-user-manager sup-actions">
                                                <button type="button" data-id="{{ $supplier->id }}"
                                                    onclick="window.location='{{ route('admin.suppliers.information', $supplier->id) }}'"
                                                    class="table-users-manager-action-btn edit btn-show-supplier"
                                                    title="Ver detalle" aria-label="Ver detalle del proveedor">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                                                        </path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </button>
                                                @permiso('suppliers','edit')
                                                <button type="button"
                                                    class="table-users-manager-action-btn edit btn-edit-supplier"
                                                    title="Editar" aria-label="Editar proveedor"
                                                    data-id="{{ $supplier->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                        </path>
                                                    </svg>
                                                </button>
                                                @endpermiso
                                                @permiso('suppliers','delete')
                                                <button type="button"
                                                    class="table-users-manager-action-btn delete btn-delete-supplier"
                                                    title="Eliminar" aria-label="Eliminar proveedor"
                                                    data-id="{{ $supplier->id }}"
                                                    data-name="{{ $supplier->company_name }}"
                                                    data-email="{{ $supplier->email }}"
                                                    data-initial="{{ strtoupper(substr($supplier->company_name, 0, 1)) }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 6h18"></path>
                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                        <line x1="10" x2="10" y1="11"
                                                            y2="17"></line>
                                                        <line x1="14" x2="14" y1="11"
                                                            y2="17"></line>
                                                    </svg>
                                                </button>
                                                @endpermiso
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="suppliers-empty-row">
                                        <td colspan="8" class="suppliers-empty">No hay proveedores registrados.</td>
                                    </tr>
                                @endforelse
                                <tr class="suppliers-no-results" hidden>
                                    <td colspan="8" class="suppliers-empty">Ningún proveedor coincide con los filtros.</td>
                                </tr>
                        </tbody>
                    </table>
                </div>
            </main>
            {{-- Pie de la tabla: conteo + paginación --}}
            <div class="suppliers-pager" id="suppliers-pager">
                <span class="suppliers-pager__info" id="suppliers-pager-info"></span>
                <div class="suppliers-pager__nav" id="suppliers-pager-nav"></div>
            </div>
        </section>
    </div>

    @include('admin.supplier.partials._modal_create')
    @include('admin.supplier.partials._modal_edit')
    @include('admin.supplier.partials._modal_delete')
    @include('admin.supplier.partials._import_supplier_products_modal')
    @include('admin.components._download_format_modal')
    @include('admin.supplier.partials._scripts')
    @include('admin.supplier.partials._import_supplier_products_scripts')

    @push('scripts')
        <script src="{{ asset('js/admin/column-visibility.js') }}"></script>
        <script>
            initColumnVisibility({
                tableKey: 'suppliers.index',
                savedColumns: @json($visibleColumns),
                saveUrl: '{{ route('admin.column-preferences.update') }}',
            });
        </script>
        <script src="{{ asset('js/admin/download-format.js') }}"></script>
        <script>
            initDownloadFormatModal();
        </script>
        <script>
            // Paginación en el navegador del listado de proveedores (mismo patrón que
            // clients/index). El filtrado (buscador, estado, condiciones de pago y rating
            // mínimo) lo hace filterSuppliers() en partials/_scripts: marca cada fila con
            // [hidden] y luego llama a window.suppliersPager.render(), que sólo reparte
            // en páginas las filas visibles (.is-paged-out) y pinta el pie.
            (function () {
                function init() {
                    var perPageSel  = document.getElementById('suppliers-per-page');
                    var pagerInfo   = document.getElementById('suppliers-pager-info');
                    var pagerNav    = document.getElementById('suppliers-pager-nav');
                    var tbody       = document.querySelector('.suppliers-index #suppliersTable tbody');
                    var currentPage = 1;

                    if (!pagerInfo || !pagerNav || !tbody) return;

                    function render() {
                        // Se vuelve a consultar en cada render: al eliminar un proveedor, _scripts quita su <tr>.
                        var allRows   = Array.prototype.slice.call(tbody.querySelectorAll('tr.supplier-row'));
                        var noResults = tbody.querySelector('tr.suppliers-no-results');
                        var perPage   = parseInt(perPageSel ? perPageSel.value : '10', 10) || 10;
                        var matches   = allRows.filter(function (tr) { return !tr.hidden; });

                        var pages = Math.max(1, Math.ceil(matches.length / perPage));
                        currentPage = Math.min(Math.max(1, currentPage), pages);
                        var from = (currentPage - 1) * perPage;
                        var to   = Math.min(from + perPage, matches.length);
                        allRows.forEach(function (tr) { tr.classList.remove('is-paged-out'); });
                        matches.forEach(function (tr, i) { tr.classList.toggle('is-paged-out', i < from || i >= to); });

                        if (noResults) noResults.hidden = !(allRows.length && !matches.length);
                        pagerInfo.innerHTML = matches.length
                            ? 'Mostrando <strong>' + (from + 1) + '–' + to + '</strong> de <strong>' + matches.length + '</strong> ' + (matches.length === 1 ? 'proveedor' : 'proveedores')
                            : (allRows.length ? 'Sin resultados' : '');

                        var html = '';
                        if (pages > 1) {
                            html += '<button type="button" class="suppliers-pager__btn" data-page="' + (currentPage - 1) + '"' + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Página anterior">‹</button>';
                            for (var p = 1; p <= pages; p++) {
                                html += '<button type="button" class="suppliers-pager__btn' + (p === currentPage ? ' is-current' : '') + '" data-page="' + p + '"' + (p === currentPage ? ' aria-current="page"' : '') + '>' + p + '</button>';
                            }
                            html += '<button type="button" class="suppliers-pager__btn" data-page="' + (currentPage + 1) + '"' + (currentPage === pages ? ' disabled' : '') + ' aria-label="Página siguiente">›</button>';
                        }
                        pagerNav.innerHTML = html;
                    }

                    window.suppliersPager = {
                        render: render,
                        reset: function () { currentPage = 1; render(); },
                    };

                    if (perPageSel) perPageSel.addEventListener('change', function () { currentPage = 1; render(); });
                    pagerNav.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-page]');
                        if (!btn || btn.disabled) return;
                        currentPage = parseInt(btn.dataset.page, 10);
                        render();
                        var bar = document.querySelector('.suppliers-index .suppliers-toolbar');
                        if (bar) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                    // Fila eliminada por _scripts -> recalcular conteo y páginas.
                    if (window.MutationObserver) {
                        new MutationObserver(function () { render(); }).observe(tbody, { childList: true });
                    }

                    if (typeof filterSuppliers === 'function') {
                        filterSuppliers();
                    } else {
                        render();
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @endpush
@endsection
