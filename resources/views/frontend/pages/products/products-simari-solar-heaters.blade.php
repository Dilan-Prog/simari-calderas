@extends('frontend.layouts.master')
@section('title', 'Calentadores Solares Industriales SIMARI')
@section('description', 'Calentadores solares industriales de alta eficiencia para generación de vapor y agua caliente. Diseño robusto, operación continua 24/7, cumplimiento normativo total.')
@section('canonical', config('app.url') . '/productos/calentadores-solares')
@section('content')

<section class="solar-hero" aria-label="Calentadores Solares Industriales SIMARI">
  <div class="container solar-hero__container">
    <div class="solar-hero__grid">
      <!-- Text column -->
      <div>
        <div class="solar-badge" aria-label="Categoría del producto">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
          Energía Solar Térmica
        </div>
 
        <h1 class="solar-hero__title">
          Calentadores<br>
          <span class="accent">Solares</span>
          <span class="sub">Industriales</span>
        </h1>
 
        <p class="solar-hero__desc">
          Reduce hasta <strong>80% tu consumo de gas</strong> con tecnología solar térmica de última generación.
          Sistemas de tubos evacuados y placa plana para aplicaciones comerciales e industriales.
        </p>
 
        <!-- Stats -->
        <div class="solar-hero__stats" role="list" aria-label="Beneficios clave">
          <div class="solar-stat" role="listitem">
            <div class="solar-stat__num" aria-label="80%">80%</div>
            <div class="solar-stat__label">Ahorro en gas</div>
          </div>
          <div class="solar-stat" role="listitem">
            <div class="solar-stat__num" aria-label="2 a 3 años">2-3</div>
            <div class="solar-stat__label">Años ROI</div>
          </div>
          <div class="solar-stat" role="listitem">
            <div class="solar-stat__num" aria-label="Más de 20 años">20+</div>
            <div class="solar-stat__label">Años vida útil</div>
          </div>
        </div>
 
        <!-- Certs -->
        <div class="solar-cert">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg>
          <div>
            <div class="solar-cert__title">Certificaciones Oficiales</div>
            <div class="solar-cert__norms">NOM-003-ENER · NOM-027-ENER · ANCE</div>
          </div>
        </div>
 
        <!-- CTAs -->
        <nav class="solar-hero__cta" aria-label="Acciones principales">
          <a href="/contacto" class="btn btn-primary" rel="noopener">
            Calcular Mi Ahorro
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 9h10M9 5l4 4-4 4"/></svg>
          </a>
          <a href="tel:+524494348018" class="btn btn-outline-blue">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Llamar
          </a>
        </nav>
      </div>
 
      <!-- Image column -->
      <div class="solar-hero__img-wrap" aria-hidden="true">
        <div class="solar-hero__img-frame">
          <img
            src="{{ asset('images/products/calentadores-solares/calentadores-solares-simari.jpg') }}"
            alt="Calentador solar industrial instalado en techo de fábrica, con cielo despejado y sol brillante"
            title="Calentador solar industrial instalado en techo de fábrica, con cielo despejado y sol brillante"
            width="600"
            height="500"
            loading="eager"
            fetchpriority="high"
            decoding="async"
          />
          <div class="solar-hero__img-overlay">
            <div class="solar-hero__img-stats">
              <div class="img-stat">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                <div class="img-stat__label">Eficiencia</div>
                <div class="img-stat__val">Hasta 92%</div>
              </div>
              <div class="img-stat">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9a4 4 0 0 0-2 7.5"/><path d="M12 3v2"/><path d="m6.6 18.4-1.4 1.4"/><path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/><path d="M4 13H2"/><path d="M6.34 7.34 4.93 5.93"/></svg>
                <div class="img-stat__label">Temperatura</div>
                <div class="img-stat__val">60–80°C</div>
              </div>
            </div>
          </div>
          <div class="solar-eco-badge" role="img" aria-label="100% Limpio, Energía Renovable">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
            <span>100%<br>LIMPIO</span>
          </div>
        </div>
      </div>
 
    </div>
  </div>
</section>
 
