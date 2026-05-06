@extends('frontend.layouts.master')

@section('title', 'Servicio de Calderas en México | Instalación, Mantenimiento y Reparación')
@section('description', 'Especialistas certificados en calderas industriales y residenciales. Instalación, mantenimiento preventivo y reparación en todo México. Diagnóstico gratuito. Respuesta en menos de 24 horas.')
@section('canonical', config('app.url') . '/servicios-calderas')
@section('og_title', 'Servicio de Calderas en México | Instalación, Mantenimiento y Reparación')
@section('og_description', 'Especialistas certificados en calderas industriales y residenciales. Instalación, mantenimiento preventivo y reparación en todo México. Diagnóstico gratuito. Respuesta en menos de 24 horas.')
@section('og_url', config('app.url') . '/servicios-calderas')
@section('og_image', config('app.url') . '/images/og-boilerservices.jpg')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": ["LocalBusiness", "HomeAndConstructionBusiness"],
  "name": "Equiterm Industries",
  "description": "Especialistas en instalación, mantenimiento y reparación de calderas industriales y residenciales en México.",
  "telephone": "+52-449-434-8018",
  "url": "{{ config('app.url') . '/servicios-calderas' }}",
  "areaServed": ["Aguascalientes", "Monterrey", "México"],
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Servicios de Calderas",
    "itemListElement": [
      {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Mantenimiento preventivo de calderas"}},
      {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Instalación de calderas"}},
      {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Reparación de calderas"}},
      {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Contratos anuales de mantenimiento"}}
    ]
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cada cuánto tiempo se debe dar mantenimiento a una caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El mantenimiento preventivo debe realizarse al menos una vez al año en equipos residenciales y cada 6 meses en calderas industriales con operación continua. La frecuencia depende del tipo de combustible, horas de operación y condiciones del agua."
      }
    },
    {
      "@type": "Question",
      "name": "¿Cuánto cuesta el servicio de calderas en México?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El costo varía según el tipo de caldera, el servicio requerido y la ciudad. Solicita un diagnóstico gratuito para obtener una cotización exacta sin compromiso."
      }
    },
    {
      "@type": "Question",
      "name": "¿Atienden emergencias fuera de horario de oficina?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Contamos con servicio de atención a emergencias las 24 horas en las ciudades donde operamos. Una caldera fuera de servicio en una planta industrial puede significar pérdidas por hora, por lo que priorizamos estos casos."
      }
    },
    {
      "@type": "Question",
      "name": "¿Trabajan con calderas de gas y de vapor?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Trabajamos con todos los tipos: calderas de gas LP y gas natural, calderas de vapor industrial, calderas de agua caliente, calderas pirotubulares y acuotubulares. Nuestros técnicos están certificados para cada tipo de equipo."
      }
    },
    {
      "@type": "Question",
      "name": "¿Ofrecen contratos anuales de mantenimiento?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Nuestros contratos de mantenimiento anual incluyen visitas programadas, reportes técnicos, prioridad en emergencias y precio fijo durante la vigencia del contrato. Ideal para empresas con calderas en operación continua."
      }
    }
  ]
}
</script>
@endsection

