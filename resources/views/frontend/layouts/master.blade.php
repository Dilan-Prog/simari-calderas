<!DOCTYPE html>
<html lang="es-MX">
  <head>
    @include('frontend.layouts.partials.head-meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
