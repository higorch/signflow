import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
        },
    },
    build: {
        chunkSizeWarningLimit: 650,
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                // CSS APP
                'resources/src/css/app.css',
                // VENDORS
                'resources/src/js/vendors/flatpickr.js',
                'resources/src/js/vendors/gallery.js',
                'resources/src/js/vendors/pdf-js.js',
                'resources/src/js/vendors/swiper.js',
                // LAYOUTS
                'resources/src/js/layouts/signer.js',
                'resources/src/js/layouts/panel.js',
                // JS APP
                'resources/src/js/app.js',
            ],
            assets: ['resources/assets/**'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@alpine': '/resources/src/js/alpine',
            '@utils': '/resources/src/js/utils',
            '@layouts': '/resources/src/js/layouts',
            '@vendors': '/resources/src/js/vendors',
            '@assets': '/resources/assets',
        },
    },
})