<section class="solar-products section-pad" id="sistemas" aria-labelledby="products-heading">
  <div class="container">
    <div class="section-header">
      <h2 id="products-heading">Sistemas Solares Térmicos</h2>
      <p>Tecnología adaptada a cada necesidad industrial</p>
    </div>
 
    <div class="solar-products__grid">
 
      <!-- Tubos evacuados -->
      <article class="product-card" aria-labelledby="prod-tubos">
        <div class="product-card__header">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
          <h3 id="prod-tubos">Tubos Evacuados</h3>
          <p>Agua caliente sanitaria</p>
        </div>
        <div class="product-card__body">
          <div class="product-card__meta"><span>Capacidad</span><strong>150–500 litros/día</strong></div>
          <div class="product-card__meta"><span>Eficiencia</span><strong class="eff">85–92%</strong></div>
          <hr class="product-card__divider">
          <p class="product-card__feat-label">Ventajas</p>
          <ul class="product-card__features">
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Mayor eficiencia en climas fríos</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Resiste heladas</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Operación hasta -20°C</li>
          </ul>
        </div>
        <div class="product-card__footer">
          <div class="product-card__price">desde $35,000 MXN</div>
          <a href="/contacto" class="product-card__btn" rel="noopener">Solicitar Información</a>
        </div>
      </article>
 
      <!-- Placa plana -->
      <article class="product-card" aria-labelledby="prod-placa">
        <div class="product-card__header">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
          <h3 id="prod-placa">Placa Plana</h3>
          <p>Precalentamiento industrial</p>
        </div>
        <div class="product-card__body">
          <div class="product-card__meta"><span>Capacidad</span><strong>200–400 litros/día</strong></div>
          <div class="product-card__meta"><span>Eficiencia</span><strong class="eff">75–85%</strong></div>
          <hr class="product-card__divider">
          <p class="product-card__feat-label">Ventajas</p>
          <ul class="product-card__features">
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Más económico</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Instalación simple</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Mantenimiento mínimo</li>
          </ul>
        </div>
        <div class="product-card__footer">
          <div class="product-card__price">desde $28,000 MXN</div>
          <a href="/contacto" class="product-card__btn" rel="noopener">Solicitar Información</a>
        </div>
      </article>
 
      <!-- Sistema Híbrido -->
      <article class="product-card" aria-labelledby="prod-hibrido">
        <div class="product-card__header">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
          <h3 id="prod-hibrido">Sistema Híbrido</h3>
          <p>Comercial e industrial</p>
        </div>
        <div class="product-card__body">
          <div class="product-card__meta"><span>Capacidad</span><strong>500–2,000 litros/día</strong></div>
          <div class="product-card__meta"><span>Eficiencia</span><strong class="eff">90–95%</strong></div>
          <hr class="product-card__divider">
          <p class="product-card__feat-label">Ventajas</p>
          <ul class="product-card__features">
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Solar + Respaldo gas</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Máximo ahorro garantizado</li>
            <li><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Agua caliente 24/7</li>
          </ul>
        </div>
        <div class="product-card__footer">
          <div class="product-card__price">Cotización a medida</div>
          <a href="/contacto" class="product-card__btn" rel="noopener">Solicitar Información</a>
        </div>
      </article>
 
    </div>
  </div>
</section>
<section class="solar-timeline section-pad" id="ahorro" aria-labelledby="timeline-heading">
  <div class="container">
    <div class="section-header">
      <h2 id="timeline-heading">Tu Viaje Hacia el Ahorro</h2>
      <p>Cómo evoluciona tu ahorro con el sistema solar</p>
    </div>
 
    <div class="timeline" role="list">
 
      <div class="timeline-item" role="listitem">
        <div class="timeline-dot" aria-hidden="true">1</div>
        <div class="timeline-card">
          <div class="timeline-card__top">
            <div>
              <div class="timeline-card__period">Mes 1–3</div>
              <div class="timeline-card__title">Instalación y puesta en marcha</div>
            </div>
            <div class="timeline-card__saving">
              <div class="timeline-card__pct">60–70%</div>
              <div class="timeline-card__saving-label">Ahorro vs gas</div>
            </div>
          </div>
          <p>Primeros ahorros visibles en factura de gas desde el primer mes de operación.</p>
        </div>
      </div>
 
      <div class="timeline-item" role="listitem">
        <div class="timeline-dot" aria-hidden="true">2</div>
        <div class="timeline-card">
          <div class="timeline-card__top">
            <div>
              <div class="timeline-card__period">Mes 4–12</div>
              <div class="timeline-card__title">Optimización del sistema</div>
            </div>
            <div class="timeline-card__saving">
              <div class="timeline-card__pct">70–80%</div>
              <div class="timeline-card__saving-label">Ahorro vs gas</div>
            </div>
          </div>
          <p>El sistema alcanza su máxima eficiencia operativa y rendimiento estacional.</p>
        </div>
      </div>
 
      <div class="timeline-item" role="listitem">
        <div class="timeline-dot" aria-hidden="true">3</div>
        <div class="timeline-card">
          <div class="timeline-card__top">
            <div>
              <div class="timeline-card__period">Año 2–3</div>
              <div class="timeline-card__title">Retorno de inversión</div>
            </div>
            <div class="timeline-card__saving">
              <div class="timeline-card__pct">75–85%</div>
              <div class="timeline-card__saving-label">Ahorro vs gas</div>
            </div>
          </div>
          <p>Recuperación completa de la inversión inicial mediante el ahorro acumulado en gas.</p>
        </div>
      </div>
 
      <div class="timeline-item" role="listitem">
        <div class="timeline-dot" aria-hidden="true">4</div>
        <div class="timeline-card">
          <div class="timeline-card__top">
            <div>
              <div class="timeline-card__period">Año 4+</div>
              <div class="timeline-card__title">Ganancia neta</div>
            </div>
            <div class="timeline-card__saving">
              <div class="timeline-card__pct">80%+</div>
              <div class="timeline-card__saving-label">Ahorro vs gas</div>
            </div>
          </div>
          <p>Ahorro puro año tras año con mínimo mantenimiento preventivo.</p>
        </div>
      </div>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════
     INDUSTRIES
