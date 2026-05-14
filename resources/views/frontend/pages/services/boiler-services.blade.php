@extends('frontend.layouts.master')

@section('title', 'Servicios de Calderas Industriales | Equiterm Industries')
@section('description', 'Servicios de calderas industriales: mantenimiento, reparación y desincrustación. Técnicos certificados NOM-020-STPS. Diagnóstico inicial sin costo.')
@section('canonical', config('app.url') . '/servicios/calderas')
@section('keywords', 'servicios de calderas, servicio de calderas, servicio de calderas industriales México, empresa servicio calderas, proveedor mantenimiento calderas')
@section('og_title', 'Servicios de Calderas Industriales | Equiterm Industries')
@section('og_description', 'Servicios de calderas industriales: mantenimiento, reparación y desincrustación. Técnicos certificados NOM-020-STPS. Diagnóstico inicial sin costo.')
@section('og_url', config('app.url') . '/servicios/calderas')
@section('og_image', config('app.url') . '/images/og-servicios-calderas.jpg')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["LocalBusiness", "HomeAndConstructionBusiness"],
      "name": "Equiterm Industries",
      "description": "Servicios de calderas industriales: mantenimiento, reparación y desincrustación. Técnicos certificados NOM-020-STPS. Diagnóstico inicial sin costo.",
      "telephone": "+52-449-434-8018",
      "url": "{{ config('app.url') . '/servicios/calderas' }}",
      "areaServed": "México",
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Servicios de Calderas Industriales",
        "itemListElement": [
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Mantenimiento preventivo y correctivo de calderas" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Reparación de calderas industriales" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Desincrustación y limpieza química de calderas" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Diagnóstico técnico y evaluación de estado" } }
        ]
      }
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Inicio",    "item": "{{ config('app.url') . '/' }}" },
        { "@type": "ListItem", "position": 2, "name": "Servicios", "item": "{{ config('app.url') . '/servicios/' }}" },
        { "@type": "ListItem", "position": 3, "name": "Calderas",  "item": "{{ config('app.url') . '/servicios/calderas/' }}" }
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "¿Qué incluye un servicio de calderas industriales?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Depende del tipo de intervención. El mantenimiento incluye inspección, limpieza de hogar, calibración de quemadores, análisis de agua y prueba de válvulas de seguridad. La reparación incluye diagnóstico en sitio, cotización previa e intervención certificada. Todos los servicios incluyen reporte técnico firmado y registro en bitácora del equipo."
          }
        },
        {
          "@type": "Question",
          "name": "¿Con qué frecuencia se debe dar servicio a una caldera?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Las calderas industriales en operación continua requieren revisión operativa mensual por personal capacitado, mantenimiento semestral por técnico especializado y mantenimiento anual completo con inspección normativa. La NOM-020-STPS exige dictamen de operación segura vigente para que el equipo opere legalmente."
          }
        },
        {
          "@type": "Question",
          "name": "¿Cuánto cuesta el servicio de una caldera industrial?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "El costo depende del tipo de servicio, la capacidad del equipo y su estado actual. Un mantenimiento semestral para una caldera de mediana capacidad oscila entre $18,000 y $45,000 MXN. El diagnóstico inicial es gratuito y permite elaborar una cotización exacta sin estimados genéricos."
          }
        },
        {
          "@type": "Question",
          "name": "¿Ofrecen contratos de servicio anuales de calderas?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Sí. Los contratos anuales incluyen visitas programadas con frecuencia definida, precio fijo por intervención, atención preferencial en emergencias con SLA garantizado por escrito y bitácora técnica acumulada del equipo. Ideal para plantas en operación continua que no pueden permitirse tiempos de paro no programados."
          }
        },
        {
          "@type": "Question",
          "name": "¿Qué empresa da servicio a calderas industriales en México?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Equiterm Industries es una empresa especializada en servicios de calderas industriales con cobertura nacional, más de 15 años de experiencia en el sector B2B, técnicos certificados NOM-020-STPS y ASME, y garantía por escrito en cada intervención. Atendemos calderas pirotubulares, acuotubulares, de fluido térmico y de vapor saturado en los principales corredores industriales del país."
          }
        }
      ]
    }
  ]
}
</script>
@endsection


