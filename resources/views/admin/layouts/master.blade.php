
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title')</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap"
        media="print" onload="this.media='all'">
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    @vite(['resources/css/admin/app.css'])
    @vite(['resources/js/admin/sidebar.js'])
    @stack('styles')
    <style>
    .val-error-input,.val-error-select{border-color:#e3342f!important;box-shadow:0 0 0 2px rgba(227,52,47,.18)!important;}
    .val-error-msg{display:block;color:#e3342f;font-size:12px;margin-top:4px;font-weight:500;}
    </style>
</head>

<body>
    <div class="admin-nav-overlay" id="adminNavOverlay">
        <div class="admin-spinner"></div>
    </div>

    @include('admin.layouts.sidebar')
    @include('admin.layouts.toast')
    <section class="admin-main">
        @include('admin.layouts.navbar')
        @yield('content')
    </section>
    @include('admin.layouts.scripts')
    @stack('scripts')
</body>
</html>
