import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import BaseSearch from './Components/BaseSearch.vue';

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) });
    
    // Use the Inertia plugin
    app.use(plugin);
    
    // Use Ziggy for route generation
    app.use(ZiggyVue, Ziggy);
    
    // Make route function available globally
    app.config.globalProperties.$route = window.route;
    
    // Register global components
    app.component('BaseSearch', BaseSearch);
    
    app.mount(el);
  },
});
