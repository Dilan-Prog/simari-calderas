@extends('frontend.layouts.master')
@section('title')
@endsection
@section('content')
<section class="rp-hero" aria-labelledby="rp-hero-title">
 
  <!-- Imagen de fondo + overlay -->
  <div class="rp-hero__bg" aria-hidden="true">
    <img
      src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1920"
      alt=""
      loading="eager"
      fetchpriority="high"
    />
    <div class="rp-hero__bg-overlay"></div>
  </div>
 
  <div class="rp-hero__inner">
    <div class="rp-hero__content">
 
      <!-- Logo -->
      <div class="rp-hero__logo">
        <img
          src="/_assets/v11/c52d6ebf603813924afaaac8334daee3d33523d1.png"
          alt="Rinnai"
          width="160"
          height="72"
          loading="eager"
          onerror="this.outerHTML='<span style=\'font-size:2.5rem;font-weight:800;color:#CE000D;letter-spacing:-2px\'>Rinnai</span>'"
        />
      </div>
 
      <!-- Badge – React: inline-block px-5 py-2 mb-8 bg-[#CE000D]/19 text-[#CE000D] (sin border-radius) -->
      <div class="rp-hero__badge">
        <div class="rp-hero__badge-inner">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
          Sin Tanque • Sin Espera
        </div>
      </div>
 
      <!-- Título -->
      <h1 id="rp-hero-title" class="rp-hero__title">
        Calentadores<br>
        <span class="rp-accent">de Paso</span><br>
        <span class="rp-sub">a Gas</span>
      </h1>
 
      <!-- Descripción -->
      <p class="rp-hero__desc">
        Agua caliente infinita, al instante. La tecnología tankless más avanzada del mundo que revoluciona tu experiencia de confort.
      </p>
 
      <!-- Stats 4 columnas -->
      <div class="rp-hero__stats" role="list" aria-label="Datos clave del sistema tankless">
        <div class="rp-stat" role="listitem">
          <div class="rp-stat__num">∞</div>
          <div class="rp-stat__label">Agua ilimitada</div>
        </div>
        <div class="rp-stat" role="listitem">
          <div class="rp-stat__num">40%</div>
          <div class="rp-stat__label">Ahorro energía</div>
        </div>
        <div class="rp-stat" role="listitem">
          <div class="rp-stat__num">70%</div>
          <div class="rp-stat__label">Espacio ahorrado</div>
        </div>
        <div class="rp-stat" role="listitem">
          <div class="rp-stat__num">20 años</div>
          <div class="rp-stat__label">Vida útil</div>
        </div>
      </div>
 
      <!-- CTAs -->
      <nav class="rp-hero__btns" aria-label="Acciones principales">
        <a href="/contacto" class="rp-btn-red" rel="noopener">
          Cotizar Sistema Tankless
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="tel:+524494348018" class="rp-btn-outline-white">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Llamar Experto
        </a>
      </nav>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     CÓMO FUNCIONA – TIMELINE 4 PASOS
     React: section "py-24 bg-gradient-to-b from-white to-gray-50"
════════════════════════════════════════════════════════ -->
<section class="rp-how" id="como-funciona" aria-labelledby="rp-how-title">
  <div class="rp-how__inner">
 
    <header class="rp-how__header">
      <div class="rp-how__badge">Tecnología Tankless</div>
      <h2 id="rp-how-title" class="rp-how__title">¿Cómo Funciona?</h2>
      <p class="rp-how__sub">El sistema más inteligente: agua caliente solo cuando la necesitas, ahorrando energía y espacio</p>
    </header>
 
    <div class="rp-timeline" role="list">
 
      <!-- Paso 1 -->
      <div class="rp-timeline-step" role="listitem">
        <div class="rp-timeline-dot" aria-hidden="true"></div>
        <div class="rp-step-card">
          <div class="rp-step-card__top">
            <span class="rp-step-num">01</span>
            <div class="rp-step-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
            </div>
          </div>
          <h3>Agua Fría Entra</h3>
          <p>El sensor de flujo detecta el paso del agua instantáneamente al abrir la llave.</p>
        </div>
        <div class="rp-step-img" aria-hidden="true">
          <img
            src="https://images.unsplash.com/photo-1563207153-f403bf289096?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600"
            alt=""
            width="600"
            height="260"
            loading="lazy"
          />
        </div>
      </div>
 
      <!-- Paso 2 -->
      <div class="rp-timeline-step rp-timeline-step--even" role="listitem">
        <div class="rp-timeline-dot" aria-hidden="true"></div>
        <div class="rp-step-card">
          <div class="rp-step-card__top">
            <span class="rp-step-num">02</span>
            <div class="rp-step-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
            </div>
          </div>
          <h3>Ignición Automática</h3>
          <p>Sistema electrónico enciende el quemador de alta eficiencia sin piloto permanente.</p>
        </div>
        <div class="rp-step-img" aria-hidden="true">
          <img
            src="https://images.unsplash.com/photo-1602080858428-57174f9431cf?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600"
            alt=""
            width="600"
            height="260"
            loading="lazy"
          />
        </div>
      </div>
 
      <!-- Paso 3 -->
      <div class="rp-timeline-step" role="listitem">
        <div class="rp-timeline-dot" aria-hidden="true"></div>
        <div class="rp-step-card">
          <div class="rp-step-card__top">
            <span class="rp-step-num">03</span>
            <div class="rp-step-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
            </div>
          </div>
          <h3>Calentamiento Instantáneo</h3>
          <p>El intercambiador de cobre calienta el agua en milisegundos con máxima eficiencia.</p>
        </div>
        <div class="rp-step-img" aria-hidden="true">
          <img
            src="https://images.unsplash.com/photo-1607400201889-565b1ee75f8e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600"
            alt=""
            width="600"
            height="260"
            loading="lazy"
          />
        </div>
      </div>
 
      <!-- Paso 4 -->
      <div class="rp-timeline-step rp-timeline-step--even" role="listitem">
        <div class="rp-timeline-dot" aria-hidden="true"></div>
        <div class="rp-step-card">
          <div class="rp-step-card__top">
            <span class="rp-step-num">04</span>
            <div class="rp-step-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>
            </div>
          </div>
          <h3>Temperatura Perfecta</h3>
          <p>Control digital mantiene los grados exactos que programaste, sin fluctuaciones.</p>
        </div>
        <div class="rp-step-img" aria-hidden="true">
          <img
            src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600"
            alt=""
            width="600"
            height="260"
            loading="lazy"
          />
        </div>
      </div>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     MODELOS – 4 CARDS
     React: section "py-20 bg-white" grid md:grid-cols-4
════════════════════════════════════════════════════════ -->
<section class="rp-models" id="modelos" aria-labelledby="rp-models-title">
  <div class="rp-models__inner">
 
    <h2 id="rp-models-title" class="rp-models__title">Modelos Disponibles</h2>
 
    <div class="rp-models__grid">
 
      <!-- V65i -->
      <article class="rp-model-card" aria-labelledby="model-v65">
        <div class="rp-model-card__top">
          <div class="rp-model-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
          </div>
          <h3 id="model-v65">V65i</h3>
          <div class="rp-model-card__flow">6.5 L/min</div>
        </div>
        <ul class="rp-model-card__list" aria-label="Características del modelo V65i">
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            1-2 usuarios
          </li>
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Regadera + Lavabo
          </li>
        </ul>
        <a href="/contacto" class="rp-model-card__btn" rel="noopener">Ver más</a>
      </article>
 
      <!-- V94i -->
      <article class="rp-model-card" aria-labelledby="model-v94">
        <div class="rp-model-card__top">
          <div class="rp-model-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
          </div>
          <h3 id="model-v94">V94i</h3>
          <div class="rp-model-card__flow">9.4 L/min</div>
        </div>
        <ul class="rp-model-card__list" aria-label="Características del modelo V94i">
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            2-3 usuarios
          </li>
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            2 Regaderas simultáneas
          </li>
        </ul>
        <a href="/contacto" class="rp-model-card__btn" rel="noopener">Ver más</a>
      </article>
 
      <!-- RL94i -->
      <article class="rp-model-card" aria-labelledby="model-rl94">
        <div class="rp-model-card__top">
          <div class="rp-model-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
          </div>
          <h3 id="model-rl94">RL94i</h3>
          <div class="rp-model-card__flow">11 L/min</div>
        </div>
        <ul class="rp-model-card__list" aria-label="Características del modelo RL94i">
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            3-4 usuarios
          </li>
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Casa completa
          </li>
        </ul>
        <a href="/contacto" class="rp-model-card__btn" rel="noopener">Ver más</a>
      </article>
 
      <!-- RU160i -->
      <article class="rp-model-card" aria-labelledby="model-ru160">
        <div class="rp-model-card__top">
          <div class="rp-model-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
          </div>
          <h3 id="model-ru160">RU160i</h3>
          <div class="rp-model-card__flow">16 L/min</div>
        </div>
        <ul class="rp-model-card__list" aria-label="Características del modelo RU160i">
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            5+ usuarios
          </li>
          <li>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Uso comercial
          </li>
        </ul>
        <a href="/contacto" class="rp-model-card__btn" rel="noopener">Ver más</a>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     ¿POR QUÉ TANKLESS? – 6 CARDS OSCURAS
     React: section "py-20 bg-gradient-to-br from-gray-900 to-black text-white"
════════════════════════════════════════════════════════ -->
<section class="rp-why" id="ventajas" aria-labelledby="rp-why-title">
  <div class="rp-why__inner">
 
    <header class="rp-why__header">
      <h2 id="rp-why-title" class="rp-why__title">
        ¿Por Qué Elegir <span>Tankless</span>?
      </h2>
      <p class="rp-why__sub">El futuro del agua caliente ya está aquí</p>
    </header>
 
    <div class="rp-why__grid">
 
      <article class="rp-why-card" aria-labelledby="why-infinita">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
        </div>
        <h3 id="why-infinita">Agua Infinita</h3>
        <p>Nunca más te quedarás sin agua caliente. Múltiples regaderas funcionando al mismo tiempo sin problemas.</p>
        <span class="rp-why-pill">Sin límite de capacidad</span>
      </article>
 
      <article class="rp-why-card" aria-labelledby="why-ahorro">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
        </div>
        <h3 id="why-ahorro">Ahorro Real</h3>
        <p>Solo calientas el agua que usas. Reduce tu consumo de gas hasta 40% comparado con boilers tradicionales.</p>
        <span class="rp-why-pill">Ahorro hasta $3,000/año</span>
      </article>
 
      <article class="rp-why-card" aria-labelledby="why-espacio">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.8 19.6A2 2 0 1 0 14 16H2"/><path d="M17.5 8a2.5 2.5 0 1 1 2 4H2"/><path d="M9.8 4.4A2 2 0 1 1 11 8H2"/></svg>
        </div>
        <h3 id="why-espacio">Espacio Libre</h3>
        <p>Diseño compacto del tamaño de una maleta. Perfecto para espacios pequeños, se instala en cualquier pared.</p>
        <span class="rp-why-pill">70% menos espacio</span>
      </article>
 
      <article class="rp-why-card" aria-labelledby="why-durabilidad">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
        </div>
        <h3 id="why-durabilidad">Máxima Durabilidad</h3>
        <p>Sin corrosión por acumulación de agua. Componentes premium japoneses garantizan 20+ años de vida útil.</p>
        <span class="rp-why-pill">Garantía 7 años</span>
      </article>
 
      <article class="rp-why-card" aria-labelledby="why-digital">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
        </div>
        <h3 id="why-digital">Control Digital</h3>
        <p>Pantalla LCD para ajustar temperatura exacta. Algunos modelos incluyen control remoto y Wi-Fi.</p>
        <span class="rp-why-pill">Precisión de 1°C</span>
      </article>
 
      <article class="rp-why-card" aria-labelledby="why-seguridad">
        <div class="rp-why-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
        </div>
        <h3 id="why-seguridad">Seguridad Total</h3>
        <p>Múltiples sensores de seguridad, apagado automático y protección contra sobrecalentamiento.</p>
        <span class="rp-why-pill">Certificación UL</span>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════════
     CTA ROJO – 2 COLUMNAS CON STATS
     React: section "py-24 bg-gradient-to-r from-[#CE000D] to-[#A00008]"
════════════════════════════════════════════════════════ -->
<section class="rp-cta" id="contacto" aria-labelledby="rp-cta-title">
  <div class="rp-cta__inner">
    <div class="rp-cta__grid">
 
      <!-- Columna izquierda: texto + CTAs -->
      <div>
        <h2 id="rp-cta-title" class="rp-cta__title">
          Haz el Cambio a<br>Tecnología Tankless
        </h2>
        <p class="rp-cta__desc">
          Únete a millones de hogares en el mundo que ya disfrutan de agua caliente ilimitada, ahorro energético y máxima comodidad.
        </p>
        <nav class="rp-cta__btns" aria-label="Opciones de contacto">
          <a href="/contacto" class="rp-btn-white-cta" rel="noopener">
            Solicitar Cotización
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
          <a href="tel:+524494348018" class="rp-btn-outline-cta">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            449 434 8018
          </a>
        </nav>
      </div>
 
      <!-- Columna derecha: 4 stats en grid 2x2 -->
      <div class="rp-cta__stats" role="list" aria-label="Datos globales de Rinnai">
        <div class="rp-cta-stat" role="listitem">
          <div class="rp-cta-stat__num">25M+</div>
          <div class="rp-cta-stat__label">Unidades vendidas mundialmente</div>
        </div>
        <div class="rp-cta-stat" role="listitem">
          <div class="rp-cta-stat__num">#1</div>
          <div class="rp-cta-stat__label">Marca tankless en Japón</div>
        </div>
        <div class="rp-cta-stat" role="listitem">
          <div class="rp-cta-stat__num">40%</div>
          <div class="rp-cta-stat__label">Ahorro promedio en gas</div>
        </div>
        <div class="rp-cta-stat" role="listitem">
          <div class="rp-cta-stat__num">20+</div>
          <div class="rp-cta-stat__label">Años de vida útil</div>
        </div>
      </div>
 
    </div>
  </div>
</section>
@endsection
