import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                // Js File
                // NOTAS FUTURAS CUANDO EL SITIO WEB SEA DINAMICO, SE DEBE AGREGAR LOS JS DE CADA PAGINA,
                'resources/js/app.js',
                'resources/css/home.css',
                'resources/css/products.css',
                'resources/css/masstercal-rinnai.css',
                'resources/css/service.css',
                'resources/css/termsconditions.css',
                'resources/css/privacy-notice.css',
            ],
            refresh: true,
        }),
    ],
});
