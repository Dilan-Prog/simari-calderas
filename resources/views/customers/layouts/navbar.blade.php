@php
    $navCustomer = auth('customer')->user();

    // displayName(): nombre del usuario del equipo si entró uno; si no, el del titular.
    $navName = \App\Support\PortalContext::displayName();
    if ($navName === '' && $navCustomer) {
        $navName = $navCustomer->company ?: ($navCustomer->email ?? 'Cliente');
    }

    $navInitials = '';
    foreach (array_slice(preg_split('/\s+/u', $navName, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 2) as $navPart) {
        $navInitials .= mb_strtoupper(mb_substr($navPart, 0, 1));
    }
    if ($navInitials === '') {
        $navInitials = 'C';
    }

    // Sección actual para el breadcrumb.
    $navSection = match (true) {
        request()->routeIs('customer.dashboard')            => 'Inicio',
        request()->routeIs('customer.calendar*')            => 'Calendario',
        request()->routeIs('customer.history*')             => 'Historial de Servicios',
        request()->routeIs('customer.service-requests.*')   => 'Solicitar Servicio',
        request()->routeIs('customer.profile*')             => 'Mi Perfil',
        request()->routeIs('customer.team.*')               => 'Usuarios y Roles',
        request()->routeIs('customer.service-reports.*')    => 'Reportes de Servicio',
        request()->routeIs('customer.technical-services.*') => 'Servicios Técnicos',
        default                                             => 'Inicio',
    };
@endphp

    <style>
        .portal-nav-link{display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 12px;border-radius:8px;color:#9ca3af;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;transition:background-color .15s,color .15s}
        .portal-nav-link:hover{background-color:#2E3A46;color:#fff}
        .portal-nav-link svg{flex-shrink:0}
        @media (max-width:768px){.portal-nav-link span{display:none}.portal-nav-link{padding:0 10px}}
    </style>

    {{-- Cabecera superior --}}
    <header class="admin-top-header">
        <div class="admin-top-header-left">
            <button class="admin-hamburger-btn" id="sidebarMobileToggle" title="Abrir menú">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" x2="20" y1="6" y2="6"/>
                    <line x1="4" x2="20" y1="12" y2="12"/>
                    <line x1="4" x2="20" y1="18" y2="18"/>
                </svg>
            </button>
            <div class="admin-breadcrumb">
                <strong>Portal de Servicios</strong>
                <span class="bc-sep">/</span>
                <span class="bc-current" id="breadcrumbSection">{{ $navSection }}</span>
            </div>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('home') }}" class="portal-nav-link" title="Ir a la tienda">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/>
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                    <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/>
                    <path d="M2 7h20"/>
                </svg>
                <span>Ir a la tienda</span>
            </a>
            @if (\App\Support\PortalContext::isOwner())
            <a href="{{ route('shop.account') }}" class="portal-nav-link" title="Mi cuenta">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Mi cuenta</span>
            </a>
            @endif
            <div class="admin-header-avatar" title="{{ $navName }}">{{ $navInitials }}</div>
        </div>
    </header>
