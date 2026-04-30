@extends('frontend.layouts.master')

@section('title', 'Mantenimiento de Calderas | Preventivo e Industrial · Equiterm Industries')
@section('description', 'Servicio de mantenimiento de calderas industriales y residenciales. Técnicos certificados, bitácora de servicio y garantía en cada visita. Cotiza sin costo.')
@section('canonical', config('app.url') . '/mantenimiento-calderas')

@section('og_title', 'Mantenimiento de Calderas | Preventivo e Industrial · Equiterm Industries')
@section('og_description', 'Servicio de mantenimiento de calderas industriales y residenciales. Técnicos certificados, bitácora de servicio y garantía en cada visita. Cotiza sin costo.')
@section('og_url', config('app.url') . '/mantenimiento-calderas')
@section('og_image', config('app.url') . '/images/og-mantenimiento-calderas.jpg')

@section('schema')
<meta name="keywords" content="mantenimiento de calderas, mantenimiento calderas industriales, mantenimiento preventivo calderas">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Mantenimiento de Calderas | Preventivo e Industrial · Equiterm Industries">
<meta name="twitter:description" content="Servicio de mantenimiento de calderas industriales y residenciales. Técnicos certificados, bitácora de servicio y garantía en cada visita. Cotiza sin costo.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Mantenimiento de Calderas",
  "serviceType": "Mantenimiento preventivo y correctivo de calderas",
  "provider": {
    "@type": "Organization",
    "name": "Equiterm Industries"
  },
  "areaServed": "México",
  "description": "Servicio de mantenimiento preventivo y correctivo de calderas industriales y residenciales con técnicos certificados, bitácora de servicio y garantía en cada visita.",
  "url": "{{ config('app.url') . '/mantenimiento-calderas' }}"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Cuánto cuesta el mantenimiento de una caldera industrial?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El costo varía según la capacidad del equipo, tipo de combustible y accesibilidad. Contáctanos para una cotización sin compromiso — no cobramos visita de diagnóstico."
      }
    },
    {
      "@type": "Question",
      "name": "¿El mantenimiento incluye refacciones?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "El mantenimiento preventivo incluye mano de obra y consumibles menores. Si durante la revisión se detectan piezas con desgaste, se cotiza por separado antes de proceder."
      }
    },
    {
      "@type": "Question",
      "name": "¿Emiten certificado de mantenimiento para STPS?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Entregamos bitácora de servicio y reporte técnico con firma de técnico certificado, válido para presentar ante inspecciones de la STPS."
      }
    },
    {
      "@type": "Question",
      "name": "¿Atienden emergencias si la caldera falla entre mantenimientos?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Los contratos de mantenimiento incluyen atención preferencial a emergencias. Para equipos sin contrato también atendemos, sujeto a disponibilidad de técnicos."
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
        {{-- TODO: <img src="{{ asset('images/services/mantenimiento-calderas/mantenimiento-preventivo-caldera-industrial.jpg') }}"
             alt="Técnico realizando mantenimiento preventivo de caldera industrial en México"
             width="1200" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-hair-repair">
        <div class="hero-content">
            <h1 class="hero-title">
                Mantenimiento de Calderas Industriales y Residenciales
            </h1>

            <p class="hero-description">
                El mantenimiento de calderas no es un gasto — es la diferencia entre una caldera que opera
                <strong>15 años</strong> y una que falla a los 5. En Equiterm Industries realizamos mantenimiento
                preventivo y correctivo con técnicos certificados, bitácora de servicio incluida y tiempos de
                respuesta reales en cada ciudad donde operamos.
            </p>

            {{-- Bloque CTA destacado --}}
            {{-- CLASE SUGERIDA: .hero-cta-block { border-top: 1px solid rgba(255,255,255,0.15); padding-top: 32px; margin-top: 8px; } --}}
            <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 32px; margin-top: 8px;">
                <p class="hero-description" style="font-weight: 700; font-size: clamp(1.1rem, 2vw, 1.375rem); margin-bottom: 10px;">
                    ¿Cuándo fue el último mantenimiento de tu caldera?
                </p>
                <p class="hero-description" style="margin-bottom: 28px;">
                    Agenda una revisión gratuita. Te decimos en qué estado está tu equipo antes de que falle.
                </p>
                <div class="hero-actions">
                    <a href="#contacto"
                       class="button-primary"
                       aria-label="Agendar revisión gratuita de caldera industrial o residencial">
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
     2. QUÉ INCLUYE
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="que-incluye">
    <div class="container what-includes-section">
        <h2 class="section-main-title">¿Qué incluye el mantenimiento preventivo de calderas?</h2>
        <p class="service-text-industrial" style="text-align: center; max-width: 860px; margin: 0 auto 40px;">
            Cada visita de mantenimiento incluye revisión completa del sistema de combustión, análisis de presión
            y temperatura de operación, limpieza de quemadores y electrodos de encendido, verificación de válvulas
            de seguridad y purgas, análisis de eficiencia de combustión, y entrega de reporte técnico con hallazgos
            y recomendaciones.
        </p>

        <div class="includes-grid">

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Revisión y ajuste de quemadores</h3>
                <p class="include-card-text">
                    Combustión eficiente reduce hasta <strong>15%</strong> el consumo de gas.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Verificación de válvulas de seguridad y presostatos</h3>
                <p class="include-card-text">
                    Cumplimiento con normativa STPS en equipos sujetos a presión.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Limpieza de sección de humos y superficies de intercambio de calor</h3>
                <p class="include-card-text">
                    Recupera la eficiencia térmica perdida por acumulación de hollín y depósitos.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Análisis de calidad del agua de alimentación</h3>
                <p class="include-card-text">
                    Previene incrustaciones y corrosión interna en la caldera.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Prueba de controles eléctricos y sistema de encendido</h3>
                <p class="include-card-text">
                    Diagnóstico completo de la secuencia de arranque y bloque de control.
                </p>
            </div>

            <div class="include-card">
                <div class="icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h3 class="include-card-title">Bitácora de servicio firmada por técnico certificado</h3>
                <p class="include-card-text">
                    Documento oficial válido para inspecciones regulatorias y auditorías internas.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     3. FRECUENCIA
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="frecuencia">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            ¿Cada cuánto tiempo se debe hacer el mantenimiento de una caldera?
        </h2>
        <p class="service-text-industrial">
            La frecuencia depende del uso. Calderas residenciales y de calefacción: mínimo una vez al año,
            idealmente antes de temporada fría. Calderas industriales con operación continua: cada 6 meses
            o según horas de operación acumuladas. Calderas de vapor a alta presión: revisión trimestral
            recomendada por normativa.
        </p>

        {{-- Callout de alerta / datos clave --}}
        <div class="applications-card">
            <p class="service-text-industrial" style="margin: 24px 0;">
                Sin mantenimiento regular, la acumulación de sarro reduce la eficiencia térmica hasta un
                <strong>20%</strong> y eleva el riesgo de fallas críticas. El costo de una reparación mayor
                supera hasta <strong>8 veces</strong> el costo de un mantenimiento preventivo anual.
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     4. CTA INTERMEDIO — CONTRATOS
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
               aria-label="Ver planes de contrato anual de mantenimiento de calderas">
                Ver planes de contrato
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
     5. POR QUÉ ELEGIRNOS
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="por-que-elegirnos">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">
            Técnicos certificados en mantenimiento de calderas a presión
        </h2>
        <p class="service-text-industrial">
            Nuestro equipo cuenta con certificación en manejo de equipos a presión conforme a la Norma Oficial
            Mexicana <strong>NOM-020-STPS</strong>. Trabajamos con calderas de gas LP, gas natural, biomasa,
            calderas pirotubulares y acuotubulares, tanto en entornos residenciales como en plantas industriales
            del sector automotriz, textil, alimentos y manufactura.
        </p>

        {{-- TODO: <img src="{{ asset('images/services/mantenimiento-calderas/equipo-tecnicos-certificados-calderas-simari.jpg') }}"
             alt="Equipo de técnicos certificados en mantenimiento de calderas a presión — Equiterm Industries"
             width="1200" height="600" loading="lazy" decoding="async"> --}}

        <div class="benefits-grid" style="margin-top: 40px;">

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Certificación NOM-020-STPS</h3>
                    <p class="benefit-text">
                        Documentación válida ante inspecciones de la Secretaría del Trabajo y Previsión Social.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Tiempos de respuesta reales</h3>
                    <p class="benefit-text">
                        SLA definido por escrito en el contrato de servicio, no promesas verbales.
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
                    <h3 class="benefit-title">Bitácora de servicio incluida</h3>
                    <p class="benefit-text">
                        Historial técnico de cada intervención para control interno y auditorías.
                    </p>
                </div>
            </div>

            <div class="benefit-item">
                <div class="benefit-icon-box" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="benefit-content">
                    <h3 class="benefit-title">Garantía en cada visita</h3>
                    <p class="benefit-text">
                        Si el equipo falla por una causa cubierta, volvemos sin costo adicional.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     6. FAQ
     ══════════════════════════════════════════ --}}

{{-- CSS SUGERIDO para el acordeón nativo:
     summary.accordion__header { list-style: none; cursor: pointer; }
     summary.accordion__header::-webkit-details-marker { display: none; }
     .faq-answer { padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px; } --}}

<section class="service-calibration-accordion" id="faq">
    <div class="container service-calibration-accordion">
        <h2>Preguntas frecuentes · Mantenimiento de calderas</h2>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cuánto cuesta el mantenimiento de una caldera industrial?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    El costo varía según la capacidad del equipo, tipo de combustible y accesibilidad.
                    Contáctanos para una cotización sin compromiso — no cobramos visita de diagnóstico.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿El mantenimiento incluye refacciones?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    El mantenimiento preventivo incluye mano de obra y consumibles menores. Si durante la
                    revisión se detectan piezas con desgaste, se cotiza por separado antes de proceder.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Emiten certificado de mantenimiento para STPS?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Sí. Entregamos bitácora de servicio y reporte técnico con firma de técnico certificado,
                    válido para presentar ante inspecciones de la STPS.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Atienden emergencias si la caldera falla entre mantenimientos?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Sí. Los contratos de mantenimiento incluyen atención preferencial a emergencias.
                    Para equipos sin contrato también atendemos, sujeto a disponibilidad de técnicos.
                </p>
            </details>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════
     7. CTA FINAL
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

            <h2>No esperes a que tu caldera falle.</h2>

            <p>
                Agenda hoy tu mantenimiento preventivo. Respuesta en menos de 24 horas.
                Cotización gratuita y sin compromiso.
            </p>

            <div class="final-cta__actions">
                <a href="tel:+524494348018"
                   class="button-primary final-cta__actions-water"
                   aria-label="Llamar para agendar mantenimiento de caldera">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Llamar ahora: +52 449 434 8018
                </a>

                <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20agendar%20un%20mantenimiento%20de%20caldera."
                   target="_blank"
                   rel="noopener"
                   class="button-secondary final-cta__actions-water"
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
