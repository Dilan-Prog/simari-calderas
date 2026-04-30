@extends('frontend.layouts.master')
@section('title', 'Bombas de Calor Rinnai - Equiterm Industries')
@section('description', 'Bombas de Calor Rinnai: Agua caliente instantánea sin gas. Tecnología eléctrica de alta eficiencia para máxima seguridad y comodidad en tu hogar.')
@section('canonical', config('app.url') . '/masstercal-rinnai/bombas-de-calor-rinnai')
@section('content')
    <section class="product-hero-section-heat-pumps">
        <div class="container heat-pumps">
            <div class="brand-logo-container-heat-pumps">
                <img src="{{ asset('images/masstercal-rinnai/rinnai-logo.png') }}" alt="Rinnai" title="Rinnai" width="200" height="60" loading="eager" fetchpriority="high" decoding="async">
            </div>
            <div class="product-hero-grid-heat-pumps">
                <div class="product-image-wrapper-heat-pumps">
                    <div class="product-image-card-heat-pumps">
                        <img src="{{ asset('images/masstercal-rinnai/productos-rinnai/bombas-calor/bomba-de-calor-para-piscina-bcp-50.jpg') }}"
                            alt="Bombas de Calor Rinnai para piscina bcp-50"
                            title="Bomba de Calor Rinnai BCP-50"
                            width="1200"
                            height="1200"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            class="main-product-img-heat-pumps">
                    </div>
                </div>

                <div class="product-info-wrapper-heat-pumps">
                    <div class="badge-premium-heat-pumps">
                        TECNOLOGÍA PREMIUM
                    </div>

                    <h1 class="product-main-title-heat-pumps">Bombas de Calor Rinnai</h1>
                    <p class="product-main-desc-heat-pumps">
                        Tecnología de última generación que aprovecha la energía del aire para calentar agua de manera eficiente, reduciendo hasta 75% el consumo energético comparado con sistemas convencionales.
                    </p>

                    <div class="product-features-grid-heat-pumps">
                        <div class="feat-card-heat-pumps">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon-heat-pumps">
                                <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline>
                                <polyline points="16 17 22 17 22 11"></polyline>
                            </svg>
                            <p class="feat-value-heat-pumps">75%</p>
                            <p class="feat-label-heat-pumps">Ahorro Energético</p>
                        </div>

                        <div class="feat-card-heat-pumps">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon-heat-pumps">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                            </svg>
                            <p class="feat-value-heat-pumps">0%</p>
                            <p class="feat-label-heat-pumps">Emisiones CO₂</p>
                        </div>

                        <div class="feat-card-heat-pumps">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon-heat-pumps">
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            </svg>
                            <p class="feat-value-heat-pumps">10</p>
                            <p class="feat-label-heat-pumps">Años Garantía</p>
                        </div>

                        <div class="feat-card-heat-pumps">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon-heat-pumps">
                                <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                <path d="M12 3v2"></path>
                                <path d="m6.6 18.4-1.4 1.4"></path>
                                <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                <path d="M4 13H2"></path>
                                <path d="m6.34 7.34 4.93 5.93"></path>
                            </svg>
                            <p class="feat-value-heat-pumps">-10°C</p>
                            <p class="feat-label-heat-pumps">Opera hasta</p>
                        </div>
                    </div>

                    <a
                    href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                    target="_blank"
                    aria-label="Abrir chat de WhatsApp"
                    class="button-primary heat-pumps">
                        Cotizar Equipo Rinnai
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="why-rinnai-section-heat-pumps">
        <div class="container why-rinnai-section-heat-pumps">
            <div class="why-rinnai-header-heat-pumps">
                <h2 class="why-rinnai-title-heat-pumps">¿Por qué elegir Bombas de Calor Rinnai?</h2>
                <p class="why-rinnai-subtitle-heat-pumps">
                    Tecnología japonesa de vanguardia que transforma la energía del ambiente en agua caliente eficiente.
                </p>
            </div>

            <div class="why-rinnai-grid-heat-pumps">
                <div class="why-rinnai-card-heat-pumps">
                    <div class="why-rinnai-icon-box-heat-pumps">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title-heat-pumps">Eficiencia Superior</h3>
                    <p class="why-rinnai-card-text-heat-pumps">
                        COP (Coefficient of Performance) de hasta 4.0, generando 4 kW de calor por cada 1 kW de electricidad consumida.
                    </p>
                </div>

                <div class="why-rinnai-card-heat-pumps">
                    <div class="why-rinnai-icon-box-heat-pumps">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-leaf">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title-heat-pumps">Sustentable</h3>
                    <p class="why-rinnai-card-text-heat-pumps">
                        Reduce hasta 3.5 toneladas de CO₂ al año comparado con calentadores eléctricos convencionales.
                    </p>
                </div>

                <div class="why-rinnai-card-heat-pumps">
                    <div class="why-rinnai-icon-box-heat-pumps">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets">
                            <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                            <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title-heat-pumps">Agua Caliente Infinita</h3>
                    <p class="why-rinnai-card-text-heat-pumps">
                        Suministro continuo sin esperas, ideal para familias numerosas o aplicaciones comerciales.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="ideal-applications-section-heat-pumps">
        <div class="container ideal-applications-section-heat-pumps">
            <h2 class="ideal-apps-title-heat-pumps">Aplicaciones Ideales</h2>

            <div class="ideal-apps-grid-heat-pumps">
                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Residencias unifamiliares</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Departamentos y condominios</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Boutique Hoteles</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Gimnasios y spas</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Restaurantes</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Oficinas corporativas</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Centros deportivos</p>
                </div>

                <div class="app-card-heat-pumps">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon-heat-pumps">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text-heat-pumps">Hospitales y clínicas</p>
                </div>
            </div>
        </div>
    </section>

    <section class="tech-specs-section-heat-pumps">
        <div class="container specs-container-heat-pumps">
            <h2 class="tech-specs-title-heat-pumps">Especificaciones Técnicas</h2>

            <div class="tech-specs-card-heat-pumps">
                <div class="tech-specs-grid-heat-pumps">
                    <div class="specs-column-heat-pumps">
                        <h3 class="specs-col-title-heat-pumps">Características Generales</h3>
                        <ul class="specs-list-heat-pumps">
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Capacidad</p>
                                <p class="specs-value-heat-pumps">200 - 300 litros</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Potencia</p>
                                <p class="specs-value-heat-pumps">1,5 - 2,5 kW</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">COP</p>
                                <p class="specs-value-heat-pumps">3.5 - 4.0</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Voltaje</p>
                                <p class="specs-value-heat-pumps">220V / 60Hz</p>
                            </li>
                        </ul>
                    </div>

                    <div class="specs-column-heat-pumps">
                        <h3 class="specs-col-title-heat-pumps">Rendimiento</h3>
                        <ul class="specs-list-heat-pumps">
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Temperatura Agua</p>
                                <p class="specs-value-heat-pumps">Hasta 60°C</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Rango Operación</p>
                                <p class="specs-value-heat-pumps">-10°C a 43°C</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Nivel de Ruido</p>
                                <p class="specs-value-heat-pumps">≤ 45 dB</p>
                            </li>
                            <li class="specs-list-item-heat-pumps">
                                <p class="specs-label-heat-pumps">Garantía</p>
                                <p class="specs-value-heat-pumps">10 años</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="final-cta-rinnai-heat-pumps">
        <div class="container cta-rinnai-container-heat-pumps">
            <h2 class="cta-rinnai-title-heat-pumps">Calidad Japonesa, Eficiencia Comprobada</h2>
            <p class="cta-rinnai-subtitle-heat-pumps">
                Únete a miles de hogares y negocios que confían en la tecnología Rinnai para su agua caliente sanitaria. Asesores especializados listos para ayudarte.
            </p>

            <div class="cta-rinnai-buttons-heat-pumps">
                <a
                href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                target="_blank"
                aria-label="Abrir chat de WhatsApp"
                class="button-primary ctn-heat">
                    Solicitar Cotización
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a
                href="tel:+524494348018"
                aria-label="Llamar a Equiterm Industries"
                class="button-primary ctn-heat-outline">
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
