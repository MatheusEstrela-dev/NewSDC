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
                navigateFallbackDenylist: [/.*/],
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                clientsClaim: true,
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            ziggy: path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        // Framework core (~80KB) - carregado em toda página
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue';
                        }
                        // Data fetching (~30KB)
                        if (id.includes('@tanstack')) {
                            return 'vendor-query';
                        }
                        // Charts (~500KB) - só carregado em páginas com gráficos
                        if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                            return 'vendor-charts';
                        }
                        // Maps (~200KB) - só carregado em páginas com mapa
                        if (id.includes('leaflet')) {
                            return 'vendor-maps';
                        }
                        // Drag & Drop (~40KB) - só Dashboard
                        if (id.includes('vuedraggable') || id.includes('sortablejs')) {
                            return 'vendor-dnd';
                        }
                        // Routing
                        if (id.includes('ziggy')) {
                            return 'vendor-utils';
                        }
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
        include: ['vue', '@inertiajs/vue3', 'ziggy-js', '@tanstack/vue-query', 'vuedraggable', 'sortablejs'],
        exclude: ['virtual:pwa-register'],
    },
    server: {
        host: '0.0.0.0',
        port: 5175,
        strictPort: false,
        hmr: {
            host: '192.168.0.5',
            port: 5175,
            protocol: 'ws',
        },
        watch: {
            ignored: ['**/storage/**', '**/vendor/**'],
        },
        cors: true,
        origin: 'http://192.168.0.5:5175',
        headers: {
            'Content-Security-Policy': "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; worker-src 'self' blob: http://localhost:* http://192.168.0.5:*; connect-src 'self' ws: wss: http://localhost:* http://127.0.0.1:* http://192.168.0.5:*;",
        },
    },
    worker: {
        format: 'es',
    },
});
// Force reload
