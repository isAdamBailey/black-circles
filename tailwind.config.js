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

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // The system's one accent (DESIGN.md). DEFAULT matches the documented
                // hex for labels/low-emphasis use; `bright` is a lifted-lightness variant
                // for focus rings/interactive states, since the DEFAULT fails 3:1 against
                // the near-black `void` background (~2:1 measured).
                oxblood: {
                    DEFAULT: '#7c1f25',
                    bright: '#c23a42',
                },
            },
        },
    },

    plugins: [forms],
};
