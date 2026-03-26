@extends('frontend.layouts.master')

@section('styles')
@endsection

@section('content')
  <section class="automation-hero">
    <div class="automation-hero__bg">
      <img
        src="https://images.unsplash.com/photo-1763296479464-fe8bee23eb65?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGNvbnRyb2wlMjBwYW5lbHxlbnwxfHx8fDE3NzIxNjA1ODZ8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
        alt="Panel de control para automatización de sistemas térmicos industriales"
        width="1080"
        height="720"
        loading="eager"
        decoding="async"
      />
    </div>

    <div class="container automation-hero__container">
      <div class="automation-hero__content">
        <h1>Automatización de Sistemas Térmicos Industriales</h1>
        <p>Modernización inteligente con PLC, HMI y SCADA para control remoto total</p>
        <a class="button-primary automation" href="/contacto" aria-label="Solicitar cotización de automatización industrial">
          Solicitar Cotización
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
        </a>
      </div>
    </div>
  </section>

  <section class="automation-overview">
    <div class="container">
      <div class="automation-overview__grid">
        <article class="automation-overview__intro">
          <h2>¿En qué consiste la Automatización?</h2>
          <p>
            Automatización industrial mediante PLC, HMI y sistemas SCADA permite
            supervisión remota en tiempo real, registro histórico de variables críticas
            y optimización de ciclos de operación.
          </p>
          <p>
            Implementamos arquitecturas de control con redundancia, protecciones
            programadas y conectividad IoT para monitoreo desde dispositivos móviles.
          </p>
        </article>

        <div class="automation-overview__list">
          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Calderas industriales</h3>
          </article>

          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Salas múltiples</h3>
          </article>

          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Plantas de vapor</h3>
          </article>

          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Distribución térmica</h3>
          </article>

          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Torres de enfriamiento</h3>
          </article>

          <article class="automation-tag">
            <span class="automation-tag__icon" aria-hidden="true">✓</span>
            <h3>Cuartos de máquinas</h3>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="automation-includes">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>¿Qué incluye la Automatización?</h2>
      </div>

      <div class="automation-includes__grid">
        <article class="automation-feature automation-feature--primary automation-feature--large">
          <div class="automation-feature__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu mb-6"><rect width="16" height="16" x="4" y="4" rx="2"></rect><rect width="6" height="6" x="9" y="9" rx="1"></rect><path d="M15 2v2"></path><path d="M15 20v2"></path><path d="M2 15h2"></path><path d="M2 9h2"></path><path d="M20 15h2"></path><path d="M20 9h2"></path><path d="M9 2v2"></path><path d="M9 20v2"></path></svg></div>
          <h3>Control con PLC Industrial</h3>
          <p>
            Programación de lógicas de control con PLC Siemens, Allen-Bradley o
            Schneider con redundancia y respaldo de energía.
          </p>

          <ul class="automation-feature__list">
            <li>Lógicas programadas personalizadas</li>
            <li>Respaldo de energía UPS</li>
            <li>Redundancia de controladores</li>
          </ul>
        </article>

        <article class="automation-feature automation-feature--light">
          <div class="automation-feature__icon automation-feature__icon--blue" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor text-[#0054ff] mb-4"><rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line></svg></div>
          <h3>Pantalla HMI Táctil</h3>
          <p>
            Interfaz gráfica intuitiva con sinópticos animados, tendencias en tiempo
            real y registro de eventos históricos.
          </p>
        </article>

        <article class="automation-feature automation-feature--light">
          <div class="automation-feature__icon automation-feature__icon--blue" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-[#0054ff] mb-4"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
          <h3>Protecciones Programadas</h3>
          <p>
            Enclavamientos de seguridad, cortes automáticos y fallas de flama programables.
          </p>
        </article>

        <article class="automation-feature automation-feature--dark automation-feature--wide">
          <div class="automation-subfeatures">
            <article class="automation-subfeature">
              <div class="automation-subfeature__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap mb-4"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg></div>
              <h3>Monitoreo Remoto</h3>
              <p>Acceso web y app móvil con alertas inteligentes por SMS.</p>
            </article>

            <article class="automation-subfeature">
              <div class="automation-subfeature__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up mb-4"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg></div>
              <h3>Registro Histórico</h3>
              <p>Base de datos de parámetros y consumos energéticos.</p>
            </article>

            <article class="automation-subfeature">
              <div class="automation-subfeature__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users mb-4"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
              <h3>Capacitación</h3>
              <p>Entrenamiento técnico y soporte remoto permanente.</p>
            </article>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="automation-benefits">
    <div class="container">
      <div class="section-heading section-heading--center section-heading--light">
        <h2>Beneficios Operativos Garantizados</h2>
      </div>

      <div class="automation-benefits__grid">
        <article class="benefit-card">
          <span class="benefit-card__number">25%</span>
          <h3>Ahorro Energético</h3>
          <p>Optimización automática de encendidos según demanda real.</p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">80%</span>
          <h3>Reducción de Fallas</h3>
          <p>Diagnóstico predictivo previene fallas catastróficas 24/7.</p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">100%</span>
          <h3>Control Remoto Total</h3>
          <p>Supervisión desde dispositivos móviles sin presencia física.</p>
        </article>

        <article class="benefit-card">
          <span class="benefit-card__number">∞</span>
          <h3>Trazabilidad Documental</h3>
          <p>Registros automáticos para ISO 9001 y auditorías.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="automation-process">
    <div class="container">
      <div class="section-heading section-heading--center">
        <h2>Proceso de Implementación</h2>
      </div>

      <div class="process-grid">
        <article class="process-card process-card--primary">
          <span class="process-card__step">01</span>
          <h3>Análisis</h3>
          <p>Levantamiento de variables y definición de funcionalidades.</p>
        </article>

        <article class="process-card process-card--primary">
          <span class="process-card__step">02</span>
          <h3>Programación</h3>
          <p>Desarrollo de lógicas y diseño de interfaces HMI.</p>
        </article>

        <article class="process-card process-card--primary">
          <span class="process-card__step">03</span>
          <h3>Instalación</h3>
          <p>Montaje de equipos, cableado y configuración de red.</p>
        </article>

        <article class="process-card process-card--primary">
          <span class="process-card__step">04</span>
          <h3>Puesta en Marcha</h3>
          <p>Arranque supervisado y capacitación del personal.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="automation-cta">
    <div class="container">
      <div class="automation-cta__stats">
        <article class="automation-stat">
          <span class="automation-stat__number">30+</span>
          <h3>Años Experiencia</h3>
        </article>

        <article class="automation-stat">
          <span class="automation-stat__number">150+</span>
          <h3>Proyectos Automatizados</h3>
        </article>

        <article class="automation-stat">
          <span class="automation-stat__number">24/7</span>
          <h3>Soporte Técnico</h3>
        </article>
      </div>

      <div class="automation-cta__content">
        <h2>Modernice sus Sistemas Térmicos</h2>
        <p>
          La operación manual genera ineficiencias y falta de trazabilidad. Implemente
          automatización industrial que optimice procesos con control remoto total.
        </p>

        <div class="automation-cta__actions">
          <a class="button-primary final-cta__actions-water" href="/contacto">
            Solicitar Cotización
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