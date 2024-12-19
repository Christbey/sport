import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import {NodeGlobalsPolyfillPlugin} from '@esbuild-plugins/node-globals-polyfill';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],

            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // Polyfill `process`
            process: 'process/browser',
        },
    },
    optimizeDeps: {
        esbuildOptions: {
            // Define `process` as a global variable
            define: {
                global: 'globalThis',
                process: 'process',
            },
            // Enable the plugin
            plugins: [
                NodeGlobalsPolyfillPlugin({
                    buffer: true,
                }),
            ],
        },
    },
});
