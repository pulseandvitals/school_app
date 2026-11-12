import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fredoka', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                bob: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                sway: {
                    '0%, 100%': { transform: 'rotate(-3deg)' },
                    '50%': { transform: 'rotate(3deg)' },
                },
                'sway-slow': {
                    '0%, 100%': { transform: 'skewX(-4deg)' },
                    '50%': { transform: 'skewX(4deg)' },
                },
                'bubble-rise': {
                    '0%': { transform: 'translateY(0) translateX(0)', opacity: '0' },
                    '10%': { opacity: '0.8' },
                    '50%': { transform: 'translateY(-50vh) translateX(8px)' },
                    '100%': { transform: 'translateY(-110vh) translateX(-6px)', opacity: '0' },
                },
                drift: {
                    '0%': { transform: 'translateX(-20vw)' },
                    '100%': { transform: 'translateX(120vw)' },
                },
                flutter: {
                    '0%, 100%': { transform: 'translate(0, 0) scaleX(1)' },
                    '25%': { transform: 'translate(30px, -20px) scaleX(0.6)' },
                    '50%': { transform: 'translate(60px, 0) scaleX(1)' },
                    '75%': { transform: 'translate(30px, 15px) scaleX(0.6)' },
                },
                twinkle: {
                    '0%, 100%': { opacity: '0', transform: 'scale(0.4) rotate(0deg)' },
                    '50%': { opacity: '1', transform: 'scale(1) rotate(45deg)' },
                },
                'pop-in': {
                    '0%': { opacity: '0', transform: 'scale(0.6)' },
                    '70%': { transform: 'scale(1.08)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'fall': {
                    '0%': { transform: 'translateY(-10vh) rotate(0deg)', opacity: '1' },
                    '100%': { transform: 'translateY(100vh) rotate(540deg)', opacity: '0' },
                },
                'light-ray': {
                    '0%, 100%': { opacity: '0.15' },
                    '50%': { opacity: '0.35' },
                },
            },
            animation: {
                bob: 'bob 3s ease-in-out infinite',
                sway: 'sway 4s ease-in-out infinite',
                'sway-slow': 'sway-slow 6s ease-in-out infinite',
                'bubble-rise': 'bubble-rise 9s ease-in infinite',
                drift: 'drift 60s linear infinite',
                flutter: 'flutter 6s ease-in-out infinite',
                twinkle: 'twinkle 2.4s ease-in-out infinite',
                'pop-in': 'pop-in 0.5s ease-out both',
                fall: 'fall 3s linear forwards',
                'light-ray': 'light-ray 7s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
