import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // The container binds to [::], which Laravel would otherwise write into
        // public/hot. Pin the browser-facing origin to IPv4 so assets always
        // reach this project's Vite and not another dev server on ::1:5173.
        origin: 'http://127.0.0.1:5173',
        hmr: { host: '127.0.0.1' },
        // Page is served from localhost:8000, assets from 127.0.0.1:5173.
        cors: { origin: /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/ },
    },
});
