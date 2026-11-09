import { createSiteApp } from '../create-vue-app';

const app = createSiteApp({
    'ag-order-products': require('./components/OrderProducts/OrderProducts').default,
});
window.axios.defaults.baseURL = window.location.origin + '/api/v2/';
if (document.querySelector('#ag-order-products-app')) app.mount('#ag-order-products-app');
