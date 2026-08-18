<!DOCTYPE html>
<html lang="es-MX">
  <head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('frontend.layouts.partials.head-meta')
    @vite($shopVite ?? [])
  </head>
  <body>

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
