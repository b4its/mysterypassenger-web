import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Port host dipetakan dari VITE_PORT (lihat compose.yaml); browser
        // di host harus menyambung ke port host, bukan 5173 internal kontainer.
        hmr: { host: 'localhost', clientPort: 5790 },
        watch: {
            usePolling: true,           // perlu pada bind mount Docker Desktop
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
