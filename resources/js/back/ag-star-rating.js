import { createSiteApp } from '../create-vue-app';
import StarRating from 'vue-star-rating';

const app = createSiteApp({
    'ag-star-rating': require('./components/StarRating/StarRating').default,
    'star-rating': StarRating,
});
window.axios.defaults.baseURL = window.location.origin + '/api/v2/';
if (document.querySelector('#ag-star-rating-component')) app.mount('#ag-star-rating-component');
