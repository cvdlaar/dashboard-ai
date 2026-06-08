/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    blue: '#0053b8',
                    'blue-dark': '#003d8a',
                    'blue-light': '#1a6fd4',
                    orange: '#e57200',
                    'orange-dark': '#b85a00',
                    'orange-light': '#ff8c1a',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
