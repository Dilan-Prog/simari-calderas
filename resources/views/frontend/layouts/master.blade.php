<!DOCTYPE html>
<html lang="es-MX">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>@yield('title')</title>
    <meta name="description" content="@yield('description', 'Equiterm Industries con 35 años de experiencia en ingeniería térmica industrial. Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7.')" />
    <meta name="robots" content="index,follow" />
    <meta name="author" content="Equiterm Industries" />
    <meta name="theme-color" content="#1a2940" />
    <link rel="canonical" href="@yield('canonical', 'https://equitermindustries.com.mx/')" />
    <link rel="shortcut icon"        type="image/x-icon"  href="{{ asset('images/logo/icon-web/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="16x16"  href="{{ asset('images/logo/icon-web/favicon-16x16.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32"  href="{{ asset('images/logo/icon-web/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="96x96"  href="{{ asset('images/logo/icon-web/favicon-96x96.png') }}" />
    <link rel="icon" type="image/png" sizes="256x256" href="{{ asset('images/logo/icon-web/favicon-256x256.png') }}" />
    <link rel="apple-touch-icon"      sizes="57x57"  href="{{ asset('images/logo/icon-web/apple-icon-57x57.png') }}" />
    <link rel="apple-touch-icon"      sizes="60x60"  href="{{ asset('images/logo/icon-web/apple-icon-60x60.png') }}" />
    <link rel="apple-touch-icon"      sizes="72x72"  href="{{ asset('images/logo/icon-web/apple-icon-72x72.png') }}" />
    <link rel="apple-touch-icon"      sizes="76x76"  href="{{ asset('images/logo/icon-web/apple-icon-76x76.png') }}" />
    <link rel="apple-touch-icon"      sizes="114x114" href="{{ asset('images/logo/icon-web/apple-icon-114x114.png') }}" />
    <link rel="apple-touch-icon"      sizes="120x120" href="{{ asset('images/logo/icon-web/apple-icon-120x120.png') }}" />
    <link rel="apple-touch-icon"      sizes="144x144" href="{{ asset('images/logo/icon-web/apple-icon-144x144.png') }}" />
    <link rel="apple-touch-icon"      sizes="152x152" href="{{ asset('images/logo/icon-web/apple-icon-152x152.png') }}" />
    <link rel="apple-touch-icon"      sizes="180x180" href="{{ asset('images/logo/icon-web/apple-icon-180x180.png') }}" />
    <meta name="msapplication-TileImage"   content="{{ asset('images/logo/icon-web/ms-icon-144x144.png') }}" />
    <meta name="msapplication-square70x70logo"  content="{{ asset('images/logo/icon-web/ms-icon-70x70.png') }}" />
    <meta name="msapplication-square150x150logo" content="{{ asset('images/logo/icon-web/ms-icon-150x150.png') }}" />
    <meta name="msapplication-square310x310logo" content="{{ asset('images/logo/icon-web/ms-icon-310x310.png') }}" />
    <meta property="og:type"        content="website" />
    <meta property="og:locale"      content="es_MX" />
    <meta property="og:site_name"   content="Equiterm Industries" />
    <meta property="og:title"       content="@yield('og_title', 'Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7 | Equiterm Industries')" />
    <meta property="og:description" content="@yield('og_description', 'Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7')" />
    <meta property="og:url"         content="@yield('og_url', 'https://equitermindustries.com.mx/')" />
    <meta property="og:image"       content="@yield('og_image', 'https://equitermindustries.com.mx/images/og-home.jpg')" />
    <meta property="og:image:width"  content="1200" />
    <meta property="og:image:height" content="630" />
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="preconnect" href="https://www.google-analytics.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" media="print" onload="this.media='all'">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "Equiterm Industries",
        "url": "equitermindustries.com.mx",
        "telephone": "+52-449-434-8018",
        "description": "Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7",
        "areaServed": "MX",
        "sameAs": [
          "https://www.facebook.com/industriasimari"
        ]
      }
    </script>
    @yield('schema')
    {{-- @php SEE TO NEED TO OPTIMIZE THIS, MAYBE COMBINE WITH OTHER CRITICAL CSS OR INLINE ONLY THE MOST IMPORTANT PARTS
         OR USE A TOOL TO EXTRACT CRITICAL CSS AUTOMATICALLY
        $critical = file_get_contents(resource_path('css/critical.css'));
    @endphp

    <style>
        {!! $critical !!}
    </style> --}}
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-RVXX78K31C"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-RVXX78K31C');
  // Analytics
</script>
  </head>
  <body>

    @include('frontend.layouts.header')
    <main>
        @yield('content')
    </main>
    @include('frontend.layouts.footer')

    <script>
      document.addEventListener('DOMContentLoaded', () => {
          const params = new URLSearchParams(window.location.search);
          const gclid  = params.get('gclid');

          if (gclid) {
              localStorage.setItem('gclid', gclid);
          }

          const storedGclid = localStorage.getItem('gclid');
          const alreadySent = localStorage.getItem('gclid_sent') === storedGclid;

          if (!storedGclid || alreadySent) return;

          fetch('/api/v1/google-ads', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  gclid:            storedGclid,
                  conversion_name:  'first_visit',
                  conversion_value: 0,
                  currency_code:    'MXN',
              }),
          })
          .then(res => {
              if (res.ok) {
                  localStorage.setItem('gclid_sent', storedGclid);
              }
          })
          .catch(() => {});
      });
    </script>
  </body>
  </html>
