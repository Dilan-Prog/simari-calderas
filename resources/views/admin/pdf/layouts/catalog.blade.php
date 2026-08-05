<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>@yield('title', 'Documento')</title>
<style>
@page {
    margin: 130px 0px 70px 0px;
}

* { box-sizing: border-box; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a2e;
    background: #fff;
    margin: 0;
    padding: 0;
}
/* ════════════════════════════════════════════════════
   HEADER FIJO — abarca todo el ancho del papel
   ════════════════════════════════════════════════════ */
#pdf-header {
    position: fixed;
    top: -130px;
    left: 0;
    right: 0;
    background: #1a1a1a;
}
.header-body {
    padding: 20px 44px 14px 44px;
    display: table;
    width: 100%;
}
.header-left  { display: table-cell; vertical-align: top; }
.header-right { display: table-cell; vertical-align: top; text-align: right; width: 46%; }

.company-meta p { font-size: 9.5px; color: #9CA3AF; line-height: 1.7; margin: 0; margin-top: 5px; }

.doc-label {
    font-size: 9px; font-weight: bold; color: #ff6213;
    letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px;
}
.doc-title { font-size: 20px; font-weight: bold; color: #ffffff; line-height: 1.25; }
.doc-meta  { margin-top: 6px; }
.doc-meta p { font-size: 9.5px; color: #9CA3AF; line-height: 1.8; margin: 0; }
.doc-meta span { color: #D1D5DC; font-weight: bold; }

.header-accent { height: 3px; background: #ff6213; margin: 0; }


/* ════════════════════════════════════════════════════
   FOOTER FIJO — abarca todo el ancho del papel
   ════════════════════════════════════════════════════ */
#pdf-footer {
    position: fixed;
    bottom: -70px;
    left: 0;
    right: 0;
    height: 50px;
    background: #1a1a1a;
    border-top: 2px solid #ff6213;
}
.footer-inner  { display: table; width: 100%; height: 50px; padding: 0 44px; }
.footer-brand  { display: table-cell; width: 30%; vertical-align: middle; }
.footer-center {
    display: table-cell; width: 40%; vertical-align: middle;
    text-align: center; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-right  {
    display: table-cell; width: 30%; vertical-align: middle;
    text-align: right; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-orange { color: #ff6213; }


/* ════════════════════════════════════════════════════
   WRAPPER DE CONTENIDO — padding lateral simula márgenes
   ════════════════════════════════════════════════════ */
#content {
    padding: 0 44px;
}


/* ════════════════════════════════════════════════════
   TABLA DE CATÁLOGO / DATOS TABULARES
   ════════════════════════════════════════════════════ */
.items-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 16px;
}
thead { display: table-header-group; }
.items-table thead tr { background: #141516; }
.items-table thead th {
    padding: 9px 8px;
    text-align: left;
    font-size: 9px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: bold;
}
.items-table thead th.th-right { text-align: right; }

.items-table tbody tr { page-break-inside: avoid; }
.items-table tbody tr:nth-child(even) { background: #f5f5f5; }
.items-table tbody td {
    padding: 9px 8px;
    font-size: 11px;
    color: #444;
    border-bottom: 1px solid #eee;
    vertical-align: top;
    word-wrap: break-word;
}
.items-table tbody td.td-right { text-align: right; }
.items-table tbody td.td-name  { font-weight: bold; color: #1a1a2e; }
.items-table tbody td.td-sku   { font-size: 9.5px; color: #999; }

/* ════════════════════════════════════════════════════
   HOJA DE REFERENCIA (plantillas) — usado por las vistas
   template-create / template-update / template
   ════════════════════════════════════════════════════ */
.ref-title { font-size: 16px; font-weight: bold; color: #1a1a2e; margin: 4px 0 10px 0; }
.ref-intro { font-size: 10.5px; color: #555; line-height: 1.6; margin-bottom: 18px; }
.ref-section { font-size: 12px; font-weight: bold; color: #1a1a2e; margin: 18px 0 8px 0; }
.ref-bullets { margin: 0 0 16px 0; padding-left: 16px; }
.ref-bullets li { font-size: 10px; color: #555; line-height: 1.7; margin-bottom: 4px; }

@yield('extra-style')
</style>
</head>
<body>

{{-- ── Header fijo ──────────────────────────────────────────── --}}
<div id="pdf-header">
    <div class="header-body">
        <div class="header-left">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
            <div class="company-meta">
                <p>administracion@equitermindustries.com.mx</p>
                <p>México, Aguascalientes</p>
            </div>
        </div>
        <div class="header-right">
            <div class="doc-label">@yield('document-label', 'Documento')</div>
            <div class="doc-title">@yield('document-title', 'Documento')</div>
            <div class="doc-meta">
                <p>Generado: <span>{{ now()->format('d/m/Y H:i') }}</span></p>
                @yield('document-meta-extra')
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- ── Footer fijo ──────────────────────────────────────────── --}}
{{--
    No se incluye número de página: el contador nativo de dompdf
    ({PAGE_NUM}/{PAGE_COUNT} vía page_text) requiere 'enable_php' => true
    en config/dompdf.php, y en este proyecto está deshabilitado a
    propósito por seguridad (ver config/dompdf.php:243). El resto de
    PDFs del proyecto (quotes, purchase-orders) tampoco lo usan.
--}}
<div id="pdf-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:22px;width:auto;">
        </div>
        <div class="footer-center">
            @yield('footer-center', 'Documento generado el ' . now()->format('d/m/Y H:i'))
        </div>
        <div class="footer-right">
            administracion@equitermindustries.com.mx<br>
            <span class="footer-orange">equitermindustries.com.mx</span>
        </div>
    </div>
</div>

{{-- ── Contenido principal ──────────────────────────────────── --}}
<div id="content">
@yield('content')
</div>{{-- /#content --}}
</body>
</html>
