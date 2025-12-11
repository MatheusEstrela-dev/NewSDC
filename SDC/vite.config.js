import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            // SSR desabilitado temporariamente para build de produção
            // ssr: 'resources/js/ssr.ts',
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
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            ziggy: path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
        },
    },
    build: {
        // Code splitting otimizado
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Separar vendor chunks para carregamento paralelo
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue';
                        }
                        if (id.includes('ziggy')) {
                            return 'vendor-utils';
                        }
                        // Outros node_modules em chunk separado
                        return 'vendor-other';
                    }
                },
                // Otimizar nomes de chunks
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        // Otimizações de build - usar esbuild (mais rápido que terser)
        minify: 'esbuild',
        // Chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Source maps apenas em dev
        sourcemap: false,
        // Otimizar assets
        assetsInlineLimit: 4096, // Inline assets < 4kb
        // Otimizar para carregamento paralelo
        cssCodeSplit: true, // Separar CSS por chunk
        reportCompressedSize: false, // Desabilitar para builds mais rápidos
    },
    // Otimizações de dependências
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'ziggy-js'],
        exclude: [],
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: true,
            interval: 1000,
        },
        hmr: {
            host: 'localhost',
            port: 5173,
            protocol: 'ws',
            clientPort: 5173,
        },
    },
});
