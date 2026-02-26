<!doctype html>
<html lang="es-MX">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- SEO BASICS -->
    <title>Calderas industriales y mantenimiento 24/7 | SIMARI Calderas</title>
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
  </head>
  <body>
    @include('frontend.layouts.header')
    <main>
        @yield('content')













    </main>

    @include('frontend.layouts.footer')
    <script>
      document.getElementById("year").textContent = new Date().getFullYear();
    </script>
  </body>
  </html>
