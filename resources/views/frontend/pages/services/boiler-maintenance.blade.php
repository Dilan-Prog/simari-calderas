@extends('frontend.layouts.master')

@section('title', 'Mantenimiento a Calderas Industriales en México | Equiterm')
@section('description', 'Servicio profesional de mantenimiento a calderas industriales en México. Cumplimiento NOM-020-STPS, equipo certificado y cobertura nacional.')
@section('canonical', config('app.url') . '/servicios/calderas/mantenimiento-calderas')

@section('og_title', 'Mantenimiento a Calderas Industriales en México | Equiterm')
@section('og_description', 'Servicio profesional de mantenimiento a calderas industriales en México. Cumplimiento NOM-020-STPS, equipo certificado y cobertura nacional.')
@section('og_url', config('app.url') . '/servicios/calderas/mantenimiento-calderas')
@section('og_image', config('app.url') . '/images/og-mantenimiento-calderas.jpg')

@section('schema')
<meta name="keywords" content="mantenimiento a calderas, mantenimiento preventivo calderas, mantenimiento correctivo calderas, mantenimiento caldera industrial, servicio de calderas México">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Mantenimiento a Calderas Industriales en México | Equiterm">
<meta name="twitter:description" content="Servicio profesional de mantenimiento a calderas industriales en México. Cumplimiento NOM-020-STPS, equipo certificado y cobertura nacional.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Mantenimiento a Calderas Industriales",
  "serviceType": "Mantenimiento preventivo y correctivo de calderas",
  "provider": {
    "@type": "Organization",
    "name": "Equiterm Industries"
  },
  "areaServed": "México",
  "description": "Servicio profesional de mantenimiento a calderas industriales en México. Cumplimiento NOM-020-STPS, equipo certificado y cobertura nacional.",
  "url": "{{ config('app.url') . '/servicios/calderas/mantenimiento-calderas' }}"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cada cuánto tiempo se hace el mantenimiento de una caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "La frecuencia mínima contempla revisiones mensuales por el operador, mantenimientos semestrales con técnico especializado y mantenimiento anual con pruebas de presión. La NOM-020-STPS exige dictamen de operación segura vigente por unidad verificadora."
      }
    },
    {
      "@type": "Question",
      "name": "¿Qué incluye el mantenimiento de una caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Inspección visual y estructural del cuerpo de presión, limpieza de superficies de intercambio de calor, revisión y calibración de quemadores, análisis fisicoquímico del agua, prueba funcional de válvulas de seguridad y controles automáticos, y reporte técnico documentado."
      }
    },
    {
      "@type": "Question",
      "name": "¿Cuánto cuesta el mantenimiento de una caldera industrial?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El costo varía según capacidad, combustible y antigüedad del equipo. Un mantenimiento semestral para caldera de mediana capacidad puede oscilar entre $18,000 y $45,000 MXN. Se recomienda evaluación técnica previa para una propuesta específica."
      }
    },
    {
      "@type": "Question",
      "name": "¿Qué norma regula el mantenimiento de calderas en México?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "La NOM-020-STPS-2011 establece las condiciones de seguridad para operación, mantenimiento e inspección de generadores de vapor y calderas. El Código ASME es la referencia técnica de diseño y fabricación reconocida en México."
      }
    },
    {
      "@type": "Question",
      "name": "¿Qué pasa si no se le da mantenimiento a una caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Acumulación de incrustaciones que reducen eficiencia, mayor consumo de combustible y riesgo de fallas críticas en válvulas y tubos. En casos extremos puede ocurrir explosión o fuga de vapor. Sin dictamen NOM-020 vigente la empresa enfrenta sanciones legales y responsabilidades ante el IMSS."
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
        <img src="{{ asset('images/services/mantenimiento-calderas/6.png') }}"
             alt="Técnico realizando mantenimiento a caldera industrial en México"
             width="1200" height="800" loading="eager" fetchpriority="high" decoding="async">
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-hair-repair">
        <div class="hero-content">

            <span class="bm-hero-badge" aria-label="Certificación NOM-020-STPS">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                NOM-020-STPS Certificado · Cobertura Nacional
            </span>

            <h1 class="hero-title">
                Mantenimiento a Calderas para Industrias en México
            </h1>

            <p class="hero-description">
                El <strong>mantenimiento a calderas</strong> es un requisito crítico de seguridad, eficiencia
                y cumplimiento legal en la industria mexicana. Un programa bien ejecutado garantiza operación
                continua, reduce hasta un 25% el consumo de combustible y mantiene tu planta en cumplimiento
                con la <strong>NOM-020-STPS-2011</strong>.
            </p>

            <div style="border-top: 1px solid rgba(255,255,255,0.12); padding-top: 32px; margin-top: 8px;">
                <p class="hero-description" style="font-weight: 700; font-size: clamp(1.05rem, 2vw, 1.3rem); margin-bottom: 10px; color: #fff;">
                    ¿Tu planta tiene el dictamen NOM-020 vigente?
                </p>
                <p class="hero-description" style="margin-bottom: 28px;">
                    Sin él, cada día de operación es una responsabilidad legal y un riesgo para tu equipo.
                    Agenda una revisión técnica gratuita hoy.
                </p>
                <div class="hero-actions">
                    <a href="#contacto"
                       class="button-primary"
                       aria-label="Agendar revisión gratuita de caldera industrial">
                        Agendar revisión gratuita
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     2. ¿POR QUÉ ES CRÍTICO?
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="por-que">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            ¿Por qué es crítico el mantenimiento preventivo de calderas?
        </h2>

        <p class="service-text-industrial">
            Una caldera sin programa de <strong>mantenimiento preventivo</strong> acumula daños silenciosos:
            fisuras en el cuerpo de presión, corrosión interna acelerada, incrustaciones en superficies de
            intercambio de calor y válvulas de seguridad que pierden calibración. Estos factores no solo
            reducen la vida útil del equipo —reducen también la seguridad de toda la planta.
        </p>

        <div class="bm-price-callout bm-price-callout--stats" role="region" aria-label="Impacto de incrustaciones según ABMA">
            <div class="bm-price-callout__stat-col">
                <p class="bm-price-callout__stat-value">3 mm</p>
                <p class="bm-price-callout__stat-label">de incrustación</p>
            </div>
            <div class="bm-price-callout__stat-sep" aria-hidden="true"></div>
            <div class="bm-price-callout__stat-col">
                <p class="bm-price-callout__stat-value">25%</p>
                <p class="bm-price-callout__stat-label">más consumo de combustible</p>
            </div>
        </div>

        <p class="service-text-industrial">
            La ABMA (American Boiler Manufacturers Association) documenta que esas cifras bastan para
            generar <strong>miles de pesos de pérdida mensual</strong> —pérdida que un mantenimiento
            semestral habría prevenido por completo.
        </p>

        <div class="applications-card">
            <p class="service-text-industrial" style="margin: 0;">
                "Sin mantenimiento, el costo de una reparación mayor supera hasta 10 veces el valor del
                mantenimiento preventivo anual."
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     3. TIPOS DE MANTENIMIENTO
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="tipos">
    <div class="container what-includes-section">
        <h2 class="section-main-title">Tipos de mantenimiento de calderas industriales</h2>

        <div class="includes-grid">

            {{-- Preventivo --}}
            <div class="include-card bm-card-preventivo">
                <span class="bm-card-type-label bm-label-preventivo">Preventivo</span>
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Mantenimiento Preventivo</h3>
                <p class="include-card-text">
                    El <strong>mantenimiento preventivo de calderas</strong> se ejecuta en intervalos
                    programados —mensual, semestral y anual— para detectar desgastes antes de que se
                    conviertan en fallas. Prolonga la vida útil del equipo, mantiene la eficiencia de
                    combustión y garantiza el cumplimiento con la NOM-020-STPS.
                </p>
            </div>

            {{-- Correctivo --}}
            <div class="include-card bm-card-correctivo">
                <span class="bm-card-type-label bm-label-correctivo">Correctivo</span>
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Mantenimiento Correctivo</h3>
                <p class="include-card-text">
                    Intervención directa ante fallas ya presentadas: sustitución de tubos dañados,
                    reparación de sellos, reposición de válvulas o reparación del sistema de combustión.
                    Para fallas mayores en el cuerpo de presión, nuestro servicio de
                    <a href="{{ route('boiler-repair') }}" style="color: var(--secondary-color); text-decoration: underline;">reparación de calderas</a>
                    incluye diagnóstico por ultrasonido y soldadura certificada ASME.
                </p>
            </div>

            {{-- Predictivo --}}
            <div class="include-card bm-card-predictivo">
                <span class="bm-card-type-label bm-label-predictivo">Predictivo</span>
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Mantenimiento Predictivo</h3>
                <p class="include-card-text">
                    Utiliza tecnologías de inspección avanzada —termografía infrarroja, ultrasonido
                    industrial y análisis de gases de combustión— para anticipar fallas antes de que
                    ocurran. Ideal para calderas de alta capacidad con operación continua donde cada
                    parada no programada tiene alto impacto económico.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     4. QUÉ INCLUYE EL SERVICIO
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="que-incluye">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            ¿Qué incluye el servicio de mantenimiento de calderas?
        </h2>

        <div class="benefits-grid">

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Inspección visual y estructural</h3>
                    <p class="benefit-text">
                        Revisión del cuerpo de presión, uniones soldadas, flanges, cámaras de humos y
                        tapas. Detección de fisuras, corrosión exterior y señales de fatiga estructural
                        mediante inspección visual y, cuando aplica, líquidos penetrantes o ultrasonido.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Revisión de quemadores y sistemas de combustión</h3>
                    <p class="benefit-text">
                        Limpieza, calibración y análisis de eficiencia del quemador. Un quemador
                        correctamente calibrado puede recuperar entre <strong>5 y 12%</strong> de
                        eficiencia energética, reduciendo directamente la factura de gas. Incluye
                        análisis de gases con analizador de combustión portátil.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Análisis de agua y tratamiento químico</h3>
                    <p class="benefit-text">
                        Análisis fisicoquímico del agua de alimentación: dureza, pH, conductividad y
                        sólidos disueltos totales. Si existen incrustaciones establecidas, el servicio
                        se complementa con nuestro programa de
                        <a href="{{ route('boiler-descaling') }}" style="color: var(--secondary-color); text-decoration: underline;">desincrustación de calderas</a>
                        para recuperar la transferencia de calor original del equipo.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Pruebas de presión y seguridad (NOM-020-STPS)</h3>
                    <p class="benefit-text">
                        Verificación funcional de válvulas de seguridad, presostatos, termostatos y
                        controles automáticos. Prueba hidrostática cuando aplica. El reporte técnico
                        emitido sirve como respaldo documental ante inspecciones de la
                        <strong>STPS</strong> y auditorías del <strong>IMSS</strong>.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     5. FRECUENCIA — Timeline visual
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="frecuencia" style="background-color: var(--background-primary-white);">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial" style="color: var(--vaner-color);">
            ¿Con qué frecuencia se debe hacer mantenimiento a calderas?
        </h2>

        <div class="bm-timeline">

            {{-- Paso 1: Mensual --}}
            <div class="bm-timeline__step">
                <div class="bm-timeline__left">
                    <div class="bm-timeline__marker bm-timeline__marker--monthly" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="bm-timeline__line" aria-hidden="true"></div>
                </div>
                <div class="bm-timeline__content">
                    <span class="bm-timeline__badge bm-badge-monthly">Mensual</span>
                    <h3 class="bm-timeline__title">Operador interno</h3>
                    <p class="bm-timeline__text">
                        Inspección visual de nivel de agua, presión de operación y temperatura. Revisión
                        de válvulas de purga, controles de llama y estado general del quemador. Estas
                        rutinas pueden y deben ejecutarse por personal de planta capacitado como parte del
                        programa de seguridad interno.
                    </p>
                </div>
            </div>

            {{-- Paso 2: Semestral --}}
            <div class="bm-timeline__step">
                <div class="bm-timeline__left">
                    <div class="bm-timeline__marker bm-timeline__marker--semestral" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="bm-timeline__line" aria-hidden="true"></div>
                </div>
                <div class="bm-timeline__content">
                    <span class="bm-timeline__badge bm-badge-semestral">Semestral</span>
                    <h3 class="bm-timeline__title">Técnico especializado</h3>
                    <p class="bm-timeline__text">
                        Limpieza de quemadores y superficies de intercambio, análisis de agua, calibración
                        de válvulas de seguridad y controles, análisis de combustión con equipo portátil y
                        entrega de reporte técnico. Intervalo recomendado para calderas con operación
                        continua o semicontinua.
                    </p>
                </div>
            </div>

            {{-- Paso 3: Anual --}}
            <div class="bm-timeline__step">
                <div class="bm-timeline__left">
                    <div class="bm-timeline__marker bm-timeline__marker--annual" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                </div>
                <div class="bm-timeline__content" style="padding-bottom: 0;">
                    <span class="bm-timeline__badge bm-badge-annual">Anual</span>
                    <h3 class="bm-timeline__title">NOM-020-STPS-2011 · Código ASME</h3>
                    <p class="bm-timeline__text">
                        Inspección interna completa del cuerpo de presión, prueba hidrostática, análisis
                        por ultrasonido de espesores de tubería y emisión del <strong>dictamen de operación
                        segura</strong> por Unidad Verificadora acreditada ante la EMA. Este documento es
                        obligatorio por la <strong>NOM-020-STPS-2011</strong> para todos los equipos
                        generadores de vapor y agua sobrecalentada en operación.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     6. NORMATIVAS
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="normativas">
    <div class="container what-includes-section">
        <h2 class="section-main-title">Normativas mexicanas que rigen el mantenimiento de calderas</h2>

        <div class="benefits-grid">

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
                    <h3 class="benefit-title">Expediente técnico obligatorio</h3>
                    <p class="benefit-text">
                        La <strong>NOM-020-STPS-2011</strong> exige que toda empresa que opere una caldera
                        mantenga un expediente técnico actualizado. El <strong>Código ASME</strong> es la
                        referencia de diseño y fabricación adoptada en México.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Operadores certificados</h3>
                    <p class="benefit-text">
                        La norma obliga a contar con <strong>operadores certificados</strong> para la
                        operación de generadores de vapor. Su incumplimiento deriva en responsabilidad
                        directa del patrón ante la STPS y el <strong>IMSS</strong>.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Dictamen ante la EMA</h3>
                    <p class="benefit-text">
                        Es obligatorio obtener el <strong>dictamen de operación segura</strong> ante una
                        Unidad Verificadora acreditada ante la EMA. Sin él, cada día de operación
                        representa una exposición legal para la empresa.
                    </p>
                </div>
            </div>

        </div>

        <div class="applications-card">
            <p class="service-text-industrial" style="margin: 0;">
                <strong>"Operar sin dictamen NOM-020 vigente expone a la empresa a sanciones legales y
                responsabilidades patronales ante el IMSS."</strong>
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     7. CTA INTERMEDIO — CONTRATOS
     ══════════════════════════════════════════ --}}
