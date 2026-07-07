/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        brand: {
          primary: {
            from: "#ff6b35",
            to: "#ff8c42",
          },
          secondary: {
            from: "#f72585",
            to: "#7209b7",
          },
          accent: {
            from: "#06ffa5",
            to: "#00d4ff",
          },
        },
      },
      fontFamily: {
        body: ["Poppins", "sans-serif"],
        display: ["Outfit", "sans-serif"],
      },
    },
  },
  plugins: [],
};
