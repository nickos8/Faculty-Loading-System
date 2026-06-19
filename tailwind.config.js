// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'non-photo-blue': '#A4DDED',
                'bice-blue': '#007BA7',
                'picton-blue': '#45B8DC',
                'pale-azure': '#C1E8FF',
                'indigo-dye': '#00416A',
            },
        },
    },

    plugins: [forms],
}