<section class="work-process-section" id="contratos">
    <div class="container work-process-section">
        <h2 class="cta-final-title-industrial">
            Contratos anuales de mantenimiento con precio fijo y prioridad en emergencias
        </h2>
        <p class="cta-final-description-industrial">
            Sin sorpresas en la factura. SLA de respuesta garantizado por escrito para tu planta.
        </p>
        <div class="cta-final-actions">
            <a href="#contacto"
               class="button-secondary work-process"
               aria-label="Solicitar cotización de contrato anual de mantenimiento de calderas">
                Solicitar cotización sin compromiso
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     8. COSTO
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="costo">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            ¿Cuánto cuesta el mantenimiento de una caldera industrial?
        </h2>

        <p class="service-text-industrial">
            El costo del <strong>mantenimiento de caldera industrial</strong> no es fijo: depende de
            variables técnicas, logísticas y del estado actual del equipo. A continuación los factores
            determinantes y una referencia de mercado para orientar tu presupuesto.
        </p>

        {{-- Callout de precio destacado --}}
        <div class="bm-price-callout" role="region" aria-label="Referencia de precio de mantenimiento">
            <p class="bm-price-callout__label">Referencia de mercado · Bajío · Caldera 150–300 HP</p>
            <p class="bm-price-callout__range">
                $18,000
                <span class="bm-price-callout__range-sep">—</span>
                $45,000
                <span class="bm-price-callout__currency">MXN</span>
            </p>
            <p class="bm-price-callout__note">por mantenimiento semestral · sujeto a evaluación técnica previa</p>
        </div>

        <div class="benefits-grid">

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Factores que afectan el costo</h3>
                    <p class="benefit-text">
                        Tipo y modelo de caldera (pirotubular / acuotubular), capacidad en HP o BHP,
                        combustible (gas natural, LP, diésel, biomasa), antigüedad del equipo y
                        accesibilidad en planta, y ubicación geográfica (zona metropolitana vs. interior
                        de la república).
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">¿Servicio externo o técnico interno?</h3>
                    <p class="benefit-text">
                        La recomendación técnica es un modelo mixto: <strong>operador interno</strong>
                        para rutinas mensuales de inspección y bitácora, y
                        <strong>empresa especializada</strong> para mantenimientos semestrales y anuales
                        que incluyan análisis de combustión, pruebas de presión y emisión del dictamen
                        NOM-020. Este esquema optimiza costo sin sacrificar seguridad ni cumplimiento
                        normativo.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     9. POR QUÉ ELEGIRNOS / EQUITERM
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="por-que-elegirnos">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            Mantenimiento de calderas en México — Equiterm Industries
        </h2>

        <p class="service-text-industrial">
            Con más de <strong>30 años</strong> de experiencia en mantenimiento de calderas industriales
            en México, nuestro equipo de ingenieros certificados ASME atiende plantas en todo el país.
            Ejecutamos cada servicio adaptando el calendario a tu producción —sin paros no programados—
            y entregamos reportes técnicos detallados con termografía, ultrasonido y análisis de
            combustión que respaldan tu expediente técnico ante la STPS.
        </p>

        {{-- TODO: <img src="{{ asset('images/services/mantenimiento-calderas/equipo-ingenieros-calderas-equiterm.jpg') }}"
             alt="Ingenieros certificados ASME realizando mantenimiento de caldera industrial — Equiterm Industries"
             width="1200" height="600" loading="lazy" decoding="async"> --}}

        <div class="bm-kpi-row" role="region" aria-label="Cifras clave Equiterm Industries">
            <div class="bm-kpi-item">
                <p class="bm-kpi-value">+500</p>
                <p class="bm-kpi-label">Calderas atendidas</p>
            </div>
            <div class="bm-kpi-item">
                <p class="bm-kpi-value">+30</p>
                <p class="bm-kpi-label">Años de experiencia</p>
            </div>
            <div class="bm-kpi-item">
                <p class="bm-kpi-value">Nacional</p>
                <p class="bm-kpi-label">Cobertura</p>
            </div>
        </div>

        <div class="benefits-grid" style="margin-top: 40px;">

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Certificación NOM-020-STPS</h3>
                    <p class="benefit-text">
                        Documentación válida ante inspecciones de la Secretaría del Trabajo y Previsión
                        Social. Expediente técnico completo entregado en cada servicio.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Tiempos de respuesta reales — SLA por escrito</h3>
                    <p class="benefit-text">
                        No promesas verbales. El tiempo de respuesta ante emergencias queda definido en
                        el contrato de servicio y es exigible por escrito.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Bitácora de servicio incluida</h3>
                    <p class="benefit-text">
                        Historial técnico completo de cada intervención. Control interno para auditorías
                        y respaldo ante cualquier inspección de autoridades.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Garantía en cada visita</h3>
                    <p class="benefit-text">
                        Si el equipo falla por una causa cubierta por el servicio, volvemos sin costo
                        adicional. Respaldamos cada intervención con garantía por escrito.
                    </p>
                </div>
            </div>

        </div>

        <div style="text-align: center; margin-top: 52px;">
            <a href="#contacto"
               class="button-primary"
               aria-label="Solicitar cotización gratuita de mantenimiento de caldera industrial">
                Solicitar cotización gratuita
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     10. FAQ
     ══════════════════════════════════════════ --}}
