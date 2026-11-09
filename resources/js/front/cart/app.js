import './bootstrap';
import { createSiteApp } from '../../create-vue-app';
import { createStore } from 'vuex';
import { createRouter, createWebHistory } from 'vue-router';
import VueSweetalert2 from 'vue-sweetalert2';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import store from './store';

const app = createSiteApp({
    'cart-nav-icon': require('./components/CartNavIcon/CartNavIcon').default,
    'cart-view': require('./components/CartView/CartView').default,
    'cart-view-aside': require('./components/CartViewAside/CartViewAside').default,
    'cart-footer-icon': require('./components/CartFooterIcon/CartFooterIcon').default,
    'add-to-cart-btn': require('./components/AddToCartBtn/AddToCartBtn').default,
    'add-to-cart-btn-simple': require('./components/AddToCartBtnSimple/AddToCartBtnSimple').default,
    'filter-view': require('../filter/components/Filter/Filter').default,
    'filter-side-view': require('../filter/components/Filter/Filterside').default,
    'products-view': require('../filter/components/ProductsList/ProductsList').default,
    'pagination': require('../filter/components/Pagination/LaravelVuePagination').default,
});
const router = createRouter({
    history: createWebHistory(),
    routes: [{ path: '/:pathMatch(.*)*', component: { render: () => null } }],
});
app.use(router);
app.use(createStore(store));
app.use(VueSweetalert2);
router.isReady().then(() => {
    if (document.querySelector('#agapp')) app.mount('#agapp');
});

const toast = (icon, timer) => Swal.mixin({
    toast: true, icon, position: 'top-end', showConfirmButton: false, timer,
});
window.ToastSuccess = toast('success', 2500);
window.ToastWarning = toast('warning', 2500);
window.ToastWarningLong = toast('warning', 5000);
