import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'robots.txt', 'imgs/**/*'],
            manifest: {
                name: 'SDC - Sistema de Defesa Civil',
                short_name: 'SDC',
                description: 'Sistema de Defesa Civil do Estado de Minas Gerais',
                theme_color: '#1e40af',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/imgs/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/imgs/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/imgs/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /\/build\/.*\.(js|css)$/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'build-assets',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 7,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/api\..*/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'api-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: /\/api\/.*/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'internal-api-cache',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 5,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: /\.(png|jpg|jpeg|svg|gif|webp|avif)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                    {
                        urlPattern: /\.(woff|woff2|ttf|eot)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'fonts-cache',
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                        },
                    },
                ],
                navigateFallback: null,
                navigateFallbackDenylist: [/^\/api\//, /^\/sanctum\//, /^\/broadcasting\//],
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                clientsClaim: true,
            },
            devOptions: {
                enabled: process.env.NODE_ENV === 'production',
            },
        }),
    ],
    resolve: {
        alias: [
            { find: '@/Composables', replacement: path.resolve(__dirname, 'resources/js/Composables') },
            { find: '@/composables', replacement: path.resolve(__dirname, 'resources/js/Composables') },
            { find: 'ziggy', replacement: path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js') },
            { find: '@', replacement: path.resolve(__dirname, 'resources/js') },
        ],
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        // Lazy chunks (nao preload) - Charts, Maps, DnD
                        if (
                            id.includes('apexcharts') ||
                            id.includes('leaflet') ||
                            id.includes('vuedraggable') || id.includes('sortablejs')
                        ) {
                            return undefined;
                        }
                        // Framework core (Vue + Inertia + Query + Ziggy)
                        if (
                            id.includes('vue') ||
                            id.includes('@inertiajs') ||
                            id.includes('@tanstack') ||
                            id.includes('ziggy')
                        ) {
                            return 'vendor-vue';
                        }
                        // Icons separados (lazy load)
                        if (id.includes('@heroicons')) {
                            return 'vendor-icons';
                        }
                        // Resto agrupado
                        return 'vendor-other';
                    }
                },
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        minify: 'esbuild',
        chunkSizeWarningLimit: 1000,
        sourcemap: false,
        assetsInlineLimit: 4096,
        cssCodeSplit: true,
        reportCompressedSize: false,
        target: 'esnext',
    },
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'ziggy-js', '@tanstack/vue-query'],
        exclude: ['virtual:pwa-register'],
    },
    server: {
        host: '0.0.0.0',
        port: 8081,
        strictPort: true,
        hmr: {
            host: 'localhost',
            clientPort: 8081,
            protocol: 'ws',
        },
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/storage/**', '**/vendor/**'],
        },
        cors: true,
        origin: 'http://localhost:8081',
        allowedHosts: ['bun', 'node', 'localhost', '127.0.0.1'],
    },
    worker: {
        format: 'es',
    },
});
// Force reload
