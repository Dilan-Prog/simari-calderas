<!doctype html>
<html lang="es-MX">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- SEO BASICS -->
    <title>Calderas industriales y mantenimiento 24/7 | SIMARI Calderas</title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-QRCNC1DEYM"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-QRCNC1DEYM');
    </script>
    <meta
      name="description"
      content="Diseño, instalación y mantenimiento de calderas industriales. Auditorías de eficiencia energética, soporte 24/7 y cumplimiento NOM/ASME."
    />
    <link rel="canonical" href="https://tudominio.com/" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="SIMARI Calderas" />
    <meta property="og:title" content="Calderas industriales y mantenimiento 24/7 | SIMARI Calderas" />
    <meta
      property="og:description"
      content="Soluciones integrales en calderas industriales, mantenimiento y eficiencia energética. Soporte técnico 24/7."
    />
    <meta property="og:url" content="https://tudominio.com/" />
    <meta property="og:image" content="https://tudominio.com/og-home.jpg" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Calderas industriales y mantenimiento 24/7 | SIMARI Calderas" />
    <meta
      name="twitter:description"
      content="Diseño, instalación y mantenimiento de calderas industriales. Auditorías de eficiencia energética y soporte 24/7."
    />
    <meta name="twitter:image" content="https://tudominio.com/og-home.jpg" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')

    <!-- JSON-LD: LocalBusiness / Organization -->
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "SIMARI Calderas",
        "url": "https://tudominio.com/",
        "telephone": "+52-449-434-8018",
        "description": "Soluciones integrales en calderas industriales, mantenimiento integral y eficiencia energética.",
        "areaServed": "MX",
        "sameAs": [
          "https://www.facebook.com/tu-pagina",
          "https://www.instagram.com/tu-cuenta",
          "https://www.linkedin.com/company/tu-empresa"
        ]
      }
    </script>

    <!-- JSON-LD: FAQPage (si incluyes FAQ visibles en la página) -->
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
          {
            "@type": "Question",
            "name": "¿Qué tipos de calderas industriales manejan?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Trabajamos con sistemas de generación de vapor y agua caliente, seleccionando la solución según capacidad, combustible y requerimientos del proceso."
            }
          },
          {
            "@type": "Question",
            "name": "¿Ofrecen mantenimiento preventivo y correctivo?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Sí. Ofrecemos planes preventivos para reducir paros no programados y mantenimiento correctivo para atender fallas con respuesta rápida."
            }
          },
          {
            "@type": "Question",
            "name": "¿Tienen soporte 24/7?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Sí. Contamos con soporte técnico 24/7 para emergencias y continuidad operativa."
            }
          },
          {
            "@type": "Question",
            "name": "¿Cumplen normativas NOM y ASME?",
            "acceptedAnswer": {
              "@type": "Answer",
              "text": "Cumplimos con lineamientos aplicables NOM y estándares internacionales como ASME, según el proyecto y requerimientos del cliente."
            }
          }
        ]
      }
    </script>

    <!-- (Opcional) Performance -->
    <meta name="robots" content="index,follow" />
    <!-- Google Tag Manager -->
      <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
      new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
      j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
      'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
      })(window,document,'script','dataLayer','GTM-PLSDTJHX');</script>
    <!-- End Google Tag Manager -->
    
  </head>
  <body>
    <!-- Google Tag Manager (noscript) -->
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PLSDTJHX"
      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    @include('frontend.layouts.header')
    @include('frontend.home.sections.chat-whastapp')
    <main>
        @yield('content')
    </main>
    @include('frontend.layouts.footer')
    <script>
      document.getElementById("year").textContent = new Date().getFullYear();
    </script>
  </body>
  </html>
