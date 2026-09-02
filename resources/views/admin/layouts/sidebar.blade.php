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
        $authUser = auth()->user();
        // Solo tiene valor real dentro de admin.statistics.show ({section} en
        // la ruta); en cualquier otra página vale null y no se usa.
        $statisticsSection = request()->route('section') ?? 'resumen';
        $activeSection = match (true) {
            request()->routeIs('admin.dashboard') => 'dashboard',
            request()->routeIs('admin.roles.*') => 'roles',
            request()->routeIs('admin.devops.*') => 'devops',
            request()->routeIs('admin.users.*') => 'usuarios',
            request()->routeIs('admin.clients.*') => 'clientes',
            request()->routeIs('admin.suppliers.*') => 'proveedores',
            request()->routeIs('admin.products.*') => 'productos',
            request()->routeIs('admin.categories.*') => 'categorias',
            request()->routeIs('admin.brands.*') => 'marcas',
            request()->routeIs('admin.google-ads.*') => 'google-ads',
            request()->routeIs('admin.ad-tracking.*') => 'ad-tracking',
            request()->routeIs('admin.pool-calculator-leads.*') => 'pool-calculator-leads',
            request()->routeIs('admin.quotes.*') => 'cotizaciones',
            request()->routeIs('admin.service-reports.*') => 'reportes-servicio',
            request()->routeIs('admin.technical-services.*') => 'servicios-tecnicos',
            request()->routeIs('admin.purchase-orders.*') => 'ordenes-compra',
            request()->routeIs('admin.sales-orders.*') => 'pedidos',
            request()->routeIs('admin.material-delivery-reports.*') => 'entrega-material',
            request()->routeIs('admin.chemical-planning.*') => 'planeacion-quimicos',
            request()->routeIs('admin.erp-settings.*') => 'erp-configuracion',
            request()->routeIs('admin.home-sections.*') => 'inicio-secciones',
            request()->routeIs('admin.collections.*') => 'colecciones',
            request()->routeIs('admin.service-pages.*') => 'paginas-servicio',
            request()->routeIs('admin.gallery.*') => 'galeria',
            request()->routeIs('admin.inventory.*') => 'inventory',
            request()->routeIs('admin.abandoned-carts.*') => 'carritos-abandonados',
            request()->routeIs('admin.orders.*') => 'ordenes',
            request()->routeIs('admin.shipments.*') => 'envios',
            request()->routeIs('admin.deliveries.*') => 'deliveries',
            request()->routeIs('admin.menus.*') => 'menus',
            request()->routeIs('admin.settings.*') => 'configuracion-sitio',
            request()->routeIs('admin.integrations.*') => 'integraciones',
            request()->routeIs('admin.audit.*') => 'audit',
            request()->routeIs('admin.whatsapp-accounts.*') => 'whatsapp',
            request()->routeIs('admin.whatsapp-funnel.*') => 'embudo-de-venta',
            request()->routeIs('admin.payment-methods.*') => 'metodos-de-pago',
            request()->routeIs('admin.shipping-rules.*') => 'reglas-de-envio',
            request()->routeIs('admin.pipelines.*') => 'pipelines',
            request()->routeIs('admin.deals.*') => 'negocios',
            request()->routeIs('admin.tasks.*') => 'tareas',
            request()->routeIs('admin.workflows.*') => 'automatizaciones',
            request()->routeIs('admin.email-marketing.*', 'admin.email-templates.*', 'admin.email-lists.*', 'admin.email-campaigns.*', 'admin.email-sequences.*') => 'email-marketing',
            request()->routeIs('admin.dashboards.*') => 'dashboards',
            request()->routeIs('admin.reports.*') => 'reportes',
            request()->routeIs('admin.goals.*') => 'metas',
            request()->routeIs('admin.statistics.*') => 'estadisticas-' . $statisticsSection,
            default => '',
        };

        // Agrupación puramente visual del sidebar — no afecta rutas, permisos
        // ni estructura de carpetas, solo cómo se muestran los módulos.
        $groupSections = [
            'ecommerce' => ['productos', 'categorias', 'marcas', 'colecciones', 'paginas-servicio', 'galeria', 'inicio-secciones', 'inventory', 'menus', 'configuracion-sitio', 'integraciones', 'metodos-de-pago', 'reglas-de-envio', 'carritos-abandonados', 'ordenes', 'envios', 'deliveries'],
            'servicios' => ['reportes-servicio', 'servicios-tecnicos'],
            'erp' => ['cotizaciones', 'proveedores', 'ordenes-compra', 'pedidos', 'entrega-material', 'planeacion-quimicos', 'erp-configuracion', 'clientes', 'pipelines', 'embudo-de-venta', 'negocios', 'automatizaciones'],
            'administracion' => ['roles', 'usuarios', 'google-ads', 'ad-tracking', 'pool-calculator-leads', 'devops', 'audit', 'whatsapp', 'email-marketing', 'dashboards', 'reportes', 'metas'],
            'estadisticas' => ['estadisticas-resumen', 'estadisticas-ventas', 'estadisticas-embudo', 'estadisticas-compras', 'estadisticas-servicios', 'estadisticas-reportes', 'estadisticas-inventario', 'estadisticas-tienda'],
        ];
        $activeGroup = collect($groupSections)->search(fn ($sections) => in_array($activeSection, $sections));

        $ecommerceVisible = $authUser->hasPermission('products')
            || $authUser->hasPermission('categories')
            || $authUser->hasPermission('brands')
            || $authUser->hasPermission('collections')
            || $authUser->hasPermission('service-pages')
            || $authUser->hasPermission('gallery')
            || $authUser->hasPermission('home-sections')
            || $authUser->hasPermission('orders')
            || $authUser->hasPermission('inventory')
            || $authUser->hasPermission('shipments')
            || $authUser->hasPermission('carriers')
            || $authUser->hasPermission('payment-methods')
            || $authUser->hasPermission('shipping-rules')
            || $authUser->hasPermission('menu')
            || $authUser->hasPermission('settings')
            || $authUser->hasPermission('abandoned-carts');

        $serviciosVisible = $authUser->hasPermission('service-reports')
            || $authUser->hasPermission('technical-services');

        $erpVisible = $authUser->hasPermission('quotes')
            || $authUser->hasPermission('suppliers')
            || $authUser->hasPermission('purchase-orders')
            || $authUser->hasPermission('sales-orders')
            || $authUser->hasPermission('material-delivery-reports')
            || $authUser->hasPermission('chemical-planning')
            || $authUser->hasPermission('erp-settings')
            || $authUser->hasPermission('clients')
            || $authUser->hasPermission('pipeline')
            || $authUser->hasPermission('whatsapp')
            || $authUser->hasPermission('deals')
            || $authUser->hasPermission('automations');

        $administracionVisible = $authUser->isAdmin()
            || $authUser->hasPermission('google-ads')
            || $authUser->hasPermission('ad-tracking')
            || $authUser->hasPermission('pool-calculator-leads')
            || $authUser->hasPermission('email-marketing')
            || $authUser->hasPermission('crm-reports')
            || $authUser->hasPermission('audit')
            || $authUser->hasPermission('blog')
            || $authUser->hasPermission('seo')
            || $authUser->hasPermission('whatsapp');

        // Módulo aparte a propósito: Estadísticas cruza Ecommerce, Servicios,
        // ERP y Administración (Ventas, Compras, Servicios Técnicos, Reportes
        // de Servicio, Inventario, Tienda pública y Embudo de ventas) — no se
        // anida en ninguno de esos grupos, va en el suyo propio.
        $statisticsVisible = $authUser->hasPermission('statistics');
    @endphp
    <nav class="sidebar-nav" id="sidebarNav">

        {{-- ══════════════ GRUPO: ECOMMERCE ══════════════ --}}
        @if ($ecommerceVisible)
        <div class="sidebar-nav-group {{ $activeGroup === 'ecommerce' ? 'has-active' : '' }}" data-group="ecommerce">
            <button type="button" class="sidebar-nav-group-header" data-group-toggle="ecommerce">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" />
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                    </svg>
                    <span class="sidebar-nav-item-label">Ecommerce</span>
                </div>
                <svg class="sidebar-nav-group-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <div class="sidebar-nav-group-body" data-group-body="ecommerce">

                {{-- Productos --}}
                @if ($authUser->hasPermission('products'))
                    <a class="sidebar-nav-item {{ $activeSection === 'productos' ? 'active' : '' }}" data-section="products"
                        href="{{ route('admin.products.index') }}" data-label="Productos">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            <span class="sidebar-nav-item-label">Productos</span>
                        </div>
                    </a>
                @endif

                {{-- Categorías --}}
                @if($authUser->hasPermission('categories'))
                <a class="sidebar-nav-item {{ $activeSection === 'categorias' ? 'active': ''  }}" data-section="categories" data-label="Categorías"
                    href="{{ route('admin.categories.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                            <path
                                d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                            <path d="M3 5a2 2 0 0 0 2 2h3" />
                            <path d="M3 3v13a2 2 0 0 0 2 2h3" />
                        </svg>
                        <span class="sidebar-nav-item-label">Categorías</span>
                    </div>
                </a>
                @endif

                {{-- Marcas --}}
                @if($authUser->hasPermission('brands'))
                <a class="sidebar-nav-item {{ $activeSection === 'marcas' ? 'active' : '' }}" data-section="brands" data-label="Marcas"
                    href="{{ route('admin.brands.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-tag">
                            <path
                                d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z">
                            </path>
                            <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
                        </svg>
                        <span class="sidebar-nav-item-label">Marcas</span>
                    </div>
                </a>
                @endif

                {{-- Colecciones --}}
                @if($authUser->hasPermission('collections'))
                <a class="sidebar-nav-item {{ $activeSection === 'colecciones' ? 'active' : '' }}" data-section="colecciones" data-label="Colecciones"
                    href="{{ route('admin.collections.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m7.5 4.27 9 5.15" />
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                            <path d="m3.3 7 8.7 5 8.7-5" />
                            <path d="M12 22V12" />
                        </svg>
                        <span class="sidebar-nav-item-label">Colecciones</span>
                    </div>
                </a>
                @endif

                {{-- Páginas de Servicio --}}
                @if($authUser->hasPermission('service-pages'))
                <a class="sidebar-nav-item {{ $activeSection === 'paginas-servicio' ? 'active' : '' }}" data-section="paginas-servicio" data-label="Páginas de Servicio"
                    href="{{ route('admin.service-pages.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M10 12.5 8 15l2 2.5" />
                            <path d="m14 12.5 2 2.5-2 2.5" />
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                        </svg>
                        <span class="sidebar-nav-item-label">Páginas de Servicio</span>
                    </div>
                </a>
                @endif

                {{-- Galería de Imágenes --}}
                @if($authUser->hasPermission('gallery'))
                <a class="sidebar-nav-item {{ $activeSection === 'galeria' ? 'active' : '' }}" data-section="galeria" data-label="Galería"
                    href="{{ route('admin.gallery.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M18 22H4a2 2 0 0 1-2-2V6" />
                            <path d="m22 13-1.296-1.296a2.41 2.41 0 0 0-3.408 0L11 18" />
                            <circle cx="12" cy="8" r="2" />
                            <rect width="16" height="16" x="6" y="2" rx="2" />
                        </svg>
                        <span class="sidebar-nav-item-label">Galería</span>
                    </div>
                </a>
                @endif

                {{-- Inicio - Secciones (page builder del home público) --}}
                @if($authUser->hasPermission('home-sections'))
                <a class="sidebar-nav-item {{ $activeSection === 'inicio-secciones' ? 'active' : '' }}" data-section="inicio-secciones" data-label="Inicio - Secciones"
                    href="{{ route('admin.home-sections.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M3 9h18" />
                            <path d="M9 21V9" />
                        </svg>
                        <span class="sidebar-nav-item-label">Inicio - Secciones</span>
                    </div>
                </a>
                @endif

                {{-- Órdenes --}}
                @if ($authUser->hasPermission('orders'))
                    <a class="sidebar-nav-item {{ $activeSection === 'ordenes' ? 'active' : '' }}" data-section="ordenes"
                        href="{{ route('admin.orders.index') }}" data-label="Órdenes">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="8" cy="21" r="1" />
                                <circle cx="19" cy="21" r="1" />
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                            </svg>
                            <span class="sidebar-nav-item-label">Órdenes</span>
                        </div>
                    </a>
                @endif

                {{-- Inventario --}}
                @if ($authUser->hasPermission('inventory'))
                    <a class="sidebar-nav-item {{ $activeSection === 'inventory' ? 'active' : '' }}" data-section="inventory"
                        href="{{ route('admin.inventory.index') }}" data-label="Inventario">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="5" x="2" y="3" rx="1" />
                                <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                                <path d="M10 12h4" />
                            </svg>
                            <span class="sidebar-nav-item-label">Inventario</span>
                        </div>
                    </a>
                @endif

                {{-- Carritos Abandonados --}}
                @if ($authUser->hasPermission('abandoned-carts'))
                    <a class="sidebar-nav-item {{ $activeSection === 'carritos-abandonados' ? 'active' : '' }}" data-section="carritos-abandonados"
                        href="{{ route('admin.abandoned-carts.index') }}" data-label="Carritos Abandonados">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" />
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                                <path d="m9 9 6 6M15 9l-6 6" />
                            </svg>
                            <span class="sidebar-nav-item-label">Carritos Abandonados</span>
                        </div>
                    </a>
                @endif

                {{-- Envíos --}}
                @if ($authUser->hasPermission('shipments'))
                    <a href="{{ route('admin.shipments.index') }}" class="sidebar-nav-item {{ $activeSection === 'envios' ? 'active' : '' }}" data-section="envios" data-label="Envíos">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                                <path d="M15 18H9" />
                                <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                                <circle cx="17" cy="18" r="2" />
                                <circle cx="7" cy="18" r="2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Envíos</span>
                        </div>
                    </a>
                @endif

                {{-- Paqueterías --}}
                @if($authUser->hasPermission('carriers'))
                <a href="{{ route('admin.deliveries.index') }}" class="sidebar-nav-item {{ $activeSection === 'deliveries' ? 'active' : '' }}"   data-section="deliveries" data-label="Paqueterías">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                            <path d="M12 22V12" />
                            <polyline points="3.29 7 12 12 20.71 7" />
                            <path d="m7.5 4.27 9 5.15" />
                        </svg>
                        <span class="sidebar-nav-item-label">Paqueterías</span>
                    </div>
                </a>
                @endif

                {{-- Métodos de Pago --}}
                @if ($authUser->hasPermission('payment-methods'))
                    <a href="{{ route('admin.payment-methods.index') }}" class="sidebar-nav-item {{ $activeSection === 'metodos-de-pago' ? 'active' : '' }}" data-section="metodos-de-pago" data-label="Métodos de Pago">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="5" rx="2" />
                                <line x1="2" x2="22" y1="10" y2="10" />
                            </svg>
                            <span class="sidebar-nav-item-label">Métodos de Pago</span>
                        </div>
                    </a>
                @endif

                {{-- Reglas de Envío --}}
                @if ($authUser->hasPermission('shipping-rules'))
                    <a href="{{ route('admin.shipping-rules.index') }}" class="sidebar-nav-item {{ $activeSection === 'reglas-de-envio' ? 'active' : '' }}" data-section="reglas-de-envio" data-label="Reglas de Envío">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                                <path d="M15 18H9" />
                                <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                                <circle cx="17" cy="18" r="2" />
                                <circle cx="7" cy="18" r="2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Reglas de Envío</span>
                        </div>
                    </a>
                @endif

                {{-- Menús de navegación --}}
                @if ($authUser->hasPermission('menu'))
                    <a class="sidebar-nav-item {{ $activeSection === 'menus' ? 'active' : '' }}" data-section="menus" data-label="Menús"
                        href="{{ route('admin.menus.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="4" x2="20" y1="12" y2="12" />
                                <line x1="4" x2="20" y1="6" y2="6" />
                                <line x1="4" x2="20" y1="18" y2="18" />
                            </svg>
                            <span class="sidebar-nav-item-label">Menús</span>
                        </div>
                    </a>
                @endif

                {{-- Configuración --}}
                @if ($authUser->hasPermission('settings'))
                    <a class="sidebar-nav-item {{ $activeSection === 'configuracion-sitio' ? 'active' : '' }}" data-section="configuracion-sitio" data-label="Configuración"
                        href="{{ route('admin.settings.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span class="sidebar-nav-item-label">Configuración</span>
                        </div>
                    </a>
                @endif

                {{-- Integraciones --}}
                @if ($authUser->hasPermission('settings'))
                    <a class="sidebar-nav-item {{ $activeSection === 'integraciones' ? 'active' : '' }}" data-section="integraciones" data-label="Integraciones"
                        href="{{ route('admin.integrations.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M9 2v6" /><path d="M15 2v6" />
                                <path d="M12 17v5" />
                                <path d="M5 8h14a1 1 0 0 1 1 1v3a5 5 0 0 1-5 5h-6a5 5 0 0 1-5-5V9a1 1 0 0 1 1-1z" />
                            </svg>
                            <span class="sidebar-nav-item-label">Integraciones</span>
                        </div>
                    </a>
                @endif

            </div>
        </div>
        @endif

        {{-- ══════════════ GRUPO: SERVICIOS ══════════════ --}}
        @if ($serviciosVisible)
        <div class="sidebar-nav-group {{ $activeGroup === 'servicios' ? 'has-active' : '' }}" data-group="servicios">
            <button type="button" class="sidebar-nav-group-header" data-group-toggle="servicios">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <span class="sidebar-nav-item-label">Servicios</span>
                </div>
                <svg class="sidebar-nav-group-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <div class="sidebar-nav-group-body" data-group-body="servicios">

                {{-- Reportes de Servicio --}}
                @if($authUser->hasPermission('service-reports'))
                <a class="sidebar-nav-item {{ $activeSection === 'reportes-servicio' ? 'active' : '' }}"
                    href="{{ route('admin.service-reports.index') }}" data-section="reportes-servicio"
                    data-label="Reporte de Servicios">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                        <span class="sidebar-nav-item-label">Reporte de Servicios</span>
                    </div>
                </a>
                @endif

                {{-- Servicios Técnicos --}}
                @if($authUser->hasPermission('technical-services'))
                <a class="sidebar-nav-item {{ $activeSection === 'servicios-tecnicos' ? 'active' : '' }}" href="{{ route('admin.technical-services.index') }}" data-section="coming-soon" data-label="Servicios Técnicos">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        <span class="sidebar-nav-item-label">Servicios Técnicos</span>
                    </div>
                </a>
                @endif

            </div>
        </div>
        @endif

        {{-- ══════════════ GRUPO: ERP ══════════════ --}}
        @if ($erpVisible)
        <div class="sidebar-nav-group {{ $activeGroup === 'erp' ? 'has-active' : '' }}" data-group="erp">
            <button type="button" class="sidebar-nav-group-header" data-group-toggle="erp">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="6" rx="2" />
                        <path d="M6 12h4" />
                        <path d="M14 12h4" />
                        <path d="M2 10h20" />
                    </svg>
                    <span class="sidebar-nav-item-label">ERP</span>
                </div>
                <svg class="sidebar-nav-group-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <div class="sidebar-nav-group-body" data-group-body="erp">

                {{-- Cotizaciones --}}
                @if ($authUser->hasPermission('quotes'))
                    <a class="sidebar-nav-item {{ $activeSection === 'cotizaciones' ? 'active' : '' }}"
                        href="{{ route('admin.quotes.index') }}" data-section="cotizaciones" data-label="Cotizaciones">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                <path d="M14 2v6h6" />
                                <path d="M8 13h8" />
                                <path d="M8 17h5" />
                            </svg>
                            <span class="sidebar-nav-item-label">Cotizaciones</span>
                        </div>
                    </a>
                @endif

                {{-- Pipelines --}}
                @if ($authUser->hasPermission('pipeline'))
                    <a class="sidebar-nav-item {{ $activeSection === 'pipelines' ? 'active' : '' }}"
                        href="{{ route('admin.pipelines.index') }}" data-section="pipelines" data-label="Pipelines">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="6" x2="6" y1="3" y2="15" />
                                <circle cx="18" cy="6" r="3" />
                                <circle cx="6" cy="18" r="3" />
                                <path d="M18 9a9 9 0 0 1-9 9" />
                            </svg>
                            <span class="sidebar-nav-item-label">Pipelines</span>
                        </div>
                    </a>
                @endif

                {{-- Embudo de Venta (kanban de conversaciones de WhatsApp) --}}
                @if ($authUser->hasPermission('whatsapp'))
                    <a class="sidebar-nav-item {{ $activeSection === 'embudo-de-venta' ? 'active' : '' }}"
                        data-section="embudo-de-venta" data-label="Embudo de Venta"
                        href="{{ route('admin.whatsapp-funnel.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 3v18h18" />
                                <path d="m19 9-5 5-4-4-3 3" />
                            </svg>
                            <span class="sidebar-nav-item-label">Embudo de Venta</span>
                        </div>
                    </a>
                @endif

                {{-- Negocios (Deals) --}}
                @if ($authUser->hasPermission('deals'))
                    <a class="sidebar-nav-item {{ $activeSection === 'negocios' ? 'active' : '' }}"
                        href="{{ route('admin.deals.index') }}" data-section="negocios" data-label="Negocios">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                                <rect width="20" height="14" x="2" y="6" rx="2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Negocios</span>
                        </div>
                    </a>
                @endif

                {{-- Tareas --}}
                <a class="sidebar-nav-item {{ $activeSection === 'tareas' ? 'active' : '' }}"
                    href="{{ route('admin.tasks.index') }}" data-section="tareas" data-label="Tareas">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span class="sidebar-nav-item-label">Tareas</span>
                    </div>
                </a>

                {{-- Automatizaciones --}}
                @if ($authUser->hasPermission('automations'))
                    <a class="sidebar-nav-item {{ $activeSection === 'automatizaciones' ? 'active' : '' }}"
                        href="{{ route('admin.workflows.index') }}" data-section="automatizaciones" data-label="Automatizaciones">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Automatizaciones</span>
                        </div>
                    </a>
                @endif

                {{-- Clientes --}}
                @if ($authUser->hasPermission('clients'))
                    <a class="sidebar-nav-item {{ $activeSection === 'clientes' ? 'active' : '' }}" data-section="clientes"
                        href="{{ route('admin.clients.index') }}" data-label="Clientes">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                                <path d="M10 6h4" />
                                <path d="M10 10h4" />
                                <path d="M10 14h4" />
                                <path d="M10 18h4" />
                            </svg>
                            <span class="sidebar-nav-item-label">Clientes</span>
                        </div>
                    </a>
                @endif

                {{-- Proveedores --}}
                @if ($authUser->hasPermission('suppliers'))
                    <a class="sidebar-nav-item {{ $activeSection === 'proveedores' ? 'active' : '' }}"
                        data-section="proveedores" href="{{ route('admin.suppliers.index') }}" data-label="Proveedores">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="18" cy="15" r="3" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M10 15H6a4 4 0 0 0-4 4v2" />
                                <path d="m21.7 16.4-.9-.3" />
                                <path d="m15.2 13.9-.9-.3" />
                                <path d="m16.6 18.7.3-.9" />
                                <path d="m19.1 12.2.3-.9" />
                                <path d="m19.6 18.7-.4-1" />
                                <path d="m16.8 12.3-.4-1" />
                                <path d="m14.3 16.6 1-.4" />
                                <path d="m20.7 13.8 1-.4" />
                            </svg>
                            <span class="sidebar-nav-item-label">Proveedores</span>
                        </div>
                    </a>
                @endif

                {{-- Órdenes de compras --}}
                @if ($authUser->hasPermission('purchase-orders'))
                    <a class="sidebar-nav-item {{ $activeSection === 'ordenes-compra' ? 'active' : '' }}"
                        href="{{ route('admin.purchase-orders.index') }}" data-section="ordenes-compra"
                        data-label="Órdenes de Compra">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-clipboard-check">
                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect>
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <path d="m9 14 2 2 4-4"></path>
                            </svg>
                            <span class="sidebar-nav-item-label">Órdenes de Compra</span>
                        </div>
                    </a>
                @endif

                {{-- Pedidos (generados automáticamente desde Cotizaciones aceptadas) --}}
                @if ($authUser->hasPermission('sales-orders'))
                    <a class="sidebar-nav-item {{ $activeSection === 'pedidos' ? 'active' : '' }}"
                        href="{{ route('admin.sales-orders.index') }}" data-section="pedidos"
                        data-label="Pedidos">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" />
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                            </svg>
                            <span class="sidebar-nav-item-label">Pedidos</span>
                        </div>
                    </a>
                @endif

                {{-- Entrega de Material (Reportes de Entrega) --}}
                @if ($authUser->hasPermission('material-delivery-reports'))
                    <a class="sidebar-nav-item {{ $activeSection === 'entrega-material' ? 'active' : '' }}"
                        href="{{ route('admin.material-delivery-reports.index') }}" data-section="entrega-material"
                        data-label="Entrega de Material">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-package-check">
                                <path d="M16 16h6" />
                                <path d="m19 13-3 3 3 3" />
                                <path d="M21 10V7a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 7v10a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14" />
                                <path d="M12 22V12" />
                                <path d="M12 12 3.29 7" />
                                <path d="m7.5 4.27 9 5.15" />
                            </svg>
                            <span class="sidebar-nav-item-label">Entrega de Material</span>
                        </div>
                    </a>
                @endif

                {{-- Planeación de Químicos --}}
                @if ($authUser->hasPermission('chemical-planning'))
                    <a class="sidebar-nav-item {{ $activeSection === 'planeacion-quimicos' ? 'active' : '' }}"
                        href="{{ route('admin.chemical-planning.index') }}" data-section="planeacion-quimicos"
                        data-label="Planeación de Químicos">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M10 2v7.31" /><path d="M14 9.3V1.99" />
                                <path d="M8.5 2h7" /><path d="M14 9.3a6.5 6.5 0 1 1-4 0" />
                                <path d="M5.52 16h12.96" />
                            </svg>
                            <span class="sidebar-nav-item-label">Planeación de Químicos</span>
                        </div>
                    </a>
                @endif

                {{-- Configuración ERP --}}
                @if ($authUser->hasPermission('erp-settings'))
                    <a class="sidebar-nav-item {{ $activeSection === 'erp-configuracion' ? 'active' : '' }}"
                        href="{{ route('admin.erp-settings.index') }}" data-section="erp-configuracion"
                        data-label="Configuración ERP">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 7h-9" />
                                <path d="M14 17H5" />
                                <circle cx="17" cy="17" r="3" />
                                <circle cx="7" cy="7" r="3" />
                            </svg>
                            <span class="sidebar-nav-item-label">Configuración ERP</span>
                        </div>
                    </a>
                @endif

            </div>
        </div>
        @endif

        {{-- ══════════════ GRUPO: ADMINISTRACIÓN ══════════════ --}}
        @if ($administracionVisible)
        <div class="sidebar-nav-group {{ $activeGroup === 'administracion' ? 'has-active' : '' }}" data-group="administracion">
            <button type="button" class="sidebar-nav-group-header" data-group-toggle="administracion">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span class="sidebar-nav-item-label">Administración</span>
                </div>
                <svg class="sidebar-nav-group-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <div class="sidebar-nav-group-body" data-group-body="administracion">

                {{-- Roles: solo admin --}}
                @if($authUser->isAdmin())
                <a class="sidebar-nav-item {{ $activeSection === 'roles' ? 'active' : '' }}" data-section="roles"
                    data-label="Roles" href="{{ route('admin.roles.index') }}">
                    <div class="sidebar-nav-item-left">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>
                        <span class="sidebar-nav-item-label">Roles</span>
                    </div>
                    <svg class="sidebar-nav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </a>
                @endif

                {{-- Usuarios: solo admin --}}
                @if ($authUser->isAdmin())
                    <a class="sidebar-nav-item {{ $activeSection === 'usuarios' ? 'active' : '' }}" data-section="usuarios"
                        data-label="Usuarios" href="{{ route('admin.users.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <span class="sidebar-nav-item-label">Usuarios</span>
                        </div>
                        <svg class="sidebar-nav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                @endif

                {{-- DevOps: solo admin, consola SQL --}}
                @if ($authUser->isAdmin())
                    <a class="sidebar-nav-item {{ $activeSection === 'devops' ? 'active' : '' }}" data-section="devops"
                        data-label="DevOps" href="{{ route('admin.devops.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 8v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8" />
                                <path d="M2 8h20" />
                                <path d="M6 12h.01" />
                                <path d="M10 12h.01" />
                            </svg>
                            <span class="sidebar-nav-item-label">DevOps</span>
                        </div>
                        <svg class="sidebar-nav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                @endif

                {{-- Google Ads --}}
                @if ($authUser->hasPermission('google-ads'))
                    <a class="sidebar-nav-item {{ $activeSection === 'google-ads' ? 'active' : '' }}"
                        href="{{ route('admin.google-ads.index') }}" data-section="google-ads" data-label="Google Ads">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                                <path d="m19 9-5 5-4-4-3 3" />
                            </svg>
                            <span class="sidebar-nav-item-label">Google Ads</span>
                        </div>
                    </a>
                @endif

                {{-- Rastreo de Anuncios --}}
                @if ($authUser->hasPermission('ad-tracking'))
                    <a class="sidebar-nav-item {{ $activeSection === 'ad-tracking' ? 'active' : '' }}"
                        href="{{ route('admin.ad-tracking.index') }}" data-section="ad-tracking" data-label="Rastreo de Anuncios">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <circle cx="12" cy="12" r="6" />
                                <circle cx="12" cy="12" r="2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Rastreo de Anuncios</span>
                        </div>
                    </a>
                @endif

                {{-- Calculadora de Alberca --}}
                @if ($authUser->hasPermission('pool-calculator-leads'))
                    <a class="sidebar-nav-item {{ $activeSection === 'pool-calculator-leads' ? 'active' : '' }}"
                        href="{{ route('admin.pool-calculator-leads.index') }}" data-section="pool-calculator-leads" data-label="Calculadora de Alberca">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.5 0 2.5 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                                <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.5 0 2.5 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                                <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.5 0 2.5 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                            </svg>
                            <span class="sidebar-nav-item-label">Calculadora de Alberca</span>
                        </div>
                    </a>
                @endif

                {{-- Email Marketing --}}
                @if ($authUser->hasPermission('email-marketing'))
                    <a class="sidebar-nav-item {{ $activeSection === 'email-marketing' ? 'active' : '' }}"
                        href="{{ route('admin.email-marketing.index') }}" data-section="email-marketing" data-label="Email Marketing">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <span class="sidebar-nav-item-label">Email Marketing</span>
                        </div>
                    </a>
                @endif

                {{-- Dashboards (CRM) --}}
                @if ($authUser->hasPermission('crm-reports'))
                    <a class="sidebar-nav-item {{ $activeSection === 'dashboards' ? 'active' : '' }}"
                        href="{{ route('admin.dashboards.index') }}" data-section="dashboards" data-label="Dashboards">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 3v16a2 2 0 0 0 2 2h16" />
                                <path d="M18 17V9" />
                                <path d="M13 17V5" />
                                <path d="M8 17v-3" />
                            </svg>
                            <span class="sidebar-nav-item-label">Dashboards</span>
                        </div>
                    </a>
                @endif

                {{-- Reportes (CRM) --}}
                @if ($authUser->hasPermission('crm-reports'))
                    <a class="sidebar-nav-item {{ $activeSection === 'reportes' ? 'active' : '' }}"
                        href="{{ route('admin.reports.index') }}" data-section="reportes" data-label="Reportes">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                <path d="M14 2v6h6" />
                                <path d="M8 13h8" />
                                <path d="M8 17h5" />
                            </svg>
                            <span class="sidebar-nav-item-label">Reportes</span>
                        </div>
                    </a>
                @endif

                {{-- Metas (CRM) --}}
                @if ($authUser->hasPermission('crm-reports'))
                    <a class="sidebar-nav-item {{ $activeSection === 'metas' ? 'active' : '' }}"
                        href="{{ route('admin.goals.index') }}" data-section="metas" data-label="Metas">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <circle cx="12" cy="12" r="6" />
                                <circle cx="12" cy="12" r="2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Metas</span>
                        </div>
                    </a>
                @endif

                {{-- Auditoría Sistema --}}
                @if ($authUser->hasPermission('audit'))
                    <a class="sidebar-nav-item {{ $activeSection === 'audit' ? 'active' : '' }}" data-section="audit"
                        data-label="Auditoría Sistema" href="{{ route('admin.audit.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
                            </svg>
                            <span class="sidebar-nav-item-label">Auditoría Sistema</span>
                        </div>
                    </a>
                @endif

                {{-- Blog --}}
                @if ($authUser->hasPermission('blog'))
                    <a class="sidebar-nav-item disabled" data-section="coming-soon" data-label="Blog">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M10 9H8" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                            </svg>
                            <span class="sidebar-nav-item-label">Blog</span>
                        </div>
                    </a>
                @endif

                {{-- SEO Global --}}
                @if ($authUser->hasPermission('seo'))
                    <a class="sidebar-nav-item disabled" data-section="coming-soon" data-label="SEO Global">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                                <path d="M2 12h20" />
                            </svg>
                            <span class="sidebar-nav-item-label">SEO Global</span>
                        </div>
                    </a>
                @endif

                {{-- WhatsApp --}}
                @if ($authUser->hasPermission('whatsapp'))
                    <a class="sidebar-nav-item {{ $activeSection === 'whatsapp' ? 'active' : '' }}"
                        data-section="whatsapp" data-label="WhatsApp"
                        href="{{ route('admin.whatsapp-accounts.index') }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.38 2 2 0 0 1 3.62 1.22l3 .01a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            <span class="sidebar-nav-item-label">WhatsApp</span>
                        </div>
                    </a>
                @endif

            </div>
        </div>
        @endif

        {{-- ══════════════ GRUPO: ESTADÍSTICAS ══════════════ --}}
        @if ($statisticsVisible)
        <div class="sidebar-nav-group {{ $activeGroup === 'estadisticas' ? 'has-active' : '' }}" data-group="estadisticas">
            <button type="button" class="sidebar-nav-group-header" data-group-toggle="estadisticas">
                <div class="sidebar-nav-item-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" x2="12" y1="20" y2="10" /><line x1="18" x2="18" y1="20" y2="4" /><line x1="6" x2="6" y1="20" y2="16" />
                    </svg>
                    <span class="sidebar-nav-item-label">Estadísticas</span>
                </div>
                <svg class="sidebar-nav-group-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
            <div class="sidebar-nav-group-body" data-group-body="estadisticas">
                @php
                    $statisticsItems = [
                        'resumen'    => ['label' => 'Resumen', 'route' => route('admin.statistics.index'), 'icon' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>'],
                        'ventas'     => ['label' => 'Ventas', 'route' => route('admin.statistics.show', 'ventas'), 'icon' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
                        'embudo'     => ['label' => 'Embudo de ventas', 'route' => route('admin.statistics.show', 'embudo'), 'icon' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>'],
                        'compras'    => ['label' => 'Compras y proveedores', 'route' => route('admin.statistics.show', 'compras'), 'icon' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>'],
                        'servicios'  => ['label' => 'Servicios técnicos', 'route' => route('admin.statistics.show', 'servicios'), 'icon' => '<path d="m14.7 6.3 1.6-1.6a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-1.6 1.6a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/>'],
                        'reportes'   => ['label' => 'Reportes de servicio', 'route' => route('admin.statistics.show', 'reportes'), 'icon' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>'],
                        'inventario' => ['label' => 'Inventario', 'route' => route('admin.statistics.show', 'inventario'), 'icon' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>'],
                        'tienda'     => ['label' => 'Tienda pública', 'route' => route('admin.statistics.show', 'tienda'), 'icon' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/>'],
                        'marketing'  => ['label' => 'Marketing por correo', 'route' => route('admin.statistics.show', 'marketing'), 'icon' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'],
                    ];
                @endphp
                @foreach ($statisticsItems as $key => $item)
                    <a class="sidebar-nav-item {{ $activeSection === 'estadisticas-' . $key ? 'active' : '' }}"
                        data-section="estadisticas-{{ $key }}" data-label="{{ $item['label'] }}" href="{{ $item['route'] }}">
                        <div class="sidebar-nav-item-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {!! $item['icon'] !!}
                            </svg>
                            <span class="sidebar-nav-item-label">{{ $item['label'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-profile">
            <div class="sidebar-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->full_name, 0, 2)) }}</div>
            <div class="sidebar-profile-info">
                <p class="sidebar-profile-name">{{ auth()->user()->full_name }}</p>
                <span class="sidebar-profile-email">{{ auth()->user()->email }}</span>
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
