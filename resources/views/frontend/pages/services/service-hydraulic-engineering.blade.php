@extends('frontend.layouts.master')
@section('title')
@endsection
@section('content')
    <!-- SECCIÓN: HERO -->
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="https://images.unsplash.com/photo-1726967023920-8f04895f11e0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NjM2Nzh8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwZHJ5ZXIlMjBsYXVuZHJ5JTIwbWFjaGluZXxlbnwxfHx8fDE3NzIxNjA1ODd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                alt="Reparación Secadoras">
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">
                <h1 class="hero-title">
                    Ingeniería Hidráulica Industrial y de Proceso
                </h1>

                <p class="hero-description">
                    Servicio técnico experto en mantenimiento correctivo y preventivo de secadoras de tambor industrial,
                    túneles de secado y equipos de lavanderías comerciales de alta capacidad.
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
    <section class="second-content-service-calibration">
        <div class="container second-section-service-calibration">
            <div class="second-text-service-calibration">
                <h2>¿En qué consiste el servicio de Ingeniería Hidráulica?</h2>
                <p>Nuestro servicio de ingeniería hidráulica especializada abarca el diseño completo de redes de distribución de vapor saturado y sobrecalentado, sistemas de retorno de condensado, líneas de alimentación de agua a calderas y circuitos de fluidos térmicos.</p>

                <p>Realizamos cálculos de caída de presión, dimensionamiento de tuberías según normas ASME B31.1, selección de válvulas de control y especificación de aislamiento térmico optimizado para máxima eficiencia energética.</p>
            </div>

            <div class="service-calibration-table-aplication">
                <h2>Aplicaciones Industriales</h2>
                <ul class="applications__list">
                    <li class="applications__item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Plantas de generación de vapor</p>
                    </li>
                    <li class="applications__item"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Sistemas de calefacción industrial</p>
                    </li>
                    <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Redes de distribución en complejos hoteleros</p>
                    </li>
                    <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Circuitos de proceso en industria química</p>
                    </li>
                    <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Sistemas de recuperación de condensado</p>
                    </li>
                    <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <p class="applications__item-des">Instalaciones de fluidos térmicos</p>
                    </li>
                </ul>
            </div>
    </section>

    <!-- SECCIÓN: Todo lo que necesitas saber -->
    <!-- TERCER SECTION -->
    <section class="hydraulic-info-section" id="que-incluye-ingenieria-hidraulica" aria-labelledby="hydraulic-info-title">
        <div class="container hydraulic-info">
            <header class="hydraulic-info-header">
                <h2 class="hydraulic-info-title" id="hydraulic-info-title">
                    Todo lo que necesita saber sobre ingeniería hidráulica
                </h2>
            </header>

            <nav class="hydraulic-info-tabs" aria-label="Contenido del servicio">
                <button class="hydraulic-info-tab hydraulic-info-tab--active" type="button" data-tab="incluye">
                    ¿Qué incluye?
                </button>
                <button class="hydraulic-info-tab" type="button" data-tab="beneficios">
                    Beneficios
                </button>
                <button class="hydraulic-info-tab" type="button" data-tab="proceso">
                    Proceso
                </button>
            </nav>

            <!-- TAB 1 -->
            <div class="hydraulic-info-panel hydraulic-info-panel--active" id="hydraulic-panel-incluye">
                <div class="hydraulic-info-grid">
                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff] mb-4"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Cálculos Hidráulicos Especializados</h3>
                        <p class="hydraulic-info-card-text">
                            Dimensionamiento de tuberías, cálculo de pérdidas por fricción,
                            determinación de velocidades óptimas y balance hidráulico del sistema completo.
                        </p>
                    </article>

                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets text-[#0054ff] mb-4"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Diseño de Redes de Vapor</h3>
                        <p class="hydraulic-info-card-text">
                            Trazado óptimo de líneas principales y ramales, pendientes adecuadas,
                            ubicación de trampas de vapor y purgas automáticas.
                        </p>
                    </article>

                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-[#0054ff] mb-4"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Selección de Componentes</h3>
                        <p class="hydraulic-info-card-text">
                            Especificación técnica de válvulas, reductoras de presión, válvulas de seguridad,
                            filtros y separadores de humedad certificados.
                        </p>
                    </article>

                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-[#0054ff] mb-4"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Planos y Especificaciones</h3>
                        <p class="hydraulic-info-card-text">
                            Elaboración de isométricos, planos de instalación, memorias de cálculo
                            y especificaciones técnicas para licitaciones.
                        </p>
                    </article>

                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap text-[#0054ff] mb-4"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Aislamiento Térmico</h3>
                        <p class="hydraulic-info-card-text">
                            Especificación de materiales aislantes, cálculo de espesores según
                            temperatura de operación y diseño de soportes térmicos.
                        </p>
                    </article>

                    <article class="hydraulic-info-card">
                        <div class="hydraulic-info-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big text-[#0054ff] mb-4"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path></svg></div>
                        <h3 class="hydraulic-info-card-title">Supervisión de Obra</h3>
                        <p class="hydraulic-info-card-text">
                            Acompañamiento técnico durante la construcción, verificación de materiales,
                            pruebas hidrostáticas y puesta en marcha.
                        </p>
                    </article>
                </div>
            </div>

            <!-- TAB 2 -->
            <div class="hydraulic-info-panel" id="hydraulic-panel-beneficios">
                <div class="hydraulic-benefits-grid">
                    <article class="hydraulic-benefit-card">
                        <div class="hydraulic-benefit-inner">
                            <div class="hydraulic-benefit-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg></div>
                            <div class="hydraulic-benefit-content">
                                <h3 class="hydraulic-benefit-title">Optimización Energética del 20-30%</h3>
                                <p class="hydraulic-benefit-text">
                                    Reducción de pérdidas térmicas mediante diseño óptimo de trazados,
                                    aislamiento adecuado y recuperación eficiente de condensado.
                                </p>
                            </div>
                        </div>
                    </article>

                    <article class="hydraulic-benefit-card">
                        <div class="hydraulic-benefit-inner">
                            <div class="hydraulic-benefit-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                            <div class="hydraulic-benefit-content">
                                <h3 class="hydraulic-benefit-title">Seguridad Operacional Garantizada</h3>
                                <p class="hydraulic-benefit-text">
                                    Cumplimiento de códigos ASME, NOM-020-STPS y estándares internacionales
                                    para instalaciones de vapor de alta presión.
                                </p>
                            </div>
                        </div>
                    </article>

                    <article class="hydraulic-benefit-card">
                        <div class="hydraulic-benefit-inner">
                            <div class="hydraulic-benefit-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets text-white"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path></svg></div>
                            <div class="hydraulic-benefit-content">
                                <h3 class="hydraulic-benefit-title">Menor Tiempo de Construcción</h3>
                                <p class="hydraulic-benefit-text">
                                    Planos detallados y especificaciones precisas que facilitan la ejecución
                                    rápida y sin improvisaciones costosas durante la obra.
                                </p>
                            </div>
                        </div>
                    </article>

                    <article class="hydraulic-benefit-card">
                        <div class="hydraulic-benefit-inner">
                            <div class="hydraulic-benefit-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path></svg></div>
                            <div class="hydraulic-benefit-content">
                                <h3 class="hydraulic-benefit-title">Retorno de Inversión Acelerado</h3>
                                <p class="hydraulic-benefit-text">
                                    Recuperación de la inversión en 18-24 meses gracias a ahorros
                                    en combustible y reducción de mantenimientos correctivos.
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- TAB 3 -->
            <div class="hydraulic-info-panel" id="hydraulic-panel-proceso">
                <div class="hydraulic-process-list">
                    <article class="hydraulic-process-card">
                        <div class="hydraulic-process-number">1</div>
                        <div class="hydraulic-process-content">
                            <h3 class="hydraulic-process-title">Levantamiento de Campo</h3>
                            <p class="hydraulic-process-text">
                                Visita técnica para identificar puntos de consumo, cargas térmicas,
                                espacios disponibles y condiciones de operación.
                            </p>
                        </div>
                    </article>

                    <article class="hydraulic-process-card">
                        <div class="hydraulic-process-number">2</div>
                        <div class="hydraulic-process-content">
                            <h3 class="hydraulic-process-title">Ingeniería de Detalle</h3>
                            <p class="hydraulic-process-text">
                                Desarrollo de cálculos hidráulicos, dimensionamiento de tuberías,
                                selección de componentes y elaboración de planos ejecutivos.
                            </p>
                        </div>
                    </article>

                    <article class="hydraulic-process-card">
                        <div class="hydraulic-process-number">3</div>
                        <div class="hydraulic-process-content">
                            <h3 class="hydraulic-process-title">Implementación Controlada</h3>
                            <p class="hydraulic-process-text">
                                Supervisión de montaje, verificación de materiales, control de calidad
                                de soldaduras y pruebas hidrostáticas certificadas.
                            </p>
                        </div>
                    </article>

                    <article class="hydraulic-process-card">
                        <div class="hydraulic-process-number">4</div>
                        <div class="hydraulic-process-content">
                            <h3 class="hydraulic-process-title">Puesta en Marcha</h3>
                            <p class="hydraulic-process-text">
                                Arranque supervisado, ajuste de válvulas reguladoras, verificación
                                de parámetros y entrega de manuales de operación.
                            </p>
                        </div>
                    </article>
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
