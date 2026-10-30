<!DOCTYPE html>
<html lang="es-MX">
  <head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MXFK4BK9');</script>
    <!-- End Google Tag Manager -->
    {{--
      Microsoft Clarity (mapas de calor y grabaciones de sesión). Solo en el dominio
      real del sitio (no depende de APP_ENV, que en Hostinger no es "production")
      para que las visitas de desarrollo no ensucien el proyecto. Las páginas del
      checkout se marcan con data-clarity-mask="True" para que Clarity no grabe el
      contenido con datos personales (nombre, correo, teléfono, domicilio, RFC).
      Su CSP vive en App\Http\Middleware\SecurityHeaders.
    --}}
    @if (in_array(request()->getHost(), ['equitermindustries.com.mx', 'www.equitermindustries.com.mx'], true))
    <!-- Clarity tracking code for https://equitermindustries.com.mx/ -->
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "z5x2zunsl6");
    </script>
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('frontend.layouts.partials.head-meta')
    {{--
      ad-tracking.js debe cargar en TODAS las páginas del shop (gclid/wbraid/UTM
      tracking sitewide), no solo en las vistas que setean $shopVite -- por eso
      se agrega aquí directo en vez de depender de que cada vista lo declare.
    --}}
    @vite(array_merge(['resources/js/frontend/shop/ad-tracking.js'], $shopVite ?? []))
  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MXFK4BK9"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    @include('frontend.shop.layouts.header')
    <main>
        @yield('content')
    </main>
    @include('frontend.shop.layouts.footer')
    @include('frontend.shop.partials.whatsapp-button')

    {{--
      Insignia de Reseñas de Clientes en Google — opcional, muestra la
      puntuación de vendedor en el sitio. position=BOTTOM_LEFT a propósito:
      BOTTOM_RIGHT ya está ocupado por el botón flotante de WhatsApp
      (.wa-float-btn) y chocarían.
    --}}
    <script id="merchantWidgetScript" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
    <script>
      merchantWidgetScript.addEventListener('load', function () {
        merchantwidget.start({
          merchant_id: 5841352274,
          position: 'BOTTOM_LEFT',
          region: 'MX',
        });
      });
    </script>

  </body>
</html>
