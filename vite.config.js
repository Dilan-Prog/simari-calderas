import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin/app.css',
                'resources/js/admin/sidebar.js',
                'resources/js/admin/google-ads.js',
                'resources/images/logo/logo_SVG.svg',
                'resources/images/logo/svg_blanco_color.svg',
            ],
            refresh: true,
        }),
    ],
});
