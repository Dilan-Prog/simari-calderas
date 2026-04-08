@extends('admin.layouts.master')

@section('content')
<div class="admin-panel-layout">
    {{-- ══════════════════════════════
         SIDEBAR
    ══════════════════════════════ --}}
    <aside class="admin-sidebar">
        {{-- Logo --}}
        <div class="sidebar-logo">
            <img src="{{ asset('images/logo/Logo_blanco.png') }}"
                 alt="Industria Simari"
                 onerror="this.style.display='none'">
        </div>
        {{-- Navegación --}}
        <nav class="sidebar-nav" id="sidebarNav">
            {{-- Dashboard --}}
            <button class="sidebar-nav-item active" data-section="dashboard">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/>
                        <rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                    </svg>
                    <span class="sidebar-nav-label">Dashboard</span>
                </div>
            </button>

            {{-- Usuarios --}}
            <button class="sidebar-nav-item" data-section="usuarios">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="sidebar-nav-label">Usuarios</span>
                </div>
                <svg class="sidebar-nav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
            </button>

            {{-- Clientes --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Clientes">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                        <path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>
                    </svg>
                    <span class="sidebar-nav-label">Clientes</span>
                </div>
            </button>

            {{-- Proveedores --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Proveedores">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="15" r="3"/><circle cx="9" cy="7" r="4"/>
                        <path d="M10 15H6a4 4 0 0 0-4 4v2"/>
                        <path d="m21.7 16.4-.9-.3"/><path d="m15.2 13.9-.9-.3"/>
                        <path d="m16.6 18.7.3-.9"/><path d="m19.1 12.2.3-.9"/>
                        <path d="m19.6 18.7-.4-1"/><path d="m16.8 12.3-.4-1"/>
                        <path d="m14.3 16.6 1-.4"/><path d="m20.7 13.8 1-.4"/>
                    </svg>
                    <span class="sidebar-nav-label">Proveedores</span>
                </div>
            </button>

            {{-- Productos --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Productos">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                        <path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <span class="sidebar-nav-label">Productos</span>
                </div>
            </button>

            {{-- Categorías --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Categorías">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                        <path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                        <path d="M3 5a2 2 0 0 0 2 2h3"/><path d="M3 3v13a2 2 0 0 0 2 2h3"/>
                    </svg>
                    <span class="sidebar-nav-label">Categorías</span>
                </div>
            </button>

            {{-- Órdenes --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Órdenes">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span class="sidebar-nav-label">Órdenes</span>
                </div>
            </button>

            {{-- Servicios Técnicos --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Servicios Técnicos">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    <span class="sidebar-nav-label">Servicios Técnicos</span>
                </div>
            </button>

            {{-- Inventario --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Inventario">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="5" x="2" y="3" rx="1"/>
                        <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/>
                        <path d="M10 12h4"/>
                    </svg>
                    <span class="sidebar-nav-label">Inventario</span>
                </div>
            </button>

            {{-- Envíos --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Envíos">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                        <path d="M15 18H9"/>
                        <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                        <circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>
                    </svg>
                    <span class="sidebar-nav-label">Envíos</span>
                </div>
            </button>

            {{-- Paqueterías --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Paqueterías">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                        <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/>
                        <path d="m7.5 4.27 9 5.15"/>
                    </svg>
                    <span class="sidebar-nav-label">Paqueterías</span>
                </div>
            </button>

            {{-- Métodos de Pago --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Métodos de Pago">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                        <line x1="2" x2="22" y1="10" y2="10"/>
                    </svg>
                    <span class="sidebar-nav-label">Métodos de Pago</span>
                </div>
            </button>

            {{-- Email Marketing --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Email Marketing">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="16" x="2" y="4" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <span class="sidebar-nav-label">Email Marketing</span>
                </div>
            </button>

            {{-- Analíticas --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Analíticas">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v16a2 2 0 0 0 2 2h16"/>
                        <path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
                    </svg>
                    <span class="sidebar-nav-label">Analíticas</span>
                </div>
            </button>

            {{-- Auditoría Sistema --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Auditoría Sistema">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>
                    </svg>
                    <span class="sidebar-nav-label">Auditoría Sistema</span>
                </div>
            </button>

            {{-- Blog --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Blog">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                        <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                    </svg>
                    <span class="sidebar-nav-label">Blog</span>
                </div>
            </button>

            {{-- Menú --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Menú">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="20" y1="12" y2="12"/>
                        <line x1="4" x2="20" y1="6" y2="6"/>
                        <line x1="4" x2="20" y1="18" y2="18"/>
                    </svg>
                    <span class="sidebar-nav-label">Menú</span>
                </div>
            </button>

            {{-- SEO Global --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="SEO Global">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                        <path d="M2 12h20"/>
                    </svg>
                    <span class="sidebar-nav-label">SEO Global</span>
                </div>
            </button>

            {{-- WhatsApp --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="WhatsApp">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.38 2 2 0 0 1 3.62 1.22l3 .01a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span class="sidebar-nav-label">WhatsApp</span>
                </div>
            </button>

            {{-- Configuración --}}
            <button class="sidebar-nav-item" data-section="coming-soon" data-label="Configuración">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span class="sidebar-nav-label">Configuración</span>
                </div>
            </button>

        </nav>

        {{-- Perfil y logout --}}
        <div class="sidebar-footer">
            <div class="sidebar-profile">
                <div class="sidebar-avatar">AD</div>
                <div class="sidebar-profile-info">
                    <p class="sidebar-profile-name">Admin User</p>
                    <span class="sidebar-profile-email">admin@simari.com</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                    Salir del Panel
                </button>
            </form>
        </div>

    </aside>
    {{-- /SIDEBAR --}}


    {{-- ══════════════════════════════
         ÁREA PRINCIPAL
    ══════════════════════════════ --}}
    <div class="admin-main">

        {{-- Cabecera superior --}}
        <header class="admin-top-header">
            <div class="admin-breadcrumb">
                <strong>Panel de Control</strong>
                <span class="bc-sep">/</span>
                <span class="bc-current" id="breadcrumbSection">Usuarios</span>
            </div>
            <div class="admin-header-actions">
                <button class="admin-notif-btn" title="Notificaciones">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <span class="admin-notif-dot"></span>
                </button>
                <div class="admin-header-avatar">AD</div>
            </div>
        </header>

        {{-- Contenido dinámico --}}
        <main class="admin-content-area" id="mainContent">
            <div class="admin-loading-state" id="loadingState">
                <div class="admin-spinner"></div>
                <span>Cargando...</span>
            </div>
        </main>

    </div>
    {{-- /ÁREA PRINCIPAL --}}

</div>
<script>
(function () {
    'use strict';

    const mainContent      = document.getElementById('mainContent');
    const loadingState     = document.getElementById('loadingState');
    const breadcrumbSection = document.getElementById('breadcrumbSection');
    const navItems         = document.querySelectorAll('.sidebar-nav-item[data-section]');

    // ── Secciones con nombres legibles ──
    const sectionLabels = {
        dashboard : 'Dashboard',
        usuarios  : 'Usuarios',
    };

    // ── Activar ítem del nav ──
    function setActiveNav(button) {
        navItems.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
    }

    // ── Mostrar spinner ──
    function showLoading() {
        mainContent.innerHTML = '';
        loadingState.style.display = 'flex';
        mainContent.appendChild(loadingState);
    }

    // ── Renderizar HTML externo en el área de contenido ──
    function renderHTML(html) {
        mainContent.innerHTML = html;
        // Re-ejecutar scripts inline del contenido cargado
        mainContent.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    // ── Sección "próximamente" ──
    function renderComingSoon(label) {
        mainContent.innerHTML = `
            <div class="admin-coming-soon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10z"/>
                    <path d="M12 8v4"/><path d="M12 16h.01"/>
                </svg>
                <h3>${label}</h3>
                <p>Esta sección estará disponible próximamente.</p>
            </div>`;
    }

    // ── Dashboard de bienvenida ──
    function renderDashboard() {
        mainContent.innerHTML = `
            <div class="admin-welcome-section">
                <h1>Bienvenido al Panel</h1>
                <p>Selecciona una sección del menú lateral para comenzar.</p>
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-card-label">Usuarios</div>
                        <div class="admin-stat-card-value">—</div>
                        <div class="admin-stat-card-sub">Registrados en el sistema</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card-label">Clientes</div>
                        <div class="admin-stat-card-value">—</div>
                        <div class="admin-stat-card-sub">Cuentas activas</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card-label">Órdenes</div>
                        <div class="admin-stat-card-value">—</div>
                        <div class="admin-stat-card-sub">Este mes</div>
                    </div>
                </div>
            </div>`;
    }

    // ── Carga de sección vía fetch ──
    async function loadSection(section, button, label) {
        setActiveNav(button);
        breadcrumbSection.textContent = label || sectionLabels[section] || section;

        if (section === 'dashboard') {
            renderDashboard();
            return;
        }

        if (section === 'coming-soon') {
            renderComingSoon(label);
            return;
        }

        if (section === 'usuarios') {
            showLoading();
            try {
                const res = await fetch('/admin/usuarios-partial', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!res.ok) throw new Error('Error ' + res.status);

                const html = await res.text();
                renderHTML(html);
            } catch (err) {
                mainContent.innerHTML = `
                    <div class="admin-coming-soon">
                        <h3>Error al cargar</h3>
                        <p>No se pudo cargar la sección de usuarios.</p>
                    </div>`;
            }
            return;
        }

        renderComingSoon(label);
    }

    // ── Eventos de navegación ──
    navItems.forEach(btn => {
        btn.addEventListener('click', function () {
            const section = this.dataset.section;
            const label   = this.dataset.label
                            || this.querySelector('.sidebar-nav-label')?.textContent.trim()
                            || section;
            loadSection(section, this, label);
        });
    });

    // ── Carga inicial: Usuarios ──
    const usuariosBtn = document.querySelector('[data-section="dashboard"]');
    if (usuariosBtn) {
        loadSection('dashboard', usuariosBtn, 'Dashboard');
    }

})();
</script>
@endsection
