import { createSiteApp } from '../create-vue-app';

const app = createSiteApp({
    'ag-auto-suggestion': require('./components/Autosuggestion/Autosuggestion').default,
});
window.axios.defaults.baseURL = window.location.origin + '/api/v2/';
if (document.querySelector('#ag-auto-suggestion-app')) app.mount('#ag-auto-suggestion-app');
