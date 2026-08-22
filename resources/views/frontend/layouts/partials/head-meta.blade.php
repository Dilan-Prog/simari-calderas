<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>@yield('title')</title>
<meta name="description" content="@yield('description', 'Equiterm Industries con 35 años de experiencia en ingeniería térmica industrial. Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7.')" />
<meta name="robots" content="index,follow" />
<meta name="author" content="Equiterm Industries" />
<meta name="theme-color" content="#1a2940" />
<meta name="google-site-verification" content="B_dbp2j3sqJ2DiovD6PaWCMgvrD_dh-GRtEW3pILS78" />
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
{{--
  Organization, no LocalBusiness: a petición del negocio, por seguridad
  (llamadas de extorsión al número público) no se publica dirección ni
  teléfono en ningún punto del sitio, incluido este bloque leído por
  buscadores. Google exige "address" como campo obligatorio para el tipo
  LocalBusiness (confirmado vía Search Console — sin él, marca el bloque
  como estructurado inválido) — Organization no tiene ese requisito y
  conserva el resto de la información (nombre, logo, sitio, redes) sin
  necesidad de publicar el domicilio.
--}}
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Equiterm Industries",
    "legalName": "Equiterm Industries S.A. de C.V.",
    "url": "https://equitermindustries.com.mx",
    "logo": "{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}",
    "email": "administracion@equitermindustries.com.mx",
    "description": "Diseñamos, instalamos y mantenemos sistemas de calderas, calentadores y tratamiento de agua para los sectores industrial, alimentario, hotelero y metalmecánico. Soporte técnico especializado disponible 24/7",
    "sameAs": [
      "https://www.facebook.com/simaricalderas"
    ]
  }
</script>
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Equiterm Industries",
    "url": "https://equitermindustries.com.mx",
    "inLanguage": "es-MX",
    "potentialAction": {
      "@type": "SearchAction",
      "target": "https://equitermindustries.com.mx/catalogo?q={search_term_string}",
      "query-input": "required name=search_term_string"
    }
  }
</script>
@yield('schema')
{{--
  GA4 (G-RVXX78K31C) y Google Ads (AW-18401897625) ya no se configuran aquí
  directo -- se disparan desde las etiquetas correspondientes dentro de
  Google Tag Manager (contenedor GTM-MXFK4BK9, ver
  frontend.shop.layouts.master). Tenerlos también aquí duplicaba cada
  pageview/conversión: dataLayer recibía dos 'config' por el mismo ID, uno
  de este script y otro del propio GTM.
--}}
