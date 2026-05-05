@extends('frontend.layouts.master')

@section('title', 'Reparación de Calderas Industriales en México | Equiterm')
@section('description', 'Reparación de calderas industriales con diagnóstico gratuito en sitio, garantía por escrito y técnicos certificados NOM-020. Cobertura nacional.')
@section('canonical', config('app.url') . '/servicios/calderas/reparacion-de-calderas')

@section('og_title', 'Reparación de Calderas Industriales en México | Equiterm')
@section('og_description', 'Reparación de calderas industriales con diagnóstico gratuito en sitio, garantía por escrito y técnicos certificados NOM-020. Cobertura nacional.')
@section('og_url', config('app.url') . '/servicios/calderas/reparacion-de-calderas')
@section('og_image', config('app.url') . '/images/og-reparacion-calderas.jpg')

@section('schema')
<meta name="keywords" content="reparación de calderas, reparación calderas industriales, reparar caldera industrial, servicio reparación calderas México">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Reparación de Calderas Industriales en México | Equiterm">
<meta name="twitter:description" content="Reparación de calderas industriales con diagnóstico gratuito en sitio, garantía por escrito y técnicos certificados NOM-020. Cobertura nacional.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Reparación de Calderas Industriales",
  "serviceType": "Reparación y diagnóstico de calderas",
  "hoursAvailable": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
    "opens": "00:00",
    "closes": "23:59"
  },
  "provider": {
    "@type": "Organization",
    "name": "Equiterm Industries"
  },
  "areaServed": "México",
  "description": "Reparación de calderas industriales con diagnóstico gratuito en sitio, garantía por escrito y técnicos certificados NOM-020. Cobertura nacional.",
  "url": "{{ config('app.url') . '/servicios/calderas/reparacion-de-calderas' }}"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cuánto tiempo tarda una reparación de caldera industrial?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Depende de la falla. Reparaciones menores —quemador, válvulas, sellos, controles— toman 1 a 2 días hábiles. Reparaciones mayores que involucran tubos, estructura o rehabilitación de superficies de calefacción requieren entre 3 y 7 días hábiles. El diagnóstico previo permite dar un tiempo real, no un estimado genérico."
      }
    },
    {
      "@type": "Question",
      "name": "¿Cuánto cuesta reparar una caldera industrial?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El costo varía según el tipo de falla, la capacidad del equipo y las refacciones requeridas. Reparaciones menores parten desde $8,000 MXN. Intervenciones mayores pueden superar los $80,000 MXN. El diagnóstico gratuito permite entregar una cotización exacta antes de iniciar cualquier trabajo."
      }
    },
    {
      "@type": "Question",
      "name": "¿Es mejor reparar o reemplazar la caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Si el equipo tiene menos de 20 años y la falla es localizada, la reparación es casi siempre la opción más rentable. Si hay corrosión generalizada, fallas recurrentes o el costo de reparación supera el 60% del valor de reemplazo, conviene evaluar sustitución. Equiterm asesora con base en el estado real del equipo, sin conflicto de interés."
      }
    },
    {
      "@type": "Question",
      "name": "¿Ofrecen garantía en sus reparaciones?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Todas las reparaciones incluyen garantía por escrito sobre la mano de obra y los componentes instalados. El plazo varía según el tipo de intervención y se especifica en el contrato de servicio antes de iniciar el trabajo."
      }
    },
    {
      "@type": "Question",
      "name": "¿Atienden emergencias fuera de horario?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Equiterm cuenta con servicio de atención a emergencias industriales. Los contratos de mantenimiento incluyen tiempo de respuesta preferencial garantizado por SLA. Para equipos sin contrato, la atención está sujeta a disponibilidad de técnicos certificados en la zona."
      }
    }
  ]
}
</script>
@endsection

@section('content')

{{-- ══════════════════════════════════════════
     1. HERO
     ══════════════════════════════════════════ --}}
