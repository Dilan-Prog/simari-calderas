@extends('frontend.layouts.master')

@section('title')
    Inicio | Industria Simari
@endsection

@section('content')
    <section class="hero-section-home">
        <div class="hero-background-home">
            <img src="https://images.unsplash.com/photo-1707596830261-9c6138a6dd3b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NjM2Nzh8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYm9pbGVyfGVufDB8fHx8MTcwNzU5NjgzMHww&ixlib=rb-4.0.3&q=80&w=1080"
                alt="Sistema de calderas industriales operando en planta en México"
                fetchpriority="high"
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
                    <button class="button-primary">
                        Solicitar Cotización
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>

                    {{-- CORRIGE EL PVTO BOTON QUE TE DIJE DESDE TODA LA PINCHE SEMANA MOPRIN  --}}
                    <button class="button-secondary">
                    Soluciones
                    </button>
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
                <p class="section-subtitle-home">Nuestras Soluciones</p>
                <h2 class="section-title-home">Ingeniería que Impulsa su Industria</h2>
                <p class="section-desc-home">
                    Diseñamos, instalamos y mantenemos sistemas térmicos de alto rendimiento
                    adaptados a las necesidades específicas de su planta.
                </p>
            </div>
            <div class="solutions-grid-home">
                <div class="solution-card-home">
                    <div class="card-img-box-home">
                        <img src="https://images.unsplash.com/photo-1707596830261-9c6138a6dd3b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NjM2Nzh8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYm9pbGVyfGVufDB8fHx8MTcwNzU5NjgzMHww&ixlib=rb-4.0.3&q=80&w=1080"
                            alt="Calderas Industriales"
                            loading="lazy"
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
                        <h4 class="card-title-home">Calderas Industriales</h4>
                        <p class="card-text-home">Sistemas de generación de vapor y agua caliente de alta eficiencia para procesos críticos.</p>
                        <a href="#" class="card-link-home">
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
                        <img src="https://images.unsplash.com/photo-1759847552281-60e45956124d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwd2VsZGluZyUyMG1hbnVmYWN0dXJpbmclMjBzcGFya3N8ZW58MXx8fDE3NzE1MjI0Mzd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                            alt="Mantenimiento Integral"
                            loading="lazy"
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
                        <h4 class="card-title-home">Mantenimiento Integral</h4>
                        <p class="card-text-home">Programas preventivos y correctivos para asegurar la continuidad operativa y seguridad.</p>
                        <a href="#" class="card-link-home">
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
                        <img src="https://images.unsplash.com/photo-1769152683420-f4eff91cb30b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx0ZWNobmljYWwlMjBibHVlcHJpbnQlMjBzY2hlbWF0aWNzJTIwZHJhd2luZ3xlbnwxfHx8fDE3NzE1MjI0Mzd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                            alt="Eficiencia Energética"
                            loading="lazy"
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
                        <h4 class="card-title-home">Eficiencia Energética</h4>
                        <p class="card-text-home">Auditorías y modernización de equipos para reducir consumo de combustible y emisiones.</p>
                        <a href="#" class="card-link-home">
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
                        src="https://images.unsplash.com/photo-1631583087046-13c813d34e90?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080"
                        alt="Engineering Team"
                        loading="lazy"
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
                    <button class="button-primary cta-section-home">
                        Solicitar Asesoría
                    </button>

                    <button class="button-secondary cta-section-home">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6 11.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                         +52 (449) 434-8018
                    </button>
                </div>
            </div>
        </div>
    </section>
@endsection

<script>
    document.getElementById("year").textContent = new Date().getFullYear(); 
    // QUITAR ESTO
</script>
