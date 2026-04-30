@extends('frontend.layouts.master')
@section('title', 'Equiterm Industries')
@section('description', 'Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7.')
@section('canonical', config('app.url') . '/')
@section('content')
    <section class="hero-section-home">
        <div class="hero-background-home">
            <img src="{{ asset('images/home/servicios-para-planta-petroquimica.jpg') }}"
                alt="Servicios para planta petroquímica en México"
                title="Servicios para planta petroquímica en México"
                fetchpriority="high"
                loading="eager"
                decoding="async"
                >
            <div class="hero-overlay-home"></div>
        </div>

        <div class="container hero-content-home">
                <div class="badge-home">
                    <span class="dot-home"></span>
                    <p class="badge-text-home">Ingeniería Térmica Avanzada</p>
                </div>
                <h1 class="hero-title-home">
                    Soluciones Integrales<br>
                    en <span class="text-highlight-home">Calderas</span> y Energía
                </h1>

                <p class="hero-description-home">
                    Optimizamos sus procesos industriales con tecnología de vanguardia.
                    Eficiencia energética garantizada y soporte técnico especializado 24/7.
                </p>

                <div class="hero-actions-home">
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

                    <a
                    href="tel:+524494348018"
                    aria-label="Llamar a Equiterm Industries"
                    class="button-secondary">
                    Llamar Ahora
                    </a>
                </div>
            </div>
    </section>
    <section class="stats-section-home">
        <div class="container stats-grid-home">
            <div class="stat-item-home">
                <p class="stat-number-home">35+</p>
                <p class="stat-label-home">Años de Experiencia</p>
            </div>

            <div class="stat-item-home">
                <p class="stat-number-home">500+</p>
                <p class="stat-label-home">Proyectos Ejecutados</p>
            </div>

            <div class="stat-item-home">
                <p class="stat-number-home">98%</p>
                <p class="stat-label-home">Eficiencia Energética</p>
            </div>

            <div class="stat-item-home">
                <p class="stat-number-home">24/7</p>
                <p class="stat-label-home">Soporte Técnico</p>
            </div>
        </div>
    </section>
    <section class="solutions-section-home">
        <div class="container solutions-section-home">
            <div class="text-center-home mb-16-home">
                <p class="section-subtitle-home">Nuestras Servicios más solicitados</p>
                <h2 class="section-title-home">Servicios Industriales</h2>
                <p class="section-desc-home">
                    Diseñamos, instalamos y mantenemos sistemas térmicos de alto rendimiento
                    adaptados a las necesidades específicas de su planta.
                </p>
            </div>
            <div class="solutions-grid-home">
                <div class="solution-card-home">
                    <div class="card-img-box-home">
                        <img src="{{ asset('images/products/calderas-industriales-simari/caldera-industrial-simari.jpg') }}"
                            alt="Instalacion, venta y mantenimiento de Calderas industriales."
                            title="calderas industriales mantenimiento, venta y instalacion"
                            fetchpriority="low"
                            loading="lazy"
                            decoding="async"
                            >
                        <div class="card-img-overlay-home"></div>
                        <div class="card-icon-overlay-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                <path d="M17 18h1" />
                                <path d="M12 18h1" />
                                <path d="M7 18h1" />
                            </svg>
                        </div>
                    </div>
                    <div class="card-body-home">
                        <h3 class="card-title-home">Calderas Industriales - Simari</h3>
                        <p class="card-text-home">Venta, Fabricacion y Servicios de Calderas Industriales</p>
                        <a href="{{ route('simari-boilers') }}" class="card-link-home">
                            Más Información
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="solution-card-home">
                    <div class="card-img-box-home">
                        <img src="{{ asset('images/masstercal-rinnai/productos-rinnai/Calentamiento/Comercial/Calentador-de-agua-rinnai-comercial.webp') }}"
                            alt="Instalacion, venta y mantenimiento de calentadores de agua Rinnai."
                            title="Calentadores de agua Rinnai"
                            fetchpriority="low"
                            loading="lazy"
                            decoding="async"
                            >
                        <div class="card-img-overlay-home"></div>
                        <div class="card-icon-overlay-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </div>
                    </div>
                    <div class="card-body-home">
                        <h3 class="card-title-home">Calentadores de agua Rinnai</h3>
                        <p class="card-text-home">Instalación, venta y mantenimiento de calentadores de agua Rinnai.</p>
                        <a href="{{ route('water-heaters') }}" class="card-link-home">
                            Más Información
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="solution-card-home">
                    <div class="card-img-box-home">
                        <img src="{{ asset('images/services/tratamiento-agua-calderas/servicio-de-tratamiento-de-agua-para-calderas-industriales.jpg') }}"
                            alt="Mantenimiento de sistemas de tratamiento de agua para la industria en México"
                            title="Mantenimiento de sistemas de tratamiento de agua para la industria en México"
                            fetchpriority="low"
                            loading="lazy"
                            decoding="async"
                            >
                        <div class="card-img-overlay-home"></div>
                        <div class="card-icon-overlay-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14H4Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="card-body-home">
                        <h3 class="card-title-home">Tratamiento de Agua industriales</h3>
                        <p class="card-text-home">Servicio y mantenimiento de sistemas de tratamiento de agua para toda la industria.</p>
                        <a href="{{ route('water-treatment') }}" class="card-link-home">
                            Más Información
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-section-home">
        <div class="container why-grid-home">
            <div class="content-left-home">
                <p class="section-subtitle-home why-section">Por Qué Elegirnos</p>
                <h2 class="why-title-home">Excelencia Técnica y Compromiso Total</h2>
                <p class="why-desc-home">
                    En SIMARI Calderas, entendemos que la energía es el corazón de su operación.
                    Nuestro enfoque combina ingeniería de precisión con un servicio al cliente inigualable.
                </p>

                <div class="feature-list-home">
                    <div class="feature-item-home">
                        <div class="feature-icon-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="feature-content-home">
                            <h4>Certificaciones Internacionales</h4>
                            <p>Cumplimos con normativas ASME, ISO y NOM.</p>
                        </div>
                    </div>

                    <div class="feature-item-home">
                        <div class="feature-icon-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="feature-content-home">
                            <h4>Tecnología de Punta</h4>
                            <p>Monitoreo remoto y sistemas automatizados.</p>
                        </div>
                    </div>

                    <div class="feature-item-home">
                        <div class="feature-icon-home">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="feature-content-home">
                            <h4>Respuesta Inmediata</h4>
                            <p>Equipo técnico listo para emergencias 24/7.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-right-home">
                <div class="why-image-wrapper-home">
                    <div class="blur-backdrop-home"></div>
                    <img class="why-img-home"
                        src="{{ asset('images/company/team/equipo_industria_simari.jpg') }}"
                        alt="Equipo Equiterm Industries"
                        title="Equipo Equiterm Industries"
                        fetchpriority="low"
                        loading="lazy"
                        decoding="async"
                        >

                    <div class="floating-card-home">
                        <p class="floating-number-home">100%</p>
                        <p class="floating-text-home">Garantía de Satisfacción</p>
                        <p class="floating-sub-home">En todos nuestros servicios de mantenimiento.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section-home">
        <div class="container cta-box-home">
            <div class="cta-pattern-home"></div>
            <div class="cta-content-home">
                <h2 class="cta-title-home">¿Listo para optimizar su planta?</h2>
                <p class="cta-text-home">
                    Contáctenos hoy para una evaluación gratuita de sus sistemas térmicos.
                </p>

                <div class="cta-buttons-home">

                    <a
                    href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                    target="_blank"
                    aria-label="Abrir chat de WhatsApp"
                    class="button-primary cta-section-home">
                        Solicitar Asesoría
                    </a>

                    <a
                    href="tel:+524494348018"
                    aria-label="Llamar a Equiterm Industries"
                    class="button-secondary cta-section-home">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                            <g clip-path="url(#clip0_20_1370)">
                                <path
                                    d="M18.3305 14.0978V16.5974C18.3314 16.8295 18.2839 17.0592 18.1909 17.2718C18.0979 17.4844 17.9616 17.6753 17.7906 17.8321C17.6196 17.989 17.4177 18.1084 17.1979 18.1828C16.9781 18.2571 16.7452 18.2847 16.5141 18.2638C13.9501 17.9853 11.4873 17.1091 9.32348 15.7059C7.31031 14.4266 5.60349 12.7198 4.32423 10.7066C2.91609 8.53298 2.03978 6.05818 1.76628 3.48273C1.74546 3.25232 1.77285 3.0201 1.84669 2.80086C1.92053 2.58161 2.03922 2.38014 2.19519 2.20928C2.35116 2.03841 2.54101 1.9019 2.75263 1.80842C2.96425 1.71495 3.19302 1.66656 3.42437 1.66634H5.92399C6.32835 1.66236 6.72037 1.80555 7.02696 2.06922C7.33356 2.3329 7.53382 2.69906 7.59041 3.09946C7.69591 3.89939 7.89157 4.68483 8.17366 5.44077C8.28576 5.739 8.31002 6.06311 8.24357 6.3747C8.17711 6.68629 8.02273 6.9723 7.79871 7.19884L6.74054 8.25702C7.92666 10.343 9.65381 12.0701 11.7398 13.2563L12.798 12.1981C13.0245 11.9741 13.3105 11.8197 13.6221 11.7532C13.9337 11.6868 14.2578 11.711 14.556 11.8231C15.312 12.1052 16.0974 12.3009 16.8973 12.4064C17.3021 12.4635 17.6717 12.6674 17.936 12.9792C18.2002 13.2911 18.3406 13.6892 18.3305 14.0978Z"
                                    stroke="white" stroke-width="1.66642" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                            <defs>
                                <clipPath id="clip0_20_1370">
                                    <rect width="19.997" height="19.997" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                         +52 (449) 434-8018
                    </a>

                </div>
            </div>
        </div>
    </section>
@endsection
