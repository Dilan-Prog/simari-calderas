@extends('frontend.layouts.master')
@section('title', 'Servicio de Proyectos Industriales Integrales Llave en Mano')
@section('description', 'Servicio de proyectos industriales integrales llave en mano')
@section('canonical', config('app.url') . '/servicios/proyectos-industriales')
@section('content')
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="{{ asset('images/services/proyectos-industriales/servicio-de-proyectos-industriales-llave-en-mano.jpg') }}"
                alt="Servicio de Proyectos Industriales Integrales Llave en Mano"
                title="Servicio de Proyectos Industriales Integrales Llave en Mano"
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
                Servicio de Proyectos Industriales Integrales Llave en Mano
                </h1>

                <p class="hero-description">
                    Diseño, suministro, instalación y puesta en marcha de salas de calderas completas, cuartos de máquinas y sistemas térmicos industriales con garantía total.
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

    {{-- SECOND --}}
    <section class="service-description-section-industrial">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">¿En qué consisten los Proyectos Industriales Integrales?</h2>

        <p class="service-text-industrial">
            Desarrollamos proyectos llave en mano que abarcan desde la ingeniería conceptual hasta la capacitación del
            personal operativo, incluyendo diseño de salas de calderas, cálculos de demanda térmica, dimensionamiento de
            equipos, ingeniería de detalle, suministro de maquinaria certificada, construcción de obra civil,
            instalación mecánica, montaje eléctrico, automatización con PLC/SCADA, pruebas de arranque, gestión de permisos y
            capacitación técnica especializada. Aplicamos estándares de gestión PMI, cronogramas Gantt y entregables
            documentados en cada etapa del proyecto.
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
                    <p>Hoteles y desarrollos turísticos</p>
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
                    <p>Industria alimentaria y bebidas</p>
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
                    <p>Hospitales y clínicas privadas</p>
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
                    <p>Procesamiento químico y petroquímico</p>
                </div>
            </div>
        </div>
    </div>
</section>

    {{-- THIRD --}}
    <section class="what-includes-section">
    <div class="container what-includes-section">
        <h2 class="section-main-title">¿Qué Incluyen Nuestros Proyectos Integrales?</h2>

        <div class="includes-grid">
            <div class="include-card">
                <div class="icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-building2 lucide-building-2">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M10 6h4"></path>
                        <path d="M10 10h4"></path>
                        <path d="M10 14h4"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Ingeniería de Detalle</h3>
                <p class="include-card-text">
                    Cálculos térmicos, dimensionamiento de equipos, ingeniería hidráulica, eléctrica,
                    estructural y especificaciones técnicas completas.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-cog">
                        <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
                        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                        <path d="M12 2v2"></path>
                        <path d="M12 22v-2"></path>
                        <path d="m17 20.66-1-1.73"></path>
                        <path d="M11 10.27 7 3.34"></path>
                        <path d="m20.66 17-1.73-1"></path>
                        <path d="m3.34 7 1.73 1"></path>
                        <path d="M14 12h8"></path>
                        <path d="M2 12h2"></path>
                        <path d="m20.66 7-1.73 1"></path>
                        <path d="m3.34 17 1.73-1"></path>
                        <path d="m17 3.34-1 1.73"></path>
                        <path d="m11 13.73-4 6.93"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Suministro de Equipos</h3>
                <p class="include-card-text">
                    Calderas, quemadores, bombas, válvulas, instrumentación, controles y accesorios
                    de marcas certificadas con garantía de fábrica.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-shield">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                </div>
                <h3 class="include-card-title">Construcción y Montaje</h3>
                <p class="include-card-text">
                    Obra civil, estructuras metálicas, instalación mecánica,
                    montaje eléctrico y acabados según normas de seguridad industrial.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-cog">
                        <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
                        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                        <path d="M12 2v2"></path>
                        <path d="M12 22v-2"></path>
                        <path d="m17 20.66-1-1.73"></path>
                        <path d="M11 10.27 7 3.34"></path>
                        <path d="m20.66 17-1.73-1"></path>
                        <path d="m3.34 7 1.73 1"></path>
                        <path d="M14 12h8"></path>
                        <path d="M2 12h2"></path>
                        <path d="m20.66 7-1.73 1"></path>
                        <path d="m3.34 17 1.73-1"></path>
                        <path d="m17 3.34-1 1.73"></path>
                        <path d="m11 13.73-4 6.93"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Automatización</h3>
                <p class="include-card-text">
                    Sistemas de control con PLC, pantallas HMI, supervisión SCADA
                    y monitoreo remoto con registro histórico de variables.
                </p>
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
                <h3 class="include-card-title">Puesta en Marcha</h3>
                <p class="include-card-text">
                    Arranque supervisado, pruebas de funcionamiento,
                    calibración de equipos y ajuste de parámetros operativos óptimos.
                </p>
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
                <h3 class="include-card-title">Capacitación</h3>
                <p class="include-card-text">
                    Entrenamiento técnico al personal, manuales de operación,
                    programas de mantenimiento y soporte post-garantía.
                </p>
            </div>
        </div>
    </div>
