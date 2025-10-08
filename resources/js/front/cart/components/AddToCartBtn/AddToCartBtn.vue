<template>
    <div class="cart pb-2 mb-2">
        <div class="mb-1" v-if="Number(context_product.main_price) > Number(context_product.main_special)">
            <span class="h3 fw-bold font-title text-blue me-1">{{ shown_price }} €</span>
            <span class="text-muted fs-lg me-3">
        <strike>{{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(price) }}</strike>
      </span>
        </div>

        <div class="mb-1" v-else>
      <span class="h3 fw-bold font-title text-blue me-1">
        {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(shown_price) }}
      </span>
        </div>

        <div class="mb-1 mt-1 text-start" v-if="Number(context_product.main_price) > Number(context_product.main_special)">
      <span class="fs-sm text-muted me-1">
        {{ trans.lowest_price }}:
        {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(shown_price) }}
      </span>
        </div>

        <div class="mb-1 mt-1 text-start">
            <span class="fs-xs text-muted me-1">{{ trans.pdv }}</span>
        </div>

        <div class="mb-3">
      <span class="fs-xs text-blue me-1">
        {{ trans.nopdv }}:
        {{ new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(shown_price / 1.25) }}
      </span>
        </div>

        <div class="mw-500" v-if="Object.keys(color_options).length">
            <div class="fs-sm mb-4">
        <span class="text-heading fw-medium me-1">
          <span class="text-danger">*</span> {{ trans.boja }}:
        </span>
                <span class="text-muted">{{ color_name }} <span class="text-warning">{{ extra_price }}</span></span>
            </div>
            <div class="position-relative me-n4 mb-3" id="select">
                <div
                    v-for="(option, index) in color_options"
                    :key="option.id"
                    class="form-check form-option form-check-inline mb-2"
                    :data-target="option.option_id"
                >
                    <input
                        class="form-check-input"
                        type="radio"
                        :value="option.id"
                        :id="`color-${option.id}`"
                        :disabled="!option.active"
                        v-model="color"
                    />
                    <label
                        :class="{ opacity: !option.active }"
                        class="form-option-label rounded-circle opacity-80"
                        :for="`color-${option.id}`"
                    >
                        <span class="form-option-color rounded-circle" :style="option.style"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mw-500" v-if="Object.keys(size_options).length && size_disabled">
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center pb-1 opac">
                    <label class="form-label" for="product-size">
                        <span class="text-danger">*</span>{{ trans.velicina }}:
                        <span class="text-muted">{{ size_name }}</span>
                    </label>
                </div>
                <select class="form-select" required id="product-size" v-model="size">
                    <option value="0">{{ trans.velicina }}</option>
                    <option v-for="option in size_options" :key="option.id" :disabled="!option.active" :value="option.id">
                        {{ option.name }}
                    </option>
                </select>
            </div>
        </div>

        <div class="d-flex align-items-center pt-2 mw-500">
            <input
                class="form-control me-3 mb-1"
                type="number"
                inputmode="numeric"
                pattern="[0-9]*"
                v-model.number="quantity"
                min="1"
                :max="maxQty"
                style="width: 5rem;"
            />
            <button class="btn btn-primary btn-shadow w-100 mb-1" @click="add" :disabled="disabled">
                <i class="ci-cart"></i> {{ trans.add_to_cart }}
            </button>
        </div>

        <p v-if="has_in_cart" class="fs-sm fw-light text-danger mb-0" style="width: 100%;">
            {{ trans.imate }} {{ has_in_cart }} {{ trans.artikala_u_kosarici }}.
        </p>
    </div>
</template>

