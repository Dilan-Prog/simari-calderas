@extends('frontend.layouts.master')
@section('styles')
    @vite(['resources/css/masstercal-rinnai.css'])
@endsection
@section('content')
<section class="wf-hero" aria-labelledby="wf-hero-title">
 
  <div class="wf-hero__inner">
    <div class="wf-hero__content">
 
      <!-- Logo -->
      <div class="wf-hero__logo">
        <img
          src="/_assets/v11/c52d6ebf603813924afaaac8334daee3d33523d1.png"
          alt="Rinnai"
          width="160"
          height="72"
          loading="eager"
          fetchpriority="high"
          onerror="this.outerHTML='<span style=\'font-size:2.5rem;font-weight:800;color:#FFD700;letter-spacing:-2px\'>Rinnai</span>'"
        />
      </div>
 
      <!-- Badge -->
      <div class="wf-hero__badge">💧 Tratamiento de Agua Profesional</div>
 
      <!-- Título -->
      <h1 id="wf-hero-title" class="wf-hero__title">
        Suavizadores y<br>
        <span>Sistemas de Filtración</span>
      </h1>
 
      <!-- Descripción -->
      <p class="wf-hero__desc">
        Transforma el agua de tu hogar. Elimina sarro, cloro, sedimentos y bacterias con tecnología Rinnai de grado industrial.
      </p>
 
      <!-- 4 pills -->
      <div class="wf-hero__pills" role="list" aria-label="Beneficios principales">
        <div class="wf-pill" role="listitem">
          <div class="wf-pill__emoji" aria-hidden="true">🛡️</div>
          <div class="wf-pill__label">Protección Total</div>
        </div>
        <div class="wf-pill" role="listitem">
          <div class="wf-pill__emoji" aria-hidden="true">💎</div>
          <div class="wf-pill__label">Agua Premium</div>
        </div>
        <div class="wf-pill" role="listitem">
          <div class="wf-pill__emoji" aria-hidden="true">💰</div>
          <div class="wf-pill__label">Ahorro 50%</div>
        </div>
        <div class="wf-pill" role="listitem">
          <div class="wf-pill__emoji" aria-hidden="true">🌱</div>
          <div class="wf-pill__label">Eco-Friendly</div>
        </div>
      </div>
 
    </div>
  </div>
 
  <!-- Wave SVG inferior -->
  <div class="wf-hero__wave" aria-hidden="true">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
      <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z"/>
    </svg>
  </div>
 
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     ¿QUÉ PROBLEMA TIENES? – 4 CARDS
     React: section "py-20 bg-gray-50"
════════════════════════════════════════════════════════ -->
<section class="wf-problems" id="problemas" aria-labelledby="wf-prob-title">
  <div class="wf-problems__inner">
 
    <header class="wf-problems__header">
      <h2 id="wf-prob-title" class="wf-problems__title">¿Qué Problema Tienes?</h2>
      <p class="wf-problems__sub">Encuentra tu solución perfecta</p>
    </header>
 
    <div class="wf-problems__grid">
 
      <article class="wf-prob-card" aria-labelledby="prob-sarro">
        <div class="wf-prob-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
        </div>
        <h3 id="prob-sarro">Sarro en tuberías</h3>
        <span class="wf-prob-card__tag">→ Suavizador</span>
      </article>
 
      <article class="wf-prob-card" aria-labelledby="prob-turbia">
        <div class="wf-prob-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
        </div>
        <h3 id="prob-turbia">Agua turbia</h3>
        <span class="wf-prob-card__tag">→ Filtro sedimentos</span>
      </article>
 
      <article class="wf-prob-card" aria-labelledby="prob-sabor">
        <div class="wf-prob-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 3h15"/><path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"/><path d="M6 14h12"/></svg>
        </div>
        <h3 id="prob-sabor">Mal sabor/olor</h3>
        <span class="wf-prob-card__tag">→ Carbón activado</span>
      </article>
 
      <article class="wf-prob-card" aria-labelledby="prob-bact">
        <div class="wf-prob-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
        </div>
        <h3 id="prob-bact">Bacterias/virus</h3>
        <span class="wf-prob-card__tag">→ Sistema UV</span>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     NUESTROS SISTEMAS – ACCORDIONS
     React: section "py-20 bg-white" con space-y-4
