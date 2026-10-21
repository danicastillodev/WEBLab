import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import { temaClaroOscuro } from './tailwind.theme.js';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: '#4f46e5',
                'brand-hover': '#4338ca',
            },
            keyframes: {
                aparecer: {
                    from: { opacity: '0', transform: 'translateY(6px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                desplegar: {
                    from: { opacity: '0', transform: 'translateY(-4px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                flotar: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '50%': { transform: 'translate(-24px, 18px) scale(1.06)' },
                },
                degradado: {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
            },
            animation: {
                // 'backwards': al terminar no queda ninguna transform en <main>, que
                // convertiría a los modales (position: fixed) en relativos a él.
                aparecer: 'aparecer 0.35s ease-out backwards',
                desplegar: 'desplegar 0.18s ease-out both',
                flotar: 'flotar 18s ease-in-out infinite',
                'flotar-lento': 'flotar 26s ease-in-out infinite reverse',
                degradado: 'degradado 12s ease infinite',
            },
            ...temaClaroOscuro.theme.extend,
        },
    },

    plugins: [forms, temaClaroOscuro.plugin],
};
