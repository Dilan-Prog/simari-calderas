<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title')</title>
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap"
        media="print" onload="this.media='all'">
    @vite(['resources/css/admin/app.css'])
    @stack('styles')
    <style>
    /* Modo embebido (iframe de modal): sin sidebar/navbar; el documento hace el scroll. */
    html, body.admin-embed { height: auto; min-height: 100%; overflow: auto; margin: 0; background: #fff; }
    body.admin-embed .admin-embed-main { margin: 0; width: 100%; min-width: 0; }
    body.admin-embed .quotes-page { padding: 16px 20px; }
    .val-error-input,.val-error-select{border-color:#e3342f!important;box-shadow:0 0 0 2px rgba(227,52,47,.18)!important;}
    .val-error-msg{display:block;color:#e3342f;font-size:12px;margin-top:4px;font-weight:500;}
    </style>
</head>

<body class="admin-embed">
    <section class="admin-embed-main">
        @yield('content')
    </section>
    @stack('scripts')
</body>
</html>