═══════════════════════════════════════ -->
<section class="solar-industries section-pad" id="aplicaciones" aria-labelledby="industries-heading">
  <div class="container">
    <div class="section-header">
      <h2 id="industries-heading">Aplicaciones Comerciales e Industriales</h2>
      <p>Casos de éxito y retornos de inversión reales</p>
    </div>
 
    <div class="industries-grid">
 
      <!-- Hoteles -->
      <article class="industry-row" aria-labelledby="ind-hoteles">
        <div class="industry-row__inner">
          <div class="industry-row__info">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            <h3 id="ind-hoteles">Hoteles &amp; Resorts</h3>
            <p>Agua caliente para habitaciones, albercas, spa, lavandería. Sistema híbrido garantiza disponibilidad 24/7.</p>
          </div>
          <div class="industry-metric orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="orange" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            <div class="industry-metric__label">Consumo diario</div>
            <div class="industry-metric__val blue-val">5,000–20,000 L/día</div>
          </div>
          <div class="industry-metric green">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="green" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div class="industry-metric__label">Ahorro anual</div>
            <div class="industry-metric__val green-val">$80,000–$250,000/año</div>
          </div>
          <div class="industry-metric blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="blueico" aria-hidden="true"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            <div class="industry-metric__label">Retorno inversión</div>
            <div class="industry-metric__val orange-val">2–3 años</div>
          </div>
        </div>
      </article>
 
      <!-- Hospitales -->
      <article class="industry-row" aria-labelledby="ind-hosp">
        <div class="industry-row__inner">
          <div class="industry-row__info">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            <h3 id="ind-hosp">Hospitales</h3>
            <p>Precalentamiento para esterilización, lavandería hospitalaria, agua sanitaria. Cumple normativa NOM-003-ENER.</p>
          </div>
          <div class="industry-metric orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="orange" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            <div class="industry-metric__label">Consumo diario</div>
            <div class="industry-metric__val blue-val">10,000–30,000 L/día</div>
          </div>
          <div class="industry-metric green">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="green" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div class="industry-metric__label">Ahorro anual</div>
            <div class="industry-metric__val green-val">$150,000–$400,000/año</div>
          </div>
          <div class="industry-metric blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="blueico" aria-hidden="true"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            <div class="industry-metric__label">Retorno inversión</div>
            <div class="industry-metric__val orange-val">2.5–3.5 años</div>
          </div>
        </div>
      </article>
 
      <!-- Industria Alimentaria -->
      <article class="industry-row" aria-labelledby="ind-alim">
        <div class="industry-row__inner">
          <div class="industry-row__info">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            <h3 id="ind-alim">Industria Alimentaria</h3>
            <p>Limpieza CIP, pasteurización, procesos térmicos. Compatible con sistemas existentes.</p>
          </div>
          <div class="industry-metric orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="orange" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            <div class="industry-metric__label">Consumo diario</div>
            <div class="industry-metric__val blue-val">8,000–25,000 L/día</div>
          </div>
          <div class="industry-metric green">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="green" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div class="industry-metric__label">Ahorro anual</div>
            <div class="industry-metric__val green-val">$120,000–$350,000/año</div>
          </div>
          <div class="industry-metric blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="blueico" aria-hidden="true"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            <div class="industry-metric__label">Retorno inversión</div>
            <div class="industry-metric__val orange-val">2–3 años</div>
          </div>
        </div>
      </article>
 
      <!-- Gimnasios -->
      <article class="industry-row" aria-labelledby="ind-gym">
        <div class="industry-row__inner">
          <div class="industry-row__info">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
            <h3 id="ind-gym">Gimnasios &amp; Clubes</h3>
            <p>Regaderas, vapor, calefacción de alberca. Instalación modular escalable.</p>
          </div>
          <div class="industry-metric orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="orange" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            <div class="industry-metric__label">Consumo diario</div>
            <div class="industry-metric__val blue-val">3,000–10,000 L/día</div>
          </div>
          <div class="industry-metric green">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="green" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div class="industry-metric__label">Ahorro anual</div>
            <div class="industry-metric__val green-val">$50,000–$150,000/año</div>
          </div>
          <div class="industry-metric blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="blueico" aria-hidden="true"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            <div class="industry-metric__label">Retorno inversión</div>
            <div class="industry-metric__val orange-val">1.5–2.5 años</div>
          </div>
        </div>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════
     ENVIRONMENTAL IMPACT
