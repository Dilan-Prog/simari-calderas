@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/water-heaters.css') }}">
@endsection
@section('content')
    <div class="rinnai-page">

        <section class="rin-hero">
            <div class="container">
                <div class="rin-logo-wrapper">
                    <span class="rin-logo-text">Rinnai</span>
                </div>

                <div class="rin-hero-text">
                    <span class="rin-badge">TECNOLOGÍA JAPONESA</span>
                    <h1 class="rin-title">
                        Calentadores de Agua<br>
                        <span class="text-red">De Depósito Rinnai</span>
                    </h1>
                    <p class="rin-subtitle">Tanques termo-aislados de última generación. Agua caliente constante con máxima
                        eficiencia energética.</p>
                </div>

                <div class="rin-tabs">
                    <button class="rin-tab active" data-target="residencial">Residencial</button>
                    <button class="rin-tab" data-target="comercial">Comercial</button>
                    <button class="rin-tab" data-target="industrial">Industrial</button>
                </div>

                <div class="rin-glass-card">

                    <div id="residencial" class="rin-tab-content active-content">
                        <div class="rin-card-grid">
                            <div class="rin-img-wrapper">
                                <div class="rin-img-overlay"></div>
                                <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=800"
                                    alt="Residencial">
                                <div class="rin-img-badge">50-80L</div>
                            </div>

                            <div class="rin-info-wrapper">
                                <h2 class="rin-info-title text-white">Modelo Residencial</h2>
                                <div class="rin-specs-grid">
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                            </path>
                                            <path
                                                d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">50-80L</div>
                                        <div class="rin-spec-label">Capacidad</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">1.5-2kW</div>
                                        <div class="rin-spec-label">Potencia</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                            <path d="M12 3v2"></path>
                                            <path d="m6.6 18.4-1.4 1.4"></path>
                                            <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                            <path d="M4 13H2"></path>
                                            <path d="M6.34 7.34 4.93 5.93"></path>
                                        </svg>
                                        <div class="rin-spec-val text-white">Casas y departamentos</div>
                                        <div class="rin-spec-label">Aplicación</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                                            </path>
                                            <circle cx="12" cy="8" r="6"></circle>
                                        </svg>
                                        <div class="rin-spec-val text-white">desde $8,500</div>
                                        <div class="rin-spec-label">Precio</div>
                                    </div>
                                </div>
                                <div class="rin-features-list">
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Instalación sencilla</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Bajo consumo</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Ideal para 2-4 personas</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Diseño compacto</span></div>
                                </div>
                                <a href="/contacto" class="btn-primary rin-btn-card">Cotizar Residencial <svg
                                        width="20" height="20" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg></a>
                            </div>
                        </div>
                    </div>

                    <div id="comercial" class="rin-tab-content">
                        <div class="rin-card-grid">
                            <div class="rin-img-wrapper">
                                <div class="rin-img-overlay"></div>
                                <img src="https://images.unsplash.com/photo-1581092160562-40aa08e78837?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=800"
                                    alt="Comercial">
                                <div class="rin-img-badge">100-200L</div>
                            </div>

                            <div class="rin-info-wrapper">
                                <h2 class="rin-info-title text-white">Modelo Comercial</h2>
                                <div class="rin-specs-grid">
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                            </path>
                                            <path
                                                d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">100-200L</div>
                                        <div class="rin-spec-label">Capacidad</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">3-5kW</div>
                                        <div class="rin-spec-label">Potencia</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                            <path d="M12 3v2"></path>
                                            <path d="m6.6 18.4-1.4 1.4"></path>
                                            <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                            <path d="M4 13H2"></path>
                                            <path d="M6.34 7.34 4.93 5.93"></path>
                                        </svg>
                                        <div class="rin-spec-val text-white">Oficinas y locales</div>
                                        <div class="rin-spec-label">Aplicación</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                                            </path>
                                            <circle cx="12" cy="8" r="6"></circle>
                                        </svg>
                                        <div class="rin-spec-val text-white">desde $15,500
                                            <br>MXN</div>
                                        <div class="rin-spec-label">Precio</div>
                                    </div>
                                </div>
                                <div class="rin-features-list">
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Alta capacidad</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Recuperación rápida</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Hasta 15 usuarios</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Garantía extendida</span></div>
                                </div>
                                <button class="btn-primary rin-btn-card" onclick="window.location.href='/contacto'">
                                    Cotizar Comercial
                                    <svg width="20" height="20" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="industrial" class="rin-tab-content">
                        <div class="rin-card-grid">
                            <div class="rin-img-wrapper">
                                <div class="rin-img-overlay"></div>
                                <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=800"
                                    alt="Industrial">
                                <div class="rin-img-badge">300-500L</div>
                            </div>

                            <div class="rin-info-wrapper">
                                <h2 class="rin-info-title text-white">Modelo Industrial</h2>
                                <div class="rin-specs-grid">
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                            </path>
                                            <path
                                                d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">300-500L</div>
                                        <div class="rin-spec-label">Capacidad</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                            </path>
                                        </svg>
                                        <div class="rin-spec-val text-white">8-15kW</div>
                                        <div class="rin-spec-label">Potencia</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                            <path d="M12 3v2"></path>
                                            <path d="m6.6 18.4-1.4 1.4"></path>
                                            <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                            <path d="M4 13H2"></path>
                                            <path d="M6.34 7.34 4.93 5.93"></path>
                                        </svg>
                                        <div class="rin-spec-val text-white">Hoteles y
                                            <br>hospitales</div>
                                        <div class="rin-spec-label">Aplicación</div>
                                    </div>
                                    <div class="rin-spec-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                            <path
                                                d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                                            </path>
                                            <circle cx="12" cy="8" r="6"></circle>
                                        </svg>
                                        <div class="rin-spec-val text-white" style="font-size: 1.2rem;">Cotización <br>
                                            personalizada</div>
                                        <div class="rin-spec-label">Precio</div>
                                    </div>
                                </div>
                                <div class="rin-features-list">
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Máxima eficiencia</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Operación continua</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Uso intensivo</span></div>
                                    <div class="rin-feature-row"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon-red">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg><span>Monitoreo inteligente</span></div>
                                </div>
                                <a href="/contacto" class="btn-primary rin-btn-card">Cotizar Industrial <svg
                                        width="20" height="20" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg></a>
                            </div>
                        </div>
                    </div>
        </section>

        <section class="rin-section bg-light">
            <div class="container">
                <h2 class="rin-section-title text-dark">Comparativa de Modelos</h2>

                <div class="rin-table-wrapper">
                    <table class="rin-table">
                        <thead>
                            <tr>
                                <th>Característica</th>
                                <th>Residencial</th>
                                <th>Comercial</th>
                                <th>Industrial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Capacidad</td>
                                <td>50-80L</td>
                                <td>100-200L</td>
                                <td>300-500L</td>
                            </tr>
                            <tr>
                                <td>Usuarios Simultáneos</td>
                                <td>2-4</td>
                                <td>8-15</td>
                                <td>30+</td>
                            </tr>
                            <tr>
                                <td>Recuperación</td>
                                <td>45 min</td>
                                <td>30 min</td>
                                <td>20 min</td>
                            </tr>
                            <tr>
                                <td>Garantía</td>
                                <td>5 años</td>
                                <td>7 años</td>
                                <td>10 años</td>
                            </tr>
                            <tr>
                                <td>Eficiencia</td>
                                <td><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg></td>
                                <td><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg></td>
                                <td><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rin-section bg-white">
            <div class="container">
                <div class="rin-benefits-grid">
                    <div class="rin-benefit-card">
                        <div class="rin-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                </path>
                            </svg></div>
                        <h3 class="text-dark">Garantía Extendida</h3>
                        <p class="text-gray">Hasta 10 años de cobertura total</p>
                    </div>
                    <div class="rin-benefit-card">
                        <div class="rin-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                <path
                                    d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z">
                                </path>
                            </svg></div>
                        <h3 class="text-dark">Calentamiento Rápido</h3>
                        <p class="text-gray">Tecnología de alta eficiencia</p>
                    </div>
                    <div class="rin-benefit-card">
                        <div class="rin-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"></polyline>
                                <polyline points="16 17 22 17 22 11"></polyline>
                            </svg></div>
                        <h3 class="text-dark">Bajo Consumo</h3>
                        <p class="text-gray">Ahorro energético certificado</p>
                    </div>
                    <div class="rin-benefit-card">
                        <div class="rin-icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="icon-red">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg></div>
                        <h3 class="text-dark">Instalación Express</h3>
                        <p class="text-gray">Servicio profesional en 24hrs</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rin-cta-section">
            <div class="container rin-cta-content">
                <h2 class="text-white">¿Listo para un sistema más eficiente?</h2>
                <p class="text-white">Nuestros expertos te ayudarán a elegir el modelo perfecto para tus necesidades</p>

                <div class="rin-btn-group">
                    <a href="/contacto" class="btn-primary rin-btn-white">
                        Solicitar Asesoría Gratuita
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="tel:+524494348018" class="btn-outline rin-btn-outline-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                        Llamar Ahora
                    </a>
                </div>
            </div>
        </section>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionamos todos los botones y todas las tarjetas
            const tabs = document.querySelectorAll('.rin-tab');
            const contents = document.querySelectorAll('.rin-tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // 1. Le quitamos la clase 'active' a todos los botones
                    tabs.forEach(t => t.classList.remove('active'));

                    // 2. Ocultamos todo el contenido
                    contents.forEach(c => c.classList.remove('active-content'));

                    // 3. Le ponemos 'active' al botón que clickeamos
                    tab.classList.add('active');

                    // 4. Mostramos el contenido que corresponde a ese botón
                    const targetId = tab.getAttribute('data-target');
                    document.getElementById(targetId).classList.add('active-content');
                });
            });
        });
    </script>
@endsection
