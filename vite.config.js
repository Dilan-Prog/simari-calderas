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
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
            ],
            refresh: true,
        }),
    ],
});