<section class="hero-section-hair-repair" id="inicio">
    <div class="hero-background">
        {{-- TODO: <img src="{{ asset('images/services/reparacion-calderas/reparacion-caldera-industrial-emergencia.jpg') }}"
             alt="Técnico de Equiterm Industries realizando reparación de caldera industrial pirotubular en planta en México"
             width="1200" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-hair-repair">
        <div class="hero-content">

            <div class="automation-tag">
                <span class="automation-tag__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path>
                    </svg>
                </span>
                <span class="br-automation-tag__text">
                    Servicio de emergencia 24/7
                </span>
            </div>

            <h1 class="hero-title">
                Reparación de Calderas Industriales — Servicio Certificado en México
            </h1>

            <p class="hero-description">
                La <strong>reparación de calderas industriales</strong> requiere diagnóstico presencial preciso,
                no estimados por teléfono. Atendemos la urgencia operativa con técnicos certificados
                <strong>NOM-020-STPS y ASME</strong>, cotización por escrito sin sorpresas y garantía en cada
                intervención.
            </p>

            <div class="br-hero-cta-block">
                <p class="hero-description br-hero-question">
                    ¿Tu caldera está fuera de servicio ahorita?
                </p>
                <p class="hero-description br-hero-sub">
                    Técnico en sitio en menos de 24 horas en zonas industriales prioritarias.
                    Sin formularios. Diagnóstico gratuito.
                </p>
                <div class="hero-actions">
                    <a href="tel:+524494348018"
                       class="button-primary"
                       aria-label="Llamar para reparación urgente de caldera industrial">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        Llamar ahora: +52 449 434 8018
                    </a>
                    <a href="https://wa.me/524494348018?text=Hola%2C%20tengo%20una%20emergencia%20con%20mi%20caldera%2C%20necesito%20un%20t%C3%A9cnico%20en%20sitio%20para%20reparaci%C3%B3n."
                       target="_blank"
                       rel="noopener"
                       class="button-secondary"
                       aria-label="Reportar emergencia de caldera por WhatsApp">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12.004 2C6.477 2 2 6.477 2 12.004a9.954 9.954 0 0 0 1.357 5.014L2 22l5.113-1.335A9.97 9.97 0 0 0 12.004 22C17.53 22 22 17.525 22 12.004 22 6.477 17.53 2 12.004 2zM12 20.007a7.997 7.997 0 0 1-4.076-1.117l-.293-.174-3.036.794.811-2.957-.191-.303A7.985 7.985 0 0 1 4.007 12c0-4.41 3.589-8 7.997-8 4.41 0 7.996 3.59 7.996 8 0 4.41-3.586 7.997-7.997 8z"/>
                        </svg>
                        Emergencia
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
{{-- ── Franja de stats ── --}}
<div class="br-stat-strip" id="stats" aria-label="Indicadores de servicio">
    <div class="container">
        <div class="br-stat-strip__inner">

            <div class="br-stat-strip__item">
                <p class="br-stat-strip__number">+500</p>
                <p class="br-stat-strip__label">calderas reparadas</p>
            </div>

            <div class="br-stat-strip__item">
                <p class="br-stat-strip__number">&minus;24 hrs</p>
                <p class="br-stat-strip__label">respuesta en sitio</p>
            </div>

            <div class="br-stat-strip__item">
                <p class="br-stat-strip__number">Garantía</p>
                <p class="br-stat-strip__label">por escrito en cada servicio</p>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     2. ¿POR QUÉ EQUITERM?
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="por-que">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            ¿Por qué elegir Equiterm para reparar tu caldera?
        </h2>

        <div class="benefits-grid">

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Técnicos certificados NOM-020-STPS y ASME — 15+ años</h3>
                    <p class="benefit-text">
                        Atendemos pirotubulares, acuotubulares, vapor saturado, agua caliente y fluido
                        térmico de cualquier marca. Cada intervención queda registrada en bitácora técnica
                        compatible con expediente STPS. Consulta nuestra oferta completa en
                        <a href="{{ route('boiler-services') }}"
                           class="br-inline-link">servicios de calderas</a>.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Diagnóstico en sitio sin costo adicional</h3>
                    <p class="benefit-text">
                        Ingeniero presencial con instrumentación calibrada: analizador de gases, ultrasonido
                        y termografía. Entregamos informe escrito de causa raíz y opciones de solución.
                        Tiempo de respuesta menor a 24 horas en zonas industriales prioritarias.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Cotización detallada sin letra pequeña</h3>
                    <p class="benefit-text">
                        Presupuesto por escrito con desglose de mano de obra y refacciones antes de tocar
                        el equipo. El precio cotizado es el precio final. Sin cargos adicionales. Sin
                        sorpresas en la factura.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     3. TIPOS DE REPARACIÓN
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="tipos">
    <div class="container what-includes-section">
        <h2 class="section-main-title">Tipos de reparación de calderas que realizamos</h2>
        <p class="service-text-industrial br-section-intro">
            Desde fallas puntuales hasta rehabilitaciones mayores.
        </p>

        {{-- Tabla Featured Snippet --}}
        <div class="br-table-wrapper">
            <table class="br-repair-table"
                   aria-label="Tipos de reparación de calderas con tiempo estimado y nivel de urgencia">
                <thead>
                    <tr>
                        <th scope="col">Tipo de reparación</th>
                        <th scope="col">Tiempo estimado</th>
                        <th scope="col">Nivel de urgencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Reparación de quemadores y combustión</td>
                        <td>1–2 días</td>
                        <td><span class="br-urgency-tag br-urgency-tag--immediate">Inmediato</span></td>
                    </tr>
                    <tr>
                        <td>Reparación de válvulas, sellos y juntas</td>
                        <td>1–2 días</td>
                        <td><span class="br-urgency-tag br-urgency-tag--immediate">Inmediato</span></td>
                    </tr>
                    <tr>
                        <td>Reparación de controles y automatización</td>
                        <td>1–3 días</td>
                        <td><span class="br-urgency-tag br-urgency-tag--both">Inmediato / Programable</span></td>
                    </tr>
                    <tr>
                        <td>Reparación de tubos y superficies de calefacción</td>
                        <td>3–7 días</td>
                        <td><span class="br-urgency-tag br-urgency-tag--scheduled">Programable</span></td>
                    </tr>
                    <tr>
                        <td>Reparación de estructuras y soportería</td>
                        <td>3–5 días</td>
                        <td><span class="br-urgency-tag br-urgency-tag--both">Programable / Preventivo</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="includes-grid">

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Quemadores y sistema de combustión</h3>
                <p class="include-card-text">
                    Limpieza, ajuste de relación aire-combustible, cambio de electrodos, boquillas,
                    célula fotoeléctrica y cableado de control. Incluye análisis de gases post-servicio.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3 class="include-card-title">Válvulas, sellos y juntas</h3>
                <p class="include-card-text">
                    Válvulas de seguridad, válvulas de paso, flanges, juntas de dilatación y empaques.
                    Componentes originales o equivalentes certificados con prueba de estanqueidad.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="2"></rect>
                        <path d="M8 10h8M8 14h4"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Controles y automatización</h3>
                <p class="include-card-text">
                    Presostatos, termostatos, controles de llama, PLC de caldera y tableros eléctricos.
                    Diagnóstico de falla eléctrica y reprogramación de parámetros de operación.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Tubos y superficies de calefacción</h3>
                <p class="include-card-text">
                    Sustitución de tubos dañados, reexpansión de tubos flojos y soldadura ASME certificada
                    en cuerpo de presión. Medición de espesores por ultrasonido antes y después.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Estructuras y soportería</h3>
                <p class="include-card-text">
                    Reparación de bases, soportes de tubería, refractarios y aislamiento térmico.
                    Evaluación de integridad estructural e informe fotográfico documentado.
                </p>
            </div>

        </div>

        <div class="applications-card">
            <p class="service-text-industrial">
                Si la falla aún no ha ocurrido y buscas prevenirla, consulta nuestro programa de
                <a href="{{ route('boiler-maintenance') }}"
                   class="br-inline-link br-inline-link--strong">mantenimiento preventivo de calderas</a>.
                Actuar antes de la falla es siempre más económico.
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     4. PROCESO DE REPARACIÓN
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="proceso">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            Proceso de reparación: ¿cómo trabajamos?
        </h2>

        <div class="benefits-grid">

            <div class="benefit-item">
                <div class="benefit-icon-box br-step-number"
                     aria-label="Paso 1">1</div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Diagnóstico técnico y evaluación de daños</h3>
                    <p class="benefit-text">
                        Visita presencial con instrumentación calibrada. El ingeniero identifica la causa
                        raíz —no solo el síntoma— y entrega informe escrito de hallazgos y opciones.
                        Sin costo. Sin compromiso.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box br-step-number"
                     aria-label="Paso 2">2</div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Cotización detallada sin letra pequeña</h3>
                    <p class="benefit-text">
                        Presupuesto escrito con desglose de mano de obra y refacciones. El precio
                        cotizado es el precio final. Tú apruebas antes de que iniciemos cualquier trabajo.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box br-step-number"
                     aria-label="Paso 3">3</div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Reparación con refacciones originales o equivalentes</h3>
                    <p class="benefit-text">
                        Técnicos certificados, refacciones verificadas y bitácora técnica en cada
                        intervención. Si se detecta necesidad de
                        <a href="{{ route('boiler-descaling') }}"
                           class="br-inline-link">desincrustación de calderas</a>,
                        se notifica y cotiza por separado antes de actuar.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box br-step-number"
                     aria-label="Paso 4">4</div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Pruebas de presión y certificación final</h3>
                    <p class="benefit-text">
                        Prueba hidrostática o neumática según aplique, verificación funcional de válvulas
                        de seguridad y entrega de reporte técnico firmado compatible con la
                        <strong>NOM-020-STPS-2011</strong>.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     5. SEÑALES DE ALERTA
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="alertas">
    <div class="container what-includes-section">
        <h2 class="section-main-title">¿Cuándo necesita tu caldera una reparación urgente?</h2>
        <p class="service-text-industrial br-section-intro">
            No todas las fallas anuncian su llegada. Estas señales indican que debes actuar hoy:
        </p>

        <ul class="br-checklist" aria-label="Señales de alerta en calderas industriales">

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Presión irregular o fluctuante</span>
                    <span class="br-checklist-item__consequence"> → riesgo de paro de emergencia</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Fugas visibles de vapor o agua</span>
                    <span class="br-checklist-item__consequence"> → pérdida energética y riesgo de quemaduras</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Ruidos: golpeteo, silbidos o vibración anormal</span>
                    <span class="br-checklist-item__consequence"> → posible colapso de tubo</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Consumo de combustible mayor sin cambio de carga</span>
                    <span class="br-checklist-item__consequence"> → incrustaciones activas</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Llama inestable o apagos frecuentes</span>
                    <span class="br-checklist-item__consequence"> → falla en quemador</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Humo negro o blanco excesivo</span>
                    <span class="br-checklist-item__consequence"> → combustión incompleta</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Disparos repetidos de válvula de seguridad</span>
                    <span class="br-checklist-item__consequence"> → sobrepresión — riesgo inmediato</span>
                </div>
            </li>

            <li class="br-checklist-item">
                <span class="br-checklist-item__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <span class="br-checklist-item__signal">Temperatura de gases de escape por encima del rango</span>
                    <span class="br-checklist-item__consequence"> → depósitos en superficies de intercambio</span>
                </div>
            </li>

        </ul>

        <div class="br-alertas-cta">
            <p class="service-text-industrial br-alertas-cta__text">
                Si identificas alguna de estas señales, no esperes al paro de producción.
            </p>
            <a href="tel:+524494348018"
               class="button-primary"
               aria-label="Llamar ahora para reparación urgente de caldera industrial">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                Llamar ahora: +52 449 434 8018
            </a>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════
     6. CTA INTERMEDIO — DIAGNÓSTICO
     ══════════════════════════════════════════ --}}
