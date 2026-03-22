@extends('frontend.layouts.master')
@section('styles')
    @vite(['resources/css/service.css'])
@endsection
@section('content')
    <!-- SECCIÓN: HERO -->
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="https://images.unsplash.com/photo-1744123146393-4b5438a5d98f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwY2hpbGxlciUyMGh2YWMlMjBzeXN0ZW18ZW58MXx8fHwxNzcyMTYwNTg2fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                alt="Mantenimiento Chillers">
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">
                <h1 class="hero-title">
                Mantenimiento Especializado de Chillers Industriales
                </h1>

                <p class="hero-description">
                Servicio técnico preventivo y correctivo para enfriadores de agua industriales, chillers de expansión directa y sistemas HVAC críticos con garantía de continuidad operativa.
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
            <h2 class="service-subtitle-industrial">¿En qué consiste el servicio de Mantenimiento de Chillers?</h2>

            <p class="service-text-industrial">
                El mantenimiento especializado de chillers industriales garantiza eficiencia energética, confiabilidad operativa y prolongación de vida útil mediante inspecciones programadas de compresores, evaporadores, condensadores, sistemas de control, circuitos refrigerantes y torres de enfriamiento. Implementamos protocolos técnicos que incluyen análisis de aceite lubricante, detección de fugas de refrigerante con equipos electrónicos, medición de presiones de succión y descarga, verificación de aislamiento eléctrico de motores y limpieza química de intercambiadores con productos biodegradables certificados.
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
                        <p>Hospitales y centros médicos</p>
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
                        <p>Centros comerciales y oficinas</p>
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
                        <p>Industria plástica y farmacéutica</p>
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
                        <p>Data centers y cuartos de servidores</p>
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
                        <p>Plantas de proceso con enfriamiento crítico</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: ¿Qué Incluye el Servicio? -->
    <section class="what-includes-section">
        <div class="container what-includes-section">
            <h2 class="section-main-title">¿Qué Incluye el Servicio de Mantenimiento de Chillers?</h2>

            <div class="includes-grid">
                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-wrench" width="28"
                            height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m10 20-1.25-2.5L6 18"></path>
                            <path d="M10 4 8.75 6.5 6 6"></path>
                            <path d="m14 20 1.25-2.5L18 18"></path>
                            <path d="m14 4 1.25 2.5L18 6"></path>
                            <path d="m17 21-3-6h-4"></path>
                            <path d="m17 3-3 6 1.5 3"></path>
                            <path d="M2 12h6.5L10 9"></path>
                            <path d="m20 10-1.5 2 1.5 2"></path>
                            <path d="M22 12h-6.5L14 15"></path>
                            <path d="m4 10 1.5 2L4 14"></path>
                            <path d="m7 21 3-6-1.5-3"></path>
                            <path d="m7 3 3 6h4"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Inspección de Compresores</h3>
                    <p class="include-card-text">Verificación de presiones, temperatura de aceite, aislamiento eléctrico, rodamientos y vibraciones con equipos especializados.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <path d="M12.8 19.6A2 2 0 1 0 14 16H2"></path>
                            <path d="M17.5 8a2.5 2.5 0 1 1 2 4H2"></path>
                            <path d="M9.8 4.4A2 2 0 1 1 11 8H2"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Limpieza de Intercambiadores</h3>
                    <p class="include-card-text">Limpieza química de evaporadores y condensadores, eliminación de incrustaciones calcáreas y lodos biológicos.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-shield">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Detección de Fugas</h3>
                    <p class="include-card-text">Búsqueda electrónica de fugas de refrigerante, reparación de sellos, carga de gas y vacío con bomba certificada.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-trending-up">
                            <path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Análisis de Refrigerante</h3>
                    <p class="include-card-text">Medición de sobrecalentamiento, subenfriamiento, análisis de aceite y verificación de niveles de carga óptimos.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Calibración de Controles</h3>
                    <p class="include-card-text">Ajuste de termostatos, presostatos, válvulas de expansión, controles de capacidad y sistemas de deshielo.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-users">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Reporte Técnico</h3>
                    <p class="include-card-text">Informe detallado con parámetros operativos, evidencias fotográficas, recomendaciones y programa preventivo.</p>
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
                        <h3 class="benefit-title">Ahorro Energético del 20-30%</h3>
                        <p class="benefit-text">Intercambiadores limpios y presiones óptimas reducen consumo eléctrico del compresor significativamente.</p>
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
                        <h3 class="benefit-title">Prevención de Fallas Críticas</h3>
                        <p class="benefit-text">Detección temprana de fugas, desgastes y anomalías evita paros no programados costosos en temporada alta.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <path d="m10 20-1.25-2.5L6 18"></path>
                            <path d="M10 4 8.75 6.5 6 6"></path>
                            <path d="m14 20 1.25-2.5L18 18"></path>
                            <path d="m14 4 1.25 2.5L18 6"></path>
                            <path d="m17 21-3-6h-4"></path>
                            <path d="m17 3-3 6 1.5 3"></path>
                            <path d="M2 12h6.5L10 9"></path>
                            <path d="m20 10-1.5 2 1.5 2"></path>
                            <path d="M22 12h-6.5L14 15"></path>
                            <path d="m4 10 1.5 2L4 14"></path>
                            <path d="m7 21 3-6-1.5-3"></path>
                            <path d="m7 3 3 6h4"></path>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Extensión de Vida Útil +50%</h3>
                        <p class="benefit-text">Mantenimiento preventivo duplica la vida operativa de compresores, evaporadores y componentes electrónicos.</p>
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
                        <h3 class="benefit-title">Cumplimiento Ambiental</h3>
                        <p class="benefit-text">Control de fugas de refrigerante cumple con Protocolo de Montreal y normativas ambientales vigentes.</p>
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
                        <h3 class="step-title">Diagnóstico Completo</h3>
                        <p class="step-desc">Inspección visual, medición de parámetros operativos, detección de fugas y evaluación del estado general del sistema.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">2</p>
                        <h3 class="step-title">Intervención Técnica</h3>
                        <p class="step-desc">Limpieza de intercambiadores, lubricación de componentes, ajuste de controles y reemplazo de refacciones críticas.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">3</p>
                        <h3 class="step-title">Pruebas de Operación</h3>
                        <p class="step-desc">Verificación de presiones, temperaturas, amperajes, análisis de eficiencia y pruebas de seguridad certificadas.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">4</p>
                        <h3 class="step-title">Entrega de Informe</h3>
                        <p class="step-desc">Documentación fotográfica, registro de hallazgos, recomendaciones técnicas y programa de mantenimiento preventivo.</p>
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
                        <p class="stat-small-desc">Especialistas certificados en refrigeración industrial y aire acondicionado comercial de gran capacidad.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">24/7</div>
                        <h3 class="stat-small-title">Servicio de Emergencia</h3>
                        <p class="stat-small-desc">Disponibilidad permanente para fallas críticas, atención inmediata y tiempos de respuesta garantizados.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">300+</div>
                        <h3 class="stat-small-title">Chillers Mantenidos</h3>
                        <p class="stat-small-desc">Sistemas de enfriamiento industrial operando con nuestros programas de mantenimiento preventivo especializado.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- NUEVA SECCIÓN: CTA FINAL -->
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="cta-final-title-industrial">Proteja su Sistema de Enfriamiento Industrial</h2>
            <p class="cta-final-description-industrial">
                Un chiller sin mantenimiento consume hasta 40% más energía y tiene 3 veces más probabilidad de fallas críticas. Implemente un programa preventivo profesional que garantice confort y continuidad operativa en hoteles, hospitales, oficinas y plantas industriales.
            </p>

            <div class="cta-final-actions">
                <a class="button-primary work-process">
                    Solicitar Mantenimiento
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