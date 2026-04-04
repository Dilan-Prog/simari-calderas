@extends('frontend.layouts.master')
@section('title')
Calentadores de Agua Rinnai - Industria Simari
@endsection
@section('content')
<section class="rd-hero" aria-labelledby="rd-title">
  <div class="rd-hero__inner">
 
    <!-- Logo centrado -->
    <div class="rd-hero__logo">
      <img
        src="/_assets/v11/c52d6ebf603813924afaaac8334daee3d33523d1.png"
        alt="Rinnai"
        width="180"
        height="72"
        loading="eager"
        fetchpriority="high"
        onerror="this.outerHTML='<span style=\'font-size:3rem;font-weight:800;color:#CE000D;letter-spacing:-2px\'>Rinnai</span>'"
      />
    </div>
 
    <!-- Copy central -->
    <div class="rd-hero__copy">
      <div class="rd-hero__badge">Tecnología Japonesa</div>
      <h1 id="rd-title" class="rd-hero__title">
        Calentadores de Agua<br>
        <span class="rd-hero__accent">De Depósito Rinnai</span>
      </h1>
      <p class="rd-hero__desc">
        Tanques termo-aislados de última generación. Agua caliente constante con máxima eficiencia energética.
      </p>
    </div>
 
    <!-- Tabs de segmento -->
    <nav class="rd-tabs" aria-label="Segmentos de producto">
      <a href="#residencial" class="rd-tab rd-tab--active">Residencial</a>
      <a href="#comercial"   class="rd-tab rd-tab--inactive">Comercial</a>
      <a href="#industrial"  class="rd-tab rd-tab--inactive">Industrial</a>
    </nav>
 
    <!-- Showcase card -->
    <div class="rd-showcase">
      <div class="rd-showcase__grid" id="residencial">
 
        <!-- Imagen con overlay y badge de capacidad -->
        <div class="rd-showcase__img-wrap">
          <div class="rd-showcase__img-tint" aria-hidden="true"></div>
          <img
            src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=800"
            alt="Calentador de agua de depósito Rinnai modelo residencial instalado en hogar"
            width="800"
            height="400"
            loading="eager"
          />
          <div class="rd-showcase__cap-badge" aria-label="Capacidad 50 a 80 litros">50-80L</div>
        </div>
 
        <!-- Info -->
        <div class="rd-showcase__content">
          <h2>Modelo Residencial</h2>
 
          <!-- 4 tiles grid-cols-2 -->
          <div class="rd-specs-grid" role="list" aria-label="Especificaciones técnicas">
 
            <div class="rd-spec-tile" role="listitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>
              <div class="rd-spec-tile__val">50-80L</div>
              <div class="rd-spec-tile__label">Capacidad</div>
            </div>
 
            <div class="rd-spec-tile" role="listitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
              <div class="rd-spec-tile__val">1.5-2kW</div>
              <div class="rd-spec-tile__label">Potencia</div>
            </div>
 
            <div class="rd-spec-tile" role="listitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 9a4 4 0 0 0-2 7.5"/><path d="M12 3v2"/><path d="m6.6 18.4-1.4 1.4"/><path d="M20 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/><path d="M4 13H2"/><path d="M6.34 7.34 4.93 5.93"/></svg>
              <div class="rd-spec-tile__val">Casas y departamentos</div>
              <div class="rd-spec-tile__label">Aplicación</div>
            </div>
 
            <div class="rd-spec-tile" role="listitem">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg>
              <div class="rd-spec-tile__val">desde $8,500 MXN</div>
              <div class="rd-spec-tile__label">Precio</div>
            </div>
 
          </div>
 
          <!-- Checklist -->
          <ul class="rd-checklist" aria-label="Características destacadas">
            <li class="rd-checklist__item">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
              Instalación sencilla
            </li>
            <li class="rd-checklist__item">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
              Bajo consumo
            </li>
            <li class="rd-checklist__item">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
              Ideal para 2-4 personas
            </li>
            <li class="rd-checklist__item">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
              Diseño compacto
            </li>
          </ul>
 
          <a href="/contacto" class="rd-btn-primary" rel="noopener">
            Cotizar Residencial
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
        </div>
 
      </div>
    </div>
 
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════
     TABLA COMPARATIVA
     React: <section class="py-20 bg-gray-50">
