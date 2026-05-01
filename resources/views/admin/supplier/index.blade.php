@extends('admin.layouts.master')
@section('title')
    Gestor de Proveedores - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">
            <header class="clients-manager-main">
                <div>
                    <h1>Gestión de Proveedores</h1>
                    <p class="breadcrumb-clients-manager main">Administra tus proveedores y sus productos</p>
                </div>
                <button type="button" class="button-primary size-adjustment suppliers">
                    + Nuevo Proveedor
                </button>
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

            {{-- Filters --}}
                <div class="filters-clients-manager-search grid-suppliers">
                    <span class="search-icon-clients-manager">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>
                    <input type="text" id="supplierSearch"
                        placeholder="Buscar por empresa, contacto, email o teléfono...">
                </div>
                <div class="filters-clients-manager-select">
                    <select id="supplierStatusFilter" class="suppliers-filters-index">
                        <option value="all">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>
                <div class="filters-clients-manager-select">
                    <select id="supplierPaymentFilter" class="suppliers-filters-index">
                        <option value="all">Todas las condiciones de pago</option>
                        <option value="15 días">15 días</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                        <option value="60 días">60 días</option>
                        <option value="contado">Contado</option>
                    </select>
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
            <main>
                <div class="table-container-clients-manager head">
                    <table class="clients-manager-table head-table">
                        <thead>
                            <tr>
                                <th>EMPRESA</th>
                                <th>CONTACTO</th>
                                <th>CONTACTO</th>
                                <th>CONDICIONES DE PAGO</th>
                                <th>CALIDAD</th>
                                <th>CUMPLIMIENTO</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                    </table>
                    <div class="table-scroll">
                        <table class="clients-manager-table" id="suppliersTable">
                            <tbody>
                                @foreach ($suppliers as $supplier)
                                    @php
                                        $statusLabel = match ($supplier->status) {
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            'suspended' => 'Suspendido',
                                            default => $supplier->status,
                                        };
                                        $statusClass = $supplier->status === 'active' ? 'status' : 'status-inactive';
                                    @endphp
                                    <tr class="supplier-row">
                                        <td class="clients-manager-table-cell">
                                            <div class="avatar-user-manager" style="background:#3b82f6;color:white;">
                                                {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="clients-manager-name-client supplier-company">
                                                    {{ $supplier->company_name }}</p>
                                                <span
                                                    class="clients-manager-ubication-client supplier-rfc">{{ $supplier->rfc ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="breadcrumb-clients-manager supplier-contact">
                                                {{ $supplier->contact_name ?? '—' }}</p>
                                        </td>
                                        <td>
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
                                        <td>
                                            <span class="users-manager-badge role-employee supplier-payment"
                                                data-payment="{{ $supplier->payment_terms }}">
                                                {{ $supplier->payment_terms ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="supplier-stars"
                                                data-rating="{{ $supplier->rating_quality ?? 0 }}">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    @if ($s <= ($supplier->rating_quality ?? 0))
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </span>
                                        </td>
                                        <td>
                                            <span class="supplier-stars"
                                                data-rating="{{ $supplier->rating_compliance ?? 0 }}">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    @if ($s <= ($supplier->rating_compliance ?? 0))
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </span>
                                        </td>
                                        <td>
                                            <span class="users-manager-badge {{ $statusClass }}"
                                                data-status="{{ $supplier->status }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="header-right-user-manager">
                                                <button type="button"
                                                    class="table-users-manager-action-btn edit btn-show-supplier"
                                                    data-id="{{ $supplier->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                                                        </path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                    class="table-users-manager-action-btn edit btn-edit-supplier"
                                                    data-id="{{ $supplier->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                    class="table-users-manager-action-btn delete btn-delete-supplier"
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
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </section>

    </div>

    @include('admin.supplier.partials._modal_create')
    @include('admin.supplier.partials._modal_edit')
    @include('admin.supplier.partials._modal_delete')
    @include('admin.supplier.partials._scripts')
@endsection
