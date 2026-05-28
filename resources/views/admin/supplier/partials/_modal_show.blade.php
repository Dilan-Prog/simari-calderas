@extends('admin.layouts.master')

@section('title')
    {{ $supplier->company_name }} - Proveedor
@endsection

@section('content')
    <div class="container user-manager show-information">
        <section class="clients-manager-section show-information-supplier">
            <div class="link-container-show">
                <a class="client-window-link" href="{{ route('admin.suppliers.index') }}">
                    <span>&lt;</span> &nbsp; Volver a Proveedores
                </a>
            </div>

            {{-- Header --}}
            <div class="supplier-header-show">
                <div class="client-left-show">
                    <div class="avatar-show supplier-avatar">
                        {{ strtoupper(substr($supplier->company_name, 0, 1)) }}
                    </div>
                    <div class="information-client-show">
                        <h2>{{ $supplier->company_name }}</h2>
                        <p class="breadcrumb-users-manager">{{ $supplier->contact_name ?? '—' }}</p>
                        @php
                            $statusLabel = match ($supplier->status) {
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'suspended' => 'Suspendido',
                                default => $supplier->status,
                            };
                            $statusClass = $supplier->status === 'active' ? 'status' : 'status-inactive';
                        @endphp
                        <span class="users-manager-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </div>
                <div class="client-actions-show up">
                    <div class="client-actions-show">
                        <button type="button" class="button-primary size-adjustment edit-client btn-edit-supplier-show">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                </path>
                            </svg>
                            Editar Proveedor
                        </button>
                    </div>
                </div>
            </div>


            {{-- Tabs --}}
            <div class="tabs-show-clients-container supplier-tabs">
                <button class="tab-show-client active" data-tab="general">Información General</button>
                <button class="tab-show-client" data-tab="products">Productos ({{ $supplier->products->count() }})</button>
                <button class="tab-show-client" data-tab="evaluation">Evaluación</button>
            </div>

            {{-- Tab: General --}}
            <div class="tab-content-show-client active" id="general">
                <div class="card-information-show">
                    <div class="show-user-grid">
                        <div class="show-user-field">
                            <span class="show-user-field-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                    <path d="M10 6h4"></path>
                                    <path d="M10 10h4"></path>
                                    <path d="M10 14h4"></path>
                                    <path d="M10 18h4"></path>
                                </svg>
                                Nombre de la Empresa
                            </span>
                            <span class="show-user-field-value">{{ $supplier->company_name }}</span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">Contacto Principal</span>
                            <span class="show-user-field-value">{{ $supplier->contact_name ?? '—' }}</span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                    </path>
                                </svg>
                                Teléfono
                            </span>
                            <span class="show-user-field-value">{{ $supplier->phone ?? '—' }}</span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">RFC</span>
                            <span class="show-user-field-value">{{ $supplier->rfc ?? '—' }}</span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                Email
                            </span>
                            <span class="show-user-field-value">{{ $supplier->email ?? '—' }}</span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">Condiciones de Pago</span>
                            <span class="users-manager-badge role-employee" style="width:fit-content;">
                                {{ $supplier->payment_terms ?? '—' }}
                            </span>
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                                    <path d="M2 12h20"></path>
                                </svg>
                                Sitio Web
                            </span>
                            @if ($supplier->website)
                                <a href="{{ $supplier->website }}" target="_blank" class="show-user-field-value"
                                    style="color:#2563eb;">
                                    {{ $supplier->website }}
                                </a>
                            @else
                                <span class="show-user-field-value">—</span>
                            @endif
                        </div>
                        <div class="show-user-field">
                            <span class="show-user-field-label">Estado</span>
                            <span class="users-manager-badge {{ $statusClass }}" style="width:fit-content;">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="show-user-field show-user-field--full">
                            <span class="show-user-field-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                    </path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                Dirección
                            </span>
                            <span class="show-user-field-value">{{ $supplier->address ?? '—' }}</span>
                        </div>
                    </div>

                    @if ($supplier->notes)
                        <div class="show-user-field" style="margin-top:16px;">
                            <span class="show-user-field-label">Notas Internas</span>
                            <div class="note-box-information-client">
                                <p class="breadcrumb-clients-manager main">{{ $supplier->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tab: Products --}}
            <div class="tab-content-show-client" id="products">
                <div class="card-information-show">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="margin:0;">Productos del Proveedor</h3>
                        <button type="button" class="button-primary size-adjustment"
                            style="font-size:14px;padding:8px 20px;">
                            + Asignar Producto
                        </button>
                    </div>
                    <table class="clients-manager-table">
                        <thead>
                            <tr>
                                <th>PRODUCTO</th>
                                <th>SKU</th>
                                <th>COSTO</th>
                                <th>LEAD TIME</th>
                                <th>PRIMARIO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($supplier->products as $product)
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                                </path>
                                                <path d="M12 22V12"></path>
                                                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                                            </svg>
                                            {{ $product->name }}
                                        </div>
                                    </td>
                                    <td><span class="breadcrumb-clients-manager">{{ $product->sku }}</span></td>
                                    <td><strong>${{ number_format($product->pivot->cost ?? 0, 0) }}</strong></td>
                                    <td>
                                        <span style="display:flex;align-items:center;gap:4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            {{ $product->pivot->lead_time_days ?? 0 }} días
                                        </span>
                                    </td>
                                    <td>
                                        @if ($product->pivot->is_primary)
                                            <span style="display:flex;align-items:center;gap:4px;color:#16a34a;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <path d="m9 11 3 3L22 4"></path>
                                                </svg>
                                                Sí
                                            </span>
                                        @else
                                            <span class="breadcrumb-clients-manager">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="header-right-user-manager">
                                            <button type="button" class="table-users-manager-action-btn edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button type="button" class="table-users-manager-action-btn delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17">
                                                    </line>
                                                    <line x1="14" x2="14" y1="11" y2="17">
                                                    </line>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:24px;">
                                        <p class="breadcrumb-clients-manager">Sin productos asignados.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: Evaluation --}}
            <div class="tab-content-show-client" id="evaluation">
                <div class="show-user-grid" style="margin-bottom:20px;">
                    <div class="card-information-show" style="background:#fefce8;border:1px solid #fef08a;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Rating de Calidad</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                                </polygon>
                            </svg>
                        </div>
                        <div style="display:flex;align-items:baseline;gap:8px;margin:8px 0;">
                            <h2 style="margin:0;font-size:2rem;">{{ $supplier->rating_quality ?? 0 }}</h2>
                            <span class="supplier-stars-svg">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
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
                        </div>
                        <p class="breadcrumb-clients-manager">de 5 estrellas</p>
                    </div>
                    <div class="card-information-show" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;">Rating de Cumplimiento</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                        </div>
                        <div style="display:flex;align-items:baseline;gap:8px;margin:8px 0;">
                            <h2 style="margin:0;font-size:2rem;">{{ $supplier->rating_compliance ?? 0 }}</h2>
                            <span class="supplier-stars-svg">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
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
                        </div>
                        <p class="breadcrumb-clients-manager">de 5 estrellas</p>
                    </div>
                </div>
                @if ($supplier->notes)
                    <div class="card-information-show">
                        <h3>Notas de Evaluación</h3>
                        <div class="note-box-information-client">
                            <p class="breadcrumb-users-manager main">{{ $supplier->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tab: Audit --}}
            {{-- <div class="tab-content-show-client" id="audit">
                <div class="card-information-show">
                    <h3>Historial de Auditoría</h3>
                    <p class="breadcrumb-clients-manager">Próximamente disponible.</p>
                </div>
            </div> --}}

        </section>
        @include('admin.supplier.partials._modal_edit')
    </div>
    @include('admin.supplier.partials._scripts_show')
@endsection