<section class="work-process-section" id="diagnostico">
    <div class="container work-process-section">
        <h2 class="cta-final-title-industrial">
            Diagnóstico sin costo. Solo pagas si procedes con la reparación.
        </h2>
        <p class="cta-final-description-industrial">
            Sin letra chica. Sin cargos ocultos. Cotización por escrito antes de tocar el equipo.
        </p>
        <div class="cta-final-actions">
            <a href="#contacto"
               class="button-primary work-process"
               aria-label="Solicitar diagnóstico gratuito de caldera">
                Solicitar diagnóstico
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </a>
            <a href="tel:+524494348018"
               class="button-secondary work-process"
               aria-label="Llamar para emergencia de caldera industrial">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                Emergencia: llamar directo
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     7. TIPOS DE CALDERA
     ══════════════════════════════════════════ --}}
<section class="why-choose-us" id="tipos-caldera">
    <div class="container why-choose-simari-section">
        <h2 class="section-main-title">Reparamos todos los tipos de caldera</h2>
        <p class="service-text-industrial br-section-intro">
            Pirotubulares, acuotubulares, vapor saturado, agua caliente, gas LP, gas natural y sistemas de
            calefacción central industrial. Refacciones originales o equivalentes certificadas para cualquier
            marca y modelo.
        </p>

        <div class="equipment-grid">

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <h3>Calderas pirotubulares</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="18" rx="2" ry="2"></rect>
                        <line x1="8" y1="3" x2="8" y2="21"></line>
                        <line x1="16" y1="3" x2="16" y2="21"></line>
                    </svg>
                </div>
                <h3>Calderas acuotubulares</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                    </svg>
                </div>
                <h3>Calderas de vapor saturado</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"></path>
                    </svg>
                </div>
                <h3>Calderas de agua caliente</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path>
                    </svg>
                </div>
                <h3>Calderas de gas LP y gas natural</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <h3>Sistemas de calefacción central industrial</h3>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     8. FAQ
     ══════════════════════════════════════════ --}}
