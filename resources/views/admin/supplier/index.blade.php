@extends('admin.layouts.master')
<<<<<<< HEAD

@section('title')
    Gestor de proveedores - Admin
@endsection

@section('content')
    <div class="container user-manager">

        {{-- Main content --}}

        {{-- Text --}}
        <section class="clients-manager-section">

            {{-- clients manager section --}}
            <header class="clients-manager-main">
                <div>
                    <h1>Gestión de Proveedores</h1>

                    <p class="breadcrumb-clients-manager main">
                        Administra tus proveedores y sus productos
                    </p>
                </div>

                <button class="button-primary size-adjustment">
=======
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
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
                    + Nuevo Proveedor
                </button>
            </header>

<<<<<<< HEAD
            <section class="filters-clients-manager">

                {{-- Page search --}}
                <div class="filters-clients-manager-search">
                    <span class="search-icon-clients-manager">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
=======
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
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>
<<<<<<< HEAD
                    <input type="text" placeholder="Buscar por empresa, contacto, email o teléfono...">
                </div>

                {{-- Filter  --}}
                <div class="filters-clients-manager-select">
                    <select>
=======
                    <input type="text" id="supplierSearch"
                        placeholder="Buscar por empresa, contacto, email o teléfono...">
                </div>
                <div class="filters-clients-manager-select">
                    <select id="supplierStatusFilter" class="suppliers-filters-index">
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
                        <option value="all">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>
<<<<<<< HEAD

                <div class="filters-clients-manager-select">
                    <select>
                        <option value="all">Todos las condiciones de pago</option>
                        <option value="active">30 dias</option>
                    </select>
                </div>
            </section>

            {{-- slider --}}
            <section class="supliers-manager-slider-section">
                    <label class="supliers-manager-slider-label" id="rating-label">Rating mínimo: 0/5</label>
                    <div class="supliers-manager-custom-slider" id="slider-container">
                        <div class="supliers-manager-slider-track-fill" id="slider-fill"></div>
                        <div class="supliers-manager-slider-handle" id="slider-handle"></div>
                    </div>
            </section>

            <!-- TABLE -->
            <main class="table-container-clients-manager">

                <table class="clients-manager-table">

                    <thead>
                        <tr>
                            <th>CLIENTE</th>
                            <th>EMPRESA</th>
                            <th>CONTACTO</th>
                            <th>RFC</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>

                    <tbody>

                        {{-- Rows --}}
                        @for ($i = 0; $i <= 14; $i++)
                            <tr>
                                <td class="clients-manager-table-cell">
                                    <div class="avatar-user-manager">
                                        D
                                    </div>
                                    <div>
                                        <p class="clients-manager-name-client">Cesar Gavidia</p>
                                        <span class="clients-manager-ubication-client">Aguascalientes,
                                            Aguascalientes</span>
                                    </div>
                                </td>

                                <td>
                                    <p class="breadcrumb-clients-manager">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-building2 lucide-building-2 text-gray-600">
                                            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                            <path d="M10 6h4"></path>
                                            <path d="M10 10h4"></path>
                                            <path d="M10 14h4"></path>
                                            <path d="M10 18h4"></path>
                                        </svg> Alimentos Procesados del Norte
                                    </p>
                                </td>

                                <td>
                                    <p class="breadcrumb-clients-manager">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-mail text-gray-400">
                                            <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                        </svg> cesar.gavidia@apnorte.mx
                                    </p>
                                    <p class="breadcrumb-clients-manager">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-phone text-gray-400">
                                            <path
                                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                            </path>
                                        </svg> (444) 825-1167
                                    </p>
                                </td>

                                <td>
                                    <span class="breadcrumb-clients-manager">APN870504KL9</span>
                                </td>

                                <td>
                                    <span class="users-manager-badge status">Activo</span>
                                </td>

                                <td>
                                    <div class="header-right-user-manager">
                                        <button class="table-users-manager-action-btn edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                                                <path
                                                    d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                                                </path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
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

=======
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
                                        {{-- Quality stars --}}
                                        <td>
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
                                        <td>
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
                                        <td>
                                            <span class="users-manager-badge {{ $statusClass }} supplier-status-badge"
                                                data-status="{{ $supplier->status }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="header-right-user-manager">
                                                <button type="button" data-id="{{ $supplier->id }}"
                                                    onclick="window.location='{{ route('admin.suppliers.information', $supplier->id) }}'"
                                                    class="table-users-manager-action-btn edit btn-show-supplier">
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
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
            </main>
        </section>
    </div>

<<<<<<< HEAD
    <script>
        const slider = document.getElementById('slider-container');
        const handle = document.getElementById('slider-handle');
        const fill = document.getElementById('slider-fill');
        const label = document.getElementById('rating-label');

        let isDragging = false;

        function updateSlider(e) {
            const rect = slider.getBoundingClientRect();
            let clientX = e.clientX || (e.touches && e.touches[0].clientX);
            let x = clientX - rect.left;

            // 1. Calculamos el porcentaje real del mouse (0 a 100)
            let rawPercentage = (x / rect.width) * 100;
            if (rawPercentage < 0) rawPercentage = 0;
            if (rawPercentage > 100) rawPercentage = 100;

            // 2. LÓGICA DE "SNAP": Forzamos a que solo existan valores 0, 1, 2, 3, 4, 5
            // Dividimos 100% entre 5 espacios = 20% cada salto
            const step = 20;
            const value = Math.round(rawPercentage / step); // Esto nos da un entero del 0 al 5
            const snappedPercentage = value * step; // Esto convierte el 3 en 60%, el 4 en 80%, etc.

            // 3. Aplicamos la posición calculada (ahora "salta" a los puntos)
            handle.style.left = `calc(${snappedPercentage}% - 9px)`;
            fill.style.width = `${snappedPercentage}%`;

            // Actualizar el texto
            label.innerText = `Rating mínimo: ${value}/5`;
        }

        // --- Eventos (Se mantienen igual) ---
        handle.addEventListener('mousedown', () => isDragging = true);
        document.addEventListener('mouseup', () => isDragging = false);
        document.addEventListener('mousemove', (e) => {
            if (isDragging) updateSlider(e);
        });

        handle.addEventListener('touchstart', () => isDragging = true);
        document.addEventListener('touchend', () => isDragging = false);
        document.addEventListener('touchmove', (e) => {
            if (isDragging) updateSlider(e);
        });

        slider.addEventListener('click', (e) => {
            if (!isDragging) updateSlider(e);
        });
    </script>
=======
    @include('admin.supplier.partials._modal_create')
    @include('admin.supplier.partials._modal_edit')
    @include('admin.supplier.partials._modal_delete')
    @include('admin.supplier.partials._scripts')
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
@endsection