@section('content')

    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 1 — HERO
    ═══════════════════════════════════════════════════════ --}}
    <section id="inicio" class="hero-section-hair-repair" aria-labelledby="hero-heading">
        <div class="hero-background">
            {{-- TODO: <img
                src="{{ asset('images/services/servicios-calderas/servicios-calderas-industriales-equiterm.jpg') }}"
                alt="Técnico de Equiterm Industries realizando servicio de caldera industrial en planta manufacturera en México"
                width="1440" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
            <div class="hero-overlay"></div>
        </div>

        <div class="container hero-hair-repair">
            <div class="hero-content">

                <div class="badge-home">
                    <span class="dot-home" aria-hidden="true"></span>
                    <p class="badge-text-home">Especialistas certificados · NOM-020-STPS · México</p>
                </div>

                <h1 id="hero-heading" class="hero-title">
                    Servicios de Calderas Industriales en México — Equiterm Industries
                </h1>

                <p class="hero-description">
                    El servicio de calderas industriales requiere respaldo documentado, no solo experiencia.
                    En Equiterm llevamos más de 15 años brindando mantenimiento preventivo y correctivo,
                    reparación, desincrustación y diagnóstico técnico a plantas de todos los sectores —
                    con técnicos certificados NOM-020-STPS y cobertura nacional.
                </p>

                <div class="hero-actions">
                    <a href="#cotizacion" class="button-primary" aria-label="Solicitar cotización gratuita de servicio de calderas">
                        Solicitar cotización gratuita
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left:8px" aria-hidden="true">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="#servicios" class="button-secondary" aria-label="Ver servicios de calderas industriales de Equiterm">
                        Ver nuestros servicios ↓
                    </a>
                </div>

                <div class="hero-trust-grid">
                    <div class="hero-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span class="hero-trust-text">Técnicos certificados NOM-020-STPS y ASME</span>
                    </div>
                    <div class="hero-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span class="hero-trust-text">Respuesta en menos de 24 horas</span>
                    </div>
                    <div class="hero-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m16 13-3.5 3.5-2-2L9 16"/></svg>
                        <span class="hero-trust-text">Garantía por escrito en cada intervención</span>
                    </div>
                    <div class="hero-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span class="hero-trust-text">Cobertura nacional — Bajío, norte, centro y sureste</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ── Franja de stats ── --}}
    <div id="stats" class="bs-stat-strip" aria-label="Cifras clave de Equiterm Industries">
        <div class="bs-stat-strip__inner">
            <div class="bs-stat-strip__item">
                <span class="bs-stat-strip__number">+500</span>
                <span class="bs-stat-strip__label">Plantas atendidas</span>
            </div>
            <div class="bs-stat-strip__item">
                <span class="bs-stat-strip__number">15+</span>
                <span class="bs-stat-strip__label">Años de experiencia</span>
            </div>
            <div class="bs-stat-strip__item">
                <span class="bs-stat-strip__number">Nacional</span>
                <span class="bs-stat-strip__label">Cobertura en México</span>
            </div>
            <div class="bs-stat-strip__item">
                <span class="bs-stat-strip__number">Sin costo</span>
                <span class="bs-stat-strip__label">Diagnóstico inicial</span>
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 2 — QUÉ SERVICIOS OFRECEMOS
    ═══════════════════════════════════════════════════════ --}}
    <section id="servicios" class="what-includes-section" aria-labelledby="servicios-heading">
        <div class="container what-includes-section">

            <p class="section-subtitle-home">¿Qué hacemos?</p>
            <h2 id="servicios-heading" class="section-main-title">
                ¿Qué servicios de calderas industriales ofrecemos?
            </h2>
            <p class="service-text-industrial" style="text-align:center;max-width:720px;margin:0 auto 40px;">
                Nuestro portafolio cubre el ciclo completo de atención a calderas industriales:
                desde el mantenimiento programado hasta la intervención de emergencia,
                la limpieza química y el diagnóstico técnico de precisión.
            </p>

            <div class="includes-grid">

                {{-- Card 1 — Mantenimiento --}}
                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    <h3 class="include-card-title">Mantenimiento preventivo y correctivo de calderas</h3>
                    <p class="include-card-text">
                        Inspección, limpieza de hogar, calibración de quemadores, análisis de agua
                        y prueba de válvulas de seguridad. Frecuencia semestral recomendada por
                        NOM-020-STPS. Reporte técnico firmado incluido en cada visita.
                    </p>
                    <a href="/servicios/calderas/mantenimiento-de-calderas/" style="font-size:13px;color:var(--secondary-color);text-decoration:none;font-weight:500;margin-top:8px;display:inline-block;">
                        Ver programa completo →
                    </a>
                </div>

                {{-- Card 2 — Reparación --}}
                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <h3 class="include-card-title">Reparación de calderas industriales</h3>
                    <p class="include-card-text">
                        Diagnóstico en sitio con instrumentación calibrada. Atendemos fallas en
                        quemadores, tubería, controles y sistemas de seguridad. Cotización previa
                        y garantía por escrito. Respuesta en menos de 24 horas.
                    </p>
                    <a href="/servicios/calderas/reparacion-de-calderas/" style="font-size:13px;color:var(--secondary-color);text-decoration:none;font-weight:500;margin-top:8px;display:inline-block;">
                        Solicitar diagnóstico →
                    </a>
                </div>

                {{-- Card 3 — Desincrustación --}}
                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                    </div>
                    <h3 class="include-card-title">Desincrustación y limpieza química de calderas</h3>
                    <p class="include-card-text">
                        La incrustación puede causar hasta 20% de pérdida de eficiencia térmica
                        y dañar la tubería. Aplicamos métodos químico y mecánico con análisis de
                        agua previo para seleccionar el proceso correcto sin dañar el equipo.
                    </p>
                    <a href="/servicios/calderas/desincrustacion-de-calderas/" style="font-size:13px;color:var(--secondary-color);text-decoration:none;font-weight:500;margin-top:8px;display:inline-block;">
                        Ver proceso →
                    </a>
                </div>

                {{-- Card 4 — Diagnóstico --}}
                <div class="include-card">
                    <div class="icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <h3 class="include-card-title">Diagnóstico técnico y evaluación de estado</h3>
                    <p class="include-card-text">
                        Termografía, ultrasonido, análisis de gases de combustión y medición de
                        eficiencia. Informe técnico escrito con hallazgos por nivel de prioridad.
                        Ideal para nuevos clientes que quieren saber exactamente en qué estado
                        está su equipo antes de comprometerse con un proveedor.
                    </p>
                </div>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 3 — POR QUÉ EQUITERM
    ═══════════════════════════════════════════════════════ --}}
    <section id="por-que" class="service-description-section-industrial" aria-labelledby="porque-heading">
        <div class="container service-description-section">

            <p class="section-subtitle-home">Nuestras credenciales</p>
            <h2 id="porque-heading" class="service-subtitle-industrial">
                ¿Por qué contratar el servicio de calderas con Equiterm?
            </h2>
            <p class="service-text-industrial">
                El mercado tiene muchas opciones. Lo que diferencia a Equiterm son los respaldos
                documentados, la cobertura real y el historial de intervenciones en plantas como la tuya.
            </p>

            <div class="benefits-grid">

                <div class="benefit-item">
                    <div class="benefit-icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Certificación NOM-020-STPS y códigos ASME</h3>
                        <p class="benefit-text">
                            Bitácora técnica válida para revisión de la STPS, dictamen de operación
                            segura vigente y reducción directa de tu exposición ante una inspección
                            del IMSS o autoridad laboral.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">15+ años de experiencia en servicio industrial B2B</h3>
                        <p class="benefit-text">
                            Cientos de plantas atendidas en los sectores alimentos, farmacéutico,
                            textil, papel y químico. Conocemos las marcas y configuraciones más comunes
                            del mercado mexicano sin necesidad de un periodo de adaptación.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Cobertura nacional — respuesta en menos de 24 horas</h3>
                        <p class="benefit-text">
                            Operamos en los corredores Bajío, norte, centro y sureste. Los contratos
                            incluyen SLA por escrito. Las visitas se coordinan con tu calendario de
                            producción para minimizar paros no planificados.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m16 13-3.5 3.5-2-2L9 16"/></svg>
                    </div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Garantía por escrito en cada intervención</h3>
                        <p class="benefit-text">
                            Carta firmada con alcance, vigencia y condiciones. Sin garantías verbales
                            ni promesas de palabra. Documentación lista para tu expediente de
                            proveedores y auditorías internas.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 4 — TIPOS DE CALDERA
    ═══════════════════════════════════════════════════════ --}}
    <section id="tipos-caldera" class="what-includes-section" aria-labelledby="tipos-heading">
        <div class="container what-includes-section">

            <h2 id="tipos-heading" class="section-main-title">Tipos de calderas que atendemos</h2>
            <p class="service-text-industrial" style="text-align:center;max-width:680px;margin:0 auto 8px;">
                Trabajamos con equipos de todas las tecnologías y capacidades. Si tu planta opera
                una caldera, es muy probable que ya hayamos atendido uno igual o similar.
            </p>

            <div class="bs-table-wrap">
                <table class="bs-boiler-table">
                    <thead>
                        <tr>
                            <th>Tipo de caldera</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pirotubular</td>
                            <td>Los gases calientes circulan por el interior de los tubos sumergidos en agua. Alta eficiencia en capacidades medias y bajas.</td>
                        </tr>
                        <tr>
                            <td>Acuotubular</td>
                            <td>El agua circula por los tubos expuestos a los gases de combustión. Diseñada para alta presión y gran capacidad de producción.</td>
                        </tr>
                        <tr>
                            <td>Vapor saturado</td>
                            <td>Producción de vapor para procesos de cocción, esterilización y calefacción industrial a temperatura y presión controladas.</td>
                        </tr>
                        <tr>
                            <td>Agua caliente</td>
                            <td>Calefacción de procesos o espacios industriales con temperatura regulada. Alta eficiencia y menor riesgo operativo.</td>
                        </tr>
                        <tr>
                            <td>Fluido térmico</td>
                            <td>Transferencia de calor de alta temperatura sin presión de vapor. Mayor seguridad operativa y menor costo de mantenimiento.</td>
                        </tr>
                        <tr>
                            <td>Gas LP y gas natural</td>
                            <td>Calderas de combustión a gas en cualquier configuración, capacidad y tecnología. Compatible con la mayoría de los quemadores del mercado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="bs-table-note">
                ¿Tu equipo no aparece en esta lista?
                <a href="#cotizacion" style="color:var(--secondary-color);text-decoration:none;font-weight:500;">Contáctanos</a>
                — en la mayoría de los casos podemos atenderlo con los mismos estándares.
            </p>

        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 5 — PROCESO DE SERVICIO
    ═══════════════════════════════════════════════════════ --}}
    <section id="proceso" class="service-description-section-industrial" aria-labelledby="proceso-heading" style="background:var(--background--white);">
        <div class="container service-description-section">

            <h2 id="proceso-heading" class="service-subtitle-industrial">
                ¿Cómo funciona nuestro proceso de servicio de calderas?
            </h2>
            <p class="service-text-industrial">
                Sin sorpresas, sin costos ocultos y con toda la información por escrito antes de
                que iniciemos cualquier trabajo.
            </p>

            <div class="benefits-grid" style="margin-top:32px;">

                <div class="benefit-item">
                    <div class="benefit-icon-box bs-step-number" aria-hidden="true">1</div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Diagnóstico técnico inicial</h3>
                        <p class="benefit-text">
                            Visita presencial con instrumentación calibrada: termografía, ultrasonido
                            y análisis de gases de combustión. Informe técnico escrito con hallazgos
                            por nivel de prioridad. Sin costo para nuevos clientes.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box bs-step-number" aria-hidden="true">2</div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Cotización detallada por escrito</h3>
                        <p class="benefit-text">
                            Desglose de mano de obra, refacciones y tiempo estimado de intervención.
                            Lo que cotizamos es lo que cobramos. Tú apruebas antes de que iniciemos
                            cualquier trabajo en el equipo.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box bs-step-number" aria-hidden="true">3</div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Ejecución del servicio</h3>
                        <p class="benefit-text">
                            Técnicos certificados, refacciones verificadas, bitácora técnica continua.
                            Coordinación con tu calendario de producción. Todo documentado con
                            fotografías y registro de mediciones antes y después de la intervención.
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon-box bs-step-number" aria-hidden="true">4</div>
                    <div class="benefit-content">
                        <h3 class="benefit-title">Entrega y certificación</h3>
                        <p class="benefit-text">
                            Pruebas de funcionamiento completas, verificación de seguridad y reporte
                            técnico firmado conforme a NOM-020-STPS. Documentación lista para tu
                            archivo de cumplimiento ante la STPS.
                        </p>
                    </div>
                </div>

            </div>

            {{-- Tabla de frecuencias --}}
            <h3 style="font-size:17px;font-weight:600;color:#111827;margin:48px 0 0;text-align:center;">
                Frecuencias de servicio recomendadas
            </h3>
            <div class="bs-table-wrap">
                <table class="bs-frequency-table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Frecuencia recomendada</th>
                            <th>Normativa de referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Revisión operativa</td>
                            <td>Mensual</td>
                            <td>Buenas prácticas industriales</td>
                        </tr>
                        <tr>
                            <td>Mantenimiento preventivo</td>
                            <td>Semestral</td>
                            <td>NOM-020-STPS</td>
                        </tr>
                        <tr>
                            <td>Mantenimiento anual completo</td>
                            <td>Anual</td>
                            <td>NOM-020-STPS / ASME</td>
                        </tr>
                        <tr>
                            <td>Desincrustación</td>
                            <td>Según análisis de agua</td>
                            <td>Recomendación técnica</td>
                        </tr>
                        <tr>
                            <td>Diagnóstico técnico</td>
                            <td>Al detectar anomalía</td>
                            <td>NOM-020-STPS</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 6 — STATS
    ═══════════════════════════════════════════════════════ --}}
    <section id="por-que-elegirnos" class="why-choose-simari-section" aria-labelledby="stats-heading">
        <div class="container why-choose-simari-section">

            <h2 id="stats-heading" class="section-main-title">Números que nos respaldan</h2>

            <div class="simari-stats-grid">

                <div class="simari-stat-item">
                    <div class="stat-big-number">500+</div>
                    <h3 class="stat-small-title">Plantas atendidas</h3>
                    <p class="stat-small-desc">
                        Desde PyMEs hasta plantas de manufactura en operación continua
                        con demanda crítica de vapor.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">15+</div>
                    <h3 class="stat-small-title">Años en servicio industrial</h3>
                    <p class="stat-small-desc">
                        Historial documentado de intervenciones en los principales
                        corredores industriales del país.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">&lt;24h</div>
                    <h3 class="stat-small-title">Tiempo de respuesta</h3>
                    <p class="stat-small-desc">
                        SLA garantizado por escrito en contratos de servicio.
                        Prioridad en atención a clientes con contrato anual.
                    </p>
                </div>

                <div class="simari-stat-item">
                    <div class="stat-big-number">100%</div>
                    <h3 class="stat-small-title">Técnicos certificados</h3>
                    <p class="stat-small-desc">
                        NOM-020-STPS y ASME. Todo el personal de campo cuenta
                        con certificación vigente verificable.
                    </p>
                </div>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 7 — INDUSTRIAS
    ═══════════════════════════════════════════════════════ --}}
    <section id="industrias" class="service-description-section-industrial" aria-labelledby="industrias-heading">
        <div class="container service-description-section">

            <h2 id="industrias-heading" class="service-subtitle-industrial">
                Industrias que confían en nuestros servicios de calderas
            </h2>
            <p class="service-text-industrial">
                La experiencia multisectorial es una señal de confianza que los proveedores
                especializados en un solo giro no pueden ofrecer. Conocemos los requisitos
                regulatorios y las exigencias operativas de cada industria.
            </p>

            <ul class="bs-industry-list" aria-label="Sectores atendidos por Equiterm Industries">
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">🍽️</span>
                    <div>
                        <p class="bs-industry-name">Alimentos y bebidas</p>
                        <p class="bs-industry-desc">Vapor para cocción, pasteurización y limpieza CIP en planta.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">💊</span>
                    <div>
                        <p class="bs-industry-name">Farmacéutico</p>
                        <p class="bs-industry-desc">Vapor limpio para procesos de alta pureza y esterilización validada.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">🧵</span>
                    <div>
                        <p class="bs-industry-name">Textil y lavanderías</p>
                        <p class="bs-industry-desc">Vapor saturado para acabados, planchado industrial y teñido.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">📦</span>
                    <div>
                        <p class="bs-industry-name">Papel y cartón</p>
                        <p class="bs-industry-desc">Calor de proceso para secado de bobinas y prensado de lámina.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">⚗️</span>
                    <div>
                        <p class="bs-industry-name">Químico y petroquímico</p>
                        <p class="bs-industry-desc">Fluido térmico para reactores y procesos a alta temperatura sin presión.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">🏥</span>
                    <div>
                        <p class="bs-industry-name">Hospitalario y hotelero</p>
                        <p class="bs-industry-desc">Agua caliente sanitaria 24 horas con disponibilidad crítica.</p>
                    </div>
                </li>
                <li class="bs-industry-item">
                    <span class="bs-industry-icon" aria-hidden="true">🏭</span>
                    <div>
                        <p class="bs-industry-name">Manufactura general</p>
                        <p class="bs-industry-desc">Cualquier proceso térmico industrial que requiera vapor, agua caliente o fluido.</p>
                    </div>
                </li>
            </ul>

            <a href="/servicios/" class="bs-internal-link" aria-label="Ver toda la oferta de servicios industriales de Equiterm">
                Conoce toda nuestra oferta de servicios industriales →
            </a>

        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 8 — CTA INTERMEDIO
    ═══════════════════════════════════════════════════════ --}}
    <section id="cotizacion" class="work-process-section" aria-labelledby="cta-mid-heading">
        <div class="container work-process-section">

            <h2 id="cta-mid-heading" class="cta-final-title-industrial">
                Contratos anuales con precio fijo y prioridad en emergencias
            </h2>
            <p class="cta-final-description-industrial">
                Diagnóstico inicial sin costo. Cotización por escrito antes de cualquier intervención.
                SLA garantizado para tu planta.
            </p>

            <div class="cta-final-actions">
                <a href="#contacto" class="button-primary work-process" aria-label="Solicitar cotización gratuita de servicio de calderas">
                    Solicitar cotización gratuita
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-left:8px" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
                <a href="tel:+524494348018" class="button-secondary work-process" aria-label="Llamar a Equiterm Industries">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-right:8px" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.9 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.82 2.69h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    Llamar ahora: +52 449 434 8018
                </a>
            </div>

        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 9 — FAQ
    ═══════════════════════════════════════════════════════ --}}
    <section id="faq" class="service-description-section-industrial" aria-labelledby="faq-heading">
        <div class="container service-description-section">

            <p class="section-subtitle-home">Preguntas frecuentes</p>
            <h2 id="faq-heading" class="section-main-title">FAQ · Servicios de calderas</h2>

            <div class="faq-list">

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Qué incluye un servicio de calderas industriales?
                    </summary>
                    <div class="faq-body">
                        <p>Depende del tipo de intervención. El mantenimiento incluye inspección, limpieza de hogar,
                        calibración de quemadores, análisis de agua y prueba de válvulas de seguridad. La reparación
                        incluye diagnóstico en sitio, cotización previa e intervención certificada. Todos los servicios
                        incluyen reporte técnico firmado y registro en bitácora del equipo.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Con qué frecuencia se debe dar servicio a una caldera?
                    </summary>
                    <div class="faq-body">
                        <p>Las calderas industriales en operación continua requieren revisión operativa mensual por
                        personal capacitado, mantenimiento semestral por técnico especializado y mantenimiento anual
                        completo con inspección normativa. La NOM-020-STPS exige dictamen de operación segura vigente
                        para que el equipo opere legalmente.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Cuánto cuesta el servicio de una caldera industrial?
                    </summary>
                    <div class="faq-body">
                        <p>El costo depende del tipo de servicio, la capacidad del equipo y su estado actual. Un
                        mantenimiento semestral para una caldera de mediana capacidad oscila entre $18,000 y
                        $45,000 MXN. El diagnóstico inicial es gratuito y permite elaborar una cotización exacta
                        sin estimados genéricos.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Ofrecen contratos de servicio anuales de calderas?
                    </summary>
                    <div class="faq-body">
                        <p>Sí. Los contratos anuales incluyen visitas programadas con frecuencia definida, precio fijo
                        por intervención, atención preferencial en emergencias con SLA garantizado por escrito y
                        bitácora técnica acumulada del equipo. Ideal para plantas en operación continua que no pueden
                        permitirse tiempos de paro no programados.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">
                        ¿Qué empresa da servicio a calderas industriales en México?
                    </summary>
                    <div class="faq-body">
                        <p>Equiterm Industries es una empresa especializada en servicios de calderas industriales con
                        cobertura nacional, más de 15 años de experiencia en el sector B2B, técnicos certificados
                        NOM-020-STPS y ASME, y garantía por escrito en cada intervención. Atendemos calderas
                        pirotubulares, acuotubulares, de fluido térmico y de vapor saturado en los principales
                        corredores industriales del país.</p>
                    </div>
                </details>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         SECCIÓN 10 — CTA FINAL
    ═══════════════════════════════════════════════════════ --}}
    <section id="contacto" class="final-cta" aria-labelledby="cta-final-heading">
        <div class="final-cta__content">

            <div class="final-cta__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M8 13h8"/><path d="M8 17h5"/>
                </svg>
            </div>

            <h2 id="cta-final-heading" style="font-size:clamp(22px,3vw,32px);font-weight:700;color:var(--text-white-color);margin:0 0 12px;line-height:1.2;">
                ¿Listo para hablar sobre el servicio de tu caldera?
            </h2>
            <p style="font-size:15px;color:var(--text-subwhite-color);max-width:560px;margin:0 auto 28px;line-height:1.6;">
                Diagnóstico inicial sin costo. Cotización detallada por escrito. Sin compromisos
                hasta que apruebes el presupuesto.
            </p>

            <div class="final-cta__actions">
                <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20el%20servicio%20de%20calderas%20industriales."
                    target="_blank" rel="noopener noreferrer"
                    class="button-primary final-cta__actions-water"
                    aria-label="Solicitar cotización de servicio de calderas por WhatsApp">
                    Solicitar cotización por WhatsApp
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-left:8px" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
                <a href="tel:+524494348018"
                    class="button-secondary final-cta__actions-water"
                    aria-label="Llamar a Equiterm Industries para servicio de calderas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="margin-right:8px" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.9 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.82 2.69h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    Llamar: +52 449 434 8018
                </a>
            </div>

        </div>
    </section>

@endsection
