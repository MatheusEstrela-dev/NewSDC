import vue from '@vitejs/plugin-vue';
import path from 'path';
import { defineConfig } from 'vite';

/**
 * Build config dedicated to the email-SSR entry.
 * Produces bootstrap/ssr/email-ssr.js — a Node-importable bundle that
 * renders email Vue components to HTML strings server-side.
 *
 * Run via: vite build --config vite.email-ssr.config.js --ssr
 */
export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: [
            { find: '@', replacement: path.resolve(__dirname, 'resources/js') },
        ],
    },
    build: {
        ssr: 'resources/js/email-ssr.ts',
        outDir: 'bootstrap/ssr',
        emptyOutDir: false,
        rollupOptions: {
            output: {
                entryFileNames: 'email-ssr.js',
                chunkFileNames: 'email-ssr-chunks/[name].js',
                format: 'es',
            },
        },
        target: 'node18',
    },
});
