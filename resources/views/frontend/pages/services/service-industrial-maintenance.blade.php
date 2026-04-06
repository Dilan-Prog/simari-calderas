@extends('frontend.layouts.master')
@section('title')
@endsection
@section('content')
    <!-- SECCIÓN: HERO -->
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="https://images.unsplash.com/photo-1570086625762-7c1396540ac5?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwbWFpbnRlbmFuY2UlMjBib2lsZXIlMjB0ZWNobmljaWFufGVufDF8fHx8MTc3MjE2MDU4NXww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                alt="Mantenimiento Industrial">
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">
                <h1 class="hero-title">
                Mantenimiento Industrial Preventivo y Correctivo
                </h1>

                <p class="hero-description">
                Maximice la eficiencia operativa y reduzca paros no programados con nuestro servicio técnico especializado en calderas, generadores de vapor y sistemas térmicos industriales.
                </p>

                <div class="hero-actions">
                    <a class="button-primary">
                        Solicitar Cotización
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Detalle del Servicio y Aplicaciones -->
    <section class="service-description-section-industrial">
        <div class="container service-description-section">
            <h2 class="service-subtitle-industrial">¿En qué consiste el servicio de Mantenimiento Industrial?</h2>

            <p class="service-text-industrial">
                Nuestro servicio de mantenimiento industrial especializado garantiza la continuidad operativa de sus sistemas térmicos mediante intervenciones preventivas programadas y correctivas de emergencia. Aplicamos protocolos técnicos certificados que aseguran el cumplimiento normativo, la seguridad del personal y la optimización del consumo energético en calderas pirotubulares, acuotubulares, generadores de vapor e intercambiadores de calor.
            </p>

            <div class="applications-card">
                <h4 class="applications-title">Aplicaciones Industriales:</h4>
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
                        <p>Hospitales y centros de salud</p>
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
                        <p>Lavanderías industriales</p>
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
                        <p>Plantas manufactureras</p>
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
                        <p>Industria alimentaria</p>
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
                        <p>Procesamiento químico</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: ¿Qué Incluye el Servicio? -->
    <section class="what-includes-section">
        <div class="container what-includes-section">
            <h2 class="section-main-title">¿Qué Incluye el Servicio de Mantenimiento?</h2>

            <div class="includes-grid">
                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-wrench" width="28"
                            height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Inspección Técnica Completa</h3>
                    <p class="include-card-text">Evaluación exhaustiva de componentes críticos: tubos, quemadores, controles de nivel, manómetros, válvulas de seguridad y sistemas de combustión.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Lubricación y Ajustes</h3>
                    <p class="include-card-text">Lubricación de rodamientos, ajuste de tensión en correas, calibración de presostatos y verificación de alineación de bombas centrífugas.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-shield">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Análisis de Combustión</h3>
                    <p class="include-card-text">Medición de eficiencia de combustión, análisis de gases, ajuste de relación aire-combustible y optimización de rendimiento térmico.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-trending-up">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Limpieza de Componentes</h3>
                    <p class="include-card-text">Limpieza de superficies de transferencia de calor, eliminación de hollín, inspección de refractarios y limpieza de filtros de combustible.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Reemplazo de Refacciones</h3>
                    <p class="include-card-text">Sustitución de empaques, juntas, electrodos de ignición, fotoceldas y componentes desgastados con refacciones originales certificadas.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-users">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Informe Técnico Detallado</h3>
                    <p class="include-card-text">Documentación fotográfica, reporte de hallazgos, recomendaciones técnicas y registro de parámetros operativos para trazabilidad.</p>
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
                        <h3 class="benefit-title">Ahorro Energético del 15-25%</h3>
                        <p class="benefit-text">Reducción significativa en consumo de combustible mediante optimización de combustión y limpieza regular de superficies de intercambio térmico.</p>
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
                        <h3 class="benefit-title">Reducción de Fallas del 70%</h3>
                        <p class="benefit-text">Disminución drástica de paros no programados gracias a la detección temprana de anomalías y reemplazo preventivo de componentes críticos.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Extensión de Vida Útil +40%</h3>
                        <p class="benefit-text">Prolongación significativa de la vida operativa del equipo mediante mantenimiento predictivo y uso de refacciones originales certificadas.</p>
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
                        <h3 class="benefit-title">Cumplimiento Normativo 100%</h3>
                        <p class="benefit-text">Garantía de cumplimiento con NOM-020-STPS, normas ambientales y certificaciones de seguridad industrial requeridas por autoridades.</p>
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
                        <h3 class="step-title">Diagnóstico Inicial</h3>
                        <p class="step-desc">Inspección visual, pruebas de funcionamiento, medición de parámetros y evaluación del estado general del sistema térmico.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">2</p>
                        <h3 class="step-title">Intervención Técnica</h3>
                        <p class="step-desc">Ejecución de actividades de mantenimiento preventivo o correctivo según protocolo técnico y plan de trabajo autorizado.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">3</p>
                        <h3 class="step-title">Pruebas de Validación</h3>
                        <p class="step-desc">Verificación de presiones, temperaturas, análisis de combustión y pruebas de seguridad para garantizar operación óptima.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">4</p>
                        <h3 class="step-title">Entrega de Informe</h3>
                        <p class="step-desc">Documentación completa de trabajos realizados, evidencias fotográficas, recomendaciones y programa de seguimiento.</p>
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
                        <p class="stat-small-desc">Tres décadas de trayectoria en ingeniería térmica industrial y mantenimiento especializado de calderas.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">24/7</div>
                        <h3 class="stat-small-title">Soporte Técnico</h3>
                        <p class="stat-small-desc">Disponibilidad permanente para emergencias, asistencia remota y atención inmediata a fallas críticas.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">500+</div>
                        <h3 class="stat-small-title">Clientes Industriales</h3>
                        <p class="stat-small-desc">Confianza de empresas líderes en hotelería, salud, manufactura y servicios industriales de alto desempeño.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- NUEVA SECCIÓN: CTA FINAL -->
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="cta-final-title-industrial">Proteja su Inversión Industrial</h2>
            <p class="cta-final-description-industrial">
                No espere a que una falla crítica paralice sus operaciones. Nuestros técnicos especializados están listos para implementar un programa de mantenimiento que garantice la continuidad de sus procesos productivos en hoteles, hospitales, fábricas y lavanderías industriales.
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
