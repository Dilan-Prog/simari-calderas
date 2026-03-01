@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/products-calderas_simari.css') }}">
@endsection
@section('content')
    <div class="calderas-page">

    <section class="cald-hero">
        <div class="cald-hero-bg">
            <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1920" alt="Caldera Industrial SIMARI">
            <div class="cald-overlay-gradient"></div>
        </div>

        <div class="container cald-hero-content">
            <div class="cald-hero-max-w">
                <div class="cald-tag-glass">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                    <span>TECNOLOGÍA DE COMBUSTIÓN AVANZADA</span>
                </div>

                <h1 class="cald-title-main">
                    Calderas<br>
                    <span class="text-red">Industriales</span><br>
                    <span class="text-gray-light cald-title-sub">SIMARI</span>
                </h1>

                <p class="cald-hero-desc">Soluciones térmicas de alta eficiencia para generación de vapor y agua caliente. Diseño robusto, operación continua 24/7, cumplimiento normativo total.</p>

                <div class="cald-hero-stats">
                    <div class="cald-stat-glass">
                        <div class="cald-stat-val text-red">50-1000</div>
                        <div class="cald-stat-label">HP Capacidad</div>
                    </div>
                    <div class="cald-stat-glass">
                        <div class="cald-stat-val text-white">95%</div>
                        <div class="cald-stat-label">Eficiencia máx</div>
                    </div>
                    <div class="cald-stat-glass">
                        <div class="cald-stat-val text-white">600 PSI</div>
                        <div class="cald-stat-label">Presión máx</div>
                    </div>
                    <div class="cald-stat-glass">
                        <div class="cald-stat-val text-white">20+</div>
                        <div class="cald-stat-label">Años vida útil</div>
                    </div>
                </div>

                <div class="cald-btn-group">
                    <a href="/contacto" class="btn-primary">
                        Solicitar Cotización Técnica
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </a>
                    <a href="tel:+524494348018" class="btn-outline">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        449 434 8018
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="cald-section bg-light">
        <div class="container">
            <div class="cald-section-header">
                <h2 class="cald-title-section text-dark">Modelos Disponibles</h2>
                <p class="cald-subtitle text-gray">Desde 50 hasta más de 1000 HP. Pirotubulares y acuotubulares.</p>
            </div>

            <div class="cald-grid-4">
                <div class="cald-model-card">
                    <div class="cald-model-top">
                        <div class="cald-model-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                            <span>Pirotubular</span>
                        </div>
                        <h3 class="text-dark">PIRO-300</h3>
                        <div class="cald-model-hp text-red">50-300 HP</div>
                    </div>
                    <div class="cald-model-body">
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg> Presión: <strong>Hasta 150 PSI</strong></div>
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg> Eficiencia: <strong>85-88%</strong></div>
                        <div class="cald-apps-list">
                            <h4>APLICACIONES:</h4>
                            <ul>
                                <li>• Hoteles medianos</li>
                                <li>• Lavanderías industriales</li>
                            </ul>
                        </div>
                    </div>
                    <div class="cald-model-footer">
                        <a href="/contacto" class="cald-btn-light">Consultar</a>
                    </div>
                </div>

                <div class="cald-model-card">
                    <div class="cald-model-top">
                        <div class="cald-model-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                            <span>Pirotubular</span>
                        </div>
                        <h3 class="text-dark">PIRO-500</h3>
                        <div class="cald-model-hp text-red">300-500 HP</div>
                    </div>
                    <div class="cald-model-body">
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg> Presión: <strong>Hasta 200 PSI</strong></div>
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg> Eficiencia: <strong>88-91%</strong></div>
                        <div class="cald-apps-list">
                            <h4>APLICACIONES:</h4>
                            <ul>
                                <li>• Industria alimentaria</li>
                                <li>• Textil</li>
                            </ul>
                        </div>
                    </div>
                    <div class="cald-model-footer">
                        <a href="/contacto" class="cald-btn-light">Consultar</a>
                    </div>
                </div>

                <div class="cald-model-card">
                    <div class="cald-model-top">
                        <div class="cald-model-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                            <span>Pirotubular</span>
                        </div>
                        <h3 class="text-dark">PIRO-750</h3>
                        <div class="cald-model-hp text-red">500-750 HP</div>
                    </div>
                    <div class="cald-model-body">
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg> Presión: <strong>Hasta 250 PSI</strong></div>
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg> Eficiencia: <strong>90-93%</strong></div>
                        <div class="cald-apps-list">
                            <h4>APLICACIONES:</h4>
                            <ul>
                                <li>• Hospitales grandes</li>
                                <li>• Complejos hoteleros</li>
                            </ul>
                        </div>
                    </div>
                    <div class="cald-model-footer">
                        <a href="/contacto" class="cald-btn-light">Consultar</a>
                    </div>
                </div>

                <div class="cald-model-card">
                    <div class="cald-model-top">
                        <div class="cald-model-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                            <span>Acuotubular</span>
                        </div>
                        <h3 class="text-dark">ACUO-1000</h3>
                        <div class="cald-model-hp text-red">750-1000+ HP</div>
                    </div>
                    <div class="cald-model-body">
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg> Presión: <strong>Hasta 600 PSI</strong></div>
                        <div class="cald-spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg> Eficiencia: <strong>92-95%</strong></div>
                        <div class="cald-apps-list">
                            <h4>APLICACIONES:</h4>
                            <ul>
                                <li>• Petroquímica</li>
                                <li>• Generación eléctrica</li>
                            </ul>
                        </div>
                    </div>
                    <div class="cald-model-footer">
                        <a href="/contacto" class="cald-btn-light">Consultar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cald-section bg-white">
        <div class="container">
            <div class="cald-section-header">
                <h2 class="cald-title-section text-dark">Características Técnicas Superiores</h2>
                <p class="cald-subtitle text-gray">Diseño optimizado para operación continua, eficiencia energética y bajo mantenimiento</p>
            </div>

            <div class="cald-grid-3">
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></div>
                    <h3 class="text-dark">Quemadores Modulantes</h3>
                    <p class="text-gray">Combustión inteligente que se adapta a la demanda real, optimizando consumo de combustible.</p>
                </div>
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg></div>
                    <h3 class="text-dark">Control Automático</h3>
                    <p class="text-gray">PLC integrado con pantalla táctil. Monitoreo en tiempo real y ajustes precisos de parámetros.</p>
                </div>
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                    <h3 class="text-dark">Seguridad Multinivel</h3>
                    <p class="text-gray">Válvulas de alivio, control de flama, bajo nivel de agua. Cumple NOM-020-STPS.</p>
                </div>
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg></div>
                    <h3 class="text-dark">Alta Eficiencia</h3>
                    <p class="text-gray">Diseño optimizado de tubos de fuego y economizador para máximo aprovechamiento energético.</p>
                </div>
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                    <h3 class="text-dark">Mantenimiento Simplificado</h3>
                    <p class="text-gray">Puertas de inspección amplias, fácil acceso a componentes críticos. Reduce tiempos de paro.</p>
                </div>
                <div class="cald-feature-card">
                    <div class="cald-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                    <h3 class="text-dark">Disponibilidad Rápida</h3>
                    <p class="text-gray">Entrega en 4-6 semanas. Stock de refacciones críticas en almacén Aguascalientes.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cald-section cald-bg-gray-dark">
        <div class="container">
            <div class="cald-section-header">
                <h2 class="cald-title-section text-white">Aplicaciones por Sector Industrial</h2>
                <p class="cald-subtitle text-gray-light">Soluciones especializadas para cada industria con requerimientos específicos</p>
            </div>

            <div class="cald-grid-2">
                <div class="cald-app-card">
                    <div class="cald-app-content">
                        <div class="cald-icon-box-large"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M10 22v-6.57"></path><path d="M12 11h.01"></path><path d="M12 7h.01"></path><path d="M14 15.43V22"></path><path d="M15 16a5 5 0 0 0-6 0"></path><path d="M16 11h.01"></path><path d="M16 7h.01"></path><path d="M8 11h.01"></path><path d="M8 7h.01"></path><rect x="4" y="2" width="16" height="20" rx="2"></rect></svg></div>
                        <div class="cald-app-info">
                            <h3 class="text-dark">Hotelería</h3>
                            <div class="cald-app-needs">
                                <h4>NECESIDADES TÍPICAS:</h4>
                                <ul>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Agua caliente sanitaria 24/7</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Calefacción de albercas</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Lavandería industrial</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Calefacción HVAC</li>
                                </ul>
                            </div>
                            <div class="cald-app-badge text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                                Recomendado: Serie PIRO 300-500 HP
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cald-app-card">
                    <div class="cald-app-content">
                        <div class="cald-icon-box-large"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M12 6v4"></path><path d="M14 14h-4"></path><path d="M14 18h-4"></path><path d="M14 8h-4"></path><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"></path><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"></path></svg></div>
                        <div class="cald-app-info">
                            <h3 class="text-dark">Hospitales</h3>
                            <div class="cald-app-needs">
                                <h4>NECESIDADES TÍPICAS:</h4>
                                <ul>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Esterilización de instrumental</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Agua caliente sanitaria crítica</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Calefacción áreas pacientes</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Lavandería hospitalaria</li>
                                </ul>
                            </div>
                            <div class="cald-app-badge text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                                Recomendado: Serie PIRO 500-750 HP
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cald-app-card">
                    <div class="cald-app-content">
                        <div class="cald-icon-box-large"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path><path d="M17 18h1"></path><path d="M12 18h1"></path><path d="M7 18h1"></path></svg></div>
                        <div class="cald-app-info">
                            <h3 class="text-dark">Industria Alimentaria</h3>
                            <div class="cald-app-needs">
                                <h4>NECESIDADES TÍPICAS:</h4>
                                <ul>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Cocción y pasteurización</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Limpieza CIP/SIP</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Esterilización envases</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Procesos térmicos</li>
                                </ul>
                            </div>
                            <div class="cald-app-badge text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                                Recomendado: Serie PIRO 500+ HP
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cald-app-card">
                    <div class="cald-app-content">
                        <div class="cald-icon-box-large"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg></div>
                        <div class="cald-app-info">
                            <h3 class="text-dark">Textil & Química</h3>
                            <div class="cald-app-needs">
                                <h4>NECESIDADES TÍPICAS:</h4>
                                <ul>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Teñido y acabado textil</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Reactores químicos</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Secado industrial</li>
                                    <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg> Destilación</li>
                                </ul>
                            </div>
                            <div class="cald-app-badge text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
                                Recomendado: Serie ACUO 750-1000+ HP
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cald-section cald-bg-very-dark">
        <div class="container">
            <div class="cald-section-header">
                <h2 class="cald-title-section text-white">¿Por Qué Elegir SIMARI?</h2>
                <p class="cald-subtitle text-gray-light">Más de 30 años de experiencia en el sector térmico industrial</p>
            </div>

            <div class="cald-grid-4">
                <div class="cald-choose-card">
                    <div class="cald-icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                    <h3 class="text-white">Garantía Extendida</h3>
                    <p class="text-gray-light">2 años en equipos completos, 5 años en caldera</p>
                </div>
                <div class="cald-choose-card">
                    <div class="cald-icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg></div>
                    <h3 class="text-white">Stock Local</h3>
                    <p class="text-gray-light">Almacén en Aguascalientes con refacciones críticas</p>
                </div>
                <div class="cald-choose-card">
                    <div class="cald-icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                    <h3 class="text-white">Servicio 24/7</h3>
                    <p class="text-gray-light">Soporte técnico telefónico y visitas de emergencia</p>
                </div>
                <div class="cald-choose-card">
                    <div class="cald-icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg></div>
                    <h3 class="text-white">Financiamiento</h3>
                    <p class="text-gray-light">Planes de pago flexibles para proyectos grandes</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cald-cta-section">
        <div class="container cald-cta-content">
            <h2 class="cald-title-main text-white">Cotiza tu Proyecto Térmico</h2>
            <p class="cald-subtitle text-white">Nuestros ingenieros analizarán tus necesidades y diseñarán la solución óptima en capacidad, eficiencia y presupuesto.</p>

            <div class="cald-btn-group">
                <a href="/contacto" class="btn-primary cald-btn-white">
                    Solicitar Visita de Ingeniería
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                </a>
                <a href="tel:+524494348018" class="btn-outline cald-btn-outline-white">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    Llamar Ahora
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
