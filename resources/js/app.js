import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faRightToBracket, faUserShield } from '@fortawesome/free-solid-svg-icons';
import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
library.add(faRightToBracket, faUserShield);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const rootPages = import.meta.glob('./Pages/**/*.vue');
        const modulePages = import.meta.glob('../../Modules/*/resources/js/pages/**/*.vue');

        const moduleCandidate = `../../Modules/${name.split('/')[0]}/resources/js/pages/${name
            .split('/')
            .slice(1)
            .join('/')}.vue`;
        const coreCandidate = `../../Modules/Core/resources/js/pages/${name}.vue`;
        const rootCandidate = `./Pages/${name}.vue`;

        if (modulePages[moduleCandidate]) {
            return modulePages[moduleCandidate]();
        }

        if (modulePages[coreCandidate]) {
            return modulePages[coreCandidate]();
        }

        if (rootPages[rootCandidate]) {
            return rootPages[rootCandidate]();
        }

        throw new Error(`Page not found: ${name}`);
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.app-dark',
                    },
                },
            })
            .component('FontAwesomeIcon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
