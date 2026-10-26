<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a class="sidebar-logo-link" href="{{ route('home') }}">
            <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Equiterm Industries"
                fetchpriority="low" loading="lazy" onerror="this.style.display='none'">
        </a>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Plegar menú">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </button>
    </div>
    <style>
        /* Temporal: deshabilitar enlaces visualmente sin tocar lógica
           Quitar estas líneas cuando se re-habiliten los módulos. */
        .sidebar-nav-item.disabled {
            pointer-events: none;
            opacity: 0.6;
            cursor: default;
        }

        .sidebar-nav-item.disabled .sidebar-nav-item-label {
            cursor: default;
            color: inherit;
        }
    </style>
    @php
        $authUser = auth('customer')->user();
        $activeSection = match (true) {
            request()->routeIs('customer.dashboard') => 'dashboard',
            request()->routeIs('customer.service-reports.*') => 'reportes-servicio',
            request()->routeIs('customer.technical-services.*') => 'servicios-tecnicos',
            request()->routeIs('customer.calendar*') => 'calendario',
            request()->routeIs('customer.history') => 'historial',
            request()->routeIs('customer.quotes.*') => 'cotizaciones',
            request()->routeIs('customer.deliveries.*') => 'entregas',
            request()->routeIs('customer.timeline') => 'expediente',
            request()->routeIs('customer.service-requests.*') => 'solicitar-servicio',
            request()->routeIs('customer.monitoring.*') => 'monitoreo',
            request()->routeIs('customer.equipment.*') => 'equipos',
            request()->routeIs('customer.orders.*') => 'pedidos',
            request()->routeIs('customer.addresses.*') => 'direcciones',
            request()->routeIs('customer.profile*') => 'perfil',
            default => '',
        };
    @endphp
    <nav class="sidebar-nav" id="sidebarNav">

        {{-- Dashboard: siempre visible --}}
        <a class="sidebar-nav-item {{ $activeSection === 'dashboard' ? 'active' : '' }}"
            href="{{ route('customer.dashboard') }}" data-section="dashboard" data-label="Dashboard">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="9" x="3" y="3" rx="1" />
                    <rect width="7" height="5" x="14" y="3" rx="1" />
                    <rect width="7" height="9" x="14" y="12" rx="1" />
                    <rect width="7" height="5" x="3" y="16" rx="1" />
                </svg>
                <span class="sidebar-nav-item-label">Inicio</span>
            </div>
        </a>

        {{-- Solicitar Servicio --}}
        <a class="sidebar-nav-item {{ $activeSection === 'solicitar-servicio' ? 'active' : '' }}"
            href="{{ Route::has('customer.service-requests.create') ? route('customer.service-requests.create') : '#' }}"
            data-section="solicitar-servicio" data-label="Solicitar Servicio">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                <span class="sidebar-nav-item-label">Solicitar Servicio</span>
            </div>
        </a>

        {{-- Reportes de Servicio --}}
        <a class="sidebar-nav-item {{ $activeSection === 'reportes-servicio' ? 'active' : '' }}"
            href="{{ Route::has('customer.service-reports.index') ? route('customer.service-reports.index') : '#' }}"
            data-section="reportes-servicio" data-label="Reporte de Servicios">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                <span class="sidebar-nav-item-label">Reporte de Servicios</span>
            </div>
        </a>

        {{-- Servicios Técnicos --}}
        <a class="sidebar-nav-item {{ $activeSection === 'servicios-tecnicos' ? 'active' : '' }}"
            href="{{ Route::has('customer.technical-services.index') ? route('customer.technical-services.index') : '#' }}"
            data-section="servicios-tecnicos" data-label="Servicios Técnicos">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                <span class="sidebar-nav-item-label">Servicios Técnicos</span>
            </div>
        </a>


        {{-- Calendario --}}
        <a class="sidebar-nav-item {{ $activeSection === 'calendario' ? 'active' : '' }}"
            href="{{ Route::has('customer.calendar') ? route('customer.calendar') : '#' }}"
            data-section="calendario" data-label="Calendario">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span class="sidebar-nav-item-label">Calendario</span>
            </div>
        </a>

        {{-- Historial de Servicios --}}
        <a class="sidebar-nav-item {{ $activeSection === 'historial' ? 'active' : '' }}"
            href="{{ Route::has('customer.history') ? route('customer.history') : '#' }}"
            data-section="historial" data-label="Historial de Servicios">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg>
                <span class="sidebar-nav-item-label">Historial de Servicios</span>
            </div>
        </a>

        {{-- Cotizaciones --}}
        <a class="sidebar-nav-item {{ $activeSection === 'cotizaciones' ? 'active' : '' }}"
            href="{{ Route::has('customer.quotes.index') ? route('customer.quotes.index') : '#' }}"
            data-section="cotizaciones" data-label="Cotizaciones">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                <span class="sidebar-nav-item-label">Cotizaciones</span>
            </div>
        </a>

        {{-- Entregas de Material --}}
        <a class="sidebar-nav-item {{ $activeSection === 'entregas' ? 'active' : '' }}"
            href="{{ Route::has('customer.deliveries.index') ? route('customer.deliveries.index') : '#' }}"
            data-section="entregas" data-label="Entregas de Material">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/></svg>
                <span class="sidebar-nav-item-label">Entregas de Material</span>
            </div>
        </a>

        {{-- Expediente (todo lo ligado al cliente) --}}
        <a class="sidebar-nav-item {{ $activeSection === 'expediente' ? 'active' : '' }}"
            href="{{ Route::has('customer.timeline') ? route('customer.timeline') : '#' }}"
            data-section="expediente" data-label="Mi Expediente">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                <span class="sidebar-nav-item-label">Mi Expediente</span>
            </div>
        </a>

        {{-- Monitoreo --}}
        <a class="sidebar-nav-item {{ $activeSection === 'monitoreo' ? 'active' : '' }}"
            href="{{ Route::has('customer.monitoring.index') ? route('customer.monitoring.index') : '#' }}"
            data-section="monitoreo" data-label="Monitoreo">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg>
                <span class="sidebar-nav-item-label">Monitoreo</span>
            </div>
        </a>

        {{-- Mis Equipos --}}
        <a class="sidebar-nav-item {{ $activeSection === 'equipos' ? 'active' : '' }}"
            href="{{ Route::has('customer.equipment.index') ? route('customer.equipment.index') : '#' }}"
            data-section="equipos" data-label="Mis Equipos">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h3"/></svg>
                <span class="sidebar-nav-item-label">Mis Equipos</span>
            </div>
        </a>

        {{-- Mis Pedidos --}}
        <a class="sidebar-nav-item {{ $activeSection === 'pedidos' ? 'active' : '' }}"
            href="{{ Route::has('customer.orders.index') ? route('customer.orders.index') : '#' }}"
            data-section="pedidos" data-label="Mis Pedidos">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="sidebar-nav-item-label">Mis Pedidos</span>
            </div>
        </a>

        {{-- Direcciones --}}
        <a class="sidebar-nav-item {{ $activeSection === 'direcciones' ? 'active' : '' }}"
            href="{{ Route::has('customer.addresses.index') ? route('customer.addresses.index') : '#' }}"
            data-section="direcciones" data-label="Direcciones">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-7.2 7-12a7 7 0 1 0-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
                <span class="sidebar-nav-item-label">Direcciones</span>
            </div>
        </a>

        {{-- Mi Perfil --}}
        <a class="sidebar-nav-item {{ $activeSection === 'perfil' ? 'active' : '' }}"
            href="{{ Route::has('customer.profile') ? route('customer.profile') : '#' }}"
            data-section="perfil" data-label="Mi Perfil">
            <div class="sidebar-nav-item-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"/></svg>
                <span class="sidebar-nav-item-label">Mi Perfil</span>
            </div>
        </a>

    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-profile">
            <div class="sidebar-avatar">{{ mb_strtoupper(mb_substr($authUser->first_name, 0, 2)) }}</div>
            <div class="sidebar-profile-info">
                <p class="sidebar-profile-name">{{ $authUser->first_name }} {{ $authUser->last_name }}</p>
                <span class="sidebar-profile-email">{{ $authUser->email }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" x2="9" y1="12" y2="12" />
                </svg>
                <span class="sidebar-logout-label">Salir del Panel</span>
            </button>
        </form>
    </div>
</aside>
