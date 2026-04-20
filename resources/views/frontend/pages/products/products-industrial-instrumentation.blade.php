@extends('frontend.layouts.master')

@section('title')
Instrumentación Industrial - Industria Simari
@endsection
@section('content')
<section class="ii-hero" aria-labelledby="ii-hero-title">
  <div class="ii-hero__pattern" aria-hidden="true"></div>
  <div class="ii-hero__overlay" aria-hidden="true"></div>
 
  <div class="ii-hero__inner">
    <div class="ii-hero__content">
 
      <!-- Badge con ícono -->
      <div class="ii-hero__badge">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
        <div>
          <div class="ii-hero__badge-title">Instrumentación Industrial</div>
          <div class="ii-hero__badge-sub">Medición · Control · Monitoreo</div>
        </div>
      </div>
 
      <!-- Título -->
      <h1 id="ii-hero-title" class="ii-hero__title">
        Instrumentos<br>
        de <span class="ii-accent">Precisión</span><br>
        <span class="ii-sub">Industrial</span>
      </h1>
 
      <!-- Descripción -->
      <p class="ii-hero__desc">
        Soluciones completas de medición y control para procesos térmicos e industriales. Manómetros, transmisores, sensores, analizadores y sistemas de automatización.
      </p>
 
      <!-- 4 stats -->
      <div class="ii-hero__stats" role="list" aria-label="Indicadores clave">
        <div class="ii-stat" role="listitem">
          <div class="ii-stat__num">CENAM</div>
          <div class="ii-stat__label">Certificados</div>
        </div>
        <div class="ii-stat" role="listitem">
          <div class="ii-stat__num">±0.25%</div>
          <div class="ii-stat__label">Precisión</div>
        </div>
        <div class="ii-stat" role="listitem">
          <div class="ii-stat__num">1-3 años</div>
          <div class="ii-stat__label">Garantía</div>
        </div>
        <div class="ii-stat" role="listitem">
          <div class="ii-stat__num">200+ SKU</div>
          <div class="ii-stat__label">Stock</div>
        </div>
      </div>
 
      <!-- CTAs -->
      <nav class="ii-hero__btns" aria-label="Acciones principales">
        <a 
        href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
        target="_blank"
        aria-label="Abrir chat de WhatsApp"
        class="ii-btn-red">
          Solicitar Catálogo Técnico
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a 
        href="tel:+524494348018"
        aria-label="Llamar a Industria Simari"
        class="ii-btn-outline-white">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          Asesoría Técnica
        </a>
      </nav>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     CATÁLOGO – 4 CARDS (2 filas × 2 columnas)
     React: section bg-[#2E3A46] grid md:grid-cols-2 lg:grid-cols-3
════════════════════════════════════════════════════════ -->
<section class="ii-catalog" id="catalogo" aria-labelledby="ii-catalog-title">
  <div class="ii-container">
 
    <header class="ii-catalog__header">
      <h2 id="ii-catalog-title" class="ii-catalog__title">Catálogo de Instrumentación</h2>
      <p class="ii-catalog__sub">6 familias principales con más de 200 productos en stock</p>
    </header>
 
    <div class="ii-catalog__grid">
 
      <!-- ── Medición de Presión (azul) ── -->
      <article class="ii-cat-card" aria-labelledby="cat-presion">
        <div class="ii-cat-card__header ii-cat-card__header--blue">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
          <h3 id="cat-presion">Medición de Presión</h3>
        </div>
        <div class="ii-cat-card__body">
          <div class="ii-product-list">
            <div class="ii-product-item">
              <div class="ii-product-item__name">Manómetros Análogos</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-300 PSI</div>
                <div><span>Precisión:</span> ±2%</div>
              </div>
              <div class="ii-product-item__app">✓ Calderas, tuberías</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Manómetros Digitales</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-600 PSI</div>
                <div><span>Precisión:</span> ±0.5%</div>
              </div>
              <div class="ii-product-item__app">✓ Procesos críticos</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Transmisores 4-20mA</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-1000 PSI</div>
                <div><span>Precisión:</span> ±0.25%</div>
              </div>
              <div class="ii-product-item__app">✓ Control automático</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Presostatos</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> Ajustable</div>
                <div><span>Tipo:</span> Switch</div>
              </div>
              <div class="ii-product-item__app">✓ Alarmas, seguridad</div>
            </div>
          </div>
          <a href="/contacto" class="ii-cat-card__btn ii-cat-card__btn--blue" rel="noopener">Ver Detalles</a>
        </div>
      </article>
 
      <!-- ── Medición de Temperatura (rojo) ── -->
      <article class="ii-cat-card" aria-labelledby="cat-temp">
        <div class="ii-cat-card__header ii-cat-card__header--red">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>
          <h3 id="cat-temp">Medición de Temperatura</h3>
        </div>
        <div class="ii-cat-card__body">
          <div class="ii-product-list">
            <div class="ii-product-item">
              <div class="ii-product-item__name">Termómetros Bimetálicos</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-400°C</div>
                <div><span>Precisión:</span> ±2°C</div>
              </div>
              <div class="ii-product-item__app">✓ Lectura local</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Termopares Tipo K</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> -200 a 1200°C</div>
                <div><span>Precisión:</span> ±1°C</div>
              </div>
              <div class="ii-product-item__app">✓ Alta temperatura</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">RTD Pt100</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> -200 a 600°C</div>
                <div><span>Precisión:</span> ±0.15°C</div>
              </div>
              <div class="ii-product-item__app">✓ Máxima precisión</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Termostatos Digitales</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-300°C</div>
                <div><span>Precisión:</span> ±0.5°C</div>
              </div>
              <div class="ii-product-item__app">✓ Control ON/OFF</div>
            </div>
          </div>
          <a href="/contacto" class="ii-cat-card__btn ii-cat-card__btn--red" rel="noopener">Ver Detalles</a>
        </div>
      </article>
 
      <!-- ── Análisis de Agua (ámbar) ── -->
      <article class="ii-cat-card" aria-labelledby="cat-agua">
        <div class="ii-cat-card__header ii-cat-card__header--amber">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
          <h3 id="cat-agua">Análisis de Agua</h3>
        </div>
        <div class="ii-cat-card__body">
          <div class="ii-product-list">
            <div class="ii-product-item">
              <div class="ii-product-item__name">Medidores pH</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-14 pH</div>
                <div><span>Precisión:</span> ±0.02</div>
              </div>
              <div class="ii-product-item__app">✓ Control químico</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Conductivímetros</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-2000 µS</div>
                <div><span>Precisión:</span> ±1%</div>
              </div>
              <div class="ii-product-item__app">✓ TDS, salinidad</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Medidores ORP</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> ±2000 mV</div>
                <div><span>Precisión:</span> ±5mV</div>
              </div>
              <div class="ii-product-item__app">✓ Oxidación-reducción</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Analizadores O₂ Disuelto</div>
              <div class="ii-product-item__specs">
                <div><span>Rango:</span> 0-20 ppm</div>
                <div><span>Precisión:</span> ±0.1</div>
              </div>
              <div class="ii-product-item__app">✓ Desgasificación</div>
            </div>
          </div>
          <a href="/contacto" class="ii-cat-card__btn ii-cat-card__btn--amber" rel="noopener">Ver Detalles</a>
        </div>
      </article>
 
      <!-- ── Control y Automatización (pink) ── -->
      <article class="ii-cat-card" aria-labelledby="cat-auto">
        <div class="ii-cat-card__header ii-cat-card__header--pink">
          <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
          <h3 id="cat-auto">Control y Automatización</h3>
        </div>
        <div class="ii-cat-card__body">
          <div class="ii-product-list">
            <div class="ii-product-item">
              <div class="ii-product-item__name">Controladores PID</div>
              <div class="ii-product-item__specs">
                <div><span>Tipo:</span> Universal</div>
                <div><span>Salida:</span> Digital</div>
              </div>
              <div class="ii-product-item__app">✓ Temperatura, nivel</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">Registradores</div>
              <div class="ii-product-item__specs">
                <div><span>Canales:</span> Multi-canal</div>
                <div><span>Datos:</span> Histórico</div>
              </div>
              <div class="ii-product-item__app">✓ Trazabilidad</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">PLCs Industriales</div>
              <div class="ii-product-item__specs">
                <div><span>Tipo:</span> Modular</div>
                <div><span>Salida:</span> Lógica</div>
              </div>
              <div class="ii-product-item__app">✓ Automatización total</div>
            </div>
            <div class="ii-product-item">
              <div class="ii-product-item__name">HMI Pantallas Táctiles</div>
              <div class="ii-product-item__specs">
                <div><span>Tamaño:</span> 7-15 pulgadas</div>
                <div><span>Tipo:</span> Interfaz</div>
              </div>
              <div class="ii-product-item__app">✓ Operación/monitoreo</div>
            </div>
          </div>
          <a href="/contacto" class="ii-cat-card__btn ii-cat-card__btn--pink" rel="noopener">Ver Detalles</a>
        </div>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     APLICACIONES CRÍTICAS – SECCIÓN NEGRA
     React: section "py-20 bg-black text-white"
════════════════════════════════════════════════════════ -->
<section class="ii-apps" id="aplicaciones" aria-labelledby="ii-apps-title">
  <div class="ii-container">
 
    <header class="ii-apps__header">
      <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
      <h2 id="ii-apps-title" class="ii-apps__title">Aplicaciones Críticas por Sistema</h2>
      <p class="ii-apps__sub">Instrumentación obligatoria y recomendada según normatividad vigente</p>
    </header>
 
    <div class="ii-apps__list" role="list">
 
      <!-- Cuarto de Calderas -->
      <div class="ii-app-row" role="listitem">
        <div class="ii-app-row__inner">
          <div>
            <div class="ii-app-row__info-top">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>
              <div>
                <div class="ii-app-row__name">Cuarto de Calderas</div>
                <span class="ii-criticality ii-criticality--alta" style="margin-top:8px;display:inline-block;">Alta</span>
              </div>
            </div>
            <div class="ii-app-row__norm">📋 NOM-020-STPS obligatorio</div>
          </div>
          <div>
            <div class="ii-app-row__req-label">Instrumentación Requerida:</div>
            <div class="ii-chips">
              <span class="ii-chip">Manómetro de vapor</span>
              <span class="ii-chip">Manómetro de gas</span>
              <span class="ii-chip">Termómetro chimenea</span>
              <span class="ii-chip">Control nivel agua</span>
              <span class="ii-chip">Analizador combustión</span>
            </div>
          </div>
        </div>
      </div>
 
      <!-- Tratamiento de Agua -->
      <div class="ii-app-row" role="listitem">
        <div class="ii-app-row__inner">
          <div>
            <div class="ii-app-row__info-top">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>
              <div>
                <div class="ii-app-row__name">Tratamiento de Agua</div>
                <span class="ii-criticality ii-criticality--media" style="margin-top:8px;display:inline-block;">Media-Alta</span>
              </div>
            </div>
            <div class="ii-app-row__norm">📋 Depende del proceso</div>
          </div>
          <div>
            <div class="ii-app-row__req-label">Instrumentación Requerida:</div>
            <div class="ii-chips">
              <span class="ii-chip">Medidor pH</span>
              <span class="ii-chip">Conductivímetro</span>
              <span class="ii-chip">Medidor ORP</span>
              <span class="ii-chip">Bomba dosificadora</span>
              <span class="ii-chip">Totalizador de flujo</span>
            </div>
          </div>
        </div>
      </div>
 
      <!-- Procesos Térmicos -->
      <div class="ii-app-row" role="listitem">
        <div class="ii-app-row__inner">
          <div>
            <div class="ii-app-row__info-top">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>
              <div>
                <div class="ii-app-row__name">Procesos Térmicos</div>
                <span class="ii-criticality ii-criticality--alta" style="margin-top:8px;display:inline-block;">Alta</span>
              </div>
            </div>
            <div class="ii-app-row__norm">📋 Trazabilidad FDA/HACCP</div>
          </div>
          <div>
            <div class="ii-app-row__req-label">Instrumentación Requerida:</div>
            <div class="ii-chips">
              <span class="ii-chip">Termopares múltiples</span>
              <span class="ii-chip">Controlador PID</span>
              <span class="ii-chip">Registrador temperatura</span>
              <span class="ii-chip">Transmisor presión</span>
              <span class="ii-chip">Válvulas control</span>
            </div>
          </div>
        </div>
      </div>
 
      <!-- HVAC Industrial -->
      <div class="ii-app-row" role="listitem">
        <div class="ii-app-row__inner">
          <div>
            <div class="ii-app-row__info-top">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>
              <div>
                <div class="ii-app-row__name">HVAC Industrial</div>
                <span class="ii-criticality ii-criticality--media" style="margin-top:8px;display:inline-block;">Media</span>
              </div>
            </div>
            <div class="ii-app-row__norm">📋 NOM-008-ENER</div>
          </div>
          <div>
            <div class="ii-app-row__req-label">Instrumentación Requerida:</div>
            <div class="ii-chips">
              <span class="ii-chip">Termostatos</span>
              <span class="ii-chip">Humidistatos</span>
              <span class="ii-chip">Presostatos diferenciales</span>
              <span class="ii-chip">Sensores CO₂</span>
              <span class="ii-chip">Actuadores modulantes</span>
            </div>
          </div>
        </div>
      </div>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     SERVICIO TÉCNICO – SECCIÓN AZUL
     React: section bg-[#ff6213]
════════════════════════════════════════════════════════ -->
<section class="ii-service" id="servicio" aria-labelledby="ii-service-title">
  <div class="ii-container">
 
    <header class="ii-service__header">
      <h2 id="ii-service-title" class="ii-service__title">Servicio Técnico Especializado</h2>
      <p class="ii-service__sub">Más que venta de equipos: solución integral</p>
    </header>
 
    <div class="ii-service__grid">
 
      <article class="ii-service-card" aria-labelledby="svc-precision">
        <div class="ii-service-card__body">
          <div class="ii-service-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
          </div>
          <div>
            <h3 id="svc-precision">Precisión Certificada</h3>
            <p>Todos nuestros instrumentos cuentan con certificados de calibración trazables a estándares nacionales e internacionales (CENAM, NIST).</p>
          </div>
        </div>
      </article>
 
      <article class="ii-service-card" aria-labelledby="svc-norm">
        <div class="ii-service-card__body">
          <div class="ii-service-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
          </div>
          <div>
            <h3 id="svc-norm">Normatividad Cumplida</h3>
            <p>Instrumentación que cumple NOM-020-STPS (calderas), NOM-001-SEDE (eléctrica) y estándares ASME, ISA, IEC según aplicación.</p>
          </div>
        </div>
      </article>
 
      <article class="ii-service-card" aria-labelledby="svc-inst">
        <div class="ii-service-card__body">
          <div class="ii-service-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
          </div>
          <div>
            <h3 id="svc-inst">Instalación Profesional</h3>
            <p>Técnicos especializados en instrumentación industrial. Instalamos, calibramos y validamos in-situ. Capacitación incluida.</p>
          </div>
        </div>
      </article>
 
      <article class="ii-service-card" aria-labelledby="svc-post">
        <div class="ii-service-card__body">
          <div class="ii-service-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <div>
            <h3 id="svc-post">Servicio Post-Venta</h3>
            <p>Calibración periódica, reparación de equipos, stock de refacciones. Contratos de mantenimiento preventivo disponibles.</p>
          </div>
        </div>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     MARCAS – SECCIÓN DARK
     React: section bg-[#2E3A46]
════════════════════════════════════════════════════════ -->
<section class="ii-brands" id="marcas" aria-labelledby="ii-brands-title">
  <div class="ii-container">
 
    <h2 id="ii-brands-title" class="ii-brands__title">Representamos Marcas Líderes Mundiales</h2>
 
    <div class="ii-brands__grid" role="list" aria-label="Marcas representadas">
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Winters</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Ashcroft</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Dwyer</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Omega</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Endress+Hauser</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Yokogawa</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Rosemount</div></div>
      <div class="ii-brand-tile" role="listitem"><div class="ii-brand-tile__name">Siemens</div></div>
    </div>
 
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     CTA ROJO FINAL
     React: section "py-24 bg-gradient from-[#C62828] to-[#8B0000]"
════════════════════════════════════════════════════════ -->
<section class="ii-cta" id="contacto" aria-labelledby="ii-cta-title">
  <div class="ii-cta__inner">
 
    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
 
    <h2 id="ii-cta-title">¿Necesitas Asesoría Técnica?</h2>
 
    <p>
      Nuestros ingenieros te ayudarán a seleccionar la instrumentación correcta según tu proceso, rangos de operación y normatividad aplicable.
    </p>
 
    <nav class="ii-cta__btns" aria-label="Opciones de contacto">
      <a
      href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
      target="_blank"
      aria-label="Abrir chat de WhatsApp"
      class="button-primary li-cta">
        Solicitar Visita Técnica
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
      <a 
      href="tel:+524494348018"
      aria-label="Llamar a Industria Simari"
      class="button-secondary li-cta">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        449 434 8018
      </a>
    </nav>
 
  </div>
</section>
@endsection
