@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/legal.css'];
    $metaTitle = 'Política de Devoluciones — Equiterm Industries';
    $metaDescription = 'Condiciones para cancelar tu compra, devolver un producto y recibir tu reembolso en Equiterm Industries.';
    $canonicalUrl = route('return-policy');
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('canonical', $canonicalUrl)
@section('og_title', $metaTitle)
@section('og_description', $metaDescription)
@section('og_url', $canonicalUrl)

@section('content')
<div class="eq-shop-legal">
<section class="simari-privacy-body" aria-label="Contenido de política de devoluciones">
    <section class="simari-privacy-presentation">
        <div class="presentation-container">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="lucide lucide-undo-2">
                <path d="M9 14 4 9l5-5"></path>
                <path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"></path>
            </svg>

            <h1>Política de Devoluciones</h1>

            <p class="presentation-subcontent-title">
                Cómo cancelar tu compra, devolver un producto y recibir tu reembolso
            </p>

            <p class="presentation-subcontent-date">
                Última actualización: {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>
    </section>
</section>

<section class="simari-terms-section">
  <div class="container simari-terms-section">
    <div class="simari-terms-list">

      <!-- Derecho de cancelación -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Derecho de cancelación (5 días hábiles)</h2>
            <div class="simari-privacy-card__text">
              <p>De acuerdo con la Ley Federal de Protección al Consumidor, tienes derecho a cancelar tu compra dentro de los <strong>5 días hábiles</strong> siguientes a la entrega del producto, sin necesidad de justificar el motivo.</p>
              <p>Este derecho aplica a los <strong>productos en existencia</strong> comprados a través de nuestra tienda en línea. No se cobra ninguna penalización ni comisión por cancelación dentro de este plazo.</p>
              <p class="simari-privacy-note">Este plazo es el mínimo que marca la ley. Si tu producto llegó dañado, incompleto o distinto al que ordenaste, contáctanos de inmediato — ese caso se resuelve como garantía/error de envío, no como cancelación, y no está sujeto a este límite de días.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Cómo solicitar una devolución -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12.04 2C6.6 2 2.2 6.4 2.2 11.84c0 1.94.55 3.75 1.5 5.28L2 22l5.02-1.63a9.8 9.8 0 0 0 5.02 1.37c5.43 0 9.84-4.4 9.84-9.84C21.88 6.4 17.47 2 12.04 2Z"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Cómo solicitar una devolución</h2>
            <div class="simari-privacy-card__text">
              <p>Escríbenos por <a class="simari-privacy-link" href="https://wa.me/{{ \App\Models\Setting::get('footer.phone_link', '5214494577320') }}" target="_blank" rel="noopener nofollow">WhatsApp</a> o al correo <a class="simari-privacy-link" href="mailto:{{ \App\Models\Setting::get('footer.email', 'administracion@equitermindustries.com.mx') }}">{{ \App\Models\Setting::get('footer.email', 'administracion@equitermindustries.com.mx') }}</a> indicando tu número de pedido, el producto que deseas devolver y el motivo.</p>
              <p>Te confirmaremos si tu producto es elegible y te daremos las instrucciones para el envío de regreso.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Condiciones del producto -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6 9 17l-5-5"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Condiciones para que la devolución proceda</h2>
            <div class="simari-privacy-card__text">
              <ul class="simari-privacy-bullets">
                <li>El producto debe regresar sin usar, en su empaque original y con todos sus accesorios/manuales</li>
                <li>Debe incluir la factura o comprobante de compra</li>
                <li>No debe presentar daños causados por el cliente durante el uso o una instalación no autorizada</li>
              </ul>
            </div>
          </div>
        </div>
      </article>

      <!-- Excepciones -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
              <path d="M12 9v4"></path>
              <path d="M12 17h.01"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Productos que no aplican para devolución</h2>
            <div class="simari-privacy-card__text">
              <ul class="simari-privacy-bullets">
                <li><strong>Equipos bajo pedido o de fabricación especial:</strong> al ser fabricados o importados específicamente para tu compra, una vez iniciado el proceso de fabricación no pueden cancelarse ni devolverse. Te lo indicamos siempre antes de confirmar tu pedido.</li>
                <li><strong>Equipos ya instalados:</strong> una vez instalado el equipo por personal autorizado, la devolución física ya no es posible; en su lugar aplica la garantía correspondiente (ver <a class="simari-privacy-link" href="{{ route('terms-of-service') }}">Términos y Condiciones</a>).</li>
                <li><strong>Proyectos especiales cotizados a la medida:</strong> se rigen por las condiciones de cancelación pactadas en la cotización correspondiente.</li>
              </ul>
            </div>
          </div>
        </div>
      </article>

      <!-- Costos de envío de devolución -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="16" height="13" x="4" y="6" rx="2"></rect>
              <path d="M4 11h16"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">¿Quién paga el envío de regreso?</h2>
            <div class="simari-privacy-card__text">
              <p><strong>Si cancelas por decisión propia</strong> dentro de los 5 días hábiles (no te gustó, ya no lo necesitas), el costo de envío de regreso corre por tu cuenta.</p>
              <p><strong>Si el producto llegó dañado, incompleto o es distinto al que ordenaste</strong>, el costo de envío de regreso lo cubre Equiterm Industries.</p>
              <p>El costo de envío original (si tu pedido tuvo cargo de envío) no es reembolsable, salvo cuando la devolución se debe a un error nuestro.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Reembolsos -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2v20"></path>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Tiempo y forma de reembolso</h2>
            <div class="simari-privacy-card__text">
              <p>Una vez que recibimos e inspeccionamos el producto devuelto, procesamos el reembolso en un plazo de <strong>5 a 10 días hábiles</strong>, usando el mismo método de pago con el que realizaste tu compra.</p>
              <p>Te avisaremos por WhatsApp o correo en cuanto tu reembolso haya sido procesado.</p>
            </div>
          </div>
        </div>
      </article>

    </div>
  </div>
</section>
</div>
@endsection
