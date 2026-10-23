import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Admin panel assets (Livewire / Blade). Output: public/build -> served at /build.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Self-hosted from the @fontsource/inter npm package (no CDN at runtime).
                fontsource('Inter', { weights: [400, 500, 600, 700] }),
                // Page titles and figures (same serif as the website).
                fontsource('Playfair Display', { weights: [500, 600, 700] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