<script>
export default {
    props: {
        product: String,
        available: String,
        options: String,
        action: String,
    },
    data() {
        return {
            id: '',
            quantity: 1,
            has_in_cart: 0, // koliko je već u košarici za ovu varijantu
            disabled: false,
            is_available: 0, // stock za trenutno odabranu opciju
            size_options: {},
            color_options: {},
            selected_size: {},
            selected_color: {},
            trans: window.trans,
            size: 0,
            color: '',
            parent: '',
            color_name: '',
            size_name: '',
            extra_price: '',
            context_product: {},
            price: 0,
            size_disabled: false,
            context_action: {},
            shown_price: 0,
        };
    },
    computed: {
        // koliko maksimalno smije unijeti SADA (stock opcije – već u košarici)
        maxQty() {
            const avail = Number(this.is_available) || 0;
            const inCart = Number(this.has_in_cart) || 0;
            const remaining = avail - inCart;
            return remaining > 0 ? remaining : 1;
        },
    },
    watch: {
        size(value) {
            if (value) this.checkAvailableOptions(value, 'size');
        },
        color(value) {
            this.checkAvailableOptions(value, 'color');
        },
        quantity(val) {
            if (val < 1) this.quantity = 1;
            const mx = this.maxQty;
            if (val > mx) this.quantity = mx;
        },
    },
    beforeMount() {
        this.context_product = JSON.parse(this.product);
        this.id = this.context_product.id;
        this.price = this.context_product.main_price;
        this.shown_price = this.price;
        this.context_action = JSON.parse(this.action);
    },
    mounted() {
        const cart = this.$store.state.storage.getCart();
        if (cart) {
            // ako se vraćaš na artikl, ovo postavlja KOLIKO JE VEĆ u košarici za isti product id (fallback)
            for (const key in cart.items) {
                if (this.id == cart.items[key].id) {
                    this.has_in_cart = cart.items[key].quantity;
                }
            }
        }

        // fallback ako nema opcija
        this.is_available = Number(this.available) || 0;

        this.setOptionsSelection();
        this.setPrice();

        // ponovno izračunaj točno po varijanti ako je već nešto odabrano
        this.computeExistingInCartForSelection();
        this.checkAvailability();
    },
    methods: {
        setOptionsSelection() {
            const res = JSON.parse(this.options);
            this.parent = res.parent ? res.parent : null;
            if (!this.parent) this.size_disabled = true;
            this.size_options = res.size ? res.size.options : {};
            this.color_options = res.color ? res.color.options : {};
        },
        setPrice() {
            if (Number(this.context_product.main_price) > Number(this.context_product.main_special)) {
                this.price = this.context_product.main_price;
                this.shown_price = this.context_product.main_special;
            }
        },
        add() {
            this.checkAvailability(true);
            if (this.has_in_cart) {
                this.updateCart();
            } else {
                this.addToCart();
                if (window.fbq) {
                    window.fbq('track', 'AddToCart', {
                        content_ids: [this.id],
                        content_type: 'product',
                        value: this.shown_price,
                        currency: 'EUR',
                    });
                }
            }
        },
        addToCart() {
            const item = {
                id: this.id,
                quantity: this.quantity,
                options: this.setRequestOptions(),
            };
            this.$store.dispatch('addToCart', item);
        },
        updateCart() {
            const item = {
                id: this.id,
                quantity: this.quantity,
                options: this.setRequestOptions(),
                relative: true,
            };
            this.$store.dispatch('updateCart', item);
        },
        checkAvailability(add = false) {
            this.disabled = false;
            if (this.available === undefined) this.is_available = 0;
            if (add) this.has_in_cart = parseFloat(this.has_in_cart) + parseFloat(this.quantity);

            // disable ako nema preostalih količina za odabranu kombinaciju
            if (Number(this.is_available) <= Number(this.has_in_cart)) {
                this.disabled = true;
                this.has_in_cart = this.is_available;
            }
            if (Object.keys(this.color_options).length && !Object.keys(this.selected_color).length) this.disabled = true;
            if (Object.keys(this.size_options).length && !Object.keys(this.selected_size).length) this.disabled = true;
        },
        checkAvailableOptions(option, type) {
            const is_parent = type === this.parent ? 1 : 0;
            if (option !== 0) {
                if (Object.keys(this.color_options).length && Object.keys(this.size_options).length) {
                    this.$store.state.service.checkOptions(option, is_parent).then((response) => {
                        if (type === 'color') {
                            this.size_options = response.size.options;
                            this.setSelectedColor(option);
                            this.size_disabled = true;
                        } else {
                            this.color_options = response.color.options;
                            this.setSelectedSize(option);
                        }
                        this.checkAvailability();
                    });
                } else {
                    if (Object.keys(this.color_options).length) this.setSelectedColor(option);
                    if (Object.keys(this.size_options).length) this.setSelectedSize(option);
                    this.checkAvailability();
                }
            } else {
                if (type === 'color') {
                    for (const item in this.size_options) this.size_options[item].active = 1;
                } else {
                    for (const item in this.color_options) this.color_options[item].active = 1;
                }
            }
        },
        setRequestOptions() {
            const response = {};
            response.id = this.id;
            response.parent_id =
                this.parent && this.parent === 'color'
                    ? this.selected_color.option_id
                    : this.parent
                        ? this.selected_size.option_id
                        : undefined;
            response.option_id =
                this.parent === 'color'
                    ? this.selected_size.option_id
                    : this.selected_color.option_id
                        ? this.selected_color.option_id
                        : this.selected_size.option_id;
            return response;
        },
        // koliko već imam u košarici za trenutno odabranu varijantu (po option.id)
        computeExistingInCartForSelection() {
            const cart = this.$store.state.storage.getCart();
            if (!cart) {
                this.has_in_cart = 0;
                return;
            }

            const selectedOptionId =
                this.parent === 'color'
                    ? this.selected_size?.option_id || null
                    : this.selected_color?.option_id || this.selected_size?.option_id || null;

            let sum = 0;
            for (const key in cart.items) {
                const it = cart.items[key];
                const optIdInCart = it?.attributes?.options?.option?.id ?? null;
                if (selectedOptionId && optIdInCart && Number(optIdInCart) === Number(selectedOptionId)) {
                    sum += Number(it.quantity) || 0;
                }
            }
            this.has_in_cart = sum;
        },
        setSelectedColor(id) {
            for (const item in this.color_options) {
                if (id == this.color_options[item].id) {
                    this.selected_color = this.color_options[item];
                    this.color_name = this.selected_color.name;

                    // stock direktno iz opcije (quantity/qty)
                    const optQty = Number(this.selected_color.quantity ?? this.selected_color.qty ?? 0);
                    this.is_available = optQty || Number(this.available) || 0;

                    if (this.selected_color.price != '0.0000') {
                        this.price = Math.round(Number(this.context_product.main_price + this.selected_color.price)).toFixed(2);
                        const price = Number(this.selected_color.price);
                        this.extra_price = (price < 0 ? '' : '+') + this.$store.state.service.formatMainPrice(price);
                    } else {
                        this.price = this.context_product.main_price;
                        this.extra_price = '';
                    }
                    if (this.isFixedDiscount()) {
                        // za F: NA PDP-u zadrži main_special s proizvoda (bez opcije)
                        this.shown_price = this.toNum(this.context_product.main_special).toFixed(2);
                    } else {
                        // postojeća logika popusta (percent/amount itd.)
                        this.shown_price = this.setShowPrice();
                    }
                }
            }
            // ponovo izračunaj “već u košarici” za ovu varijantu
            this.computeExistingInCartForSelection();
            this.size = 0;
        },
        setSelectedSize(id) {
            for (const item in this.size_options) {
                if (id == this.size_options[item].id) {
                    this.selected_size = this.size_options[item];
                    this.size_name = this.selected_size.name;

                    const optQty = Number(this.selected_size.quantity ?? this.selected_size.qty ?? 0);
                    this.is_available = optQty || Number(this.available) || 0;

                    if (this.selected_size.price != '0.0000') {
                        this.price = Number(this.context_product.main_price) + Number(this.selected_size.price);
                        const price = Number(this.selected_size.price);
                        this.extra_price = (price < 0 ? '' : '+') + this.$store.state.service.formatMainPrice(price);
                    } else {
                        this.price = this.context_product.main_price;
                        this.extra_price = '';
                    }
                    if (this.isFixedDiscount()) {
                        // za F: NA PDP-u zadrži main_special s proizvoda (bez opcije)
                        this.shown_price = this.toNum(this.context_product.main_special).toFixed(2);
                    } else {
                        // postojeća logika popusta (percent/amount itd.)
                        this.shown_price = this.setShowPrice();
                    }
                }
            }
            // ponovo izračunaj “već u košarici” za ovu varijantu
            this.computeExistingInCartForSelection();
        },
        setShowPrice() {
            const d = this.context_action?.discount;
            const t = (typeof d === 'object' ? d.type : this.context_action?.type || '').toString().toUpperCase();
            const discountVal = typeof d === 'object' && d.value !== undefined ? Number(d.value) : Number(d);

            const base = Number(this.context_product.main_price);        // 86.90
            const opt = Number(this.selected_color?.price || this.selected_size?.price || 0); // opcija +6
            let final = base + opt;

            if (t === 'F') {
                // fixed iznos popusta: (base - discount) + opcijska cijena
                final = (base - discountVal) + opt;
            } else if (t === 'P' || t === 'PERCENT') {
                // postotni: (base + opcijska) * (1 - discount%)
                final = (base + opt) * (1 - (discountVal / 100));
            } else if (this.context_action?.discount) {
                // fallback na service funkciju ako ima
                final = Number(this.$store.state.service.setDiscount(this.context_action.discount, base + opt));
            }

            return final.toFixed(2);
        },
        isFixedDiscount() {
            // discount iz props.action; F = fixed (u tvojoj tablici)
            const d = this.context_action?.discount;
            if (!d) return false;
            // podrži više formata: {type:'F'} ili 'F' ili slično
            const t = (typeof d === 'object' ? d.type : d)?.toString().toUpperCase();
            return t === 'F' || t === 'FIXED';
        },
        toNum(v) {
            if (v === null || v === undefined || v === '') return 0;
            if (typeof v === 'number') return isNaN(v) ? 0 : v;
            const n = parseFloat(String(v).replace(/\s/g, '').replace(',', '.'));
            return isNaN(n) ? 0 : n;
        },
    },
};
</script>

<style scoped>
.opacity {
    opacity: 0.2 !important;
    cursor: revert;
    filter: grayscale(1);
}
</style>
