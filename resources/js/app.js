import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createGtag } from 'vue-gtag';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const googleTagId = import.meta.env.VITE_GOOGLE_TAG_ID;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        if (googleTagId) {
            app.use(createGtag({ tagId: googleTagId }));
        }

        const instance = app.mount(el);

        if (googleTagId && typeof window.gtag === 'function') {
            router.on('navigate', (event) => {
                window.gtag('event', 'page_view', {
                    page_location: event.detail.page.url,
                    page_path: new URL(event.detail.page.url).pathname,
                });
            });
        }

        return instance;
    },
    progress: {
        color: '#4B5563',
    },
});
