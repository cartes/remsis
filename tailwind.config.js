import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./Modules/**/*.blade.php",
        "./Modules/**/*.php",
        "./app/**/*.php",
        "./resources/js/**/*.js",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", "Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    blue: "#006aff", // Square Payroll primary blue
                    dark: "#1A1A1A", // Almost black for text
                },
                gray: {
                    50: "#F2F2F2", // Square's light gray
                    600: "#666666", // Square's muted text
                },
            },
            fontSize: {
                "7xl": ["74px", { lineHeight: "74px", fontWeight: "500" }], // Square H1
                "6xl": ["62px", { lineHeight: "70px", fontWeight: "500" }], // Square H2
                "2xl": ["24px", { lineHeight: "32px", fontWeight: "500" }], // Square body large
            },
            borderRadius: {
                card: "32px", // Square's card radius
                button: "4px", // Square's button radius
            },
            spacing: {
                18: "4.5rem",
                22: "5.5rem",
            },
        },
    },

    plugins: [forms],
};
