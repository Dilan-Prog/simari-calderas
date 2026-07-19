<!DOCTYPE html>
<html lang="es-MX">
  <head>
    @include('frontend.layouts.partials.head-meta')
    @vite($shopVite ?? [])
  </head>
  <body>

    @include('frontend.shop.layouts.header')
    <main>
        @yield('content')
    </main>
    @include('frontend.shop.layouts.footer')
    @include('frontend.shop.partials.cart-drawer')
    @include('frontend.shop.partials.quote-modal')

  </body>
</html>
