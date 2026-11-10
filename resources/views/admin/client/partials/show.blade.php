@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/clientes.css')
    @vite('resources/css/admin/pages/clientes-modals.css')
@endpush

@push('styles')
<style>
/* ── Ficha del cliente: adaptación a teléfono (solo ≤768px / ≤640px; escritorio sin cambios) ── */
@media (max-width: 768px) {
    .cshow-m { max-width: 100%; overflow-x: clip; }
    .cshow-m .clients-manager-section.show { min-width: 0; }
    .cshow-m .link-container-show { margin-bottom: 16px; }
    .cshow-m .client-window-link { display: inline-flex; align-items: center; min-height: 44px; }

    /* Header: identidad + acciones */
    .cshow-m .client-header-show { padding: 16px; gap: 16px; margin-bottom: 16px; }
    .cshow-m .client-left-show { flex-direction: row; align-items: flex-start; justify-content: flex-start; gap: 12px; min-width: 0; }
    .cshow-m .avatar-show { width: 56px; height: 56px; flex: 0 0 56px; font-size: 20px; }
    .cshow-m .information-client-show { gap: 6px; min-width: 0; flex: 1; }
    .cshow-m .information-client-show h2 { font-size: 22px; line-height: 1.2; overflow-wrap: anywhere; }
    .cshow-m .information-client-show .breadcrumb-users-manager { display: flex; align-items: flex-start; gap: 6px; margin: 0; overflow-wrap: anywhere; min-width: 0; }
    .cshow-m .information-client-show .breadcrumb-users-manager svg { flex: 0 0 auto; margin-top: 2px; }
    .cshow-m .client-actions-show.up { width: 100%; align-items: stretch; }
    .cshow-m .client-actions-show.up > .client-actions-show { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; width: 100%; }
    .cshow-m .client-actions-show .button-primary { width: 100%; min-height: 44px; height: auto; justify-content: center; align-items: center; text-align: center; white-space: normal; line-height: 1.25; padding: 10px 12px; font-size: 14px; margin: 0; }

    /* Pestañas: una fila con desplazamiento horizontal */
    .cshow-m .tabs-show-clients-container { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; gap: 4px; margin: 0 0 16px; scrollbar-width: thin; overscroll-behavior-x: contain; scroll-padding-inline: 8px; }
    .cshow-m .tab-show-client { flex: 0 0 auto; white-space: nowrap; min-height: 44px; padding: 10px 12px; }

    /* Columnas apiladas: contacto/fiscal primero, notas después */
    .cshow-m .grid-2-information-client-show { grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .cshow-m .grid-2-information-client-show > * { min-width: 0; }
    .cshow-m .card-information-show { padding: 16px; min-width: 0; }
    .cshow-m .card-information-show .breadcrumb-users-manager { overflow-wrap: anywhere; word-break: normal; }
    .cshow-m #info .card-information-show .breadcrumb-users-manager:not(.main) { display: flex; align-items: flex-start; gap: 2px; margin-top: 0; }
    .cshow-m #info .card-information-show .breadcrumb-users-manager svg { flex: 0 0 auto; margin-top: 2px; }
    .cshow-m .note-box-information-client { overflow-wrap: anywhere; }

    /* Direcciones */
    .cshow-m .card-information-show-header { flex-wrap: wrap; gap: 8px; }
    .cshow-m .card-information-show-header h3 { margin: 0; overflow-wrap: anywhere; }
    .cshow-m .btn-dashed { min-height: 44px; font-size: 15px; margin-top: 16px; }

    /* Historial: KPIs, filtros y línea de tiempo */
    .cshow-m .ch-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .cshow-m .ch-kpi { padding: 12px; min-width: 0; }
    .cshow-m .ch-kpi strong { font-size: 16px; }
    .cshow-m .ch-filters { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; scrollbar-width: thin; }
    .cshow-m .ch-chip { flex: 0 0 auto; min-height: 44px; padding: 8px 14px; font-size: 14px; white-space: nowrap; }
    .cshow-m .ch-list { padding-left: 14px; }
    .cshow-m .ch-item::before { left: -21px; }
    .cshow-m .ch-card { gap: 4px 10px; padding: 10px 12px; min-width: 0; }
    .cshow-m .ch-main { flex: 1 1 100%; order: 3; min-width: 0; overflow-wrap: anywhere; }
    .cshow-m .ch-badge { order: 4; white-space: normal; }
    .cshow-m .ch-amount { order: 5; margin-left: auto; }
    .cshow-m .ch-group h3 { overflow-wrap: anywhere; }
    .cshow-m .ch-group h3 span { display: block; margin: 2px 0 0; }

    /* Tablas del historial / portal / análisis → tarjetas apiladas */
    .cshow-m .cshow-cards .table-scroll { max-height: none; overflow: visible; }
    .cshow-m .cshow-cards table,
    .cshow-m .cshow-cards tbody,
    .cshow-m .cshow-cards tr { display: block; width: 100%; min-width: 0 !important; max-width: 100%; box-sizing: border-box; }
    .cshow-m .cshow-cards .table-scroll,
    .cshow-m .cshow-cards [class*="table-container"] { max-width: 100%; min-width: 0; box-sizing: border-box; }
    .cshow-m .cshow-cards table { background: transparent; border: 0; border-radius: 0; overflow: visible; }
    .cshow-m .cshow-cards thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .cshow-m .cshow-cards tbody tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 12px; padding: 12px; margin-bottom: 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
    .cshow-m .cshow-cards td { display: block; min-width: 0; max-width: none; padding: 0; border: 0 !important; background: transparent; text-align: left !important; font-size: 13.5px; overflow-wrap: anywhere; white-space: normal !important; }
    .cshow-m .cshow-cards td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 2px; font-size: 10.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; }
    .cshow-m .cshow-cards td.cshow-full { grid-column: 1 / -1; }
    .cshow-m .cshow-cards td:empty { display: none; }

    /* Historial por área */
    .cshow-m .ch-table td.ch-td-folio { font-size: 14.5px; }
    .cshow-m .ch-table td.ch-td-amount { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-top: 8px; border-top: 1px dashed #e5e7eb !important; font-weight: 600; font-size: 14.5px; }
    .cshow-m .ch-table td.ch-td-amount::before { margin: 0; }

    /* Usuarios del portal */
    .cshow-m .pu-owner { background: #fffaf5 !important; border-color: #fed7aa !important; }
    .cshow-m .pu-owner td { background: transparent; }
    .cshow-m .pu-table td.pu-td-name { font-size: 14.5px; font-weight: 600; }
    .cshow-m .pu-table td.pu-td-name small { font-weight: 400; }
    .cshow-m .pu-badge { white-space: normal; }
    .cshow-m .pu-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; justify-content: stretch; padding-top: 4px; }
    .cshow-m .pu-actions form { display: block; }
    .cshow-m .pu-actions .pu-btn { width: 100%; min-height: 44px; font-size: 14px; padding: 10px 12px; white-space: normal; line-height: 1.25; }
    .cshow-m .pu-actions [data-pu-password] { grid-column: 1 / -1; order: -1; }

    /* Análisis químico */
    .cshow-m #water-analysis .ma-head { align-items: stretch; }
    .cshow-m #water-analysis .ma-head > div:first-child { min-width: 0; overflow-wrap: anywhere; }
    .cshow-m #water-analysis .ma-head-right { width: 100%; justify-content: space-between; }
    .cshow-m #water-analysis .ma-period { display: flex; width: 100%; }
    .cshow-m #water-analysis .ma-period a { flex: 1; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }
    .cshow-m #water-analysis .wa-link { display: inline-flex; align-items: center; min-height: 44px; font-size: 14px; }
    .cshow-m #water-analysis .ma-chips { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; }
    .cshow-m #water-analysis .ma-chip { flex: 0 0 auto; min-height: 44px; font-size: 14px; white-space: nowrap; }
    .cshow-m #water-analysis .ma-card { padding: 16px; }
    .cshow-m .wa-table td.wa-td-date { font-size: 14.5px; }
    .cshow-m .wa-table td.wa-notes { max-width: none; }
    .cshow-m .wa-legend .wa-val { white-space: normal; }
}

/* Diálogos del portal (contraseña / eliminar): hoja inferior a pantalla completa en teléfono */
@media (max-width: 640px) {
    .cshow-m .pu-dialog { width: 100%; max-width: 100%; max-height: 92dvh; margin: auto 0 0; border-radius: 16px 16px 0 0; overflow-y: auto; overscroll-behavior: contain; }
    .cshow-m .pu-dialog form { padding: 20px 16px calc(16px + env(safe-area-inset-bottom)); gap: 14px; }
    .cshow-m .pu-dialog h4 { font-size: 18px; }
    .cshow-m .pu-dialog p { font-size: 14px; overflow-wrap: anywhere; }
    .cshow-m .pu-dialog label { font-size: 14px; }
    .cshow-m .pu-dialog input[type=password] { font-size: 16px; min-height: 44px; width: 100%; box-sizing: border-box; }
    .cshow-m .pu-dialog-actions { flex-direction: column-reverse; gap: 8px; }
    .cshow-m .pu-dialog-actions .pu-btn { width: 100%; min-height: 44px; font-size: 15px; }
}
</style>
@endpush

@section('title')
    {{ $customer->first_name }} {{ $customer->last_name }} - Admin
@endsection

@section('content')
    <div class="container user-manager show-information cshow-m">
        <section class="clients-manager-section show">

            {{-- Back link --}}
            <div class="link-container-show">
                <a class="client-window-link" href="{{ route('admin.clients.index') }}">
                    <span>&lt;</span> &nbsp; Clientes
                </a>
            </div>

            {{-- Header --}}
            <div class="client-header-show">
                <div class="client-left-show">
                    <div class="avatar-show">
                        {{ strtoupper(substr($customer->first_name, 0, 1)) }}
                    </div>
                    <div class="information-client-show">
                        <h2>{{ $customer->first_name }} {{ $customer->last_name }}</h2>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-building2">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                                <path d="M10 6h4"></path><path d="M10 10h4"></path>
                                <path d="M10 14h4"></path><path d="M10 18h4"></path>
                            </svg>
                            {{ $customer->company ?? '—' }}
                        </p>
                        @php $addr = $customer->customer_addresses->first(); @endphp
                        @if ($addr)
                            <p class="breadcrumb-users-manager">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-map-pin">
                                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                {{ $addr->city }}, {{ $addr->state }}
                            </p>
                        @endif
                        @php
                            $statusLabel = match($customer->status) {
                                'active'    => 'Activo',
                                'inactive'  => 'Inactivo',
                                'suspended' => 'Suspendido',
                                default     => $customer->status,
                            };
                            $statusClass = $customer->status === 'active' ? 'status' : 'status-inactive';
                        @endphp
                        <span class="users-manager-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </div>

                <div class="client-actions-show up">
                    <div class="client-actions-show">
                        <button type="button"
                            class="button-primary size-adjustment edit-client btn-edit-client-show"
                            data-id="{{ $customer->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-pen">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path>
                            </svg>
                            Editar Cliente
                        </button>
                        <button type="button" class="button-primary size-adjustment new-order">
                            ＋ Nueva Orden
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="tabs-show-clients-container">
                <button class="tab-show-client active" data-tab="info">Información</button>
                <button class="tab-show-client" data-tab="history">Historial</button>
                @foreach ($history['sections'] as $key => $section)
                    <button class="tab-show-client" data-tab="hist-{{ $key }}">{{ $section['label'] }}
                        <span class="ch-count">{{ $section['count'] }}</span></button>
                @endforeach
                <button class="tab-show-client" data-tab="water-analysis">Análisis químico</button>
                <button class="tab-show-client" data-tab="portal-users">Usuarios del portal</button>
                <button class="tab-show-client" data-tab="addresses">Direcciones</button>
            </div>

            {{-- Tab: Info --}}
            <div class="tab-content-show-client active" id="info">
                <div class="grid-2-information-client-show">

                    <div class="card-information-show">
                        <h3>Información de Contacto</h3>

                        <p class="breadcrumb-users-manager main"><strong>Email</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-mail">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            &nbsp; {{ $customer->email ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>Teléfono</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-phone">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            &nbsp; {{ $customer->phone ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>RFC</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-file-text">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>
                            </svg>
                            &nbsp; {{ $customer->rfc ?? '—' }}
                        </p>

                        <p class="breadcrumb-users-manager main"><strong>Tipo de documento</strong></p>
                        <p class="breadcrumb-users-manager">&nbsp; {{ strtoupper($customer->document_type ?: '—') }}</p>

                        <p class="breadcrumb-users-manager main"><strong>¿Cómo nos conoció?</strong></p>
                        <p class="breadcrumb-users-manager">&nbsp; {{ ucfirst($customer->source ?? '—') }}</p>

                        <p class="breadcrumb-users-manager main"><strong>Fecha de registro</strong></p>
                        <p class="breadcrumb-users-manager">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-calendar">
                                <path d="M8 2v4"></path><path d="M16 2v4"></path>
                                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            &nbsp; {{ $customer->created_at?->translatedFormat('j \d\e F \d\e Y') ?? '—' }}
                        </p>
                    </div>

                    <div class="notes-information-show">
                        <div class="card-information-show notes">
                            <h3>Notas Internas</h3>
                            <div class="note-box-information-client">
                                <p class="breadcrumb-users-manager main">
                                    {{ $customer->notes ?? 'Sin notas.' }}
                                </p>
                            </div>
                        </div>
                        <div class="card-information-show activitie">
                            <h3>Última Actividad</h3>
                            <p class="breadcrumb-users-manager main activitie">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-calendar">
                                    <path d="M8 2v4"></path><path d="M16 2v4"></path>
                                    <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg>
                                {{ $customer->updated_at?->translatedFormat('j \d\e F, Y') ?? '—' }}
                            </p>
                            <p class="breadcrumb-users-manager">Sin actividad registrada.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs: Historial (línea de tiempo) y listas por área --}}
            @include('admin.client.partials._history', ['customer' => $customer, 'history' => $history])

            {{-- Tab: Análisis químico del cliente (registro diario del portal) --}}
            @include('admin.client.partials._water_analysis', ['customer' => $customer])

            {{-- Tab: Usuarios del portal del cliente (equipo con roles) --}}
            @include('admin.client.partials._portal_users', ['customer' => $customer])

            {{-- Tab: Addresses --}}
            <div class="tab-content-show-client" id="addresses">
                <div class="grid-2-information-client-show">
                    @forelse ($customer->customer_addresses as $address)
                        <div class="card-information-show">
                            <div class="card-information-show-header" style="display:flex;justify-content:space-between;align-items:center;">
                                <h3>{{ ucfirst($address->label ?? 'Dirección') }}</h3>
                                @if ($address->is_default)
                                    <span class="badge blue">Predeterminada</span>
                                @endif
                            </div>
                            <p class="breadcrumb-users-manager main"><strong>Destinatario</strong></p>
                            <p class="breadcrumb-users-manager">{{ $address->recipient_name ?? '—' }}</p>
                            <p class="breadcrumb-users-manager main"><strong>Dirección</strong></p>
                            <p class="breadcrumb-users-manager">
                                {{ $address->address_line1 }}
                                @if ($address->address_line2), {{ $address->address_line2 }}@endif
                                @if ($address->city), {{ $address->city }}@endif
                                @if ($address->state), {{ $address->state }}@endif
                                @if ($address->postal_code), {{ $address->postal_code }}@endif
                            </p>
                            <p class="breadcrumb-users-manager main"><strong>Teléfono</strong></p>
                            <p class="breadcrumb-users-manager">{{ $address->phone ?? '—' }}</p>
                        </div>
                    @empty
                        <p class="breadcrumb-users-manager main">Sin direcciones registradas.</p>
                    @endforelse
                </div>
                <button type="button" class="btn-dashed">＋ Agregar dirección</button>
            </div>

        </section>

        {{-- Edit modal --}}
        @include('admin.client.partials._edit_modal')

        <script>
            // Tab switching (la pestaña activa queda en el hash: #water-analysis, #portal-users, ...)
            const activateClientTab = (btn) => {
                document.querySelectorAll('.tab-show-client').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content-show-client').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
                // Las gráficas de una pestaña oculta se miden en 0: se vuelven a ajustar al mostrarla.
                requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
            };
            document.querySelectorAll('.tab-show-client').forEach(btn => {
                btn.addEventListener('click', () => {
                    activateClientTab(btn);
                    history.replaceState(null, '', '#' + btn.dataset.tab);
                });
            });
            if (location.hash) {
                const hashBtn = document.querySelector('.tab-show-client[data-tab="' + location.hash.slice(1) + '"]');
                if (hashBtn) activateClientTab(hashBtn);
            }
            // En teléfono la barra de pestañas se desplaza en horizontal: deja visible la pestaña activa.
            (function () {
                const bar = document.querySelector('.tabs-show-clients-container');
                const act = bar && bar.querySelector('.tab-show-client.active');
                if (bar && act && bar.scrollWidth > bar.clientWidth) {
                    bar.scrollLeft = Math.max(0, act.offsetLeft - bar.offsetLeft - 16);
                }
            })();

            // Edit client button in show page
            const editClientModal   = document.getElementById('clientEditModal');
            const editClientForm    = document.getElementById('clientEditForm');
            const editClientUrl     = '{{ url('/admin/clientes/editar-cliente') }}';
            let currentClientId     = null;

            const closeEditWithAnim = () => {
                const content = editClientModal.querySelector('.user-manager-modal-content');
                if (content) {
                    content.style.transition = 'transform 0.2s ease-in';
                    content.style.transform  = 'translateX(100%)';
                }
                editClientModal.style.transition = 'opacity 0.2s ease-in';
                editClientModal.style.opacity    = '0';
                setTimeout(() => {
                    editClientModal.style.display    = 'none';
                    editClientModal.style.opacity    = '';
                    editClientModal.style.transition = '';
                    if (content) { content.style.transform = ''; content.style.transition = ''; }
                }, 200);
            };

            document.querySelector('.btn-edit-client-show').addEventListener('click', () => {
                const id = '{{ $customer->id }}';
                currentClientId = id;

                fetch(editClientUrl + '/' + id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
                .then(res => res.json())
                .then(customer => {
                    const addr = customer.customer_addresses?.[0] ?? null;

                    editClientForm.querySelector('[name="full_name"]').value      = [customer.first_name, customer.last_name].filter(Boolean).join(' ');
                    editClientForm.querySelector('[name="company"]').value        = customer.company       ?? '';
                    editClientForm.querySelector('[name="email"]').value          = customer.email         ?? '';
                    editClientForm.querySelector('[name="phone"]').value          = customer.phone         ?? '';
                    editClientForm.querySelector('[name="whatsapp"]').value       = customer.whatsapp      ?? '';
                    editClientForm.querySelector('[name="rfc"]').value            = customer.rfc           ?? '';
                    editClientForm.querySelector('[name="document_type"]').value  = customer.document_type ?? '';
                    editClientForm.querySelector('[name="source"]').value         = customer.source        ?? '';
                    editClientForm.querySelector('[name="status"]').value         = customer.status        ?? 'active';
                    editClientForm.querySelector('[name="address_line1"]').value  = addr?.address_line1    ?? '';
                    editClientForm.querySelector('[name="address_line2"]').value  = addr?.address_line2    ?? '';
                    editClientForm.querySelector('[name="city"]').value           = addr?.city             ?? '';
                    editClientForm.querySelector('[name="state"]').value          = addr?.state            ?? '';
                    editClientForm.querySelector('[name="postal_code"]').value    = addr?.postal_code      ?? '';
                    editClientForm.querySelector('[name="country"]').value        = addr?.country          ?? '';
                    editClientForm.querySelector('[name="reference"]').value      = addr?.reference        ?? '';
                    editClientForm.querySelector('[name="notes"]').value          = customer.notes         ?? '';

                    editClientModal.style.display = 'flex';
                })
                .catch(err => console.error('Error loading client:', err));
            });

            document.getElementById('closeClientEditModal').addEventListener('click',  () => closeEditWithAnim());
            document.getElementById('cancelClientEditModal').addEventListener('click', () => closeEditWithAnim());
            editClientModal.addEventListener('click', (e) => {
                if (e.target === editClientModal) closeEditWithAnim();
            });

            // Same as fiscal
            const editSameAsFiscal = document.getElementById('editSameAsFiscal');
            const editShippingAddr = document.getElementById('editShippingAddress');
            const editFiscalAddr   = document.getElementById('editFiscalAddress');

            editSameAsFiscal.addEventListener('change', () => {
                editShippingAddr.disabled = editSameAsFiscal.checked;
                if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
            });
            editFiscalAddr.addEventListener('input', () => {
                if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
            });

            // Submit edit form
            editClientForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const errorsContainer = document.getElementById('client-edit-errors');
                errorsContainer.style.display = 'none';
                errorsContainer.innerHTML = '';

                const formData = new FormData(editClientForm);
                formData.append('_method', 'PUT');

                try {
                    const response = await fetch(editClientUrl + '/' + currentClientId, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json();
                    if (response.ok) {
                        closeEditWithAnim();
                        setTimeout(() => window.location.reload(), 200);
                    } else if (response.status === 422) {
                        const errorList = Object.values(data.errors).flat();
                        errorsContainer.innerHTML     = errorList.map(msg => `<p>${msg}</p>`).join('');
                        errorsContainer.style.display = 'block';
                    }
                } catch (err) {
                    console.error('Error updating client:', err);
                }
            });
        </script>
    </div>
@endsection
