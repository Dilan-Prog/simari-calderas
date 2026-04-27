@extends('admin.layouts.master')
@section('title')
    Gestor de Clientes - Admin
@endsection
@section('content')
    <div class="container user-manager show-information">
        <section class="clients-manager-section show">
            <div class="link-container-show">
                <a class="client-window-link" href="{{ route('admin.clients.index') }}">
                    <span>
                        < </span> &nbsp; Clientes </a>
            </div>

            <div class="client-header-show">
                <div class="client-left-show">
                    <div class="avatar-show">
                        J
                    </div>

                    <div class="information-client-show">
                        <h2>Juan Pérez</h2>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-building2 lucide-building-2">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                <path d="M10 6h4"></path>
                                <path d="M10 10h4"></path>
                                <path d="M10 14h4"></path>
                                <path d="M10 18h4"></path>
                            </svg> Industrias Metálicas del Bajío S.A. de C.V.</p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin">
                                <path
                                    d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                </path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg> Aguascalientes, Aguascalientes</p>
                        <span class="users-manager-badge status">Activo</span>
                    </div>
                </div>

                <div class="client-actions-show up">
                    <div class="client-actions-show">
                        <button class="button-primary size-adjustment edit-client">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-pen">
                                <path
                                    d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                </path>
                            </svg> Editar Cliente
                        </button>
                        <button class="button-primary size-adjustment new-order">
                            ＋ Nueva Orden
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-show-clients-container">
                <button class="tab-show-client active" data-tab="info">Información</button>
                <button class="tab-show-client" data-tab="orders">Órdenes</button>
                <button class="tab-show-client" data-tab="addresses">Direcciones</button>
            </div>

            <!-- Content -->
            <div class="tab-content-show-client active" id="info">
                <div class="grid-2-information-client-show">

                    <!-- Information -->
                    <div class="card-information-show">
                        <h3>Información de Contacto</h3>
                        <p class="breadcrumb-users-manager main"><strong>Email</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-mail text-gray-400">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg> &nbsp; juan.perez@abc.com
                        </p>

                        <p class="breadcrumb-users-manager"><strong>Teléfono</strong></p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-gray-400">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg> &nbsp; 524494348018</p>

                        <p class="breadcrumb-users-manager main"><strong>WhatsApp</strong></p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-gray-400">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg> &nbsp; 524494348018</p>

                        <p class="breadcrumb-users-manager main"><strong>RFC</strong></p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-file-text text-gray-400">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg> &nbsp; IMB890615HG7</p>

                        <p class="breadcrumb-users-manager main"><strong>Tipo de documento</strong></p>
                        <p class="breadcrumb-users-manager"> &nbsp; INE</p>

                        <p class="breadcrumb-users-manager main"><strong>Número de documento</strong></p>
                        <p class="breadcrumb-users-manager"> &nbsp; 1234567890123</p>

                        <p class="breadcrumb-users-manager main"><strong>Fecha de nacimiento</strong></p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-calendar text-gray-400">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            &nbsp; 15 de junio, 1985</p>

                        <p class="breadcrumb-users-manager main"><strong>¿Cómo nos conoció?</strong></p>
                        <p class="breadcrumb-users-manager"> &nbsp; Web</p>

                        <p class="breadcrumb-users-manager main"><strong>Fecha de registro</strong></p>
                        <p class="breadcrumb-users-manager"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-calendar text-gray-400">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            &nbsp; 9 de junio de 2023</p>

                    </div>

                    <!-- Notes -->
                    <div class="notes-information-show">
                        <div class="card-information-show notes">
                            <h3>Notas Internas</h3>
                            <div class="note-box-information-client">
                                <p class="breadcrumb-users-manager main">
                                    Cliente preferente. Pedidos frecuentes de calderas industriales.
                                </p>
                            </div>
                        </div>

                        <!-- Activitie -->
                        <div class="card-information-show activitie">
                            <h3>Última Actividad</h3>
                            <p class="breadcrumb-users-manager main activitie"><svg xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-calendar text-gray-400">
                                    <path d="M8 2v4"></path>
                                    <path d="M16 2v4"></path>
                                    <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg> 15 de marzo, 2024</p>
                            <p class="breadcrumb-users-manager">Nueva orden creada (ORD-001)</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Orders -->
            <div class="tab-content-show-client" id="orders">
                <div class="stats">
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Total de Órdenes</p>
                        <h2>3</h2>
                    </div>
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Total Gastado</p>
                        <h2>$75,750.00</h2>
                    </div>
                    <div class="stat-card">
                        <p class="breadcrumb-users-manager">Última Orden</p>
                        <h2>15 Mar 2024</h2>
                    </div>
                </div>
                <main class="table-container-users-manager">
                    <table class="users-manager-table">
                        <thead>
                            <tr>
                                <th>ORDEN #</th>
                                <th>FECHA</th>
                                <th>ESTADO</th>
                                <th>TOTAL</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                    </table>

                    <div class="table-scroll">
                        <table class="users-manager-table">
                            <tbody>
                                @for ($i = 0; $i < 12; $i++)
                                    <tr class="table-row-user-manager">
                                        <td class="user-manager-table-cell">
                                            <div>
                                               <h3>ORD-001</h3>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="breadcrumb-users-manager main">2024-03-15</p>
                                        </td>
                                        <td>
                                            <span class="users-manager-badge role-employee">
                                                En tránsito
                                            </span>
                                        </td>
                                        <td>
                                            <h3>$25,450.00</h3>
                                        </td>
                                        <td>
                                            <div class="header-right-user-manager">
                                                {{-- edit --}}
                                                <button class="table-users-manager-action-btn edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-package">
                                                        <path
                                                            d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                                        </path>
                                                        <path d="M12 22V12"></path>
                                                        <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                                        <path d="m7.5 4.27 9 5.15"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        <!-- Adress -->
                        <div class="tab-content-show-client" id="addresses">
                            <div class="grid-2-information-client-show">

                                <div class="card-information-show">
                                    <div class="card-information-show-header">
                                        <h3>Oficina Principal</h3>
                                        <span class="badge blue">Predeterminada</span>
                                    </div>

                                    <p><strong>Destinatario</strong><br>Juan Pérez</p>
                                    <p><strong>Dirección</strong><br>Calle 123...</p>
                                    <p><strong>Teléfono</strong><br>524494348018</p>
                                </div>

                                <div class="card-information-show">
                                    <h3>Planta de Producción</h3>

                                    <p><strong>Destinatario</strong><br>Departamento de Recepción</p>
                                    <p><strong>Dirección</strong><br>Zona Industrial...</p>
                                    <p><strong>Teléfono</strong><br>524494348018</p>
                                </div>
                            </div>

                            <button class="btn-dashed">＋ Agregar dirección</button>
                        </div>
                </main>
        </section>

        <script>
            document.querySelectorAll(".tab-show-client").forEach(btn => {
                btn.addEventListener("click", () => {

                    document.querySelectorAll(".tab-show-client").forEach(t => t.classList.remove("active"));
                    document.querySelectorAll(".tab-content-show-client").forEach(c => c.classList.remove(
                        "active"));

                    btn.classList.add("active");
                    document.getElementById(btn.dataset.tab).classList.add("active");
                });
            });
        </script>
    </div>
@endsection
