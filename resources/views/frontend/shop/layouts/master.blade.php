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

  </body>
</html>
