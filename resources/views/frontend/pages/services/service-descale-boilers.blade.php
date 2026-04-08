@extends('frontend.layouts.master')
@section('title', 'Servicio de Desincrustación Química de Calderas Industriales')
@section('description', 'Servicio de desincrustación química profesional para calderas industriales con incrustaciones severas.')
@section('canonical', config('app.url') . '/servicios/desincrustacion-calderas')
@section('content')
    <!-- SECCIÓN: HERO -->
    <section class="hero-section-hair-repair">
        <div class="hero-background">
            <img src="{{ asset('images/services/desincrustacion-calderas/servicio-de-desincrustacion-de-calderas.jpg') }}"
                alt="Servicio de Desincrustación Química de Calderas Industriales"
                title="Servicio de Desincrustación Química de Calderas Industriales"
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
                Servicio de Desincrustación Química de Calderas Industriales
                </h1>

                <p class="hero-description">
                Eliminación profesional de incrustaciones calcáreas, óxidos metálicos y depósitos que reducen eficiencia térmica y comprometen la seguridad operacional de calderas.
                </p>

                <div class="hero-actions">
                    <a
                     href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                     target="_blank"
                     aria-label="Abrir chat de WhatsApp"
                     class="button-primary">
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
            <h2 class="service-subtitle-industrial">¿En qué consiste el servicio de Desincrustación?</h2>

            <p class="service-text-industrial">
                La desincrustación química profesional mediante circulación de soluciones ácidas inhibidas remueve incrustaciones de carbonato de calcio, sulfato de calcio, óxidos de hierro, sílice y depósitos minerales que actúan como aislantes térmicos, reduciendo la transferencia de calor hasta 30% y generando sobrecalentamiento localizado en tubos que causa fallas catastróficas. Aplicamos protocolos técnicos con productos biodegradables, inhibidores de corrosión certificados, control de pH, temperatura y tiempos de contacto optimizados para remoción completa sin dañar el metal base.
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
                        <p>Calderas pirotubulares con incrustación severa</p>
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
                        <p>Calderas acuotubulares de alta presión</p>
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
                        <p>Intercambiadores de calor tubulares</p>
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
                        <p>Generadores de vapor industriales</p>
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
                        <p>Torres de enfriamiento calcificadas</p>
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
                        <p>Sistemas de agua caliente obstruidos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: ¿Qué Incluye el Servicio? -->
    <section class="what-includes-section">
        <div class="container what-includes-section">
            <h2 class="section-main-title">¿Qué Incluye el Servicio de Desincrustación?</h2>

            <div class="includes-grid">
                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-wrench" width="28"
                            height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                            <path d="M12 9v4"></path>
                            <path d="M12 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Inspección Previa</h3>
                    <p class="include-card-text">Evaluación endoscópica de nivel de incrustación, pruebas de adherencia, análisis químico de depósitos y diseño del protocolo de limpieza.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                            <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Limpieza Química</h3>
                    <p class="include-card-text">Circulación de soluciones desincrustantes con inhibidores de corrosión, control de pH, temperatura y monitoreo de disolución.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-shield">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Neutralización</h3>
                    <p class="include-card-text">Enjuague con agua desmineralizada, neutralización de ácidos residuales y pasivación de superficies metálicas limpias.</p>
                </div>

                <div class="include-card">
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-trending-up">
                            <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                            <path d="M20 3v4"></path>
                            <path d="M22 5h-4"></path>
                            <path d="M4 17v2"></path>
                            <path d="M5 18H3"></path>
                        </svg>
                    </div>
                    <h3 class="include-card-title">Pruebas de Limpieza</h3>
                    <p class="include-card-text">Inspección endoscópica final, medición de rugosidad, análisis de agua de enjuague y certificación de limpieza completa.</p>
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
                    <h3 class="include-card-title">Tratamiento Preventivo</h3>
                    <p class="include-card-text">Aplicación de película protectora, implementación de tratamiento químico y programa preventivo para evitar reincrustación.</p>
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
                    <h3 class="include-card-title">Informe Técnico</h3>
                    <p class="include-card-text">Documentación fotográfica antes/después, análisis de depósitos, registro de parámetros y certificado de limpieza.</p>
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
                        <h3 class="benefit-title">Recuperación de Eficiencia del 20-30%</h3>
                        <p class="benefit-text">Eliminación de capa aislante de incrustación restaura transferencia de calor original, reduciendo consumo de combustible dramáticamente.</p>
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
                        <h3 class="benefit-title">Prevención de Fallas por Sobrecalentamiento</h3>
                        <p class="benefit-text">Tubos limpios eliminan puntos calientes que causan deformación, fisuras y perforaciones catastróficas en calderas.</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-clock">
                            <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                            <path d="M20 3v4"></path>
                            <path d="M22 5h-4"></path>
                            <path d="M4 17v2"></path>
                            <path d="M5 18H3"></path>
                        </svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Extensión de Vida Útil +10 Años</h3>
                        <p class="benefit-text">Limpieza profesional periódica previene corrosión bajo depósito y deterioro prematuro de superficies metálicas críticas.</p>
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
                        <h3 class="benefit-title">Retorno de Inversión Inmediato</h3>
                        <p class="benefit-text">Ahorro energético post-desincrustación recupera inversión del servicio en 3-6 meses de operación normal.</p>
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
                        <p class="step-desc">Inspección endoscópica, medición de espesores de incrustación, análisis químico de depósitos y diseño de protocolo.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">2</p>
                        <h3 class="step-title">Circulación Ácida</h3>
                        <p class="step-desc">Bombeo de solución desincrustante, control de pH/temperatura, monitoreo de disolución y ajuste de concentración.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">3</p>
                        <h3 class="step-title">Enjuague y Pasivación</h3>
                        <p class="step-desc">Neutralización química, enjuagues múltiples con agua tratada y aplicación de película protectora anticorrosiva.</p>
                </div>

                <div class="process-step ">
                    <p class="step-number-circle">4</p>
                        <h3 class="step-title">Certificación</h3>
                        <p class="step-desc">Inspección final endoscópica, análisis de agua final, entrega de certificado y programa de tratamiento preventivo.</p>
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
                        <p class="stat-small-desc">Especialistas en limpieza química de calderas con protocolos certificados y productos biodegradables de última generación.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">400+</div>
                        <h3 class="stat-small-title">Calderas Desincrustadas</h3>
                        <p class="stat-small-desc">Equipos térmicos industriales limpiados exitosamente sin daño a superficies metálicas con garantía documentada.</p>
                </div>

                <div class="simari-stat-item ">
                    <div class="stat-big-number">100%</div>
                        <h3 class="stat-small-title">Seguridad Operativa</h3>
                        <p class="stat-small-desc">Protocolos de seguridad industrial certificados, personal capacitado y seguros de responsabilidad civil vigentes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- NUEVA SECCIÓN: CTA FINAL -->
    <section class="work-process-section">
        <div class="container work-process-section">
            <h2 class="cta-final-title-industrial">Recupere la Eficiencia de su Caldera Industrial</h2>
            <p class="cta-final-description-industrial">
                Las incrustaciones severas reducen eficiencia hasta 30% y generan riesgo de falla catastrófica por sobrecalentamiento. Implemente desincrustación química profesional que restaure capacidad térmica en hoteles, hospitales, lavanderías y plantas industriales.
            </p>

            <div class="cta-final-actions">
                <a
                    href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                    target="_blank"
                    aria-label="Abrir chat de WhatsApp"
                    class="button-primary work-process">
                    Solicitar Inspección
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-left: 8px;">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </a>

                <a
                    href="tel:524494348018"
                    aria-label="Llamar por teléfono"
                    class="button-secondary work-process">
                    <svg
                        xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.79 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
                    Llamar Ahora
                </a>
            </div>
        </div>
    </section>
@endsection