<section class="service-calibration-accordion" id="faq">
    <div class="container service-calibration-accordion">
        <h2>Preguntas frecuentes sobre mantenimiento de calderas</h2>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cada cuánto tiempo se hace el mantenimiento de una caldera?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.75; margin: 0; font-size: 0.97rem;">
                    La frecuencia mínima contempla revisiones mensuales por el operador, mantenimientos
                    semestrales con técnico especializado y mantenimiento anual con pruebas de presión.
                    La <strong>NOM-020-STPS</strong> exige dictamen de operación segura vigente por
                    unidad verificadora acreditada ante la EMA.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Qué incluye el mantenimiento de una caldera?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.75; margin: 0; font-size: 0.97rem;">
                    Inspección visual y estructural del cuerpo de presión, limpieza de superficies de
                    intercambio de calor, revisión y calibración de quemadores, análisis fisicoquímico
                    del agua, prueba funcional de válvulas de seguridad y controles automáticos, y
                    reporte técnico documentado.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cuánto cuesta el mantenimiento de una caldera industrial?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.75; margin: 0; font-size: 0.97rem;">
                    El costo varía según capacidad, combustible y antigüedad del equipo. Un mantenimiento
                    semestral para caldera de mediana capacidad puede oscilar entre
                    <strong>$18,000 y $45,000 MXN</strong>. Se recomienda evaluación técnica previa
                    para una propuesta específica.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Qué norma regula el mantenimiento de calderas en México?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.75; margin: 0; font-size: 0.97rem;">
                    La <strong>NOM-020-STPS-2011</strong> establece las condiciones de seguridad para
                    operación, mantenimiento e inspección de generadores de vapor y calderas. El
                    <strong>Código ASME</strong> es la referencia técnica de diseño y fabricación
                    reconocida en México.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Qué pasa si no se le da mantenimiento a una caldera?
                    </span>
                    <span class="accordion__icon-box" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.75; margin: 0; font-size: 0.97rem;">
                    Acumulación de incrustaciones que reducen eficiencia, mayor consumo de combustible y
                    riesgo de fallas críticas en válvulas y tubos. En casos extremos puede ocurrir
                    explosión o fuga de vapor. Sin dictamen NOM-020 vigente la empresa enfrenta sanciones
                    legales y responsabilidades ante el <strong>IMSS</strong>.
                </p>
            </details>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════
     11. CTA FINAL
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

            <h2>Mantén tu caldera operando de forma segura y eficiente.</h2>

            <p>
                Agenda tu mantenimiento preventivo hoy. Respuesta en menos de 24 horas.
                Cotización gratuita y sin compromiso para tu planta.
            </p>

            <div class="final-cta__actions">
                <a href="tel:+524494348018"
                   class="button-primary final-cta__actions-water"
                   aria-label="Llamar para agendar mantenimiento de caldera industrial">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Llamar ahora: +52 449 434 8018
                </a>

                <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20agendar%20mantenimiento%20a%20caldera."
                   target="_blank"
                   rel="noopener"
                   class="button-secondary final-cta__actions-water bm-whatsapp-btn"
                   aria-label="Solicitar cotización de mantenimiento de caldera por WhatsApp">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12.004 2C6.477 2 2 6.477 2 12.004a9.954 9.954 0 0 0 1.357 5.014L2 22l5.113-1.335A9.97 9.97 0 0 0 12.004 22C17.53 22 22 17.525 22 12.004 22 6.477 17.53 2 12.004 2zM12 20.007a7.997 7.997 0 0 1-4.076-1.117l-.293-.174-3.036.794.811-2.957-.191-.303A7.985 7.985 0 0 1 4.007 12c0-4.41 3.589-8 7.997-8 4.41 0 7.996 3.59 7.996 8 0 4.41-3.586 7.997-7.997 8z"/>
                    </svg>
                    Cotizar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
