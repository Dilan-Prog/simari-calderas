@extends('frontend.layouts.master')

@section('title', 'Reparación de Calderas | Servicio de Emergencia · Equiterm Industries')
@section('description', 'Reparación de calderas industriales con técnicos en sitio. Diagnóstico, refacciones y solución en el menor tiempo posible. Atendemos emergencias. Cotiza gratis.')
@section('canonical', config('app.url') . '/reparacion-calderas')

@section('og_title', 'Reparación de Calderas | Servicio de Emergencia · Equiterm Industries')
@section('og_description', 'Reparación de calderas industriales con técnicos en sitio. Diagnóstico, refacciones y solución en el menor tiempo posible. Atendemos emergencias. Cotiza gratis.')
@section('og_url', config('app.url') . '/reparacion-calderas')
@section('og_image', config('app.url') . '/images/og-reparacion-calderas.jpg')

@section('schema')
<meta name="keywords" content="reparación de calderas, reparación calderas industriales, servicio emergencia calderas">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Reparación de Calderas | Servicio de Emergencia · Equiterm Industries">
<meta name="twitter:description" content="Reparación de calderas industriales con técnicos en sitio. Diagnóstico, refacciones y solución en el menor tiempo posible. Atendemos emergencias. Cotiza gratis.">
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
  "description": "Reparación de calderas industriales con técnicos en sitio, diagnóstico inmediato y refacciones en inventario. Atención de emergencias 24/7.",
  "areaServed": "México",
  "url": "{{ config('app.url') . '/reparacion-calderas' }}"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Tienen servicio de reparación de calderas las 24 horas?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí, atendemos emergencias fuera de horario en las ciudades donde operamos. Una caldera parada en producción no puede esperar hasta el siguiente día hábil."
      }
    },
    {
      "@type": "Question",
      "name": "¿Cuánto tarda una reparación de caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Depende del tipo de falla. La mayoría de los fallos de quemador y controles se resuelven en la misma visita. Fallas estructurales o refacciones especiales pueden tomar 24 a 72 horas adicionales."
      }
    },
    {
      "@type": "Question",
      "name": "¿La reparación incluye garantía?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sí. Todas nuestras reparaciones incluyen garantía en mano de obra. Las refacciones cubren la garantía del fabricante."
      }
    },
    {
      "@type": "Question",
      "name": "¿Qué información necesito dar al llamar por una emergencia?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Marca y modelo de la caldera si lo tienes a la mano, descripción del síntoma (qué hace o qué deja de hacer), y tu ubicación. Con eso asignamos el técnico adecuado."
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
             alt="Técnico reparando quemador de caldera industrial en sitio"
             width="1200" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-hair-repair">
        <div class="hero-content">

            {{-- Badge de urgencia --}}
            {{-- CLASE SUGERIDA: .badge-emergency { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; background: #dc2626; color: #fff; border-radius: 999px; font-size: 14px; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 24px; } --}}
            <div class="automation-tag" style="width: fit-content; margin-bottom: 24px;">
                <span class="automation-tag__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"></path>
                    </svg>
                </span>
                <h2 style="margin: 0; font-size: 14px; font-weight: 700; letter-spacing: 0.4px;">
                    Servicio de emergencia 24/7
                </h2>
            </div>

            <h1 class="hero-title">
                Reparación de Calderas Industriales — Atención de Emergencia
            </h1>

            <p class="hero-description">
                Una caldera fuera de operación detiene la producción. Cada hora parada tiene un costo
                real para tu empresa. Nuestro servicio de reparación de calderas prioriza el tiempo de
                respuesta: técnico en sitio, diagnóstico inmediato y reparación con refacciones en
                inventario para los fallos más comunes.
            </p>

            {{-- Bloque CTA de urgencia — máximo peso visual --}}
            <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 32px; margin-top: 8px;">
                <p class="hero-description" style="font-weight: 700; font-size: clamp(1.1rem, 2vw, 1.375rem); margin-bottom: 10px;">
                    ¿Tu caldera está fuera de servicio ahorita?
                </p>
                <p class="hero-description" style="margin-bottom: 28px;">
                    Llama directo. No formularios. Técnico en sitio en menos de 24 horas
                    en las ciudades donde operamos.
                </p>
                <div class="hero-actions">
                    <a href="tel:+524494348018"
                       class="button-primary"
                       aria-label="Llamar para emergencia de caldera ahora">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        Llamar ahora: +52 449 434 8018
                    </a>
                    <a href="https://wa.me/524494348018?text=Hola%2C%20tengo%20una%20emergencia%20con%20mi%20caldera%2C%20necesito%20un%20t%C3%A9cnico."
                       target="_blank"
                       rel="noopener"
                       class="button-secondary"
                       aria-label="Reportar emergencia de caldera por WhatsApp">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12.004 2C6.477 2 2 6.477 2 12.004a9.954 9.954 0 0 0 1.357 5.014L2 22l5.113-1.335A9.97 9.97 0 0 0 12.004 22C17.53 22 22 17.525 22 12.004 22 6.477 17.53 2 12.004 2zM12 20.007a7.997 7.997 0 0 1-4.076-1.117l-.293-.174-3.036.794.811-2.957-.191-.303A7.985 7.985 0 0 1 4.007 12c0-4.41 3.589-8 7.997-8 4.41 0 7.996 3.59 7.996 8 0 4.41-3.586 7.997-7.997 8z"/>
                        </svg>
                        WhatsApp emergencia
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     2. FALLAS QUE ATENDEMOS
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="fallas">
    <div class="container what-includes-section">
        <h2 class="section-main-title">Fallas más comunes en calderas que reparamos</h2>
        <p class="service-text-industrial" style="text-align: center; max-width: 860px; margin: 0 auto 40px;">
            Con años de experiencia en campo, hemos atendido prácticamente todos los tipos de falla. Las más
            frecuentes son falla en quemador o sistema de encendido, pérdida de presión por fugas en tubería
            o conexiones, falla en válvulas de seguridad o purga, problemas en controles electrónicos y
            termostatos, caldera que no enciende o apaga de forma intermitente, y ruidos anormales por
            golpes de ariete o incrustaciones.
        </p>

        <div class="includes-grid">

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
                <h3 class="include-card-title">
                    <strong>Falla en quemador o sistema de encendido</strong>
                </h3>
                <p class="include-card-text">
                    Causa más frecuente de paro no programado en calderas industriales y residenciales.
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
                <h3 class="include-card-title">
                    <strong>Fugas de agua o vapor</strong>
                </h3>
                <p class="include-card-text">
                    En tuberías, conexiones y sellos; implican pérdida de presión y riesgo operativo.
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
                <h3 class="include-card-title">
                    <strong>Válvulas de seguridad, presostatos y controles eléctricos defectuosos</strong>
                </h3>
                <p class="include-card-text">
                    Fallas que comprometen la seguridad y el cumplimiento normativo STPS.
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
                <h3 class="include-card-title">
                    <strong>Caldera que no mantiene presión o temperatura de operación</strong>
                </h3>
                <p class="include-card-text">
                    Síntoma de múltiples causas posibles: incrustaciones, fugas internas o falla de control.
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
                <h3 class="include-card-title">
                    <strong>Humo negro o exceso de CO₂</strong>
                </h3>
                <p class="include-card-text">
                    Combustión ineficiente por quemador sucio o desajustado — aumenta el consumo y el riesgo.
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
                <h3 class="include-card-title">
                    <strong>Golpes de ariete, ruidos y vibraciones anormales</strong>
                </h3>
                <p class="include-card-text">
                    Indicadores de incrustaciones severas o problemas hidráulicos en el sistema de vapor.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     3. PROCESO DE REPARACIÓN
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="proceso">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">¿Cómo funciona nuestro servicio de reparación?</h2>
        <p class="service-text-industrial">
            Cuando nos contactas por una emergencia, asignamos al técnico más cercano a tu ubicación.
            Al llegar realiza un diagnóstico completo del equipo e identifica la <strong>causa raíz</strong>
            de la falla — no solo el síntoma. Te presentamos la <strong>cotización</strong> de reparación
            con desglose de mano de obra y refacciones antes de proceder. Una vez autorizada, ejecutamos
            la reparación y realizamos prueba de funcionamiento completa antes de entregar el equipo.
        </p>

        <div class="applications-card">
            <p class="service-text-industrial" style="margin: 24px 0;">
                Para fallas que requieren refacciones especiales, te damos un tiempo estimado realista.
                Nunca dejamos un equipo "medio reparado" — si no podemos completar en esa visita,
                acordamos contigo la siguiente acción.
            </p>
        </div>

        {{-- TODO: <img src="{{ asset('images/services/reparacion-calderas/diagnostico-caldera-industrial-tecnico-sitio.jpg') }}"
             alt="Técnico realizando diagnóstico de caldera industrial en sitio"
             width="1200" height="600" loading="lazy" decoding="async"> --}}
    </div>
</section>

{{-- ══════════════════════════════════════════
     4. CTA INTERMEDIO — DIAGNÓSTICO
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
                     stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </a>
            <a href="tel:+524494348018"
               class="button-secondary work-process"
               aria-label="Llamar para emergencia de caldera">
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
     5. TIPOS DE CALDERA
     ══════════════════════════════════════════ --}}
<section class="why-choose-us" id="tipos-caldera">
    <div class="container why-choose-simari-section">
        <h2 class="section-main-title">Reparamos todos los tipos de caldera</h2>
        <p class="service-text-industrial" style="text-align: center; max-width: 820px; margin: 0 auto 40px;">
            Contamos con experiencia en reparación de calderas pirotubulares, acuotubulares, calderas de
            vapor saturado, calderas de agua caliente, calderas de gas LP y gas natural, así como sistemas
            de calefacción central industrial. Si tu equipo es de una marca o modelo específico, consúltanos
            — en la mayoría de los casos podemos conseguir la refacción original o equivalente.
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
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <h3>Calderas acuotubulares</h3>
            </div>

            <div class="equipment-card">
                <div class="equipment-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2a5 5 0 0 1 5 5c0 5-5 11-5 11S7 12 7 7a5 5 0 0 1 5-5z"></path>
                        <circle cx="12" cy="7" r="2"></circle>
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
     6. FAQ
     ══════════════════════════════════════════ --}}

{{-- CSS SUGERIDO para acordeón nativo:
     summary.accordion__header { list-style: none; cursor: pointer; }
     summary.accordion__header::-webkit-details-marker { display: none; }
     .faq-answer { padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px; } --}}

<section class="service-calibration-accordion" id="faq">
    <div class="container service-calibration-accordion">
        <h2>Preguntas frecuentes · Reparación de calderas</h2>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Tienen servicio de reparación de calderas las 24 horas?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Sí, atendemos emergencias fuera de horario en las ciudades donde operamos.
                    Una caldera parada en producción no puede esperar hasta el siguiente día hábil.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Cuánto tarda una reparación de caldera?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Depende del tipo de falla. La mayoría de los fallos de quemador y controles se
                    resuelven en la misma visita. Fallas estructurales o refacciones especiales pueden
                    tomar <strong>24 a 72 horas</strong> adicionales.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿La reparación incluye garantía?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Sí. Todas nuestras reparaciones incluyen <strong>garantía</strong> en mano de obra.
                    Las refacciones cubren la garantía del fabricante.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Qué información necesito dar al llamar por una emergencia?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Marca y modelo de la caldera si lo tienes a la mano, descripción del síntoma
                    (qué hace o qué deja de hacer), y tu ubicación. Con eso asignamos el técnico adecuado.
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

            <h2>Cada minuto con la caldera parada tiene un costo. Llama ahora.</h2>

            <p>
                Técnico en sitio. <strong>Diagnóstico gratuito</strong>. Reparación con <strong>garantía</strong>.
            </p>

            <div class="final-cta__actions">
                <a href="tel:+524494348018"
                   class="button-primary final-cta__actions-water"
                   aria-label="Llamar para reparación urgente de caldera">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Llamar: +52 449 434 8018
                </a>

                <a href="https://wa.me/524494348018?text=Hola%2C%20necesito%20reparaci%C3%B3n%20urgente%20de%20caldera."
                   target="_blank"
                   rel="noopener"
                   class="button-secondary final-cta__actions-water"
                   aria-label="Solicitar reparación de caldera por WhatsApp">
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
