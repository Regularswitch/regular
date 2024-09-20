/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "pages/**/*.{js,ts,jsx,tsx}",
        "components/**/*.{js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {},
        fontFamily: {
            hk: ["HK Grotesk", 'sans-serif'],
            home: ["HK-home", 'sans-serif'],
            hg: ["Hanken Grotesk", 'sans-serif'],
        }
    },
    plugins: [
        require('@tailwindcss/aspect-ratio'),
    ],
}