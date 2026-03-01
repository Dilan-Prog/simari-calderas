@extends('frontend.layouts.master')

@section('styles')
    <!-- Cargamos el CSS específico de esta página de producto -->
    <link rel="stylesheet" href="{{ asset('css/heat-pumps.css') }}" />
@endsection

@section('content')
    <!-- SECCIÓN 1: HERO PRODUCTO RINNAI -->
    <section class="product-hero-section">
        <div class="container">
            <!-- Logotipo Superior -->
            <div class="brand-logo-container">
                <h1 class="brand-logo-text">Rinnai</h1>
            </div>

            <div class="product-hero-grid">
                <!-- Columna Izquierda: Imagen del Producto -->
                <div class="product-image-wrapper">
                    <div class="product-image-card">
                        <img src="https://images.unsplash.com/photo-1697665896503-8b1fd6a8ff0f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoZWF0JTIwcHVtcCUyMHdhdGVyJTIwaGVhdGVyJTIwbW9kZXJufGVufDF8fHx8MTc3MjE2MzA1OHww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                             alt="Bomba de Calor Rinnai"
                             class="main-product-img">
                    </div>
                </div>

                <!-- Columna Derecha: Información y Características -->
                <div class="product-info-wrapper">
                    <!-- Badge / Etiqueta -->
                    <div class="badge-premium">
                        TECNOLOGÍA PREMIUM
                    </div>

                    <!-- Título y Descripción -->
                    <h2 class="product-main-title">Bombas de Calor Rinnai</h2>
                    <p class="product-main-desc">
                        Tecnología de última generación que aprovecha la energía del aire para calentar agua de manera eficiente, reduciendo hasta 75% el consumo energético comparado con sistemas convencionales.
                    </p>

                    <!-- Grid de Características (2x2) -->
                    <div class="product-features-grid">
                        <div class="feat-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon">
                                <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline>
                                <polyline points="16 17 22 17 22 11"></polyline>
                            </svg>
                            <div class="feat-value">75%</div>
                            <div class="feat-label">Ahorro Energético</div>
                        </div>

                        <div class="feat-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon">
                                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                            </svg>
                            <div class="feat-value">0%</div>
                            <div class="feat-label">Emisiones CO₂</div>
                        </div>

                        <div class="feat-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon">
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            </svg>
                            <div class="feat-value">10</div>
                            <div class="feat-label">Años Garantía</div>
                        </div>

                        <div class="feat-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feat-icon">
                                <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                <path d="M12 3v2"></path>
                                <path d="m6.6 18.4-1.4 1.4"></path>
                                <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                <path d="M4 13H2"></path>
                                <path d="m6.34 7.34 4.93 5.93"></path>
                            </svg>
                            <div class="feat-value">-10°C</div>
                            <div class="feat-label">Opera hasta</div>
                        </div>
                    </div>

                    <!-- Botón de Acción -->
                    <button class="button-primary">
                        Cotizar Equipo Rinnai
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 2: ¿POR QUÉ ELEGIR RINNAI? -->
    <section class="why-rinnai-section">
        <div class="container">
            <div class="why-rinnai-header">
                <h2 class="why-rinnai-title">¿Por qué elegir Bombas de Calor Rinnai?</h2>
                <p class="why-rinnai-subtitle">
                    Tecnología japonesa de vanguardia que transforma la energía del ambiente en agua caliente eficiente.
                </p>
            </div>

            <div class="why-rinnai-grid">
                <!-- Beneficio 1 -->
                <div class="why-rinnai-card">
                    <div class="why-rinnai-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title">Eficiencia Superior</h3>
                    <p class="why-rinnai-card-text">
                        COP (Coefficient of Performance) de hasta 4.0, generando 4 kW de calor por cada 1 kW de electricidad consumida.
                    </p>
                </div>

                <!-- Beneficio 2 -->
                <div class="why-rinnai-card">
                    <div class="why-rinnai-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-leaf">
                            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title">Sustentable</h3>
                    <p class="why-rinnai-card-text">
                        Reduce hasta 3.5 toneladas de CO₂ al año comparado con calentadores eléctricos convencionales.
                    </p>
                </div>

                <!-- Beneficio 3 -->
                <div class="why-rinnai-card">
                    <div class="why-rinnai-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplets">
                            <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                            <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                        </svg>
                    </div>
                    <h3 class="why-rinnai-card-title">Agua Caliente Infinita</h3>
                    <p class="why-rinnai-card-text">
                        Suministro continuo sin esperas, ideal para familias numerosas o aplicaciones comerciales.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 3: APLICACIONES IDEALES -->
    <section class="ideal-applications-section">
        <div class="container">
            <h2 class="ideal-apps-title">Aplicaciones Ideales</h2>

            <div class="ideal-apps-grid">
                <!-- Tarjeta 1 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Residencias unifamiliares</p>
                </div>

                <!-- Tarjeta 2 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Departamentos y condominios</p>
                </div>

                <!-- Tarjeta 3 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Boutique Hoteles</p>
                </div>

                <!-- Tarjeta 4 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Gimnasios y spas</p>
                </div>

                <!-- Tarjeta 5 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Restaurantes</p>
                </div>

                <!-- Tarjeta 6 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Oficinas corporativas</p>
                </div>

                <!-- Tarjeta 7 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Centros deportivos</p>
                </div>

                <!-- Tarjeta 8 -->
                <div class="app-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-card-icon">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <p class="app-card-text">Hospitales y clínicas</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 4: ESPECIFICACIONES TÉCNICAS -->
    <section class="tech-specs-section">
        <div class="container specs-container">
            <h2 class="tech-specs-title">Especificaciones Técnicas</h2>

            <div class="tech-specs-card">
                <div class="tech-specs-grid">
                    <!-- Columna 1: Características Generales -->
                    <div class="specs-column">
                        <h3 class="specs-col-title">Características Generales</h3>
                        <ul class="specs-list">
                            <li class="specs-list-item">
                                <span class="specs-label">Capacidad</span>
                                <span class="specs-value">200 - 300 litros</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">Potencia</span>
                                <span class="specs-value">1,5 - 2,5 kW</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">COP</span>
                                <span class="specs-value">3.5 - 4.0</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">Voltaje</span>
                                <span class="specs-value">220V / 60Hz</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Columna 2: Rendimiento -->
                    <div class="specs-column">
                        <h3 class="specs-col-title">Rendimiento</h3>
                        <ul class="specs-list">
                            <li class="specs-list-item">
                                <span class="specs-label">Temperatura Agua</span>
                                <span class="specs-value">Hasta 60°C</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">Rango Operación</span>
                                <span class="specs-value">-10°C a 43°C</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">Nivel de Ruido</span>
                                <span class="specs-value">≤ 45 dB</span>
                            </li>
                            <li class="specs-list-item">
                                <span class="specs-label">Garantía</span>
                                <span class="specs-value">10 años</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 5: CTA FINAL (CALIDAD JAPONESA) -->
    <section class="final-cta-rinnai">
        <div class="container cta-rinnai-container">
            <h2 class="cta-rinnai-title">Calidad Japonesa, Eficiencia Comprobada</h2>
            <p class="cta-rinnai-subtitle">
                Únete a miles de hogares y negocios que confían en la tecnología Rinnai para su agua caliente sanitaria. Asesores especializados listos para ayudarte.
            </p>

            <div class="cta-rinnai-buttons">
                <!-- Botón Principal -->
                <button class="btn-rinnai-primary">
                    Solicitar Cotización
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </button>

                <!-- Botón Secundario (Teléfono) -->
                <button class="btn-rinnai-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6 11.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Llamar Ahora
                </button>
            </div>
        </div>
    </section>
@endsection
