<template>
    <div class="cart  pb-2 mb-3">
        <div class="mw-500">
            <div class="fs-sm mb-4"><span class="text-heading fw-medium me-1"><span class="text-danger">*</span> {{ trans.boja }}:</span><span class="text-muted" id="colorOption">Bijela</span></div>
            <div class="position-relative me-n4 mb-3" id="select">
                <div class="form-check form-option form-check-inline mb-2" data-target="0">
                    <input class="form-check-input" type="radio" name="color"  id="color1" data-bs-label="colorOption" value="Bijela" checked>
                    <label class="form-option-label rounded-circle" for="color1"><span class="form-option-color rounded-circle" style="background-color: #f7f7f7;"></span></label>
                </div>
                <div class="form-check form-option form-check-inline mb-2" data-target="1">
                    <input class="form-check-input" type="radio" name="color"  id="color2" data-bs-label="colorOption" value="Crvena">
                    <label class="form-option-label rounded-circle" for="color2"><span class="form-option-color rounded-circle" style="background-color: #cd232d;"></span></label>
                </div>
                <div class="form-check form-option form-check-inline mb-2" data-target="2">
                    <input class="form-check-input" type="radio" name="color" id="color3"  data-bs-label="colorOption" value="Plava">
                    <label class="form-option-label rounded-circle" for="color3"><span class="form-option-color rounded-circle" style="background-color: #3666ac;"></span></label>
                </div>
                <div class="form-check form-option form-check-inline mb-2" data-target="3">
                    <input class="form-check-input" type="radio" name="color"  id="color4" data-bs-label="colorOption" value="Žuta">
                    <label class="form-option-label rounded-circle" for="color4"><span class="form-option-color rounded-circle" style="background-color: #e5cf50;"></span></label>
                </div>

                <div class="form-check form-option form-check-inline mb-2" data-target="4">
                    <input class="form-check-input" type="radio" name="color"  id="color5" data-bs-label="colorOption" value="Flouroscentno zelena">
                    <label class="form-option-label rounded-circle" for="color5"><span class="form-option-color rounded-circle" style="background-color: #b2d245;"></span></label>
                </div>

                <div class="form-check form-option form-check-inline mb-2" data-target="5">
                    <input class="form-check-input" type="radio" name="color"  id="color6" data-bs-label="colorOption" value="Tamno crvena">
                    <label class="form-option-label rounded-circle" for="color6"><span class="form-option-color rounded-circle" style="background-color: #c12f32;"></span></label>
                </div>

                <div class="form-check form-option form-check-inline mb-2" data-target="6">
                    <input class="form-check-input" type="radio" name="color"  id="color7" data-bs-label="colorOption" value="Zelena">
                    <label class="form-option-label rounded-circle" for="color7"><span class="form-option-color rounded-circle" style="background-color: #239752;"></span></label>
                </div>


            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center pb-1">
                    <label class="form-label" for="product-size"><span class="text-danger">*</span>{{ trans.velicina }}:</label><a class="nav-link-style fs-sm" href="#size-chart" data-bs-toggle="modal"><i class="ci-ruler lead align-middle me-1 mt-n1"></i>Tablica veličina</a>
                </div>
                <select class="form-select" required id="product-size">
                    <option value="">{{ trans.velicina }}</option>
                    <option value="xs">XS</option>
                    <option value="s">S</option>
                    <option value="m">M</option>
                    <option value="l">L</option>
                    <option value="xl">XL</option>
                </select>
            </div>
        </div>
    <div class="d-flex align-items-center pt-2 mw-500" >
        <input class="form-control me-3 mb-1" type="number" inputmode="numeric" pattern="[0-9]*" v-model="quantity" min="1" :max="available" style="width: 5rem;">
        <button class="btn btn-primary btn-shadow  w-100 mb-1 " @click="add()" :disabled="disabled"><i class="ci-cart"></i> {{trans.add_to_cart }}</button>
    </div>
    <p style="width: 100%;" class="fs-sm fw-light text-danger mb-0" v-if="has_in_cart">{{ trans.imate }} {{ has_in_cart }} {{trans.artikala_u_kosarici }}.</p>
    </div>
</template>

<script>
export default {
    props: {
        id: String,
        available: String
    },

    data() {
        return {
            quantity: 1,
            has_in_cart: 0,
            disabled: false,
           trans: window.trans,
        }
    },

    mounted() {
        let cart = this.$store.state.storage.getCart();
            if(cart){

                for (const key in cart.items) {
                    if (this.id == cart.items[key].id) {
                        this.has_in_cart = cart.items[key].quantity;
                    }
                }


            }

        if (this.available == undefined) {
            this.available = 0;
        }

        this.checkAvailability();
    },

    methods: {
        add() {
            this.checkAvailability(true);

            if (this.has_in_cart) {
                this.updateCart();
            } else {
                this.addToCart();
            }
        },
        /**
         *
         */
        addToCart() {
            let item = {
                id: this.id,
                quantity: this.quantity
            }

            this.$store.dispatch('addToCart', item);
        },

        /**
         *
         */
        updateCart() {
            /*if (parseFloat(this.quantity) > parseFloat(this.available)) {
                this.quantity = this.available;
            }*/

            let item = {
                id: this.id,
                quantity: this.quantity,
                relative: true
            }

            this.$store.dispatch('updateCart', item);
        },

        checkAvailability(add = false) {
            if (add) {
                this.has_in_cart = parseFloat(this.has_in_cart) + parseFloat(this.quantity);
            }

            if (this.available <= this.has_in_cart) {
                this.disabled = true;
                this.has_in_cart = this.available;
            }
        }
    }
};
</script>
