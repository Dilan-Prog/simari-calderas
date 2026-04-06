@extends('frontend.layouts.master')

@section('title')
@endsection

@section('content')

  <section class="hero-water-treatment">
    <div class="hero-water-treatment__bg">
      <img
        src="https://images.unsplash.com/photo-1770048924540-e6311d4308ce?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3YXRlciUyMHRyZWF0bWVudCUyMGluZHVzdHJpYWwlMjBzeXN0ZW18ZW58MXx8fHwxNzcyMTYwNTg1fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
        alt="Sistema industrial para tratamiento de agua en calderas"
        width="1080"
        height="720"
        loading="eager"
        decoding="async"
      />
    </div>

    <div class="container hero-water-treatment__container">
      <div class="hero-water-treatment__content">
        <h1>Tratamiento de Agua para Calderas Industriales</h1>
        <p>
          Proteja su inversión con tratamiento químico especializado que previene
          incrustaciones, corrosión y deterioro prematuro de equipos térmicos críticos.
        </p>
        <a class="button-primary hero-water-treatment-button" href="/contacto" aria-label="Solicitar análisis gratuito de tratamiento de agua">
          Solicitar Análisis Gratuito
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
        </a>
      </div>
    </div>
  </section>

  <section class="service-overview">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>¿En qué consiste el servicio de Tratamiento de Agua?</h2>
        <p>
          El tratamiento químico del agua de alimentación y agua de caldera es
          fundamental para prevenir incrustaciones calcáreas, corrosión electroquímica
          y arrastre de sólidos que reducen la eficiencia térmica y comprometen la
          integridad estructural de tubos, domos y superficies de intercambio.
        </p>
      </div>

      <div class="equipment-grid">
        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Calderas pirotubulares</h3>
        </article>

        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Generadores de vapor</h3>
        </article>

        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Torres de enfriamiento</h3>
        </article>

        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Intercambiadores de calor</h3>
        </article>

        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Sistemas de condensado</h3>
        </article>

        <article class="equipment-card">
          <div class="equipment-card__icon" aria-hidden="true">✓</div>
          <h3>Chillers industriales</h3>
        </article>
      </div>
    </div>
  </section>

  <section class="service-includes">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>¿Qué incluye nuestro servicio?</h2>
      </div>

      <div class="timeline">
        <article class="timeline-item">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-beaker text-white"><path d="M4.5 3h15"></path><path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path><path d="M6 14h12"></path></svg></div>
          <div class="timeline-item__content">
            <h3>Análisis fisicoquímico completo</h3>
            <p>
              Determinación de dureza total, alcalinidad, pH, conductividad, sílice,
              cloruros, sulfatos y contenido de oxígeno disuelto.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask-conical text-white"><path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"></path><path d="M6.453 15h11.094"></path><path d="M8.5 2h7"></path></svg></div>
          <div class="timeline-item__content">
            <h3>Programa de dosificación química</h3>
            <p>
              Diseño de tratamiento interno con antiincrustantes, anticorrosivos,
              secuestrantes de oxígeno y neutralizadores de pH.
            </p>
          </div>
        </article>

        <article class="timeline-item">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
          <div class="timeline-item__content">
            <h3>Control de purgas</h3>
            <p>
              Optimización de purgas de fondo y superficie para eliminar lodos y
              mantener concentración de sólidos dentro de límites seguros.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplet text-white"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path></svg></div>
          <div class="timeline-item__content">
            <h3>Suministro de químicos</h3>
            <p>
              Entrega mensual de productos químicos dosificados según consumo real,
              con soporte técnico incluido en el servicio.
            </p>
          </div>
        </article>

        <article class="timeline-item">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg></div>
          <div class="timeline-item__content">
            <h3>Monitoreo periódico</h3>
            <p>
              Visitas técnicas programadas, toma de muestras, análisis de tendencias
              y ajuste de dosificación según resultados.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path></svg></div>
          <div class="timeline-item__content">
            <h3>Reportes técnicos</h3>
            <p>
              Informes mensuales con históricos de parámetros, gráficas de tendencias
              y recomendaciones correctivas documentadas.
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="operational-benefits">
    <div class="container">
      <div class="section-heading section-heading--center section-heading--light">
        <h2>Beneficios operativos medibles</h2>
      </div>

      <div class="benefits-grid">
        <article class="benefit-card">
          <span class="benefit-card__number">15%</span>
          <h3>Aumento de eficiencia</h3>
          <p>
            Superficies limpias sin incrustaciones permiten mejor transferencia de calor,
            reduciendo consumo de combustible significativamente.
          </p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">100%</span>
          <h3>Prevención de fallas</h3>
          <p>
            Eliminación de corrosión bajo depósito que causa perforaciones en tubos y
            fallas estructurales costosas.
          </p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">+50%</span>
          <h3>Extensión de vida útil</h3>
          <p>
            Protección química permanente que duplica la vida operativa de calderas,
            tubos e intercambiadores de calor.
          </p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">70%</span>
          <h3>Reducción de paros</h3>
          <p>
            Menor frecuencia de paros para desincrustación mecánica, manteniendo
            continuidad operativa del proceso productivo.
          </p>
        </article>
      </div>
    </div>
  </section>

  <section class="work-process">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>Nuestro proceso de trabajo</h2>
      </div>

      <div class="process-grid">
        <article class="process-card process-card--primary">
          <span class="process-card__step">01</span>
          <h3>Análisis inicial</h3>
          <p>
            Toma de muestras de agua de alimentación, agua de caldera y condensado
            para caracterización completa.
          </p>
        </article>

        <article class="process-card process-card--dark">
          <span class="process-card__step">02</span>
          <h3>Diseño del programa</h3>
          <p>
            Formulación personalizada según calidad de agua, presión de operación y
            ciclos de concentración.
          </p>
        </article>

        <article class="process-card process-card--primary">
          <span class="process-card__step">03</span>
          <h3>Implementación</h3>
          <p>
            Instalación de bombas dosificadoras, puesta en marcha y capacitación al
            personal operativo.
          </p>
        </article>

        <article class="process-card process-card--dark">
          <span class="process-card__step">04</span>
          <h3>Seguimiento</h3>
          <p>
            Monitoreo mensual, análisis de tendencias y entrega de reportes técnicos
            detallados.
          </p>
        </article>
      </div>
    </div>
  </section>

  <section class="why-choose-us">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>Por qué elegir a SIMARI CALDERAS</h2>
      </div>

      <div class="trust-grid">
        <article class="trust-card">
          <span class="trust-card__number">30+</span>
          <h3>Años de experiencia</h3>
          <p>
            Especialistas en tratamiento químico de agua para calderas industriales
            con certificación técnica avalada.
          </p>
        </article>

        <article class="trust-card">
          <span class="trust-card__number">500+</span>
          <h3>Sistemas protegidos</h3>
          <p>
            Calderas y sistemas térmicos operando con nuestros programas de tratamiento
            químico exitosamente.
          </p>
        </article>

        <article class="trust-card">
          <span class="trust-card__number">99%</span>
          <h3>Satisfacción cliente</h3>
          <p>
            Clientes industriales que renuevan anualmente por resultados comprobados y
            servicio excepcional.
          </p>
        </article>
      </div>
    </div>
  </section>

  <section class="final-cta">
    <div class="container">
      <div class="final-cta__content">
        <div class="final-cta__icon" aria-hidden="true">!</div>
        <h2>Proteja su inversión industrial con agua de calidad</h2>
        <p>
          Las incrustaciones y la corrosión son enemigos silenciosos que deterioran sus
          equipos térmicos progresivamente. Implemente un programa de tratamiento químico
          profesional que garantice operación eficiente en hoteles, hospitales, lavanderías
          y plantas industriales.
        </p>

        <div class="final-cta__actions">
          <a class="button-primary final-cta__actions-water" href="/contacto">
            Solicitar Análisis Gratuito
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>

          <a class="button-secondary final-cta__actions-water" href="tel:+524494348018">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            Llamar Ahora
          </a>
        </div>
      </div>
    </div>
  </section>
@endsection
