import { createApp } from 'vue';
import axios from 'axios';

window.axios = axios;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export function createSiteApp(components = {}) {
    const app = createApp({});
    Object.entries(components).forEach(([name, component]) => app.component(name, component));

    app.directive('tooltip', {
        mounted(element, binding) {
            const options = { title: binding.value, placement: binding.arg || 'top', trigger: 'hover' };
            if (window.bootstrap && window.bootstrap.Tooltip) {
                element.siteTooltip = new window.bootstrap.Tooltip(element, options);
            } else if (window.jQuery && window.jQuery.fn.tooltip) {
                window.jQuery(element).tooltip(options);
            }
        },
        unmounted(element) {
            if (element.siteTooltip) element.siteTooltip.dispose();
        },
    });

    return app;
}
