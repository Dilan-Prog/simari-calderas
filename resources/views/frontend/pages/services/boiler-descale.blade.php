@extends('frontend.layouts.master')

@section('title', 'Desincrustación de Calderas | Limpieza Química e Industrial · Equiterm Industries')
@section('description', 'Servicio de desincrustación de calderas con productos químicos certificados. Eliminamos sarro e incrustaciones y recuperamos la eficiencia original de tu equipo. Cotiza gratis.')
@section('canonical', config('app.url') . '/desincrustacion-calderas')

@section('og_title', 'Desincrustación de Calderas | Limpieza Química e Industrial · Equiterm Industries')
@section('og_description', 'Servicio de desincrustación de calderas con productos químicos certificados. Eliminamos sarro e incrustaciones y recuperamos la eficiencia original de tu equipo. Cotiza gratis.')
@section('og_url', config('app.url') . '/desincrustacion-calderas')
@section('og_image', config('app.url') . '/images/og-desincrustacion-calderas.jpg')

@section('schema')
<meta name="keywords" content="desincrustación de calderas, limpieza de calderas, tratamiento antiincrustante, sarro en calderas">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Desincrustación de Calderas | Limpieza Química e Industrial · Equiterm Industries">
<meta name="twitter:description" content="Servicio de desincrustación de calderas con productos químicos certificados. Eliminamos sarro e incrustaciones y recuperamos la eficiencia original de tu equipo. Cotiza gratis.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Desincrustación de Calderas",
  "serviceType": "Limpieza química e industrial de calderas",
  "description": "Eliminación de sarro e incrustaciones en calderas industriales y residenciales con productos químicos certificados y biodegradables.",
  "provider": {
    "@type": "Organization",
    "name": "Equiterm Industries"
  },
  "areaServed": "México",
  "url": "{{ config('app.url') . '/desincrustacion-calderas' }}"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Con qué frecuencia se debe hacer la desincrustación de una caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Depende de la dureza del agua de alimentación y las horas de operación. Con agua dura sin tratamiento previo, puede requerirse cada 6 a 12 meses. Con un programa de tratamiento antiincrustante continuo, el intervalo puede extenderse a 2 o 3 años."
      }
    },
    {
      "@type": "Question",
      "name": "¿La desincrustación daña los materiales de la caldera?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No, siempre que se use el producto correcto y en la concentración adecuada. Por eso realizamos análisis previo — un ácido demasiado agresivo puede atacar el acero. Nunca aplicamos un tratamiento genérico sin diagnóstico."
      }
    },
    {
      "@type": "Question",
      "name": "¿Tienen que parar la producción durante la desincrustación?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Generalmente sí, el equipo debe estar fuera de operación durante el tratamiento. El tiempo varía entre 4 y 12 horas según el grado de incrustación. Coordinamos el servicio para minimizar el impacto en tu proceso productivo."
      }
    },
    {
      "@type": "Question",
      "name": "¿Qué es el tratamiento antiincrustante y lo ofrecen?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Es la aplicación continua o periódica de productos químicos que evitan que los minerales precipiten y se depositen. Sí lo ofrecemos como complemento al servicio de desincrustación, en modalidad de suministro mensual o trimestral."
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
        {{-- TODO: <img src="{{ asset('images/services/desincrustacion-calderas/desincrustacion-quimica-caldera-industrial.jpg') }}"
             alt="Proceso de desincrustación química de caldera industrial en México"
             width="1200" height="800" loading="eager" fetchpriority="high" decoding="async"> --}}
        <div class="hero-overlay"></div>
    </div>

    <div class="container hero-hair-repair">
        <div class="hero-content">

            {{-- Badge informativo --}}
            {{-- CLASE SUGERIDA: .badge-info { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; background: #f59e0b; color: #fff; border-radius: 999px; font-size: 14px; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 24px; } --}}
            <div class="automation-tag" style="width: fit-content; margin-bottom: 24px;">
                <span class="automation-tag__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M12 2a10 10 0 0 1 0 20 10 10 0 0 1 0-20z"></path>
                        <path d="M12 7v5l3 3"></path>
                    </svg>
                </span>
                <h2 style="margin: 0; font-size: 14px; font-weight: 700; letter-spacing: 0.4px;">
                    Limpieza química certificada
                </h2>
            </div>

            <h1 class="hero-title">
                Desincrustación de Calderas — Recupera la Eficiencia de tu Equipo
            </h1>

            <p class="hero-description">
                El sarro y las incrustaciones minerales son el enemigo silencioso de una caldera. Solo
                <strong>3 mm</strong> de incrustación en las superficies de intercambio de calor pueden
                reducir la eficiencia térmica hasta un <strong>25%</strong> y aumentar el riesgo de
                sobrecalentamiento y ruptura de tubos. El servicio de desincrustación de calderas elimina
                estos depósitos y devuelve al equipo su eficiencia y vida útil originales.
            </p>

            {{-- Bloque CTA con problema + solución --}}
            <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 32px; margin-top: 8px;">
                <p class="hero-description" style="font-weight: 700; font-size: clamp(1.1rem, 2vw, 1.375rem); margin-bottom: 10px;">
                    ¿Tu caldera consume más combustible que antes? El sarro puede ser la causa.
                </p>
                <p class="hero-description" style="margin-bottom: 28px;">
                    Solicita un análisis de agua gratuito. Te decimos si tu equipo necesita
                    desincrustación antes de que el problema sea mayor.
                </p>
                <div class="hero-actions">
                    <a href="#contacto"
                       class="button-primary"
                       aria-label="Solicitar análisis de agua gratuito para caldera">
                        Solicitar análisis gratuito
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20el%20servicio%20de%20desincrustaci%C3%B3n%20de%20caldera."
                       target="_blank"
                       rel="noopener"
                       class="button-secondary"
                       aria-label="Consultar sobre desincrustación de caldera por WhatsApp">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                             viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12.004 2C6.477 2 2 6.477 2 12.004a9.954 9.954 0 0 0 1.357 5.014L2 22l5.113-1.335A9.97 9.97 0 0 0 12.004 22C17.53 22 22 17.525 22 12.004 22 6.477 17.53 2 12.004 2zM12 20.007a7.997 7.997 0 0 1-4.076-1.117l-.293-.174-3.036.794.811-2.957-.191-.303A7.985 7.985 0 0 1 4.007 12c0-4.41 3.589-8 7.997-8 4.41 0 7.996 3.59 7.996 8 0 4.41-3.586 7.997-7.997 8z"/>
                        </svg>
                        Consultar por WhatsApp
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     2. EL PROBLEMA
     ══════════════════════════════════════════ --}}
<section class="service-description-section-industrial" id="el-problema">
    <div class="container service-description-section">
        <h2 class="service-subtitle-industrial">¿Qué le hace el sarro a una caldera?</h2>
        <p class="service-text-industrial">
            El agua de alimentación siempre contiene minerales disueltos — calcio, magnesio, sílice. Con
            el calor, estos precipitan y se depositan en las superficies internas del equipo formando una
            capa dura y porosa conocida como incrustación o sarro. Esta capa actúa como aislante térmico:
            el quemador trabaja más para transferir el mismo calor, el consumo de combustible sube, la
            temperatura de los gases de escape aumenta y los tubos sufren estrés térmico acelerado.
        </p>

        {{-- TODO: <img src="{{ asset('images/services/desincrustacion-calderas/sarro-incrustacion-tubos-caldera.jpg') }}"
             alt="Tubos de caldera con incrustación de sarro mineral — antes de desincrustación química"
             width="1200" height="600" loading="lazy" decoding="async"> --}}

        <div class="applications-card">
            <p class="service-text-industrial" style="margin: 24px 0;">
                Ignorar el sarro no solo encarece la operación — puede terminar en una
                <strong>falla catastrófica</strong> del equipo, con <strong>riesgo para el personal</strong>
                y costos de reparación que superan con creces el valor del servicio de
                desincrustación preventiva.
            </p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     3. NUESTRO PROCESO
     ══════════════════════════════════════════ --}}