<section class="service-calibration-accordion" id="faq">
    <div class="container service-calibration-accordion">
        <h2>Preguntas frecuentes · Reparación de calderas</h2>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cuánto tiempo tarda una reparación de caldera industrial?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p class="faq-answer">
                    Depende de la falla. Reparaciones menores —quemador, válvulas, sellos, controles—
                    toman <strong>1 a 2 días hábiles</strong>. Reparaciones mayores que involucran tubos,
                    estructura o rehabilitación de superficies de calefacción requieren entre
                    <strong>3 y 7 días hábiles</strong>. El diagnóstico previo permite dar un tiempo real,
                    no un estimado genérico.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cuánto cuesta reparar una caldera industrial?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p class="faq-answer">
                    El costo varía según el tipo de falla, la capacidad del equipo y las refacciones
                    requeridas. Reparaciones menores parten desde <strong>$8,000 MXN</strong>. Intervenciones
                    mayores pueden superar los <strong>$80,000 MXN</strong>. El diagnóstico gratuito
                    permite entregar una cotización exacta antes de iniciar cualquier trabajo.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Es mejor reparar o reemplazar la caldera?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p class="faq-answer">
                    Si el equipo tiene menos de 20 años y la falla es localizada, la reparación es casi
                    siempre la opción más rentable. Si hay corrosión generalizada, fallas recurrentes o el
                    costo de reparación supera el <strong>60% del valor de reemplazo</strong>, conviene
                    evaluar sustitución. Equiterm asesora con base en el estado real del equipo, sin
                    conflicto de interés.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Ofrecen garantía en sus reparaciones?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p class="faq-answer">
                    Sí. Todas las reparaciones incluyen <strong>garantía por escrito</strong> sobre la
                    mano de obra y los componentes instalados. El plazo varía según el tipo de intervención
                    y se especifica en el contrato de servicio antes de iniciar el trabajo.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Atienden emergencias fuera de horario?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p class="faq-answer">
                    Sí. Equiterm cuenta con servicio de atención a emergencias industriales. Los
                    <strong>contratos de mantenimiento</strong> incluyen tiempo de respuesta preferencial
                    garantizado por SLA. Para equipos sin contrato, la atención está sujeta a disponibilidad
                    de técnicos certificados en la zona.
                </p>
            </details>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════
     9. CTA FINAL
     ══════════════════════════════════════════ --}}