═══════════════════════════════════════ -->
<section class="solar-env section-pad" id="impacto-ambiental" aria-labelledby="env-heading">
  <div class="container">
    <div class="section-header">
      <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:rgb(34,197,94);margin:0 auto 16px;display:block;" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
      <h2 id="env-heading">Impacto Ambiental Positivo</h2>
      <p>Más que ahorro: contribución real a la sostenibilidad</p>
    </div>
 
    <div class="solar-env__grid">
      <article class="env-card">
        <div class="env-card__emoji" aria-hidden="true">🌍</div>
        <h3>Cero Emisiones CO₂</h3>
        <div class="env-card__num">4–6 ton/año</div>
        <p>Un sistema promedio evita la emisión de 4 a 6 toneladas de CO₂ anuales, equivalente a plantar 200 árboles.</p>
      </article>
      <article class="env-card">
        <div class="env-card__emoji" aria-hidden="true">💨</div>
        <h3>Reducción NOx y SOx</h3>
        <div class="env-card__num">100%</div>
        <p>Al no quemar gas, eliminas completamente las emisiones de óxidos nitrosos y azufre dañinos para el aire.</p>
      </article>
      <article class="env-card">
        <div class="env-card__emoji" aria-hidden="true">♻️</div>
        <h3>Energía Renovable</h3>
        <div class="env-card__num">100%</div>
        <p>La radiación solar es inagotable. Tu sistema operará décadas sin consumir recursos no renovables.</p>
      </article>
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════
     CTA
═══════════════════════════════════════ -->
<section class="solar-cta section-pad" id="calcular-ahorro" aria-labelledby="cta-heading">
  <div class="container">
    <div class="solar-cta__inner">
      <h2 id="cta-heading">Calcula Tu Ahorro en 2 Minutos</h2>
      <p>Ingresa tus datos y recibe una estimación personalizada de inversión, ahorro y ROI</p>
 
      <div class="solar-cta__steps" role="list" aria-label="Pasos para obtener tu cotización">
        <div class="cta-step" role="listitem">
          <div class="cta-step__num">1</div>
          <div class="cta-step__text">Consumo actual de gas</div>
        </div>
        <div class="cta-step" role="listitem">
          <div class="cta-step__num">2</div>
          <div class="cta-step__text">Litros agua caliente/día</div>
        </div>
        <div class="cta-step" role="listitem">
          <div class="cta-step__num">3</div>
          <div class="cta-step__text">Recibe propuesta</div>
        </div>
      </div>
 
      <nav class="solar-cta__btns" aria-label="Solicitar cotización">
        <a href="/contacto" class="btn btn-white" rel="noopener">
          Solicitar Cálculo Gratuito
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 10h10M10 5l5 5-5 5"/></svg>
        </a>
        <a href="tel:+524494348018" class="btn btn-outline-white">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          449 434 8018
        </a>
      </nav>
    </div>
  </div>
</section>

  @endsection