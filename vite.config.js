import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    motion: ['framer-motion'],
                    swiper: ['swiper'],
                    state: ['@tanstack/react-query', 'zustand'],
                    table: ['@tanstack/react-table'],
                    charts: ['recharts'],
                    icons: ['lucide-react'],
                },
            },
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