<section class="what-includes-section" id="proceso">
    <div class="container what-includes-section">
        <h2 class="section-main-title">¿Cómo realizamos la desincrustación de calderas?</h2>
        <p class="service-text-industrial" style="text-align: center; max-width: 860px; margin: 0 auto 40px;">
            Utilizamos desincrustación química con productos certificados y biodegradables, adaptados al
            tipo de incrustación presente en el equipo. El proceso inicia con un análisis del agua y del
            depósito para seleccionar el producto correcto. Se circula la solución desincrustante por el
            sistema, controlando concentración, temperatura y tiempo de contacto. Al finalizar se neutraliza,
            se enjuaga el sistema y se realiza inspección visual y de presión para confirmar la limpieza.
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
                <h3 class="include-card-title">
                    <strong>Análisis previo de agua y tipo de incrustación</strong>
                </h3>
                <p class="include-card-text">
                    No aplicamos el mismo producto a todo — el diagnóstico determina el tratamiento correcto.
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
                <h3 class="include-card-title">
                    <strong>Productos químicos certificados y biodegradables</strong>
                </h3>
                <p class="include-card-text">
                    Sin daño a los materiales del equipo — acero, cobre y latón protegidos.
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
                <h3 class="include-card-title">
                    <strong>Proceso controlado con monitoreo continuo</strong>
                </h3>
                <p class="include-card-text">
                    Seguimiento de pH y temperatura durante todo el tratamiento para garantizar resultados.
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
                <h3 class="include-card-title">
                    <strong>Neutralización y enjuague final</strong>
                </h3>
                <p class="include-card-text">
                    El equipo queda listo para operar al concluir el servicio, sin residuos químicos.
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
                <h3 class="include-card-title">
                    <strong>Recomendación de tratamiento antiincrustante continuo</strong>
                </h3>
                <p class="include-card-text">
                    Para prevenir recurrencia y extender el intervalo entre desincrustaciones.
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
                <h3 class="include-card-title">
                    <strong>Reporte técnico con antes y después</strong>
                </h3>
                <p class="include-card-text">
                    Documentación del tratamiento para tus registros internos y cumplimiento normativo.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     4. CTA INTERMEDIO — ROI / AHORRO
     ══════════════════════════════════════════ --}}
