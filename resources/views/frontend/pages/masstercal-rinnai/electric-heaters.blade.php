@extends('frontend.layouts.master')
@section('title', 'Calentadores Eléctricos Rinnai - Industria Simari')
@section('description', 'Calentadores Electricos Rinnai: Agua caliente instantánea sin gas. Tecnología eléctrica de alta
    eficiencia para máxima seguridad y comodidad en tu hogar.')
@section('canonical', config('app.url') . '/masstercal-rinnai/calentadores-electricos-rinnai')
@section('content')
    <section class="re-hero" aria-labelledby="re-hero-title">
        <div class="re-hero__inner">
            <div class="re-hero__grid">

                <!-- Columna izquierda: texto -->
                <div>
                    <!-- Logo alineado a la izquierda -->
                    <div class="re-hero__logo">
                        <img src="{{ asset('images/masstercal-rinnai/rinnai-logo.png') }}" 
                          alt="Rinnai Logo" 
                          title="Rinnai" 
                          width="200"
                          height="60" 
                          loading="eager" 
                          fetchpriority="high" 
                          decoding="async">
                    </div>

                    <div class="re-hero__badge">Electricidad Eficiente</div>

                    <h1 id="re-hero-title" class="re-hero__title">
                        Calentadores<br>
                        <span class="re-accent">Eléctricos</span><br>
                        <span class="re-sub">Rinnai</span>
                    </h1>

                    <p class="re-hero__desc">
                        Agua caliente instantánea sin gas. Tecnología eléctrica de alta eficiencia para máxima seguridad y
                        comodidad en tu hogar.
                    </p>

                    <!-- Stats 3 columnas -->
                    <div class="re-hero__stats" role="list" aria-label="Estadísticas clave">
                        <div class="re-stat" role="listitem">
                            <div class="re-stat__num">98%</div>
                            <div class="re-stat__label">Eficiencia</div>
                        </div>
                        <div class="re-stat" role="listitem">
                            <div class="re-stat__num">0</div>
                            <div class="re-stat__label">Emisiones CO₂</div>
                        </div>
                        <div class="re-stat" role="listitem">
                            <div class="re-stat__num">24/7</div>
                            <div class="re-stat__label">Disponible</div>
                        </div>
                    </div>

                    <!-- CTAs -->
                    <nav class="re-hero__btns" aria-label="Acciones principales">
                        <a href="/contacto" class="re-btn-primary" rel="noopener">
                            Cotizar Ahora
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="tel:+524494348018" class="re-btn-outline-rinnai">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            Llamar
                        </a>
                    </nav>
                </div>

                <!-- Columna derecha: imagen con overlay y badge circular -->
                <div class="re-hero__img-wrap" aria-hidden="true">
                    <div class="re-hero__img-frame">
                        <img src="{{ asset('images/masstercal-rinnai/productos-rinnai/calentamiento-electrico/calentador-electrico-rinnai.webp') }}"
                            alt="Calentador Eléctrico Rinnai" title="Calentador Eléctrico Rinnai" width="1200"
                            height="1200" loading="eager" fetchpriority="high" decoding="async" />
                        <!-- Overlay con 3 stats -->
                        <div class="re-hero__img-overlay">
                            <div class="re-img-stats">
                                <div class="re-img-stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
                                    </svg>
                                    <div class="re-img-stat__label">Potencia</div>
                                    <div class="re-img-stat__val">1.5-3.5kW</div>
                                </div>
                                <div class="re-img-stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z" />
                                        <path
                                            d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97" />
                                    </svg>
                                    <div class="re-img-stat__label">Capacidad</div>
                                    <div class="re-img-stat__val">30-80L</div>
                                </div>
                                <div class="re-img-stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                    </svg>
                                    <div class="re-img-stat__label">Garantía</div>
                                    <div class="re-img-stat__val">Hasta 7 años</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Badge circular rotado -->
                    <div class="re-circle-badge" role="img" aria-label="100% Eléctrico">
                        <div class="re-circle-badge__num">100%</div>
                        <div class="re-circle-badge__label">Eléctrico</div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════════
         PRICING – 3 CARDS
         React: section class="py-24 bg-white"
    ════════════════════════════════════════════════════════ -->
    <section class="re-pricing" id="modelos" aria-labelledby="re-pricing-title">
        <div class="re-pricing__inner">

            <div class="re-pricing__header">
                <h2 id="re-pricing-title" class="re-pricing__title">Elige tu Modelo Ideal</h2>
                <p class="re-pricing__sub">Planes diseñados para cada necesidad y presupuesto</p>
            </div>

            <div class="re-pricing__grid">

                <!-- ── BÁSICO ── -->
                <article class="re-price-card" aria-labelledby="plan-basico">
                    <div style="text-align:center; margin-bottom:28px;">
                        <h3 id="plan-basico" class="re-price-card__name">Básico</h3>
                        <div class="re-price-card__spec">30L • 1.5kW</div>
                        <div class="re-price-card__price">$6,900 <span>MXN</span></div>
                    </div>
                    <ul class="re-price-card__list" aria-label="Características del plan Básico">
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Ideal para 1-2 personas</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Termostato ajustable</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Protección anti-quemaduras</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Garantía 3 años</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Instalación incluida</li>
                    </ul>
                    <a href="/contacto" class="re-price-card__btn re-price-card__btn--dark" rel="noopener">Solicitar
                        Básico</a>
                </article>

                <!-- ── PREMIUM (destacado) ── -->
                <article class="re-price-card re-price-card--featured" aria-labelledby="plan-premium">
                    <div style="text-align:center; margin-bottom:20px;">
                        <div class="re-badge-best">Más Vendido</div>
                        <h3 id="plan-premium" class="re-price-card__name">Premium</h3>
                        <div class="re-price-card__spec">50L • 2.5kW</div>
                        <div class="re-price-card__price">$10,500 <span>MXN</span></div>
                    </div>
                    <ul class="re-price-card__list" aria-label="Características del plan Premium">
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Perfecto para 3-4 personas</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Control digital LCD</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Doble termostato</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Ánodo de magnesio</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Garantía 5 años</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Mantenimiento gratis 1 año</li>
                    </ul>
                    <a href="/contacto" class="re-price-card__btn re-price-card__btn--light" rel="noopener">Solicitar
                        Premium</a>
                </article>

                <!-- ── ELITE ── -->
                <article class="re-price-card" aria-labelledby="plan-elite">
                    <div style="text-align:center; margin-bottom:28px;">
                        <h3 id="plan-elite" class="re-price-card__name">Elite</h3>
                        <div class="re-price-card__spec">80L • 3.5kW</div>
                        <div class="re-price-card__price">$15,900 <span>MXN</span></div>
                    </div>
                    <ul class="re-price-card__list" aria-label="Características del plan Elite">
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Para familias grandes 5-6 personas</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Wi-Fi + App móvil</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Autodiagnóstico inteligente</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Aislamiento premium</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Garantía 7 años</li>
                        <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>Servicio prioritario</li>
                    </ul>
                    <a href="/contacto" class="re-price-card__btn re-price-card__btn--dark" rel="noopener">Solicitar
                        Elite</a>
                </article>

            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════════
         SUSTENTABILIDAD
         React: section class="py-20 bg-gradient-to-br from-green-50 to-blue-50"
    ════════════════════════════════════════════════════════ -->
    <section class="re-sustain" id="sustentabilidad" aria-labelledby="re-sustain-title">
        <div class="re-sustain__inner">

            <div class="re-sustain__header">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                    <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                </svg>
                <h2 id="re-sustain-title" class="re-sustain__title">Tecnología Sustentable</h2>
                <p class="re-sustain__sub">Cuida el planeta mientras disfrutas agua caliente</p>
            </div>

            <div class="re-sustain__grid">

                <article class="re-sustain-card" aria-labelledby="sc-emisiones">
                    <div class="re-sustain-card__body">
                        <div class="re-sustain-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
                            </svg>
                        </div>
                        <div class="re-sustain-card__text">
                            <div class="re-sustain-card__top">
                                <h3 id="sc-emisiones" class="re-sustain-card__title">Cero Emisiones</h3>
                                <span class="re-sustain-card__pill">0 kg CO₂</span>
                            </div>
                            <p class="re-sustain-card__desc">Al ser 100% eléctrico, no genera gases contaminantes ni
                                requiere ventilación especial. Perfecto para espacios cerrados.</p>
                        </div>
                    </div>
                </article>

                <article class="re-sustain-card" aria-labelledby="sc-eficiencia">
                    <div class="re-sustain-card__body">
                        <div class="re-sustain-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                <polyline points="16 7 22 7 22 13" />
                            </svg>
                        </div>
                        <div class="re-sustain-card__text">
                            <div class="re-sustain-card__top">
                                <h3 id="sc-eficiencia" class="re-sustain-card__title">Eficiencia Superior</h3>
                                <span class="re-sustain-card__pill">98% eficaz</span>
                            </div>
                            <p class="re-sustain-card__desc">Convierte el 98% de la energía en calor útil, superando a
                                sistemas tradicionales de gas o combustible.</p>
                        </div>
                    </div>
                </article>

                <article class="re-sustain-card" aria-labelledby="sc-solar">
                    <div class="re-sustain-card__body">
                        <div class="re-sustain-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
                            </svg>
                        </div>
                        <div class="re-sustain-card__text">
                            <div class="re-sustain-card__top">
                                <h3 id="sc-solar" class="re-sustain-card__title">Energía Renovable</h3>
                                <span class="re-sustain-card__pill">100% solar</span>
                            </div>
                            <p class="re-sustain-card__desc">Compatible con paneles solares. Instala tu sistema
                                fotovoltaico y obtén agua caliente con energía 100% limpia.</p>
                        </div>
                    </div>
                </article>

                <article class="re-sustain-card" aria-labelledby="sc-cert">
                    <div class="re-sustain-card__body">
                        <div class="re-sustain-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526" />
                                <circle cx="12" cy="8" r="6" />
                            </svg>
                        </div>
                        <div class="re-sustain-card__text">
                            <div class="re-sustain-card__top">
                                <h3 id="sc-cert" class="re-sustain-card__title">Certificación Ambiental</h3>
                                <span class="re-sustain-card__pill">ISO 14001</span>
                            </div>
                            <p class="re-sustain-card__desc">Cumple con las normas internacionales más estrictas de
                                eficiencia energética y cuidado ambiental.</p>
                        </div>
                    </div>
                </article>

            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════════
         CTA NEGRO FINAL
         React: section class="py-24 bg-black text-white"
    ════════════════════════════════════════════════════════ -->
    <section class="re-cta-black" id="contacto" aria-labelledby="re-cta-title">
        <div class="re-cta-black__inner">

            <h2 id="re-cta-title">
                Cambia a Tecnología<br>
                <span>100% Eléctrica</span>
            </h2>

            <p>Sin gas, sin riesgos, sin emisiones. Solo agua caliente eficiente y confiable.</p>

            <nav class="re-cta-black__btns" aria-label="Opciones de contacto">
                <a href="/contacto" class="re-btn-cta-red" rel="noopener">
                    Solicitar Cotización Personalizada
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </a>
                <a href="tel:+524494348018" class="re-btn-cta-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg>
                    449 434 8018
                </a>
            </nav>

        </div>
    </section>
@endsection
