<template>
    <div class="cart  pb-2 mb-3">
        <div class="mw-500" v-if="options.includes('color')">
            <div class="fs-sm mb-4">
                <span class="text-heading fw-medium me-1">
                    <span class="text-danger">*</span> {{ trans.boja }}:</span>
                    <span class="text-muted" id="colorOption"> </span>
            </div>
            <div class="position-relative me-n4 mb-3" id="select"  v-for="options in arr_options">
                <div v-for="(option, index) in options.options" class="form-check form-option form-check-inline mb-2" :data-target="index" >  <!--:data-target="index" služi za slidanje slike  -->
                    <input class="form-check-input" type="radio" name="color"  :id="'color' + index" data-bs-label="colorOption"  v-bind:value="option.name"  >
                    <label  class="form-option-label rounded-circle" :for="'color' + index">
                        <span  class="form-option-color rounded-circle" :style="option.style"></span>
                    </label>
                </div>

            </div>
        </div>
        <div class="mw-500" v-if="options.includes('size')">
            <div class="mb-3" >
                <div class="d-flex justify-content-between align-items-center pb-1">
                    <label class="form-label" for="product-size"><span class="text-danger">*</span>{{ trans.velicina }}:</label><a class="nav-link-style fs-sm" href="#size-chart" data-bs-toggle="modal"><i class="ci-ruler lead align-middle me-1 mt-n1"></i>Tablica veličina</a>
                </div>
                <select class="form-select" required id="product-size" v-for="options in arr_options">
                    <option value="">{{ trans.velicina }} </option>
                    <option v-for="option in options.options"   v-bind:value="option.sku">{{ option.name }}</option>
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
        available: String,
        options: String
    },

    data() {
        return {
            quantity: 1,
            has_in_cart: 0,
            disabled: false,
            arr_options: [],
           trans: window.trans,
        }
    },

    mounted() {
        this.arr_options = JSON.parse(this.options);

        console.log(this.options);
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
