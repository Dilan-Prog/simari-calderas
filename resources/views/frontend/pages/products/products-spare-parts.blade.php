@extends('frontend.layouts.master')

@section('title')
Refacciones para Calderas Industriales - Industria Simari
@endsection
@section('content')

<section class="boilers-hero">
    <div class="boilers-hero__media">
      <img
        src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1920"
        alt="Caldera industrial SIMARI en instalación de proceso térmico"
        width="1920"
        height="1280"
        loading="eager"
        decoding="async"
      />
      <div class="boilers-hero__overlay"></div>
    </div>

    <div class="container boilers-hero__container">
      <div class="boilers-hero__content">
        <div class="boilers-badge">
          <span class="boilers-badge__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></span>
          <span>Tecnología de combustión avanzada</span>
        </div>

        <h1>
          Calderas
          <span class="boilers-hero__highlight">Industriales</span>
          <span class="boilers-hero__brand">SIMARI</span>
        </h1>

        <p>
          Soluciones térmicas de alta eficiencia para generación de vapor y agua caliente.
          Diseño robusto, operación continua 24/7, cumplimiento normativo total.
        </p>

        <div class="boilers-hero__stats">
          <article class="hero-stat-card">
            <strong>50-1000</strong>
            <span>HP Capacidad</span>
          </article>

          <article class="hero-stat-card">
            <strong>95%</strong>
            <span>Eficiencia máx</span>
          </article>

          <article class="hero-stat-card">
            <strong>600 PSI</strong>
            <span>Presión máx</span>
          </article>

          <article class="hero-stat-card">
            <strong>20+</strong>
            <span>Años vida útil</span>
          </article>
        </div>

        <div class="boilers-hero__actions">
          <a class="button-primary simari-caldera" href="/contacto" aria-label="Solicitar cotización técnica de calderas industriales">
            Solicitar Cotización Técnica
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
          </a>

          <a class="button-secondary simari-caldera" href="tel:+524494348018">
            <svg
                        xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.79 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
            Llamar Ahora
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="boilers-models">
    <div class="container ">
      <div class="section-heading-boilers-models">
        <h2>Modelos Disponibles</h2>
        <p>Desde 50 hasta más de 1000 HP. Pirotubulares y acuotubulares.</p>
      </div>

      <div class="boilers-models__grid">
        <article class="boiler-model-card">
          <div class="boiler-model-card__top">
            <div class="boiler-model-card__top-row">
              <span class="boiler-model-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></span>
              <span class="boiler-model-card__tag">Pirotubular</span>
            </div>
            <h3>PIRO-300</h3>
            <p class="boiler-model-card__capacity">50-300 HP</p>
          </div>

          <div class="boiler-model-card__body">
            <ul class="boiler-model-card__specs">
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff]"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>Presión: <strong>Hasta 150 PSI</strong></li>
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-[#0054ff]"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>Eficiencia: <strong>85-88%</strong></li>
            </ul>

            <div class="boiler-model-card__applications">
              <span>Aplicaciones:</span>
              <ul>
                <li>Hoteles medianos</li>
                <li>Lavanderías industriales</li>
              </ul>
            </div>
          </div>

          <div class="boiler-model-card__footer">
            <a href="/contacto">Consultar</a>
          </div>
        </article>

        <article class="boiler-model-card">
          <div class="boiler-model-card__top">
            <div class="boiler-model-card__top-row">
              <span class="boiler-model-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></span>
              <span class="boiler-model-card__tag">Pirotubular</span>
            </div>
            <h3>PIRO-500</h3>
            <p class="boiler-model-card__capacity">300-500 HP</p>
          </div>

          <div class="boiler-model-card__body">
            <ul class="boiler-model-card__specs">
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff]"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>Presión: <strong>Hasta 200 PSI</strong></li>
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-[#0054ff]"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>Eficiencia: <strong>88-91%</strong></li>
            </ul>

            <div class="boiler-model-card__applications">
              <span>Aplicaciones:</span>
              <ul>
                <li>Industria alimentaria</li>
                <li>Textil</li>
              </ul>
            </div>
          </div>

          <div class="boiler-model-card__footer">
            <a href="/contacto">Consultar</a>
          </div>
        </article>

        <article class="boiler-model-card">
          <div class="boiler-model-card__top">
            <div class="boiler-model-card__top-row">
              <span class="boiler-model-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></span>
              <span class="boiler-model-card__tag">Pirotubular</span>
            </div>
            <h3>PIRO-750</h3>
            <p class="boiler-model-card__capacity">500-750 HP</p>
          </div>

          <div class="boiler-model-card__body">
            <ul class="boiler-model-card__specs">
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff]"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>Presión: <strong>Hasta 250 PSI</strong></li>
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-[#0054ff]"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>Eficiencia: <strong>90-93%</strong></li>
            </ul>

            <div class="boiler-model-card__applications">
              <span>Aplicaciones:</span>
              <ul>
                <li>Hospitales grandes</li>
                <li>Complejos hoteleros</li>
              </ul>
            </div>
          </div>

          <div class="boiler-model-card__footer">
            <a href="/contacto">Consultar</a>
          </div>
        </article>

        <article class="boiler-model-card">
          <div class="boiler-model-card__top">
            <div class="boiler-model-card__top-row">
              <span class="boiler-model-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></span>
              <span class="boiler-model-card__tag">Acuotubular</span>
            </div>
            <h3>ACUO-1000</h3>
            <p class="boiler-model-card__capacity">750-1000+ HP</p>
          </div>

          <div class="boiler-model-card__body">
            <ul class="boiler-model-card__specs">
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff]"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg>Presión: <strong>Hasta 600 PSI</strong></li>
              <li><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-[#0054ff]"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>Eficiencia: <strong>92-95%</strong></li>
            </ul>

            <div class="boiler-model-card__applications">
              <span>Aplicaciones:</span>
              <ul>
                <li>Petroquímica</li>
                <li>Generación eléctrica</li>
              </ul>
            </div>
          </div>

          <div class="boiler-model-card__footer">
            <a href="/contacto">Consultar</a>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="boilers-features">
    <div class="container">
      <div class="section-heading-boilers-models">
        <h2>Características Técnicas Superiores</h2>
        <p>
          Diseño optimizado para operación continua, eficiencia energética y bajo mantenimiento
        </p>
      </div>

      <div class="boilers-features__grid">
        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-[#0054ff]"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg></div>
          <h3>Quemadores Modulantes</h3>
          <p>
            Combustión inteligente que se adapta a la demanda real, optimizando consumo de combustible.
          </p>
        </article>

        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gauge text-[#0054ff]"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg></div>
          <h3>Control Automático</h3>
          <p>
            PLC integrado con pantalla táctil. Monitoreo en tiempo real y ajustes precisos de parámetros.
          </p>
        </article>

        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-[#0054ff]"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
          <h3>Seguridad Multinivel</h3>
          <p>
            Válvulas de alivio, control de flama, bajo nivel de agua. Cumple NOM-020-STPS.
          </p>
        </article>

        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up text-[#0054ff]"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg></div>
          <h3>Alta Eficiencia</h3>
          <p>
            Diseño optimizado de tubos de fuego y economizador para máximo aprovechamiento energético.
          </p>
        </article>

        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench text-[#0054ff]"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
          <h3>Mantenimiento Simplificado</h3>
          <p>
            Puertas de inspección amplias, fácil acceso a componentes críticos. Reduce tiempos de paro.
          </p>
        </article>

        <article class="feature-box">
          <div class="feature-box__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock text-[#0054ff]"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
          <h3>Disponibilidad Rápida</h3>
          <p>
            Entrega en 4-6 semanas. Stock de refacciones críticas en almacén Aguascalientes.
          </p>
        </article>
      </div>
    </div>
  </section>

  <section class="boilers-industries">
    <div class="container">
      <div class="section-heading-boilers-models industries">
        <h2>Aplicaciones por Sector Industrial</h2>
        <p>Soluciones especializadas para cada industria con requerimientos específicos</p>
      </div>

      <div class="boilers-industries__grid">
        <article class="industry-card">
          <div class="industry-card__header">
            <div class="industry-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hotel text-[#0054ff]"><path d="M10 22v-6.57"></path><path d="M12 11h.01"></path><path d="M12 7h.01"></path><path d="M14 15.43V22"></path><path d="M15 16a5 5 0 0 0-6 0"></path><path d="M16 11h.01"></path><path d="M16 7h.01"></path><path d="M8 11h.01"></path><path d="M8 7h.01"></path><rect x="4" y="2" width="16" height="20" rx="2"></rect></svg></div>
            <div>
              <h3>Hotelería</h3>
            </div>
          </div>

          <div class="industry-card__body">
            <span class="industry-card__label">Necesidades típicas:</span>
            <ul>
              <li>Agua caliente sanitaria 24/7</li>
              <li>Calefacción de albercas</li>
              <li>Lavandería industrial</li>
              <li>Calefacción HVAC</li>
            </ul>
          </div>

          <div class="industry-card__recommendation">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award text-[#0054ff]"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
            Recomendado: Serie PIRO 300-500 HP
          </div>
        </article>

        <article class="industry-card">
          <div class="industry-card__header">
            <div class="industry-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hospital text-[#0054ff]"><path d="M12 6v4"></path><path d="M14 14h-4"></path><path d="M14 18h-4"></path><path d="M14 8h-4"></path><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"></path><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"></path></svg></div>
            <div>
              <h3>Hospitales</h3>
            </div>
          </div>

          <div class="industry-card__body">
            <span class="industry-card__label">Necesidades típicas:</span>
            <ul>
              <li>Esterilización de instrumental</li>
              <li>Agua caliente sanitaria crítica</li>
              <li>Calefacción áreas pacientes</li>
              <li>Lavandería hospitalaria</li>
            </ul>
          </div>

          <div class="industry-card__recommendation">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award text-[#0054ff]"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
            Recomendado: Serie PIRO 500-750 HP
          </div>
        </article>

        <article class="industry-card">
          <div class="industry-card__header">
            <div class="industry-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-factory text-[#0054ff]"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path><path d="M17 18h1"></path><path d="M12 18h1"></path><path d="M7 18h1"></path></svg></div>
            <div>
              <h3>Industria Alimentaria</h3>
            </div>
          </div>

          <div class="industry-card__body">
            <span class="industry-card__label">Necesidades típicas:</span>
            <ul>
              <li>Cocción y pasteurización</li>
              <li>Limpieza CIP/SIP</li>
              <li>Esterilización envases</li>
              <li>Procesos térmicos</li>
            </ul>
          </div>

          <div class="industry-card__recommendation">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award text-[#0054ff]"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
            Recomendado: Serie PIRO 500+ HP
          </div>
        </article>

        <article class="industry-card">
          <div class="industry-card__header">
            <div class="industry-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 text-[#0054ff]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg></div>
            <div>
              <h3>Textil &amp; Química</h3>
            </div>
          </div>

          <div class="industry-card__body">
            <span class="industry-card__label">Necesidades típicas:</span>
            <ul>
              <li>Teñido y acabado textil</li>
              <li>Reactores químicos</li>
              <li>Secado industrial</li>
              <li>Destilación</li>
            </ul>
          </div>

          <div class="industry-card__recommendation">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award text-[#0054ff]"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
            Recomendado: Serie ACUO 750-1000+ HP
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="boilers-why">
    <div class="container">
      <div class="section-heading-boilers-models industries">
        <h2>¿Por Qué Elegir SIMARI?</h2>
        <p>Más de 30 años de experiencia en el sector térmico industrial</p>
      </div>

      <div class="boilers-why__grid">
        <article class="why-card">
          <div class="why-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield text-[#0054ff]"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
          <h3>Garantía Extendida</h3>
          <p>2 años en equipos completos, 5 años en caldera</p>
        </article>

        <article class="why-card">
          <div class="why-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 text-[#0054ff]"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg></div>
          <h3>Stock Local</h3>
          <p>Almacén en Aguascalientes con refacciones críticas</p>
        </article>

        <article class="why-card">
          <div class="why-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock text-[#0054ff]"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
          <h3>Servicio 24/7</h3>
          <p>Soporte técnico telefónico y visitas de emergencia</p>
        </article>

        <article class="why-card">
          <div class="why-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award text-[#0054ff]"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg></div>
          <h3>Financiamiento</h3>
          <p>Planes de pago flexibles para proyectos grandes</p>
        </article>
      </div>
    </div>
  </section>

  <section class="boilers-cta">
    <div class="container">
      <div class="boilers-cta__content">
        <h2>Cotiza tu Proyecto Térmico</h2>
        <p>
          Nuestros ingenieros analizarán tus necesidades y diseñarán la solución óptima
          en capacidad, eficiencia y presupuesto.
        </p>

        <div class="boilers-cta__actions">
          <a class="button-primary simari-caldera button-first" href="/contacto">
            Solicitar Visita de Ingeniería
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" style="margin-left: 8px;">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
          </a>

          <a class="button-secondary simari-caldera" href="tel:+524494348018">
            <svg
                        xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.79 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
            Llamar Ahora
          </a>
        </div>
      </div>
    </div>
  </section>
  @endsection