══════════════════════════════════════════════════════ -->
<section class="rd-compare" id="comparativa" aria-labelledby="rd-compare-h">
  <div class="rd-compare__inner">
 
    <h2 id="rd-compare-h" class="rd-compare__title">Comparativa de Modelos</h2>
 
    <div class="rd-table-scroll">
      <table class="rd-table">
        <caption style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">
          Comparativa entre modelos Rinnai residencial, comercial e industrial
        </caption>
        <thead>
          <tr>
            <th scope="col">Característica</th>
            <th scope="col">Residencial</th>
            <th scope="col">Comercial</th>
            <th scope="col">Industrial</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Capacidad</td>
            <td>50-80L</td>
            <td>100-200L</td>
            <td>300-500L</td>
          </tr>
          <tr>
            <td>Usuarios Simultáneos</td>
            <td>2-4</td>
            <td>8-15</td>
            <td>30+</td>
          </tr>
          <tr>
            <td>Recuperación</td>
            <td>45 min</td>
            <td>30 min</td>
            <td>20 min</td>
          </tr>
          <tr>
            <td>Garantía</td>
            <td>5 años</td>
            <td>7 años</td>
            <td>10 años</td>
          </tr>
          <tr>
            <td>Eficiencia</td>
            <td>
              <svg class="rd-table__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Incluido"><path d="M20 6 9 17l-5-5"/></svg>
            </td>
            <td>
              <svg class="rd-table__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Incluido"><path d="M20 6 9 17l-5-5"/></svg>
            </td>
            <td>
              <svg class="rd-table__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Incluido"><path d="M20 6 9 17l-5-5"/></svg>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
 
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════
     4 FEATURE CARDS
     React: <section class="py-20 bg-white"> grid md:grid-cols-2 lg:grid-cols-4
══════════════════════════════════════════════════════ -->
<section class="rd-features" id="ventajas" aria-labelledby="rd-feat-h">
  <div class="rd-features__inner">
    <h2 id="rd-feat-h" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Ventajas de los calentadores de depósito Rinnai</h2>
 
    <div class="rd-features__grid">
 
      <article class="rd-feat-card" aria-labelledby="feat-garantia">
        <div class="rd-feat-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
        </div>
        <h3 id="feat-garantia">Garantía Extendida</h3>
        <p>Hasta 10 años de cobertura total</p>
      </article>
 
      <article class="rd-feat-card" aria-labelledby="feat-calor">
        <div class="rd-feat-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
        </div>
        <h3 id="feat-calor">Calentamiento Rápido</h3>
        <p>Tecnología de alta eficiencia</p>
      </article>
 
      <article class="rd-feat-card" aria-labelledby="feat-consumo">
        <div class="rd-feat-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
        </div>
        <h3 id="feat-consumo">Bajo Consumo</h3>
        <p>Ahorro energético certificado</p>
      </article>
 
      <article class="rd-feat-card" aria-labelledby="feat-inst">
        <div class="rd-feat-card__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h3 id="feat-inst">Instalación Express</h3>
        <p>Servicio profesional en 24hrs</p>
      </article>
 
    </div>
  </div>
</section>
 
 
<!-- ═══════════════════════════════════════════════════
     CTA FINAL
     React: style="background: linear-gradient(135deg, rgb(206,0,13) 0%, rgb(160,0,8) 100%)"
══════════════════════════════════════════════════════ -->
<section class="rd-cta" id="contacto" aria-labelledby="rd-cta-h">
  <div class="rd-cta__inner">
 
    <h2 id="rd-cta-h">¿Listo para un sistema más eficiente?</h2>
    <p>Nuestros expertos te ayudarán a elegir el modelo perfecto para tus necesidades</p>
 
    <nav class="rd-cta__btns" aria-label="Opciones de contacto">
      <a href="/contacto" class="rd-btn-white" rel="noopener">
        Solicitar Asesoría Gratuita
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
      <a href="tel:+524494348018" class="rd-btn-outline-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        Llamar Ahora
      </a>
    </nav>
 
  </div>
</section>
@endsection
