import { createSiteApp } from '../create-vue-app';

const app = createSiteApp({
    'ag-input-field': require('./components/InputField/InputField').default,
});
window.axios.defaults.baseURL = window.location.origin + '/api/v2/';
if (document.querySelector('#ag-table-with-input-fields')) app.mount('#ag-table-with-input-fields');