<section class="final-cta" id="contacto">
    <div class="container">
        <div class="final-cta__content">
            <div class="final-cta__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </div>

            <h2>Cada minuto con la caldera parada tiene un costo. Llama ahora.</h2>

            <p>
                Técnico en sitio. <strong>Diagnóstico gratuito</strong>. Reparación con <strong>garantía por escrito</strong>.
            </p>

            <div class="final-cta__actions">
                <a href="tel:+524494348018"
                   class="button-primary final-cta__actions-water"
                   aria-label="Llamar para reparación urgente de caldera industrial">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Llamar ahora: +52 449 434 8018
                </a>

                <a href="https://wa.me/524494348018?text=Hola%2C%20necesito%20reparaci%C3%B3n%20urgente%20de%20caldera%20industrial.%20%C2%BFPueden%20enviar%20un%20t%C3%A9cnico%3F"
                   target="_blank"
                   rel="noopener"
                   class="button-secondary final-cta__actions-water"
                   aria-label="Solicitar reparación urgente de caldera por WhatsApp">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12.004 2C6.477 2 2 6.477 2 12.004a9.954 9.954 0 0 0 1.357 5.014L2 22l5.113-1.335A9.97 9.97 0 0 0 12.004 22C17.53 22 22 17.525 22 12.004 22 6.477 17.53 2 12.004 2zM12 20.007a7.997 7.997 0 0 1-4.076-1.117l-.293-.174-3.036.794.811-2.957-.191-.303A7.985 7.985 0 0 1 4.007 12c0-4.41 3.589-8 7.997-8 4.41 0 7.996 3.59 7.996 8 0 4.41-3.586 7.997-7.997 8z"/>
                    </svg>
                    WhatsApp urgente
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