@section('content')

    <!-- =========================================================
         1. HERO
         ========================================================= -->
    <section id="inicio" class="hero-section-hair-repair">
        <div class="hero-background">
            {{-- TODO: <img src="{{ asset('images/services/servicio-calderas-mexico.jpg') }}" alt="Técnico certificado revisando caldera industrial en México" width="1200" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">

                <div class="badge-home">
                    <span class="dot-home" aria-hidden="true"></span>
                    <p class="badge-text-home">Especialistas certificados · México</p>
                </div>

                <h1 class="hero-title">Servicio de Calderas en México</h1>

                <p class="hero-description">
                    Instalación, mantenimiento preventivo y reparación de calderas industriales
                    y residenciales. Más de 35 años de experiencia, atención en todo el país.
                </p>

                <div class="hero-actions">
                    <a
                        href="#contacto"
                        class="button-primary"
                        aria-label="Solicitar diagnóstico gratuito de caldera">
                        Solicitar diagnóstico gratuito
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </a>
                    <a
                        href="#servicios"
                        class="button-secondary"
                        aria-label="Ver los servicios de calderas de Equiterm Industries">
                        Ver nuestros servicios ↓
                    </a>
                </div>

                {{-- CLASE SUGERIDA: .hero-trust-grid, .hero-trust-item, .hero-trust-text --}}
                <div class="hero-trust-grid">
                    <div class="hero-trust-item">
                        <span aria-hidden="true">✓</span>
                        <span class="hero-trust-text">Técnicos certificados en calderas a presión</span>
                    </div>
                    <div class="hero-trust-item">
                        <span aria-hidden="true">⚡</span>
                        <span class="hero-trust-text">Respuesta en menos de 24 horas</span>
                    </div>
                    <div class="hero-trust-item">
                        <span aria-hidden="true">📍</span>
                        <span class="hero-trust-text">Cobertura en Aguascalientes · Monterrey y más</span>
                    </div>
                    <div class="hero-trust-item">
                        <span aria-hidden="true">🛡️</span>
                        <span class="hero-trust-text">Garantía en todos los servicios</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         2. NOSOTROS
         ========================================================= -->
    <section id="nosotros" class="service-description-section-industrial">
        <div class="container service-description-section">

            <h2 class="service-subtitle-industrial">Especialistas en calderas industriales y residenciales</h2>

            <p class="service-text-industrial">
                El servicio de calderas requiere técnicos certificados con experiencia real en campo.
                En Equiterm Industries ofrecemos soluciones completas: desde la instalación de equipos
                nuevos hasta el mantenimiento preventivo que alarga la vida útil de tu caldera
                y reduce el consumo de energía.
            </p>

            <p class="service-text-industrial">
                Trabajamos con calderas de gas, vapor, agua caliente y biomasa, tanto en entornos
                residenciales como en plantas industriales y procesos de manufactura. Nuestro equipo
                responde en menos de 24 horas en las ciudades donde operamos.
            </p>

        </div>
    </section>

    <!-- =========================================================
         3. SERVICIOS
         ========================================================= -->
    <section id="servicios" class="what-includes-section">
        <div class="container what-includes-section">

            <p class="section-subtitle-home">¿Qué hacemos?</p>
            <h2 class="section-main-title">Nuestros servicios de calderas</h2>

            <div class="includes-grid">

                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">🔧</div>
                    <h3 class="include-card-title">Mantenimiento preventivo</h3>
                    <p class="include-card-text">
                        Revisión completa de quemadores, válvulas, presión y eficiencia.
                        Evita fallas costosas y cumple con normativa vigente.
                    </p>
                </div>

                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">⚙️</div>
                    <h3 class="include-card-title">Instalación de calderas</h3>
                    <p class="include-card-text">
                        Proyecto, suministro e instalación de calderas nuevas con pruebas
                        de arranque y capacitación al personal.
                    </p>
                </div>

                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">🔥</div>
                    <h3 class="include-card-title">Reparación de calderas</h3>
                    <p class="include-card-text">
                        Diagnóstico y reparación de fallas en quemadores, tubería, controles
                        y sistemas de seguridad.
                    </p>
                </div>

                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">📋</div>
                    <h3 class="include-card-title">Contratos de servicio</h3>
                    <p class="include-card-text">
                        Planes anuales de mantenimiento con visitas programadas, prioridad
                        en emergencias y precios fijos.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         4. COBERTURA
         ========================================================= -->
    <section id="cobertura" class="service-description-section-industrial">
        <div class="container service-description-section">

            <p class="section-subtitle-home">Cobertura nacional</p>
            <h2 class="section-main-title">¿Dónde damos servicio?</h2>

            <p class="service-text-industrial">
                Contamos con técnicos en las siguientes ciudades. Selecciona tu ciudad
                para ver información específica de cobertura, tiempos de respuesta
                y precios en tu zona.
            </p>

            {{-- CLASE SUGERIDA: .coverage-grid, .coverage-card, .coverage-card--disabled --}}
            <div class="coverage-grid">

                <a
                    href="/calderas-aguascalientes/"
                    class="coverage-card"
                    aria-label="Servicio de calderas en Aguascalientes — ver cobertura y precios">
                    <span aria-hidden="true">📍</span>
                    Servicio de calderas en Aguascalientes
                </a>

                <a
                    href="/calderas-monterrey/"
                    class="coverage-card"
                    aria-label="Servicio de calderas en Monterrey — ver cobertura y precios">
                    <span aria-hidden="true">📍</span>
                    Servicio de calderas en Monterrey
                </a>

                <div class="coverage-card coverage-card--disabled" aria-disabled="true">
                    <span aria-hidden="true">🔜</span>
                    Próximamente
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         5. STATS / NÚMEROS
         ========================================================= -->
    <section id="por-que-elegirnos" class="why-choose-simari-section">
        <div class="container why-choose-simari-section">

            <h2 class="section-main-title">Números que nos respaldan</h2>

            {{-- CLASE SUGERIDA: .simari-stats-grid--4col (variante de 4 columnas) --}}
            <div class="simari-stats-grid">

                <div class="simari-stat-item">
                    <div class="stat-big-number">35+</div>
                    <h3 class="stat-small-title">Años de experiencia</h3>
                    <p class="stat-small-desc">
                        En instalación y mantenimiento de calderas industriales
                        y residenciales en México.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">500+</div>
                    <h3 class="stat-small-title">Empresas atendidas</h3>
                    <p class="stat-small-desc">
                        Desde PyMEs hasta plantas de manufactura con demanda
                        continua de vapor.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">&lt;24h</div>
                    <h3 class="stat-small-title">Tiempo de respuesta</h3>
                    <p class="stat-small-desc">
                        En ciudades donde operamos, garantizamos atención
                        en menos de 24 horas.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">100%</div>
                    <h3 class="stat-small-title">Técnicos certificados</h3>
                    <p class="stat-small-desc">
                        Personal con certificación en manejo de equipos a presión
                        y normativa vigente.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================
         6. FAQ
         ========================================================= -->
    <section id="faq" class="service-description-section-industrial">
        <div class="container service-description-section">

            <p class="section-subtitle-home">Preguntas frecuentes</p>
            <h2 class="section-main-title">FAQ · Servicio de calderas</h2>

            {{-- CLASE SUGERIDA: .faq-list, .faq-item, .faq-summary, .faq-body --}}
            <div class="faq-list">

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Cada cuánto tiempo se debe dar mantenimiento a una caldera?
                    </summary>
                    <div class="faq-body">
                        <p>El mantenimiento preventivo debe realizarse al menos una vez al año
                        en equipos residenciales y cada 6 meses en calderas industriales con
                        operación continua. La frecuencia depende del tipo de combustible,
                        horas de operación y condiciones del agua.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Cuánto cuesta el servicio de calderas en México?
                    </summary>
                    <div class="faq-body">
                        <p>El costo varía según el tipo de caldera, el servicio requerido y la ciudad.
                        Solicita un diagnóstico gratuito para obtener una cotización exacta
                        sin compromiso.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Atienden emergencias fuera de horario de oficina?
                    </summary>
                    <div class="faq-body">
                        <p>Sí. Contamos con servicio de atención a emergencias las 24 horas en las
                        ciudades donde operamos. Una caldera fuera de servicio en una planta
                        industrial puede significar pérdidas por hora, por lo que priorizamos
                        estos casos.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Trabajan con calderas de gas y de vapor?
                    </summary>
                    <div class="faq-body">
                        <p>Trabajamos con todos los tipos: calderas de gas LP y gas natural, calderas
                        de vapor industrial, calderas de agua caliente, calderas pirotubulares y
                        acuotubulares. Nuestros técnicos están certificados para cada tipo de equipo.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Ofrecen contratos anuales de mantenimiento?
                    </summary>
                    <div class="faq-body">
                        <p>Sí. Nuestros contratos de mantenimiento anual incluyen visitas programadas,
                        reportes técnicos, prioridad en emergencias y precio fijo durante la vigencia
                        del contrato. Ideal para empresas con calderas en operación continua.</p>
                    </div>
                </details>

            </div>
        </div>
    </section>

    <!-- =========================================================
         7. CTA FINAL
         ========================================================= -->
    <section id="contacto" class="work-process-section">
        <div class="container work-process-section">

            <h2 class="cta-final-title-industrial">¿Necesitas servicio de calderas?</h2>
            <p class="cta-final-description-industrial">
                Solicita tu diagnóstico gratuito. Te respondemos en menos de 24 horas.
            </p>

            <div class="cta-final-actions">
                <a
                    href="tel:524494348018"
                    class="button-primary work-process"
                    aria-label="Llamar para solicitar servicio de calderas">
                    Llamar ahora: +52 449 434 8018
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-left: 8px;" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </a>

                <a
                    href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n%20de%20servicio%20de%20calderas."
                    target="_blank"
                    rel="noopener noreferrer"
                    class="button-secondary work-process"
                    aria-label="Solicitar cotización de servicio de calderas por WhatsApp">
                    Solicitar cotización por WhatsApp
                </a>
            </div>

        </div>
    </section>

@endsection
