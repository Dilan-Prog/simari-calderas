import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
<<<<<<< HEAD
                'resources/images/logo/logo_SVG.svg',
                'resources/css/admin/app.css',
                'resources/js/admin/sidebar.js',
                'resources/js/admin/google-ads.js',
=======
                'resources/css/admin/app.css',
                'resources/js/admin/sidebar.js',
                'resources/js/admin/google-ads.js',
                'resources/css/admin-quotes.css',
                'resources/js/admin-quotes.js',
<<<<<<< HEAD
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
=======
                'resources/css/admin/roles.css',
                'resources/js/admin/roles.js',
                'resources/css/admin/technical-services.css',
                'resources/js/admin/technical-services.js',
>>>>>>> 04638f4537a2a9460f7400914815b28dcf8c8c55
            ],
            refresh: true,
        }),
    ],
});
