@extends('frontend.layouts.master')
@section('title', 'Servicio de Reparación Especializada de Secadoras Industriales')
@section('description', 'Servicio técnico experto en mantenimiento correctivo y preventivo de secadoras de tambor industrial, túneles de secado y equipos de lavanderías comerciales de alta capacidad')
@section('canonical', config('app.url') . '/servicios/reparacion-secadoras')
@section('content')
    <!-- SECCIÓN: HERO -->
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="{{ asset('images/services/secadoras-industriales/servicio-de-reparacion-de-secadoras-industriales.jpg') }}"
                alt="Servicio de Reparación Especializada de Secadoras Industriales"
                title="Servicio de Reparación Especializada de Secadoras Industriales"
                width="1080"
                height="720"
                loading="eager"
                fetchpriority="high"
                decoding="async"
                >
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">
                <h1 class="hero-title">
                    Servicio de Reparación Especializada de Secadoras Industriales
                </h1>

                <p class="hero-description">
                    Servicio técnico experto en mantenimiento correctivo y preventivo de secadoras de tambor industrial,
                    túneles de secado y equipos de lavanderías comerciales de alta capacidad.
                </p>

                <div class="hero-actions">
                    <button class="button-primary">
                        Solicitar Cotización
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Detalle del Servicio y Aplicaciones -->
    <section class="service-description-section-industrial">
        <div class="container service-description-section">
            <h2 class="service-subtitle-industrial">¿En qué consiste el servicio de Reparación de Secadoras?</h2>

            <p class="service-text-industrial">
                El servicio técnico especializado en secadoras industriales abarca diagnóstico de fallas mecánicas,
                eléctricas y térmicas,
                reparación de sistemas de transmisión (motores, poleas, correas, rodamientos), sistemas de calentamiento
                (quemadores de gas,
                serpentines eléctricos, intercambiadores de vapor), sistemas de control (termostatos, temporizadores,
                controles de seguridad),
                sistemas de extracción de aire, filtros de pelusa y válvulas de gas. Trabajamos con marcas líderes como
                Electrolux, Girbau,
                Alliance, Huebsch, Speed Queen y Primus, manteniendo refacciones originales certificadas en stock para
                reducir tiempos de paro.
            </p>

            <div class="applications-card">
                <h3 class="applications-title">Aplicaciones Industriales:</h3>
                <div class="applications-grid">
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Lavanderías industriales comerciales</p>
                    </div>
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Hoteles y complejos hoteleros</p>
                    </div>
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Hospitales y clínicas</p>
                    </div>
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Autoservicio de Lavanderías</p>
                    </div>
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Gimnasios y clubes deportivos</p>
                    </div>
                    <div class="app-item">
                        <div class="check-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <p>Servicios de lavandería a domicilio</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: ¿Qué Incluye el Servicio? -->
    <section class="what-includes-section">
        <div class="container what-includes-section">
            <h2 class="section-main-title">¿Qué Incluye el Servicio de Reparación?</h2>

            <div class="includes-grid">
                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg"class="lucide lucide-wrench" width="28"
                            height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                        </svg>
                    </div>
                    <h3 class="include-card-title">Diagnóstico Técnico</h3>
                    <p class="include-card-text">Evaluación completa de sistemas mecánicos, eléctricos, térmicos y
                        controles con equipos de medición especializados para identificación precisa de fallas.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path d="m12 14 4-4" />
                            <path d="M3.34 19a10 10 0 1 1 17.32 0" />
                        </svg>
                    </div>
                    <h3 class="include-card-title">Reparación de Motores</h3>
                    <p class="include-card-text">Revisión de rodamientos, reemplazo de carbones, verificación de
                        aislamiento eléctrico, balanceo dinámico y ajuste de tensión de correas.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12.8 19.6A2 2 0 1 0 14 16H2" />
                            <path d="M17.5 8a2.5 2.5 0 1 1 2 4H2" />
                            <path d="M9.8 4.4A2 2 0 1 1 11 8H2" />
                        </svg>
                    </div>
                    <h3 class="include-card-title">Sistema de Calentamiento</h3>
                    <p class="include-card-text">Calibración de quemadores, limpieza de serpentines, verificación de
                        válvulas de gas, termostatos y sistemas de ignición electrónica.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-shield ">
                            <path
                                d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Sistemas de Seguridad</h3>
                    <p class="include-card-text">Verificación de termostatos de límite, presostatos, interruptores de
                        puerta, fusibles térmicos y válvulas de corte de emergencia.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-trending-up ">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Refacciones Originales</h3>
                    <p class="include-card-text">Suministro e instalación de partes originales certificadas: rodamientos,
                        correas, poleas, sensores, válvulas y componentes electrónicos.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                    <h3 class="include-card-title">Pruebas de Funcionamiento</h3>
                    <p class="include-card-text">Ciclos de prueba completos, verificación de temperaturas, tiempos de
                        secado, ruidos anormales y calibración de controles finales.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Beneficios Operativos Comprobados -->
    <section class="benefits-section">
        <div class="container benefits-section">
            <h2 class="section-main-title">Beneficios Operativos Comprobados</h2>

            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Reducción de Tiempos de Paro</h3>
                        <p class="benefit-text">Stock de refacciones críticas y técnicos especializados disponibles para
                            atención inmediata y reparaciones en sitio el mismo día.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Eficiencia Energética Restaurada</h3>
                        <p class="benefit-text">Calibración de controles y limpieza de sistemas de calentamiento recuperan
                            tiempos de secado óptimos reduciendo consumo de gas/electricidad.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2">
                            </path>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Extensión de Vida Útil +30%</h3>
                        <p class="benefit-text">Mantenimiento preventivo programado y reparaciones profesionales prolongan
                            significativamente la vida operativa de equipos costosos.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Garantía de Reparaciones</h3>
                        <p class="benefit-text">Todas las reparaciones incluyen garantía por escrito de mano de obra y
                            refacciones instaladas con respaldo técnico post-servicio.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Nuestro Proceso de Trabajo -->
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="section-main-title">Nuestro Proceso de Trabajo</h2>

            <div class="process-grid">
                <div class="process-step ">
                    <p class="step-number-circle">1</p>
                    <h3 class="step-title">Evaluación</h3>
                    <p class="step-desc">Inspección completa de sistemas, identificación de fallas, medición de parámetros
                        operativos y cotización detallada de reparación.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">2</p>
                    <h3 class="step-title">Reparación</h3>
                    <p class="step-desc">Desmontaje de componentes dañados, instalación de refacciones originales, ajustes
                        mecánicos y calibración de controles.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">3</p>
                    <h3 class="step-title">Pruebas</h3>
                    <p class="step-desc">Ciclos completos de secado, verificación de temperaturas, tiempos, ruidos
                        anormales y seguridad de operación certificada.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">4</p>
                    <h3 class="step-title">Entrega</h3>
                    <p class="step-desc">Informe técnico de trabajos, garantía por escrito, recomendaciones de operación y
                        programa de mantenimiento preventivo.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Por qué elegir a SIMARI CALDERAS -->
    <section class="why-choose-simari-section">
        <div class="container why-choose-simari-section">
            <h2 class="section-main-title">Por qué elegir a SIMARI CALDERAS</h2>

            <div class="simari-stats-grid">
                <div class="simari-stat-item ">
                    <div class="stat-big-number">30+</div>
                    <h3 class="stat-small-title">Años de Experiencia</h3>
                    <p class="stat-small-desc">Especialistas certificados en reparación de secadoras industriales de todas
                        las marcas líderes del mercado.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">24/7</div>
                    <h3 class="stat-small-title">Servicio de Emergencia</h3>
                    <p class="stat-small-desc">Disponibilidad inmediata para fallas críticas en lavanderías con tiempos de
                        respuesta garantizados.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">500+</div>
                    <h3 class="stat-small-title">Equipos Reparados</h3>
                    <p class="stat-small-desc">Secadoras industriales restauradas a condición operativa óptima con garantía
                        y soporte técnico permanente.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- NUEVA SECCIÓN: CTA FINAL -->
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="cta-final-title-industrial">Mantenga su Lavandería Operando sin Interrupciones</h2>
            <p class="cta-final-description-industrial">
                Cada hora de paro en una secadora industrial representa pérdidas operativas significativas. Confíe en
                técnicos especializados con refacciones en stock para reparaciones rápidas en lavanderías comerciales,
                hoteles, hospitales y servicios industriales.
            </p>

            <div class="cta-final-actions">
                <a class="button-primary work-process">
                    Solicitar Reparación
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-left: 8px;">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </a>

                <a class="button-secondary work-process">
                    <svg
                        xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
                    Llamar Ahora
                </a>
            </div>
        </div>
    </section>
@endsection
