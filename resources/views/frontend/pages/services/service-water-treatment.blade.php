@extends('frontend.layouts.master')

@section('styles')
    @vite('resources/css/service.css')
@endsection

@section('content')
<div class="water-treatment-page">
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
        <a class="btn btn-primary" href="/contacto" aria-label="Solicitar análisis gratuito de tratamiento de agua">
          Solicitar Análisis Gratuito
          <span aria-hidden="true">→</span>
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
          <div class="timeline-item__marker" aria-hidden="true">1</div>
          <div class="timeline-item__content">
            <h3>Análisis fisicoquímico completo</h3>
            <p>
              Determinación de dureza total, alcalinidad, pH, conductividad, sílice,
              cloruros, sulfatos y contenido de oxígeno disuelto.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true">2</div>
          <div class="timeline-item__content">
            <h3>Programa de dosificación química</h3>
            <p>
              Diseño de tratamiento interno con antiincrustantes, anticorrosivos,
              secuestrantes de oxígeno y neutralizadores de pH.
            </p>
          </div>
        </article>

        <article class="timeline-item">
          <div class="timeline-item__marker" aria-hidden="true">3</div>
          <div class="timeline-item__content">
            <h3>Control de purgas</h3>
            <p>
              Optimización de purgas de fondo y superficie para eliminar lodos y
              mantener concentración de sólidos dentro de límites seguros.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true">4</div>
          <div class="timeline-item__content">
            <h3>Suministro de químicos</h3>
            <p>
              Entrega mensual de productos químicos dosificados según consumo real,
              con soporte técnico incluido en el servicio.
            </p>
          </div>
        </article>

        <article class="timeline-item">
          <div class="timeline-item__marker" aria-hidden="true">5</div>
          <div class="timeline-item__content">
            <h3>Monitoreo periódico</h3>
            <p>
              Visitas técnicas programadas, toma de muestras, análisis de tendencias
              y ajuste de dosificación según resultados.
            </p>
          </div>
        </article>

        <article class="timeline-item timeline-item--reverse">
          <div class="timeline-item__marker" aria-hidden="true">6</div>
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
          <a class="btn btn-primary" href="/contacto">
            Solicitar Análisis Gratuito
            <span aria-hidden="true">→</span>
          </a>

          <a class="btn btn-outline" href="tel:+524494348018">
            Llamar Ahora
          </a>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