</section>
    {{-- Benefits Section --}}
    <section class="benefits-section">
        <div class="container benefits-section">
            <h2 class="section-main-title">Beneficios Operativos Comprobados</h2>

            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                        <polyline points="16 7 22 7 22 13"></polyline>
                    </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Único Responsable</h3>
                        <p class="benefit-text">Llave en mano elimina problemas de coordinación entre múltiples contratistas con un solo interlocutor responsable.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-shield text-white">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Garantía Total</h3>
                        <p class="benefit-text">Garantía integral de equipos, instalación y funcionamiento con soporte técnico post-entrega incluido.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 text-white">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M10 6h4"></path>
                        <path d="M10 10h4"></path>
                        <path d="M10 14h4"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Cumplimiento de Plazos</h3>
                        <p class="benefit-text">
                        Gestión profesional de proyectos con cronogramas realistas y entrega en tiempo acordado contractualmente.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big text-white">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Cumplimiento Normativo</h3>
                        <p class="benefit-text">Diseños conformes a códigos ASME, NOM-020-STPS y normas ambientales con gestión de permisos incluida.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    {{-- QUARTER --}}

    {{-- PART SIX --}}
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="section-main-title">Nuestro Proceso de Trabajo</h2>

            <div class="process-grid">
                <div class="process-step ">
                    <p class="step-number-circle">1</p>
                    <h3 class="step-title">Ingeniería</h3>
                    <p class="step-desc">Levantamiento de campo, cálculos térmicos, diseño de sistemas y elaboración de planos ejecutivos aprobados.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">2</p>
                    <h3 class="step-title">Suministro</h3>
                    <p class="step-desc">Adquisición de equipos certificados, fabricación de componentes
                        especiales y logística de entrega coordinada.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">3</p>
                    <h3 class="step-title">Construcción</h3>
                    <p class="step-desc">Obra civil, instalación mecánica, montaje eléctrico,
                        automatización y pruebas de funcionamiento integral.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">4</p>
                    <h3 class="step-title">Entrega</h3>
                    <p class="step-desc">Puesta en marcha, capacitación al personal, entrega de manuales
                        y inicio de garantía total del proyecto.</p>
                </div>
            </div>
        </div>
    </section>
    {{-- SEVEN PART --}}
    <section class="why-choose-simari-section">
        <div class="container why-choose-simari-section">
            <h2 class="section-main-title">Por qué elegir a SIMARI CALDERAS</h2>

            <div class="simari-stats-grid">
                <div class="simari-stat-item ">
                    <div class="stat-big-number">30+</div>
                    <h3 class="stat-small-title">Años de Experiencia</h3>
                    <p class="stat-small-desc">Trayectoria comprobada en desarrollo de proyectos industriales llave en mano de alta complejidad técnica.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">100+</div>
                    <h3 class="stat-small-title">Proyectos Ejecutados</h3>
                    <p class="stat-small-desc">Salas de calderas, cuartos de máquinas y sistemas térmicos instalados en México y Latinoamérica.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">100%</div>
                    <h3 class="stat-small-title">Satisfacción Cliente</h3>
                    <p class="stat-small-desc">Proyectos entregados en tiempo, dentro de presupuesto y con garantía total de funcionamiento operativo.</p>
                </div>
            </div>
        </div>
    </section>
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="cta-final-title-industrial">Desarrolle su Proyecto Industrial con Expertos</h2>
            <p class="cta-final-description-industrial">
                Un proyecto mal ejecutado genera sobrecostos permanentes y problemas operativos crónicos. Confíe en especialistas que entregan proyectos llave en mano para hoteles, hospitales, lavanderías y plantas industriales con garantía total.
            </p>

            <div class="cta-final-actions">
                <a class="button-primary work-process">
                    Solicitar Inspección
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