════════════════════════════════════════════════════════ -->
<section class="wf-systems" id="sistemas" aria-labelledby="wf-sys-title">
  <div class="wf-systems__inner">
 
    <header class="wf-systems__header">
      <h2 id="wf-sys-title" class="wf-systems__title">Nuestros Sistemas</h2>
      <p class="wf-systems__sub">Explora cada solución en detalle</p>
    </header>
 
    <div class="wf-accordions">
 
      <!-- ── SUAVIZADOR (expandido por defecto) ── -->
      <div class="wf-accordion wf-accordion--open" id="acc-suavizador">
        <button
          class="wf-accordion__btn"
          aria-expanded="true"
          aria-controls="panel-suavizador"
          onclick="toggleAccordion('acc-suavizador')"
        >
          <div class="wf-accordion__btn-left">
            <div class="wf-accordion__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            </div>
            <div>
              <div class="wf-accordion__name">Suavizador de Agua</div>
              <div class="wf-accordion__desc">Elimina dureza y sarro</div>
            </div>
          </div>
          <div class="wf-accordion__btn-right">
            <span class="wf-accordion__price">desde $12,500 MXN</span>
            <svg class="wf-accordion__chevron" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </button>
 
        <div class="wf-accordion__panel" id="panel-suavizador" role="region" aria-labelledby="acc-suavizador">
          <div class="wf-accordion__panel-inner">
            <div class="wf-accordion__grid">
              <div>
                <img
                  src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=800"
                  alt="Suavizador de Agua Rinnai instalado"
                  class="wf-accordion__img"
                  loading="lazy"
                  width="800"
                  height="260"
                />
                <p class="wf-accordion__text">
                  Sistema de intercambio iónico que elimina calcio y magnesio del agua, protegiendo tus tuberías y electrodomésticos.
                </p>
                <div class="wf-chips">
                  <span class="wf-chip">30,000 granos</span>
                  <span class="wf-chip">45,000 granos</span>
                  <span class="wf-chip">60,000 granos</span>
                </div>
              </div>
              <div>
                <h4 class="wf-accordion__benefits-title">Beneficios Clave</h4>
                <ul class="wf-checklist" aria-label="Beneficios del suavizador">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Elimina 99% del sarro y cal</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Protege calentadores y lavadoras</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Piel y cabello más suaves</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Ahorro en jabón y detergente (hasta 50%)</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Ropa más limpia y duradera</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Reducción de manchas en platos y vidrios</li>
                </ul>
                <div class="wf-price-block">
                  <div class="wf-price-block__label">Precio desde</div>
                  <div class="wf-price-block__price">desde $12,500 MXN</div>
                  <div class="wf-price-block__info">
                    ✓ Instalación profesional incluida<br>
                    ✓ Garantía de fábrica<br>
                    ✓ Soporte técnico vitalicio
                  </div>
                </div>
                <a href="/contacto" class="wf-accordion__cta" rel="noopener">Solicitar Cotización</a>
              </div>
            </div>
          </div>
        </div>
      </div>
 
      <!-- ── FILTRO SEDIMENTOS ── -->
      <div class="wf-accordion" id="acc-sedimentos">
        <button
          class="wf-accordion__btn"
          aria-expanded="false"
          aria-controls="panel-sedimentos"
          onclick="toggleAccordion('acc-sedimentos')"
        >
          <div class="wf-accordion__btn-left">
            <div class="wf-accordion__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
            </div>
            <div>
              <div class="wf-accordion__name">Filtro de Sedimentos</div>
              <div class="wf-accordion__desc">Agua cristalina y limpia</div>
            </div>
          </div>
          <div class="wf-accordion__btn-right">
            <span class="wf-accordion__price">desde $3,500 MXN</span>
            <svg class="wf-accordion__chevron" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </button>
        <div class="wf-accordion__panel" id="panel-sedimentos" role="region">
          <div class="wf-accordion__panel-inner">
            <div class="wf-accordion__grid">
              <div>
                <p class="wf-accordion__text">Elimina partículas sólidas, óxido y sedimentos del agua. Protege tus equipos y mejora la claridad del agua visible.</p>
              </div>
              <div>
                <h4 class="wf-accordion__benefits-title">Beneficios</h4>
                <ul class="wf-checklist">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Agua visiblemente más limpia</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Protege válvulas y tuberías</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Fácil instalación y mantenimiento</li>
                </ul>
                <div class="wf-price-block">
                  <div class="wf-price-block__label">Precio desde</div>
                  <div class="wf-price-block__price">desde $3,500 MXN</div>
                  <div class="wf-price-block__info">✓ Instalación incluida · ✓ Garantía de fábrica</div>
                </div>
                <a href="/contacto" class="wf-accordion__cta" rel="noopener">Solicitar Cotización</a>
              </div>
            </div>
          </div>
        </div>
      </div>
 
      <!-- ── CARBÓN ACTIVADO ── -->
      <div class="wf-accordion" id="acc-carbon">
        <button
          class="wf-accordion__btn"
          aria-expanded="false"
          aria-controls="panel-carbon"
          onclick="toggleAccordion('acc-carbon')"
        >
          <div class="wf-accordion__btn-left">
            <div class="wf-accordion__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 3h15"/><path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"/><path d="M6 14h12"/></svg>
            </div>
            <div>
              <div class="wf-accordion__name">Filtro de Carbón Activado</div>
              <div class="wf-accordion__desc">Mejora sabor y olor</div>
            </div>
          </div>
          <div class="wf-accordion__btn-right">
            <span class="wf-accordion__price">desde $4,900 MXN</span>
            <svg class="wf-accordion__chevron" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </button>
        <div class="wf-accordion__panel" id="panel-carbon" role="region">
          <div class="wf-accordion__panel-inner">
            <div class="wf-accordion__grid">
              <div>
                <p class="wf-accordion__text">Elimina cloro, sabores y olores desagradables con tecnología de carbón activado de alta densidad.</p>
              </div>
              <div>
                <h4 class="wf-accordion__benefits-title">Beneficios</h4>
                <ul class="wf-checklist">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Elimina sabor a cloro</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Agua con sabor natural</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Mejora la calidad para cocinar</li>
                </ul>
                <div class="wf-price-block">
                  <div class="wf-price-block__label">Precio desde</div>
                  <div class="wf-price-block__price">desde $4,900 MXN</div>
                  <div class="wf-price-block__info">✓ Instalación incluida · ✓ Garantía de fábrica</div>
                </div>
                <a href="/contacto" class="wf-accordion__cta" rel="noopener">Solicitar Cotización</a>
              </div>
            </div>
          </div>
        </div>
      </div>
 
      <!-- ── SISTEMA UV ── -->
      <div class="wf-accordion" id="acc-uv">
        <button
          class="wf-accordion__btn"
          aria-expanded="false"
          aria-controls="panel-uv"
          onclick="toggleAccordion('acc-uv')"
        >
          <div class="wf-accordion__btn-left">
            <div class="wf-accordion__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            </div>
            <div>
              <div class="wf-accordion__name">Sistema UV Purificador</div>
              <div class="wf-accordion__desc">Desinfección sin químicos</div>
            </div>
          </div>
          <div class="wf-accordion__btn-right">
            <span class="wf-accordion__price">desde $8,900 MXN</span>
            <svg class="wf-accordion__chevron" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </button>
        <div class="wf-accordion__panel" id="panel-uv" role="region">
          <div class="wf-accordion__panel-inner">
            <div class="wf-accordion__grid">
              <div>
                <p class="wf-accordion__text">Elimina bacterias, virus y microorganismos con luz ultravioleta sin usar químicos ni alterar el sabor del agua.</p>
              </div>
              <div>
                <h4 class="wf-accordion__benefits-title">Beneficios</h4>
                <ul class="wf-checklist">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Elimina 99.99% de bacterias y virus</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Sin cloro ni químicos adicionales</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Agua 100% segura para toda la familia</li>
                </ul>
                <div class="wf-price-block">
                  <div class="wf-price-block__label">Precio desde</div>
                  <div class="wf-price-block__price">desde $8,900 MXN</div>
                  <div class="wf-price-block__info">✓ Instalación incluida · ✓ Garantía de fábrica</div>
                </div>
                <a href="/contacto" class="wf-accordion__cta" rel="noopener">Solicitar Cotización</a>
              </div>
            </div>
          </div>
        </div>
      </div>
 
      <!-- ── SISTEMA COMPLETO PREMIUM ── -->
      <div class="wf-accordion" id="acc-premium">
        <button
          class="wf-accordion__btn"
          aria-expanded="false"
          aria-controls="panel-premium"
          onclick="toggleAccordion('acc-premium')"
        >
          <div class="wf-accordion__btn-left">
            <div class="wf-accordion__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            </div>
            <div>
              <div class="wf-accordion__name">Sistema Completo Premium</div>
              <div class="wf-accordion__desc">Agua perfecta en toda la casa</div>
            </div>
          </div>
          <div class="wf-accordion__btn-right">
            <span class="wf-accordion__price">Cotización personalizada</span>
            <svg class="wf-accordion__chevron" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        </button>
        <div class="wf-accordion__panel" id="panel-premium" role="region">
          <div class="wf-accordion__panel-inner">
            <div class="wf-accordion__grid">
              <div>
                <p class="wf-accordion__text">Combinación personalizada de suavizador + filtros + UV. Diseñado a medida según el análisis de tu agua. La solución total para tu hogar o negocio.</p>
              </div>
              <div>
                <h4 class="wf-accordion__benefits-title">Incluye Todo</h4>
                <ul class="wf-checklist">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Análisis de agua gratuito</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Diseño personalizado del sistema</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Instalación y puesta en marcha</li>
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Mantenimiento preventivo incluido</li>
                </ul>
                <div class="wf-price-block">
                  <div class="wf-price-block__label">Precio</div>
                  <div class="wf-price-block__price">Cotización personalizada</div>
                  <div class="wf-price-block__info">✓ Análisis de agua gratis · ✓ Instalación incluida</div>
                </div>
                <a href="/contacto" class="wf-accordion__cta" rel="noopener">Solicitar Análisis Gratis</a>
              </div>
            </div>
          </div>
        </div>
      </div>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     ¿POR QUÉ NECESITAS TRATAMIENTO? – 6 CARDS OSCURAS
     React: section "py-20 bg-gradient-to-br from-gray-900 to-black text-white"
