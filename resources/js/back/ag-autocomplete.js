import { createSiteApp } from '../create-vue-app';

const app = createSiteApp({
    'ag-autocomplete': require('./components/Autocomplete/Autocomplete').default,
});
window.axios.defaults.baseURL = window.location.origin + '/api/v2/';
if (document.querySelector('#ag-autocomplete-app')) app.mount('#ag-autocomplete-app');
