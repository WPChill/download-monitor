/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{html,js,php}","./templates/*.{html,js,php}","./assets/views/**/*.{html,js,php}","./includes/**/*.{html,js,php}","./src/class-dlm-dom-manipulation.php"],
  theme: {
    extend: {},
  },
  plugins: [],
  prefix: 'dlm-'
}

