@extends('admin.layouts.master')

@section('title')
    Crear cliente - Admin
@endsection

@section('content')
    <div class="container user-manager">

        {{-- Main content --}}

        {{-- Text --}}
        <section class="clients-manager-section">

            {{-- clients manager section --}}
            <header class="clients-manager-main">
                <div>
                    <h1>Gestión de Clientes</h1>

                    <p class="breadcrumb-clients-manager main">
                        Administra la información de tus clientes B2B
                    </p>
                </div>

                <button class="button-primary size-adjustment clients">
                    + Nuevo Cliente
                </button>
            </header>

            <section class="filters-clients-manager">
                {{-- Page search --}}
                <div class="filters-clients-manager-search">
                    <span class="search-icon-clients-manager">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>
                    <input type="text" placeholder="Buscar por nombre, empresa, email o RFC...">
                </div>

                {{-- Filter  --}}
                <div class="filters-clients-manager-select">
                    <select>
                        <option value="all">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>

            </section>

            <!-- TABLE -->
            <main>
                <div class="table-container-clients-manager head">
                    <table class="clients-manager-table head-table">
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
                    </table>
                    <div class="table-scroll">
                        <table class="clients-manager-table">
                            <tbody>
                                {{-- Rows --}}
                                @foreach ($customers as $customer)
                                    <tr>
                                        <td class="clients-manager-table-cell">
                                            <div class="avatar-user-manager">
                                                D
                                            </div>
                                            <div>
                                                <p class="clients-manager-name-client">{{ $customer->first_name }}
                                                    {{ $customer->last_name }}</p>
                                                @php
                                                    $address = $customer->customer_addresses->first();
                                                @endphp
                                                <span class="clients-manager-ubication-client">
                                                    {{ $address ? $address->city . ', ' . $address->state : '—' }}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <p class="breadcrumb-clients-manager">
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
                                                </svg> {{ $customer->company }}
                                            </p>
                                        </td>

                                        <td>
                                            <p class="breadcrumb-clients-manager">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-mail text-gray-400">
                                                    <rect width="20" height="16" x="2" y="4" rx="2">
                                                    </rect>
                                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                                </svg> {{ $customer->email }}
                                            </p>
                                            <p class="breadcrumb-clients-manager">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-phone text-gray-400">
                                                    <path
                                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                                    </path>
                                                </svg> {{ $customer->phone }}
                                            </p>
                                        </td>

                                        <td>
                                            <span class="breadcrumb-clients-manager">{{ $customer->rfc }}</span>
                                        </td>

                                        <td>
                                            @php
                                                $statusLabel = match ($customer->status) {
                                                    'active' => 'Activo',
                                                    'inactive' => 'Inactivo',
                                                    'suspended' => 'Suspendido',
                                                    default => $customer->status,
                                                };
                                                $statusClass = match ($customer->status) {
                                                    'active' => 'status',
                                                    'inactive' => 'status-inactive',
                                                    'suspended' => 'status-inactive',
                                                    default => 'status',
                                                };
                                            @endphp
                                            <span class="users-manager-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>

                                        <td>
                                            <div class="header-right-user-manager buttons">
                                                <button class="table-users-manager-action-btn edit"
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
                                                <button type="button"
                                                    class="table-users-manager-action-btn edit btn-edit-client"
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
                                                <button type="button"
                                                    class="table-users-manager-action-btn delete btn-delete-client"
                                                    data-id="{{ $customer->id }}"
                                                    data-name="{{ $customer->first_name }} {{ $customer->last_name }}"
                                                    data-email="{{ $customer->email }}"
                                                    data-initial="{{ strtoupper(substr($customer->first_name, 0, 1)) }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-trash2 lucide-trash-2">
                                                        <path d="M3 6h18"></path>
                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                        <line x1="10" x2="10" y1="11"
                                                            y2="17">
                                                        </line>
                                                        <line x1="14" x2="14" y1="11"
                                                            y2="17">
                                                        </line>
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
            @include('admin.client.partials._modal_create')
            @include('admin.client.partials._edit_modal')
            @include('admin.client.partials._scripts')
        </section>
        @include('admin.client.partials._modal_delete')
    </div>
@endsection
