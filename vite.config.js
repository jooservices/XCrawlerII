import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('/node_modules/vue/') || id.includes('/node_modules/@vue/')) {
                        return 'vendor-vue';
                    }

                    if (id.includes('/node_modules/@inertiajs/')) {
                        return 'vendor-inertia';
                    }

                    if (id.includes('/node_modules/swiper/')) {
                        return 'vendor-swiper';
                    }

                    if (id.includes('/node_modules/@vuepic/vue-datepicker/')) {
                        return 'vendor-datepicker';
                    }

                    if (id.includes('/node_modules/@tanstack/')) {
                        return 'vendor-query';
                    }

                    if (id.includes('/node_modules/apexcharts/') || id.includes('/node_modules/vue3-apexcharts/')) {
                        return 'vendor-apexcharts';
                    }

                    return 'vendor-misc';
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'Modules/Core/resources/css/dashboard-shared.css',
                'Modules/Core/resources/js/app.js',
                'Modules/JAV/resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            '@jav': '/Modules/JAV/resources/js',
            '@core': '/Modules/Core/resources/js',
        },
    },
});
