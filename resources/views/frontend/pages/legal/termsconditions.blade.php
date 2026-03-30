@extends('frontend.layouts.master')

@section('title')
@endsection

@section('content')
<section class="simari-privacy-body" aria-label="Contenido de términos y condiciones">
    {{-- HERO con look Tailwind --}}
    <section class="simari-privacy-presentation simari-hero">
        <div class="simari-hero__bg" aria-hidden="true"></div>

        <div class="presentation-container simari-hero__container">
            <div class="simari-hero__badge" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="lucide lucide-scale">
                    <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                    <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                    <path d="M7 21h10"></path>
                    <path d="M12 3v18"></path>
                    <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
                </svg>
            </div>

            <h1 class="simari-hero__title">Términos y Condiciones</h1>

            <p class="presentation-subcontent-title simari-hero__subtitle">
                Condiciones generales de uso del sitio web y contratación de servicios de SIMARI Calderas
            </p>

            <p class="presentation-subcontent-date simari-hero__date">
                Última actualización: 28 de febrero de 2026
            </p>
        </div>
    </section>
</section>

<section class="simari-terms-section">
  <div class="container simari-terms-section">
    <div class="simari-terms-list">

      <!-- Aceptación de los Términos -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
              <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
              <path d="M10 9H8"></path>
              <path d="M16 13H8"></path>
              <path d="M16 17H8"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Aceptación de los Términos</h2>
            <div class="simari-privacy-card__text">
              <p>Al acceder y utilizar el sitio web de SIMARI Calderas S.A. de C.V. (en adelante "SIMARI" o "el Sitio"), usted acepta estar legalmente obligado por estos Términos y Condiciones de Uso.</p>
              <p>Si no está de acuerdo con estos términos, le solicitamos que no utilice nuestro sitio web ni nuestros servicios.</p>
              <p>SIMARI se reserva el derecho de modificar estos términos en cualquier momento. Es responsabilidad del usuario revisar periódicamente esta página para estar al tanto de cualquier cambio.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Uso del Sitio Web -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Uso del Sitio Web</h2>
            <div class="simari-privacy-card__text">
              <p>El usuario se compromete a utilizar el Sitio de manera legal y apropiada, comprometiéndose a:</p>
              <ul class="simari-privacy-bullets">
                <li>No utilizar el Sitio de manera que pueda dañar, deshabilitar, sobrecargar o deteriorar el servidor</li>
                <li>No intentar obtener acceso no autorizado a ninguna parte del Sitio</li>
                <li>No utilizar el Sitio para transmitir material ilegal, amenazante, abusivo, difamatorio u obsceno</li>
                <li>No utilizar robots, spiders u otros dispositivos automáticos para monitorear el Sitio</li>
                <li>No infringir los derechos de propiedad intelectual de SIMARI o de terceros</li>
              </ul>
            </div>
          </div>
        </div>
      </article>

      <!-- Propiedad Intelectual -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
              <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
              <path d="M7 21h10"></path>
              <path d="M12 3v18"></path>
              <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Propiedad Intelectual</h2>
            <div class="simari-privacy-card__text">
              <p>Todo el contenido incluido en este Sitio, incluyendo pero no limitado a textos, gráficos, logos, íconos, imágenes, clips de audio, descargas digitales y compilaciones de datos, es propiedad de SIMARI o de sus proveedores de contenido y está protegido por las leyes mexicanas e internacionales de propiedad intelectual.</p>
              <p>Las marcas comerciales, marcas de servicio y logos utilizados en este Sitio son propiedad de SIMARI o de terceros autorizados. Ningún contenido de este Sitio debe ser interpretado como una concesión de licencia o derecho de uso.</p>
              <p class="simari-privacy-emphasis">Queda estrictamente prohibida la reproducción, distribución o modificación del contenido sin autorización expresa y por escrito de SIMARI.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Productos y Servicios -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
              <path d="M12 22V12"></path>
              <polyline points="3.29 7 12 12 20.71 7"></polyline>
              <path d="m7.5 4.27 9 5.15"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Productos y Servicios</h2>
            <div class="simari-privacy-card__text">
              <p>SIMARI se esfuerza por proporcionar información precisa sobre productos y servicios. Sin embargo:</p>
              <ul class="simari-privacy-bullets">
                <li>Las especificaciones técnicas pueden estar sujetas a cambios sin previo aviso</li>
                <li>Las imágenes de productos son referenciales y pueden diferir del producto real</li>
                <li>Los precios mostrados no incluyen IVA ni costos de instalación, salvo que se indique lo contrario</li>
                <li>La disponibilidad de productos está sujeta a existencias</li>
              </ul>
              <p>Todas las cotizaciones tienen una validez de 30 días naturales, salvo que se especifique un plazo diferente.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Cotizaciones y Pedidos -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="14" x="2" y="5" rx="2"></rect>
              <line x1="2" x2="22" y1="10" y2="10"></line>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Cotizaciones y Pedidos</h2>
            <div class="simari-privacy-card__text">
              <p>El proceso de cotización y compra incluye:</p>
              <ul class="simari-privacy-bullets">
                <li><strong>Solicitud:</strong> El cliente solicita cotización mediante el sitio web o contacto directo</li>
                <li><strong>Cotización:</strong> SIMARI proporciona una cotización detallada con especificaciones y precios</li>
                <li><strong>Aceptación:</strong> El cliente acepta la cotización y proporciona datos para facturación</li>
                <li><strong>Orden de Compra:</strong> Se genera orden de compra formal con términos y condiciones</li>
                <li><strong>Anticipo:</strong> Según producto, puede requerirse anticipo del 30-50%</li>
                <li><strong>Producción/Entrega:</strong> Tiempos estimados según tipo de equipo</li>
              </ul>

              <div class="simari-privacy-note">
                <strong>Importante:</strong> La orden de compra se considera confirmada únicamente cuando SIMARI emite confirmación por escrito.
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Entregas e Instalación -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path>
              <path d="M15 18H9"></path>
              <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path>
              <circle cx="17" cy="18" r="2"></circle>
              <circle cx="7" cy="18" r="2"></circle>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Entregas e Instalación</h2>
            <div class="simari-privacy-card__text">
              <p><strong>Plazos de entrega:</strong></p>
              <ul class="simari-privacy-bullets">
                <li>Equipos en existencia: 5-10 días hábiles</li>
                <li>Equipos bajo pedido: 30-60 días hábiles según fabricación</li>
                <li>Proyectos especiales: según cotización específica</li>
              </ul>

              <p><strong>Instalación:</strong></p>
              <p>Los servicios de instalación se cotizan por separado y deben ser realizados por personal autorizado de SIMARI o técnicos certificados. La garantía del equipo puede verse afectada si la instalación es realizada por personal no autorizado.</p>
              <p class="simari-privacy-emphasis">El cliente es responsable de proporcionar las condiciones adecuadas del sitio de instalación según especificaciones técnicas.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Garantías -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Garantías</h2>
            <div class="simari-privacy-card__text">
              <p>SIMARI ofrece garantías según el tipo de producto:</p>
              <ul class="simari-privacy-bullets">
                <li><strong>Calderas industriales:</strong> 12 meses contra defectos de fabricación</li>
                <li><strong>Bombas de calor:</strong> Según garantía del fabricante (12-24 meses)</li>
                <li><strong>Accesorios y componentes:</strong> 6-12 meses según especificación</li>
                <li><strong>Instalación:</strong> 90 días sobre mano de obra</li>
              </ul>

              <p><strong>La garantía NO cubre:</strong></p>
              <ul class="simari-privacy-bullets">
                <li>Daños causados por uso inadecuado o negligencia</li>
                <li>Falta de mantenimiento preventivo</li>
                <li>Modificaciones no autorizadas</li>
                <li>Desastres naturales o fuerza mayor</li>
                <li>Desgaste normal por uso</li>
              </ul>
            </div>
          </div>
        </div>
      </article>

      <!-- Limitación de Responsabilidad -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
              <path d="M12 9v4"></path>
              <path d="M12 17h.01"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Limitación de Responsabilidad</h2>
            <div class="simari-privacy-card__text">
              <p>SIMARI no será responsable por daños directos, indirectos, incidentales, especiales o consecuentes que resulten de:</p>
              <ul class="simari-privacy-bullets">
                <li>El uso o la imposibilidad de usar el Sitio</li>
                <li>Errores u omisiones en el contenido del Sitio</li>
                <li>Interrupción del servicio o acceso al Sitio</li>
                <li>Información incorrecta proporcionada por el usuario</li>
                <li>Decisiones tomadas basándose en información del Sitio</li>
              </ul>
              <p>La información técnica en este Sitio es de carácter general. Para aplicaciones específicas, consulte con nuestro equipo de ingeniería.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Ley Aplicable y Jurisdicción -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
              <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
              <path d="M7 21h10"></path>
              <path d="M12 3v18"></path>
              <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Ley Aplicable y Jurisdicción</h2>
            <div class="simari-privacy-card__text">
              <p>Estos Términos y Condiciones se rigen por las leyes de los Estados Unidos Mexicanos. Cualquier controversia derivada del uso del Sitio o de la prestación de servicios será resuelta ante los tribunales competentes de [Ciudad/Estado], renunciando expresamente a cualquier otra jurisdicción que pudiera corresponderles.</p>
              <p>En caso de que alguna disposición de estos términos sea considerada inválida o inaplicable, las disposiciones restantes continuarán en pleno vigor y efecto.</p>
            </div>
          </div>
        </div>
      </article>

      <!-- Contacto y Atención al Cliente -->
      <article class="simari-privacy-card simari-terms-card">
        <div class="simari-terms-card__row">
          <div class="simari-privacy-card__badge simari-terms-card__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
              <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
              <path d="M10 9H8"></path>
              <path d="M16 13H8"></path>
              <path d="M16 17H8"></path>
            </svg>
          </div>

          <div class="simari-terms-card__content">
            <h2 class="simari-privacy-card__title simari-terms-card__title">Contacto y Atención al Cliente</h2>
            <div class="simari-privacy-card__text">
              <p>Para cualquier consulta sobre estos Términos y Condiciones, o para reportar un problema, puede contactarnos a través de:</p>

              <div class="simari-terms-contact-box">
                <p><strong>Correo electrónico:</strong> <a class="simari-privacy-link" href="mailto:info@simaricalderas.com">info@simaricalderas.com</a></p>
                <p><strong>Teléfono:</strong> +52 (55) 1234-5678</p>
                <p><strong>Horario de atención:</strong> Lunes a Viernes, 9:00 - 18:00 hrs</p>
              </div>
            </div>
          </div>
        </div>
      </article>

    </div>

    <!-- CTA final -->
    <div class="simari-privacy-card simari-cta--dark simari-terms-cta">
      <div class="simari-terms-cta__icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
        </svg>
      </div>

      <h3 class="simari-privacy-cta__title">Tu confianza es nuestra prioridad</h3>
      <p class="simari-privacy-cta__text">
        En SIMARI nos comprometemos a proporcionar equipos de la más alta calidad y un servicio excepcional. Estos términos están diseñados para proteger tanto tus derechos como los nuestros.
      </p>

      <div class="simari-cta__footer">
        © 2026 SIMARI Calderas S.A. de C.V. - Todos los derechos reservados
      </div>
    </div>
  </div>
</section>

@endsection
