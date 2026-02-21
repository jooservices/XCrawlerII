import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query';
import { createPinia } from 'pinia';
import { ZiggyVue } from 'ziggy-js';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
// Core layout mapping for pages
import DashboardLayout from '@core/Layouts/DashboardLayout.vue';
import '@core/../css/dashboard-shared.css';
import 'primeicons/primeicons.css';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 60 * 1000,
            gcTime: 5 * 60 * 1000,
            retry: 1,
            refetchOnWindowFocus: true,
        },
    },
});

createInertiaApp({
    resolve: async (name) => {
        // name example: 'JAV/Pages/Dashboard/Index' or 'Core/Pages/Admin/Analytics'
        let match = name.match(/^([A-Z]+)\/Pages\/(.*)$/);
        if (!match) {
            console.error(`Page name [${name}] does not match the expected module format (e.g. 'JAV/Pages/...'). Attempting to load as fallback.`);
            // fallback behavior for legacy paths still missing prefixes during transition
            const legacyPages = import.meta.glob('../../../JAV/resources/js/Pages/**/*.vue');
            const legacyMatch = legacyPages[`../../../JAV/resources/js/Pages/${name}.vue`];
            if (!legacyMatch) throw new Error(`Page not found: ${name}`);
            const page = await legacyMatch();
            if (!Object.prototype.hasOwnProperty.call(page.default, 'layout')) {
                page.default.layout = DashboardLayout;
            }
            return page;
        }

        const moduleName = match[1]; // 'JAV' or 'Core'
        const pagePath = match[2];   // 'Dashboard/Index'

        // Glob import all potential pages from modules (eager: false for chunking, or eager: true if desired)
        const pages = import.meta.glob('../../../*/resources/js/Pages/**/*.vue');

        // Find the exact file
        const targetFile = `../../../${moduleName}/resources/js/Pages/${pagePath}.vue`;

        if (!pages[targetFile]) {
            throw new Error(`Page not found: ${targetFile} for ${name}`);
        }

        const page = await pages[targetFile]();

        // Apply a shared shell by default, but allow pages to explicitly opt out.
        if (!Object.prototype.hasOwnProperty.call(page.default, 'layout')) {
            page.default.layout = DashboardLayout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(VueQueryPlugin, { queryClient })
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: 'body.app-dark',
                    },
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
