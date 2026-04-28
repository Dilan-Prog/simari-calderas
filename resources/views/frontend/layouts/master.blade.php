<!DOCTYPE html>
<html lang="es-MX">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>@yield('title')</title>
    <meta name="description" content="@yield('description', 'Industria Simari con 35 años de experiencia en ingeniería térmica industrial. Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7.')" />
    <meta name="robots" content="index,follow" />
    <meta name="author" content="Industria Simari" />
    <meta name="theme-color" content="#1a2940" />
    <link rel="canonical" href="@yield('canonical', 'https://industriasimari.com.mx/')" />
    <link rel="icon" type="image/x-icon"  href="{{ asset('images/logo/icon-web/favicon-industria-simari-48x48.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16"  href="{{ asset('images/logo/icon-web/favicon-industria-simari-16x16.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32"  href="{{ asset('images/logo/icon-web/favicon-industria-simari-32x32.png') }}" />
    <link rel="apple-touch-icon"    sizes="180x180"  href="{{ asset('images/logo/icon-web/favicon-industria-simari-180x180.png') }}" />
    <meta property="og:type"        content="website" />
    <meta property="og:locale"      content="es_MX" />
    <meta property="og:site_name"   content="Industria Simari" />
    <meta property="og:title"       content="@yield('og_title', 'Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7 | Industria Simari')" />
    <meta property="og:description" content="@yield('og_description', 'Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7')" />
    <meta property="og:url"         content="@yield('og_url', 'https://industriasimari.com.mx/')" />
    <meta property="og:image"       content="@yield('og_image', 'https://industriasimari.com.mx/images/og-home.jpg')" />
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
        "name": "Industria Simari",
        "url": "https://industriasimari.com.mx/",
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
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18002291598">
        </script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'AW-18002291598');
        </script>
    <!-- End Google Tag Manager -->

  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PLSDTJHX"
      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

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
