import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class', // Habilita dark mode usando classe

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            screens: {
                'xs': '475px',
                // sm: 640px (padrão Tailwind)
                // md: 768px (padrão Tailwind)
                // lg: 1024px (padrão Tailwind)
                // xl: 1280px (padrão Tailwind)
                '2xl': '1536px',
                '3xl': '1920px',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            minHeight: {
                'screen-safe': 'calc(100vh - 4rem)',
            },
            maxWidth: {
                '8xl': '88rem',
                '9xl': '96rem',
            },
        },
    },

    plugins: [forms],
};
