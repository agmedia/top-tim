import './bootstrap';
import { createSiteApp } from '../../create-vue-app';
import { createRouter, createWebHistory } from 'vue-router';

const app = createSiteApp({
    'filter-view': require('./components/Filter/Filter').default,
    'filter-side-view': require('./components/Filter/Filterside').default,
    'products-view': require('./components/ProductsList/ProductsList').default,
    'pagination': require('./components/Pagination/LaravelVuePagination').default,
});
const router = createRouter({
    history: createWebHistory(),
    routes: [{ path: '/:pathMatch(.*)*', component: { render: () => null } }],
});
app.use(router);
router.isReady().then(() => {
    if (document.querySelector('#filter-app')) app.mount('#filter-app');
});
