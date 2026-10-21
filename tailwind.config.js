import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Enums/*.php',
        './app/Models/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Segoe UI"', 'Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                mbsp: {
                    50: '#eef6ff', 100: '#d9eaff', 200: '#bcdaff', 300: '#8ec3ff', 400: '#59a1ff',
                    500: '#337dfc', 600: '#1d5ef1', 700: '#1549d6', 800: '#173dac', 900: '#193787', 950: '#142352',
                },
            },
        },
    },
    plugins: [forms],
};