<section class="work-process-section" id="ahorro">
    <div class="container work-process-section">

        {{-- Stat de ahorro destacado --}}
        <div class="benefit-card" style="text-align: center; max-width: 520px; margin-bottom: 40px;">
            <span class="benefit-card__number">10% – 25%</span>
            <h3>reducción en consumo de gas tras una desincrustación</h3>
            <p>ahorro medible en tu primera factura después del servicio</p>
        </div>

        <h2 class="cta-final-title-industrial">
            Una desincrustación bien hecha puede reducir tu consumo de gas entre
            <strong>10% y 25%</strong>.
        </h2>
        <p class="cta-final-description-industrial">
            En muchos casos el ahorro en combustible paga el servicio en menos de
            <strong>3 meses</strong> de operación.
        </p>
        <div class="cta-final-actions">
            <a href="#contacto"
               class="button-secondary work-process"
               aria-label="Calcular ahorro potencial con desincrustación de caldera">
                Calcular mi ahorro potencial
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
     5. SEÑALES DE ALERTA
     ══════════════════════════════════════════ --}}
<section class="why-choose-us" id="senales">
    <div class="container why-choose-simari-section">
        <h2 class="section-main-title">Señales de que tu caldera necesita desincrustación</h2>

        <ul class="first_list">

            <li class="list_element">
                <div class="accordion__icon-box2" aria-hidden="true">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </p>
                </div>
                <div class="list_text">
                    <h4>Aumento en el consumo de combustible sin cambio en la demanda</h4>
                    <p style="margin: 0; color: var(--text-body); font-size: 15px; line-height: 1.5;">
                        Si el quemador trabaja más tiempo para alcanzar la misma temperatura, el sarro es el sospechoso principal.
                    </p>
                </div>
            </li>

            <li class="list_element">
                <div class="accordion__icon-box2" aria-hidden="true">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </p>
                </div>
                <div class="list_text">
                    <h4>Temperatura de gases de escape más alta de lo normal</h4>
                    <p style="margin: 0; color: var(--text-body); font-size: 15px; line-height: 1.5;">
                        Los gases salen más calientes porque el calor no se transfiere al agua — se pierde por la chimenea.
                    </p>
                </div>
            </li>

            <li class="list_element">
                <div class="accordion__icon-box2" aria-hidden="true">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </p>
                </div>
                <div class="list_text">
                    <h4>Tiempo de calentamiento más largo que antes</h4>
                    <p style="margin: 0; color: var(--text-body); font-size: 15px; line-height: 1.5;">
                        La caldera tarda más en alcanzar la presión o temperatura de operación que cuando era nueva o recién limpiada.
                    </p>
                </div>
            </li>

            <li class="list_element">
                <div class="accordion__icon-box2" aria-hidden="true">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </p>
                </div>
                <div class="list_text">
                    <h4>Ruidos inusuales: golpes o chasquidos dentro del equipo</h4>
                    <p style="margin: 0; color: var(--text-body); font-size: 15px; line-height: 1.5;">
                        Las incrustaciones generan puntos calientes que causan micro-explosiones de vapor — el chasquido que escuchas.
                    </p>
                </div>
            </li>

            <li class="list_element">
                <div class="accordion__icon-box2" aria-hidden="true">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </p>
                </div>
                <div class="list_text">
                    <h4>Análisis de agua con dureza elevada de forma persistente</h4>
                    <p style="margin: 0; color: var(--text-body); font-size: 15px; line-height: 1.5;">
                        Agua dura sin tratamiento antiincrustante continuo garantiza depósitos — es solo cuestión de tiempo.
                    </p>
                </div>
            </li>

        </ul>
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
        <h2>Preguntas frecuentes · Desincrustación de calderas</h2>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Con qué frecuencia se debe hacer la desincrustación de una caldera?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Depende de la dureza del agua de alimentación y las horas de operación. Con agua dura
                    sin tratamiento previo, puede requerirse cada 6 a 12 meses. Con un programa de
                    tratamiento antiincrustante continuo, el intervalo puede extenderse a 2 o 3 años.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿La desincrustación daña los materiales de la caldera?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    No, siempre que se use el producto correcto y en la concentración adecuada. Por eso
                    realizamos análisis previo — un ácido demasiado agresivo puede atacar el acero.
                    Nunca aplicamos un tratamiento genérico sin diagnóstico.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Tienen que parar la producción durante la desincrustación?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Generalmente sí, el equipo debe estar fuera de operación durante el tratamiento. El
                    tiempo varía entre <strong>4 y 12 horas</strong> según el grado de incrustación.
                    Coordinamos el servicio para minimizar el impacto en tu proceso productivo.
                </p>
            </details>
        </div>

        <div class="accordion__item">
            <details>
                <summary class="accordion__header">
                    <span class="accordion__title">
                        ¿Qué es el tratamiento antiincrustante y lo ofrecen?
                    </span>
                </summary>
                <p style="padding: 0 24px 24px; color: var(--text-body); line-height: 1.6; margin: 0; font-size: 16px;">
                    Es la aplicación continua o periódica de productos químicos que evitan que los
                    minerales precipiten y se depositen. Sí lo ofrecemos como complemento al servicio
                    de desincrustación, en modalidad de suministro mensual o trimestral.
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
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>

            <h2>Recupera la eficiencia de tu caldera. Agenda tu desincrustación hoy.</h2>

            <p>
                Cotización gratuita. Sin compromiso.
                <strong>Análisis de agua sin costo</strong> en tu primera visita.
            </p>

            <div class="final-cta__actions">
                <a href="tel:+524494348018"
                   class="button-primary final-cta__actions-water"
                   aria-label="Llamar para agendar servicio de desincrustación de caldera">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.44a2 2 0 0 1 1.95-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.9a16 16 0 0 0 6 6l.9-.9a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Agendar visita: +52 449 434 8018
                </a>

                <a href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20agendar%20un%20servicio%20de%20desincrustaci%C3%B3n%20de%20caldera."
                   target="_blank"
                   rel="noopener"
                   class="button-secondary final-cta__actions-water"
                   aria-label="Solicitar cotización de desincrustación de caldera por WhatsApp">
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