════════════════════════════════════════════════════════ -->
<section class="wf-why" id="beneficios" aria-labelledby="wf-why-title">
  <div class="wf-why__inner">
 
    <header class="wf-why__header">
      <h2 id="wf-why-title" class="wf-why__title">¿Por Qué Necesitas Tratamiento de Agua?</h2>
      <p class="wf-why__sub">El agua sin tratar afecta tu salud, hogar y bolsillo</p>
    </header>
 
    <div class="wf-why__grid">
 
      <article class="wf-why-card" aria-labelledby="why-salud">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
          </div>
          <span class="wf-why-pill">85% hogares MX</span>
        </div>
        <h3 id="why-salud">Salud Familiar</h3>
        <p>Bacterias, virus y metales pesados pueden causar enfermedades. El agua tratada protege a tu familia.</p>
      </article>
 
      <article class="wf-why-card" aria-labelledby="why-inversion">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          </div>
          <span class="wf-why-pill">+$50k en daños</span>
        </div>
        <h3 id="why-inversion">Protege tu Inversión</h3>
        <p>El sarro destruye calentadores, lavadoras y tuberías. Un suavizador extiende su vida 3x.</p>
      </article>
 
      <article class="wf-why-card" aria-labelledby="why-ahorro">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
          </div>
          <span class="wf-why-pill">$3k ahorro anual</span>
        </div>
        <h3 id="why-ahorro">Ahorro Real</h3>
        <p>Agua suave requiere 50% menos jabón y detergente. Recuperas tu inversión en 2-3 años.</p>
      </article>
 
      <article class="wf-why-card" aria-labelledby="why-piel">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
          </div>
          <span class="wf-why-pill">100% notorio</span>
        </div>
        <h3 id="why-piel">Piel y Cabello</h3>
        <p>Cloro y minerales resecan tu piel. Agua filtrada te da brillo natural sin productos caros.</p>
      </article>
 
      <article class="wf-why-card" aria-labelledby="why-electro">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9a4 4 0 0 0-2 7.5"/><path d="M12 3v2"/><path d="m6.6 18.4-1.4 1.4"/><path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/><path d="M4 13H2"/><path d="M6.34 7.34 4.93 5.93"/></svg>
          </div>
          <span class="wf-why-pill">30% más eficiente</span>
        </div>
        <h3 id="why-electro">Electrodomésticos</h3>
        <p>El sarro reduce eficiencia de calentadores hasta 30%. Agua suave = menor consumo energético.</p>
      </article>
 
      <article class="wf-why-card" aria-labelledby="why-tranq">
        <div class="wf-why-card__top">
          <div class="wf-why-card__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
          </div>
          <span class="wf-why-pill">Paz mental</span>
        </div>
        <h3 id="why-tranq">Tranquilidad</h3>
        <p>Saber que tu agua es segura no tiene precio. Sistemas Rinnai certificados y garantizados.</p>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     CTA AZUL FINAL
     React: section "py-24 bg-gradient-to-r from-blue-600 to-blue-800 text-white"
