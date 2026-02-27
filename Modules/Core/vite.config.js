import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { dirname, resolve } from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export default defineConfig({
    build: {
        outDir: "../../public/build-core",
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: "../../public",
            buildDirectory: "build-core",
            input: [
                resolve(__dirname, "resources/assets/sass/app.scss"),
                resolve(__dirname, "resources/assets/js/app.js"),
            ],
            refresh: true,
        }),
    ],
});
