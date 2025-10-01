<template>
    <div>
        <div class="d-block pt-0 pb-2 mt-0 text-start" v-if="$store.state.cart.total > 0">
            <div
                class="alert alert-info d-flex"
                v-if="$store.state.cart.total < freeship && $store.state.cart.count"
                role="alert"
            >
                <div class="alert-icon"><i class="ci-announcement"></i></div>
                <div>
                    <small>
                        {{ trans.jos }} {{ $store.state.service.formatMainPrice(freeship - $store.state.cart.total) }}
                        <span v-if="$store.state.cart.secondary_price">
              ({{ $store.state.service.formatSecondaryPrice(freeship - $store.state.cart.total) }})
            </span>
                        {{ trans.do_besplatne }}
                    </small>
                </div>
            </div>

            <div
                class="alert alert-success d-flex"
                v-if="$store.state.cart.total > freeship && $store.state.cart.count"
                role="alert"
            >
                <div class="alert-icon"><i class="ci-check-circle"></i></div>
                <div><small>{{ trans.ostvarili }}</small></div>
            </div>

            <h2 class="h6 text-primary mb-0">{{ trans.artikli }}</h2>
        </div>

        <div class="d-flex pt-3 pb-2 mt-1" v-if="$store.state.cart.total < 1">
            <p class="text-dark mb-0">{{ trans.empty_cart_text }}</p>
        </div>

        <!-- Item -->
        <div
            class="d-sm-flex justify-content-between align-items-center my-2 pb-3 border-bottom"
            v-for="item in $store.state.cart.items"
            :key="item.id"
        >
            <div class="d-flex align-items-center text-start">
                <a class="d-inline-block flex-shrink-0 me-3" :href="base_path + item.attributes.path">
                    <img :src="item.attributes.slika" width="120" :alt="item.name" :title="item.name" />
                </a>
                <div class="py-2">
                    <h3 class="product-title fs-base mb-2">
                        <a :href="base_path + item.attributes.path">{{ item.name }}</a>
                    </h3>

                    <div class="fs-lg text-primary pt-2">
                        {{ item.associatedModel.main_price_text }}
                        <span
                            class="text-primary fs-md fw-light"
                            style="margin-left: 20px;"
                            v-if="Object.keys(item.conditions).length && item.associatedModel.action && item.associatedModel.action.coupon == $store.state.cart.coupon"
                        >
              {{ item.associatedModel.action.title }} ({{ Math.round(item.associatedModel.action.discount).toFixed(0) }}
              {{ item.associatedModel.action.type == 'F' ? 'kn' : '%' }})
            </span>
                        <span
                            class="text-primary fs-md fw-light"
                            style="margin-left: 20px;"
                            v-if="item.attributes.options.option && item.attributes.options.option.price != '0.0000'"
                        >
              Opcija: {{ Math.round(Number(item.attributes.options.option.price)).toFixed(2) }} EUR
            </span>
                    </div>

                    <div class="fs-sm text-dark pt-1" v-if="item.associatedModel.secondary_price">
                        {{ item.associatedModel.secondary_price_text }}
                    </div>

                    <div class="fs-sm text-dark pt-1" v-if="Object.keys(item.conditions).length">
                        <p v-for="condition in item.conditions" :key="condition.name">
                            Akcija: - {{ Math.round(Number(condition.parsedRawValue)).toFixed(2) }} EUR
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-2 pt-sm-0 ps-sm-3 mx-auto justify-content-between mx-sm-0 text-start" style="max-width: 9rem;">
                <label class="form-label">{{ trans.kolicina }}: {{ item.quantity }}</label>
                <input
                    class="form-control d-none d-sm-block"
                    type="number"
                    v-model.number="item.quantity"
                    min="1"
                    :max="lineMax(item)"
                    @input="onQuantityInput(item)"
                    @change="updateCart(item)"
                />
                <button class="btn btn-link px-0 text-danger" type="button" @click.prevent="removeFromCart(item)">
                    <i class="ci-close-circle me-2"></i><span class="fs-sm">{{ trans.ukloni }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        continueurl: String,
        checkouturl: String,
        freeship: String,
        buttons: { type: String, default: 'true' },
    },
    data() {
        return {
            base_path: window.location.origin + '/',
            mobile: false,
            show_delete_btn: true,
            coupon: '',
            show_buttons: true,
            trans: window.trans,
        };
    },
    mounted() {
        if (window.innerWidth < 800) this.mobile = true;
        this.show_buttons = this.buttons !== 'false';

        this.checkIfEmpty();
        this.setCoupon();
    },
    methods: {
        // Vrati stock opcije ako postoji, inače stock proizvoda
        lineMax(item) {
            const optStock = item?.attributes?.options?.option?.stock;
            const prodStock = item?.associatedModel?.quantity;
            return Number.isFinite(Number(optStock)) && Number(optStock) > 0
                ? Number(optStock)
                : Number(prodStock) || 9999;
        },

        // Ispravi input odmah da ne ide ispod 1 ili iznad max
        onQuantityInput(item) {
            const max = this.lineMax(item);
            if (item.quantity > max) item.quantity = max;
            if (item.quantity < 1) item.quantity = 1;
        },

        updateCart(item) {
            this.onQuantityInput(item);
            this.$store.dispatch('updateCart', item);
        },

        removeFromCart(item) {
            this.$store.dispatch('removeFromCart', item);
        },

        checkIfEmpty() {
            let cart = this.$store.state.storage.getCart();
            if (cart && !cart.count && window.location.pathname != '/kosarica') {
                window.location.href = '/kosarica';
            }
        },

        setCoupon() {
            let cart = this.$store.state.storage.getCart();
            if (cart && cart.count) this.coupon = cart.coupon;
        },

        checkCoupon() {
            this.$store.dispatch('checkCoupon', this.coupon);
        },
    },
};
</script>
