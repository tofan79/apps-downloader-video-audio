import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '$components': path.resolve(__dirname, './resources/js/components'),
            '$lib': path.resolve(__dirname, './resources/js/lib'),
        },
    },
    server: {
        watch: {
            // Prevent Vite from auto-reloading the page when yt-dlp modifies cookies.txt or downloads files
            ignored: ['**/storage/**'],
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        svelte(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
