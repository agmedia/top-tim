<template>
    <div>
        <div class=" rounded-3  p-4" v-if="route == 'kosarica'" style="border: 1px solid #dae1e7;background-color: #fff !important;">
            <div class="py-2 px-xl-2" >
                <div class="text-center mb-2 pb-2">
                    <h2 class="h6 mb-3 pb-1">{{ trans.ukupno }}</h2>
                    <h3 class="fw-bold text-primary">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                    <h4 class="fs-sm" v-if="$store.state.cart.secondary_price">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                </div>
                <a class="btn btn-primary btn-shadow d-block w-100 mt-4" :href="checkouturl"> {{ trans.nastavi_na_naplatu}} <i class="ci-arrow-right fs-sm"></i></a>
                <!-- <p class="small fw-light text-center mt-2">* Cijena dostave će biti izračunata na koraku 3: Dostava</p>-->
            </div>
        </div>


        <div class=" rounded-3  p-4 ms-lg-auto" v-if="route == 'naplata'" style="border: 1px solid #dae1e7;background-color: #fff !important;">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center mb-2">{{ trans.sazetak}}</h2>

                    <div class="d-flex align-items-center py-2 border-bottom" v-for="item in $store.state.cart.items">
                        <a class="d-block flex-shrink-0" :href="base_path + item.attributes.path"><img :src="item.attributes.slika" :alt="item.name" width="64"></a>
                        <div class="ps-2">
                            <h6 class="widget-product-title"><a :href="base_path + item.attributes.path">{{ item.name }}</a></h6>
                            <div class="widget-product-meta"><span class="text-primary me-2">
                                {{ Object.keys(item.conditions).length ? Number(item.price).toFixed(2) : Number(item.price).toFixed(2) }} EUR</span><span class="text-muted">x {{ item.quantity }}</span>
                                <span class="text-primary fs-md fw-light" style="margin-left: 20px;"
                                      v-if="Object.keys(item.conditions).length && item.associatedModel.action && item.associatedModel.action.coupon == $store.state.cart.coupon">
                                    {{ trans.kupon_kod}}: {{ item.associatedModel.action.title }} ({{ Math.round(item.associatedModel.action.discount).toFixed(0) }}
                                    {{ item.associatedModel.action.type == 'F' ? 'kn' : '%' }})
                                </span>
                            </div>
                            <div class="widget-product-meta"><span class="text-muted me-2" v-if="item.associatedModel.secondary_price_text">{{ Object.keys(item.conditions).length ? item.associatedModel.secondary_special_text : item.associatedModel.secondary_price_text }}</span><span class="text-muted">x {{ item.quantity }}</span></div>
                        </div>
                    </div>
                </div>


                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() === $store.state.cart.subtotal"><span class="me-2">{{ trans.ukupno }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice($store.state.cart.subtotal) }}</span></li>
                    <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() !== $store.state.cart.subtotal"><span class="me-2">{{ trans.ukupno }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice( getCouponPrice() ) }}</span></li>



                    <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice($store.state.cart.subtotal) }}</span>
                    </li>


                        <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() !== $store.state.cart.subtotal"><span class="me-2">Popust: </span><span class="text-end">-{{ $store.state.service.formatMainPrice( getCouponPrice() - $store.state.cart.subtotal)  }}</span></li>


                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatMainPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice(condition.value) }}</span></li>
                    </div>





                </ul>
                <h3 class="fw-bold text-primary text-center my-2">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.secondary_price" class="fs-sm text-center my-2">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                <!--  <p class="small fw-light text-center mt-4 mb-0">
                       <span class="fw-normal">{{ $store.state.service.formatMainPrice($store.state.service.calculateItemsTax($store.state.cart.items)) }}</span> PDV knjige i
                       <span class="fw-normal">{{ $store.state.service.formatMainPrice($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)) }}</span> PDV dostava
                   </p>
                   <p class="small fw-light text-center mt-2 mb-0" v-if="$store.state.cart.secondary_price">
                       <span class="fw-normal">{{ $store.state.service.formatSecondaryPrice($store.state.service.calculateItemsTax($store.state.cart.items)) }}</span> PDV knjige i
                       <span class="fw-normal">{{ $store.state.service.formatSecondaryPrice($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)) }}</span> PDV dostava
                   </p> -->
                <p class="small text-center mt-0 mb-0">{{ trans.pdv_u_cijeni }}</p>
            </div>
        </div>


        <div class=" rounded-3 p-4 ms-lg-auto" v-if="route == 'pregled'" style="border: 1px solid #dae1e7;background-color: #fff !important;">
            <div class="py-2 px-xl-2">
                <div class="widget mb-3">
                    <h2 class="widget-title text-center">{{ trans.sazetak }}</h2>
                </div>
                <ul class="list-unstyled fs-sm pb-2 border-bottom">
                    <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() === $store.state.cart.subtotal"><span class="me-2">{{ trans.ukupno }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice($store.state.cart.subtotal) }}</span></li>
                    <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() !== $store.state.cart.subtotal"><span class="me-2">{{ trans.ukupno }}:</span><span class="text-end">{{ $store.state.service.formatMainPrice( getCouponPrice() ) }}</span></li>


                    <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center">
                        <span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice($store.state.cart.subtotal) }}</span>
                    </li>

                    <li class="d-flex justify-content-between align-items-center" v-if="getCouponPrice() !== $store.state.cart.subtotal"><span class="me-2">Popust: </span><span class="text-end">-{{ $store.state.service.formatMainPrice( getCouponPrice() - $store.state.cart.subtotal)  }}</span></li>

                    <div v-for="condition in $store.state.cart.detail_con">
                        <li class="d-flex justify-content-between align-items-center"><span class="me-2">{{ condition.name }}</span><span class="text-end">{{ $store.state.service.formatMainPrice(condition.value) }}</span></li>
                        <li v-if="$store.state.cart.secondary_price" class="d-flex justify-content-between align-items-center"><span class="me-2"></span><span class="text-end">{{ $store.state.service.formatSecondaryPrice(condition.value) }}</span></li>
                    </div>
                </ul>
                <h3 class="fw-bold text-primary text-center my-2">{{ $store.state.service.formatMainPrice($store.state.cart.total) }}</h3>
                <h4 v-if="$store.state.cart.secondary_price" class="fs-sm text-center my-2">{{ $store.state.service.formatSecondaryPrice($store.state.cart.total) }}</h4>
                <!--  <p class="small fw-light text-center mt-4 mb-0">
                      <span class="fw-normal">{{ $store.state.service.formatMainPrice($store.state.service.calculateItemsTax($store.state.cart.items)) }}</span> PDV knjige i
                      <span class="fw-normal">{{ $store.state.service.formatMainPrice($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)) }}</span> PDV dostava
                  </p>
                  <p class="small fw-light text-center mt-2 mb-0" v-if="$store.state.cart.secondary_price">
                      <span class="fw-normal">{{ $store.state.service.formatSecondaryPrice($store.state.service.calculateItemsTax($store.state.cart.items)) }}</span> PDV knjige i
                      <span class="fw-normal">{{ $store.state.service.formatSecondaryPrice($store.state.service.calculateItemsTax($store.state.cart.total - $store.state.cart.subtotal)) }}</span> PDV dostava
                  </p> -->
                <p class="small text-center mt-0 mb-0">{{ trans.pdv_u_cijeni }}</p>
            </div>
        </div>


        <div class="rounded-3 p-4 mt-3" v-if="route == 'kosarica' || route == 'naplata'" style="border: 1px solid #dae1e7;background-color: #fff !important;">
            <div class="py-2 px-xl-2" v-cloak>
                <div class="form-group">

                    <label class="form-label">{{ trans.imate_kod }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" v-model="coupon" :placeholder="trans.upisite_kod" >
                        <div class="input-group-append">
                            <button type="button" v-on:click="setCoupon" class="btn btn-outline-primary btn-shadow">{{ trans.primjeni }} </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>



<!--        <div class="rounded-3 p-4 mt-3" v-if="has_loyalty && route == 'kosarica' || has_loyalty && route == 'naplata'" style="border: 1px solid #dae1e7;background-color: #fff !important;">
            <div class="py-2 px-xl-2" v-cloak>
                <div class="form-group mb-3">

                    <label class="form-label">{{ trans.use_loyalty }}</label>


                    <div class="form-check" v-if="$store.state.cart.has_loyalty >= 100">
                        <input class="form-check-input" type="radio"    v-model="selected_loyalty" value="100" >
                        <label class="form-check-label" for="ex-radio-2">{{ trans.loyalty_100 }}</label>
                    </div>
                    <div class="form-check" v-if="$store.state.cart.has_loyalty >= 200">
                        <input class="form-check-input" type="radio"   v-model="selected_loyalty" value="200">
                        <label class="form-check-label" for="ex-radio-3">{{ trans.loyalty_200 }}</label>
                    </div>


                </div>
                <button type="button" v-on:click="selected_loyalty = null" class="btn btn-outline-primary btn-shadow">{{ trans.loyalty_cancel }}</button>
                <button type="button" v-on:click="setLoyalty" class="btn btn-outline-primary btn-shadow">{{ trans.loyalty_use }} </button>
            </div>
        </div>-->

    </div>

</template>

<script>
export default {
    props: {
        continueurl: String,
        checkouturl: String,
        buttons: {type: Boolean, default: true},
        route: String
    },
    data() {
        return {
            base_path: window.location.origin + '/',
            mobile: false,
            show_delete_btn: true,
            coupon: '',
            has_loyalty: false,
            selected_loyalty: 0,
            tax: 0,
          trans: window.trans,
        }
    },
    mounted() {
        if (window.innerWidth < 800) {
            this.mobile = true;
        }

        this.checkIfEmpty();
        this.checkLoyalty();
        //this.setCoupon();
    },

    methods: {
        /**
         *
         * @param item
         */
        updateCart(item) {
            this.$store.dispatch('updateCart', item);
        },

        /**
         *
         * @param item
         */
        removeFromCart(item) {
            this.$store.dispatch('removeFromCart', item);
        },

        /**
         *
         * @param qty
         * @returns {number|*}
         * @constructor
         */
        CheckQuantity(qty) {
            if (qty < 1) {
                return 1;
            }

            return qty;
        },

        /**
         *
         */
        checkIfEmpty() {
            let cart = this.$store.state.storage.getCart();

            // Check coupon
            if (cart && cart.coupon != '' && cart.coupon != 'null') {
                this.coupon = cart.coupon;
            }

            // Check loyalty
            if (cart && cart.loyalty != '' && cart.loyalty != 'null') {
                this.selected_loyalty = cart.loyalty;
            }

            if (cart && ! cart.count && window.location.pathname != '/kosarica') {
                window.location.href = '/kosarica';
            }
        },

        /**
         *
         */
        setCoupon() {
            let cart = this.$store.state.storage.getCart();

            if (cart) {
                cart.coupon = this.coupon;
                this.checkCoupon();
            }
        },

        setLoyalty() {
            let cart = this.$store.state.storage.getCart();

            if (cart) {
                cart.loyalty = this.selected_loyalty;
                this.updateLoyalty();
            }
        },

        /**
         *
         */
        checkCoupon() {
            this.$store.dispatch('checkCoupon', this.coupon);
        },


        /**
         *
         */
        updateLoyalty() {
           // console.log('updateLoyalty')
            //console.log(this.selected_loyalty)
            this.$store.dispatch('updateLoyalty', this.selected_loyalty);
        },

        /**
         *
         */
        getCouponPrice() {
            let cart = this.$store.state.storage.getCart();

            if (cart) {
                let total = 0;

                for (const key in cart.items) {
                    total = total + (cart.items[key].price * cart.items[key].quantity);
                }

                return total;
            }
        },

        /**
         *
         */
        checkLoyalty() {
            let cart = this.$store.state.storage.getCart();

            if (cart && cart.has_loyalty > 100) {
                this.has_loyalty = true;
            }
        }
    }
};
</script>


<style>
.table th, .table td {
    padding: 0.75rem 0.45rem !important;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.empty th, .empty td {
    padding: 1rem !important;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.mobile-prices {
    font-size: .66rem;
    color: #999999;
}
</style>
