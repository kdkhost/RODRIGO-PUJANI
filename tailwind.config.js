import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                gold: '#C49A3C',
                cream: '#F0E9DC',
                ink: '#0B0C10',
            },
            fontFamily: {
                sans: ['Jost', ...defaultTheme.fontFamily.sans],
                display: ['Cormorant Garamond', ...defaultTheme.fontFamily.serif],
                title: ['Cinzel', ...defaultTheme.fontFamily.serif],
            },
        },
    },

    plugins: [forms],
};
