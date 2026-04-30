@extends('frontend.layouts.master')
@section('title', 'Tanques de Almacenamiento Rinnai - Equiterm Industries')
@section('description', 'Tanques de Almacenamiento Rinnai: Soluciones de alta capacidad para aplicaciones comerciales e industriales.')
@section('canonical', config('app.url') . '/masstercal-rinnai/tanques-de-almacenamiento-rinnai')
@section('content')
    <section class="st-hero">
        <div class="container">
            <div class="st-brand-wrap">
                <img src="{{ asset('images/masstercal-rinnai/rinnai-logo.png') }}" alt="Rinnai" title="Rinnai" width="200" height="60" loading="eager" fetchpriority="high" decoding="async"
                    class="st-brand-logo">
            </div>

            <div class="st-hero-copy">
                <p class="st-hero-badge">SOLUCIONES COMERCIALES E INDUSTRIALES</p>
                <h1 class="st-hero-title">
                    Tanques de
                    <span class="st-accent">Almacenamiento</span><br>
                    <span class="st-subtitle">Alta Capacidad Rinnai</span>
                </h1>
                <p class="st-hero-text">
                    Sistemas termo-aislados de grado industrial para aplicaciones de alta demanda y uso continuo.
                </p>
            </div>

            <div class="st-stats-grid">
                <article class="st-stat-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path
                            d="M22 7.7c0-.6-.4-1.2-.8-1.5l-6.3-3.9a1.72 1.72 0 0 0-1.7 0l-10.3 6c-.5.2-.9.8-.9 1.4v6.6c0 .5.4 1.2.8 1.5l6.3 3.9a1.72 1.72 0 0 0 1.7 0l10.3-6c.5-.3.9-1 .9-1.5Z">
                        </path>
                        <path d="M10 21.9V14L2.1 9.1"></path>
                        <path d="m10 14 11.9-6.9"></path>
                        <path d="M14 19.8v-8.1"></path>
                        <path d="M18 17.5V9.4"></path>
                    </svg>
                    <p class="st-stat-value">300-1000L</p>
                    <p class="st-stat-label">Capacidades</p>
                </article>
                <article class="st-stat-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                        <path d="M12 3v2"></path>
                        <path d="m6.6 18.4-1.4 1.4"></path>
                        <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                        <path d="M4 13H2"></path>
                        <path d="M6.34 7.34 4.93 5.93"></path>
                    </svg>
                    <p class="st-stat-value">80C</p>
                    <p class="st-stat-label">Temperatura</p>
                </article>

                <article class="st-stat-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                    <p class="st-stat-value">10 años</p>
                    <p class="st-stat-label">Garantía</p>
                </article>

                <article class="st-stat-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                        <polyline points="16 7 22 7 22 13"></polyline>
                    </svg>
                    <p class="st-stat-value">95%</p>
                    <p class="st-stat-label">Eficiencia</p>
                </article>
            </div>
        </div>
    </section>
    <section class="st-overview">
        <div class="container st-layout">
            <aside class="st-sidebar">
                <article class="st-panel">
                    <h2>Por que Tanques?</h2>
                    <ul class="st-feature-list">
                        <li>
                            <span class="st-icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                    </path>
                                    <path
                                        d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                    </path>
                                </svg>
                            </span>
                            <span>Respaldo constante de agua caliente</span>
                        </li>
                        <li>
                            <span class="st-icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                    </path>
                                </svg>
                            </span>
                            <span>Menor costo operativo que tankless multiples</span>
                        </li>
                        <li>
                            <span class="st-icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                    </path>
                                </svg>
                            </span>
                            <span>Ideal para demanda simultanea alta</span>
                        </li>
                        <li>
                            <span class="st-icon-box">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                    <path d="M12 3v2"></path>
                                    <path d="m6.6 18.4-1.4 1.4"></path>
                                    <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                    <path d="M4 13H2"></path>
                                    <path d="M6.34 7.34 4.93 5.93"></path>
                                </svg>
                            </span>
                            <span>Compatible con sistemas solares</span>
                        </li>
                    </ul>
                </article>

                <article class="st-warranty-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path
                            d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                        </path>
                        <circle cx="12" cy="8" r="6"></circle>
                    </svg>
                    <h3>Garantia Comercial</h3>
                    <p>10 anos en tanque y componentes. La mejor proteccion de la industria.</p>
                    <ul>
                        <li>Servicio tecnico prioritario</li>
                        <li>Refacciones originales</li>
                        <li>Soporte 24/7</li>
                    </ul>
                </article>

                <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
             target="_blank"
             aria-label="Abrir chat de WhatsApp" class="button-primary st">Cotizar Proyecto</a>
            </aside>

            <div class="st-content">
                <article class="st-media-card">
                    <img src="{{ asset('images/masstercal-rinnai/productos-rinnai/tanques-almacenamiento/tanque-galvanizado-de-acero-inoxidable.png') }}"
                    alt="Tanques de almacenamiento Rinnai"
                    title="Tanques de almacenamiento Rinnai"
                    fetchpriority="low"
                    loading="lazy"
                    decoding="async"
                    >
                    <div class="st-media-overlay">
                        <h2>Construccion Premium</h2>
                        <p>Acero vitrificado con aislamiento termico de alta densidad</p>
                    </div>
                </article>

                <article class="st-panel">
                    <h2>Aplicaciones Comerciales</h2>
                    <div class="st-app-grid">
                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <rect width="16" height="20" x="4" y="2" rx="2" ry="2">
                                    </rect>
                                    <path d="M9 22v-4h6v4"></path>
                                    <path d="M8 6h.01"></path>
                                    <path d="M16 6h.01"></path>
                                    <path d="M12 6h.01"></path>
                                    <path d="M12 10h.01"></path>
                                    <path d="M12 14h.01"></path>
                                    <path d="M16 10h.01"></path>
                                    <path d="M16 14h.01"></path>
                                    <path d="M8 10h.01"></path>
                                    <path d="M8 14h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h3>Hoteles y Resorts</h3>
                                <p>100+ habitaciones</p>
                                <p><strong>Demanda:</strong> Muy alta</p>
                            </div>
                        </article>

                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3>Hospitales</h3>
                                <p>Operacion critica 24/7</p>
                                <p><strong>Demanda:</strong> Continua</p>
                            </div>
                        </article>

                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                    </path>
                                    <path
                                        d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3>Gimnasios y Spas</h3>
                                <p>50+ regaderas</p>
                                <p><strong>Demanda:</strong> Picos altos</p>
                            </div>
                        </article>

                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path d="M12 9a4 4 0 0 0-2 7.5"></path>
                                    <path d="M12 3v2"></path>
                                    <path d="m6.6 18.4-1.4 1.4"></path>
                                    <path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                    <path d="M4 13H2"></path>
                                    <path d="M6.34 7.34 4.93 5.93"></path>
                                </svg>
                            </div>
                            <div>
                                <h3>Restaurantes</h3>
                                <p>Cocina industrial</p>
                                <p><strong>Demanda:</strong> Alta constante</p>
                            </div>
                        </article>

                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path
                                        d="M22 7.7c0-.6-.4-1.2-.8-1.5l-6.3-3.9a1.72 1.72 0 0 0-1.7 0l-10.3 6c-.5.2-.9.8-.9 1.4v6.6c0 .5.4 1.2.8 1.5l6.3 3.9a1.72 1.72 0 0 0 1.7 0l10.3-6c.5-.3.9-1 .9-1.5Z">
                                    </path>
                                    <path d="M10 21.9V14L2.1 9.1"></path>
                                    <path d="m10 14 11.9-6.9"></path>
                                    <path d="M14 19.8v-8.1"></path>
                                    <path d="M18 17.5V9.4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3>Lavanderias</h3>
                                <p>Equipos multiples</p>
                                <p><strong>Demanda:</strong> Intensiva</p>
                            </div>
                        </article>

                        <article class="st-app-card">
                            <div class="st-app-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <rect width="16" height="20" x="4" y="2" rx="2" ry="2">
                                    </rect>
                                    <path d="M9 22v-4h6v4"></path>
                                    <path d="M8 6h.01"></path>
                                    <path d="M16 6h.01"></path>
                                    <path d="M12 6h.01"></path>
                                    <path d="M12 10h.01"></path>
                                    <path d="M12 14h.01"></path>
                                    <path d="M16 10h.01"></path>
                                    <path d="M16 14h.01"></path>
                                    <path d="M8 10h.01"></path>
                                    <path d="M8 14h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h3>Edificios Corporativos</h3>
                                <p>Oficinas multi-piso</p>
                                <p><strong>Demanda:</strong> Media-alta</p>
                            </div>
                        </article>
                    </div>
                </article>

                <article class="st-panel">
                    <h2>Especificaciones Tecnicas</h2>

                    <div class="st-spec-card">
                        <h3><span>1</span>Construccion</h3>
                        <div class="st-spec-grid">
                            <p><small>Material del tanque</small><strong>Acero vitrificado grado comercial</strong></p>
                            <p><small>Aislamiento</small><strong>Espuma poliuretano alta densidad (50 mm)</strong></p>
                            <p><small>Recubrimiento</small><strong>Esmalte vitreo horneado 850C</strong></p>
                            <p><small>Anodo</small><strong>Magnesio de larga duracion</strong></p>
                        </div>
                    </div>
                    <div class="st-spec-card">
                        <h3><span>2</span>Rendimiento</h3>
                        <div class="st-spec-grid">
                            <p><small>Capacidades</small><strong>300L - 500L - 750L - 1000L</strong></p>
                            <p><small>Temperatura maxima</small><strong>80C constante</strong></p>
                            <p><small>Perdida termica</small><strong>&lt;2C por 24 horas</strong></p>
                            <p><small>Tiempo de recuperacion</small><strong>45 a 90 minutos</strong></p>
                        </div>
                    </div>
                    <div class="st-spec-card">
                        <h3><span>3</span>Seguridad</h3>
                        <div class="st-spec-grid">
                            <p><small>Valvula de alivio</small><strong>T&amp;P certificada 150 PSI</strong></p>
                            <p><small>Termostato dual</small><strong>Control y seguridad</strong></p>
                            <p><small>Presion maxima</small><strong>150 PSI (10.3 bar)</strong></p>
                            <p><small>Certificaciones</small><strong>UL - CSA - NSF</strong></p>
                        </div>
                    </div>
                </article>

                <article class="st-hybrid-card">
                    <div class="st-hybrid-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path
                                d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                            </path>
                        </svg>
                    </div>
                    <div class="st-hybrid-content">
                        <h3>Sistema Hibrido Inteligente</h3>
                        <p>
                            Combina tanque de almacenamiento + calentador tankless Rinnai para maxima eficiencia.
                            El tanque provee respaldo constante mientras el tankless cubre picos de demanda extremos.
                        </p>
                        <div class="st-pill-grid">
                            <div><strong>35% vs solo tanque</strong><span>Ahorro</span></div>
                            <div><strong>Virtualmente infinita</strong><span>Capacidad</span></div>
                            <div><strong>Redundancia total</strong><span>Confiabilidad</span></div>
                        </div>
                    </div>
                </article>
                <article class="st-panel">
                    <h2>Elige tu Capacidad</h2>
                    <div class="st-table-wrap">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th>Capacidad</th>
                                    <th>Usuarios simultaneos</th>
                                    <th>Tiempo recuperacion</th>
                                    <th>Aplicacion ideal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>300L</strong></td>
                                    <td>15-25</td>
                                    <td>45 min</td>
                                    <td>Gimnasio pequeno</td>
                                </tr>
                                <tr>
                                    <td><strong>500L</strong></td>
                                    <td>30-50</td>
                                    <td>60 min</td>
                                    <td>Restaurante mediano</td>
                                </tr>
                                <tr>
                                    <td><strong>750L</strong></td>
                                    <td>50-75</td>
                                    <td>75 min</td>
                                    <td>Hotel boutique</td>
                                </tr>
                                <tr>
                                    <td><strong>1000L</strong></td>
                                    <td>75-100+</td>
                                    <td>90 min</td>
                                    <td>Hospital u hotel grande</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="st-cta">
        <div class="container st-cta-content">
            <h2>Soluciones Comerciales a la <span class="st-accent">Medida</span></h2>
            <p>
                Cada proyecto es unico. Nuestro equipo de ingenieros disenara el sistema perfecto para tu aplicacion
                especifica.
            </p>
            <div class="st-cta-buttons">
                <a
                href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
                target="_blank"
                aria-label="Abrir chat de WhatsApp"
                class="button-primary st-cta-button">
                    Solicitar Diseño de Ingenieria
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none">
                        <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <a
                href="tel:+524494348018"
                aria-label="Llamar a Equiterm Industries"
                class="button-secondary st-cta-button"><svg
                        xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>Hablar con Ingeniero</a>
            </div>
            <article class="st-testimonial">
                <p class="st-stars">★★★★★</p>
                <p class="st-quote">
                    "Instalamos 4 tanques Rinnai de 750L en nuestro hotel. Excelente rendimiento, cero problemas en 3 anos,
                    y el ahorro energetico fue notable desde el primer mes."
                </p>
                <p class="st-author">Director de Operaciones, Hotel Real de Aguascalientes</p>
            </article>
        </div>
    </section>
@endsection