════════════════════════════════════════════════════════ -->
<section class="wf-cta" id="contacto" aria-labelledby="wf-cta-title">
  <div class="wf-cta__inner">
 
    <h2 id="wf-cta-title">Mejora la Calidad de tu Agua Hoy</h2>
    <p>Nuestros expertos analizarán tu agua gratis y diseñarán el sistema perfecto para tu hogar</p>
 
    <nav class="wf-cta__btns" aria-label="Opciones de contacto">
      <a href="/contacto" class="wf-btn-white" rel="noopener">
        Análisis de Agua Gratis
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
      <a href="tel:+524494348018" class="wf-btn-outline-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        Llamar Ahora
      </a>
    </nav>
 
  </div>
</section>
 
<!-- JS mínimo para los accordions -->
<script>
  function toggleAccordion(id) {
    const acc = document.getElementById(id);
    const isOpen = acc.classList.contains('wf-accordion--open');
    // Cierra todos
    document.querySelectorAll('.wf-accordion').forEach(function(el) {
      el.classList.remove('wf-accordion--open');
      el.querySelector('.wf-accordion__btn').setAttribute('aria-expanded', 'false');
    });
    // Abre el clickeado si estaba cerrado
    if (!isOpen) {
      acc.classList.add('wf-accordion--open');
      acc.querySelector('.wf-accordion__btn').setAttribute('aria-expanded', 'true');
    }
  }
</script>
@endsection
