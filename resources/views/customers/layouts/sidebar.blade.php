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
