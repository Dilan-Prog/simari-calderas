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
