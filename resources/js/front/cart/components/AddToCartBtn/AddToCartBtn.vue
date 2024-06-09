<template>
    <div class="cart  pb-2 mb-3">
        <div class="mw-500" v-if="Object.keys(this.color_options).length">
            <div class="fs-sm mb-4">
                <span class="text-heading fw-medium me-1"><span class="text-danger">*</span> {{ trans.boja }}:</span><span class="text-muted">{{ color_name }}</span>
            </div>
            <div class="position-relative me-n4 mb-3" id="select" >
                <div v-for="(option, index) in color_options" class="form-check form-option form-check-inline mb-2" :data-target="option.option_id">
                    <input class="form-check-input" type="radio" :value="option.id" :id="option.id" :disabled="!option.active" v-model="color"/>
                    <label v-bind:class="{ opacity: !option.active }" class="form-option-label rounded-circle opacity-80" :for="option.id"><span class="form-option-color rounded-circle" :style="option.style"></span> </label>
                </div>
            </div>
        </div>
        <div class="mw-500" v-if="Object.keys(this.size_options).length">
            <div class="mb-3" >
                <div class="d-flex justify-content-between align-items-center pb-1 opac">
                    <label class="form-label" for="product-size"><span class="text-danger">*</span>{{ trans.velicina }}: <span class="text-muted">{{ size_name }}</span></label>


                    <a v-if="sizeguide" class="nav-link-style fs-sm gal" :href="sizeguide" ><i class="ci-ruler lead align-middle me-1 mt-n1"></i>Tablica veličina</a>
                </div>
                <select class="form-select" required id="product-size" v-model="size">
                    <option value="0">{{ trans.velicina }} </option>
                    <option v-for="option in size_options" :disabled="!option.active" v-bind:value="option.id">{{ option.name }}</option>
                </select>
            </div>
        </div>
        <div class="d-flex align-items-center pt-2 mw-500" >
            <input class="form-control me-3 mb-1" type="number" inputmode="numeric" pattern="[0-9]*" v-model="quantity" min="1" :max="is_available" style="width: 5rem;">
            <button class="btn btn-primary btn-shadow  w-100 mb-1 " @click="add()" :disabled="disabled"><i class="ci-cart"></i> {{trans.add_to_cart }}</button>
        </div>
        <p style="width: 100%;" class="fs-sm fw-light text-danger mb-0" v-if="has_in_cart">{{ trans.imate }} {{ has_in_cart }} {{trans.artikala_u_kosarici }}.</p>
    </div>
</template>
<style>
.opacity{
    opacity: 0.2 !important;
    cursor: revert;
    filter:grayscale(1);
}
</style>



<script>
export default {
    props: {
        id: String,
        available: String,
        options: String,
        sizeguide: String
    },

    data() {
        return {
            quantity: 1,
            has_in_cart: 0,
            disabled: false,
            is_available: 0,
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

        }
    },
//
    watch: {
        size(value) {
            this.checkAvailableOptions(value, 'size');
        },
        color(value) {
            this.checkAvailableOptions(value, 'color');
        }
    },
    //
    mounted() {
        let cart = this.$store.state.storage.getCart();

        if (cart) {
            for (const key in cart.items) {
                if (this.id == cart.items[key].id) {
                    this.has_in_cart = cart.items[key].quantity;
                }
            }
        }

        this.is_available = this.available;

        this.setOptionsSelection();
        this.checkAvailability();
    },

    methods: {
        /**
         *
         */
        setOptionsSelection() {
            let res = JSON.parse(this.options);

            this.parent = res.parent;
            this.size_options = res.size ? res.size.options : {};
            this.color_options = res.color ? res.color.options : {};
        },

        /**
         *
         */
        add() {
            this.checkAvailability(true);

            console.log('add():::')
            console.log(this.is_available, this.disabled)
            console.log(Object.keys(this.color_options).length, Object.keys(this.selected_color).length)
            console.log(this.selected_size, this.selected_color)

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
                quantity: this.quantity,
                options: this.setRequestOptions()
            }

            this.$store.dispatch('addToCart', item);
        },

        /**
         *
         */
        updateCart() {
            /*if (parseFloat(this.quantity) > parseFloat(this.is_available)) {
                this.quantity = this.is_available;
            }*/

            let item = {
                id: this.id,
                quantity: this.quantity,
                options: this.setRequestOptions(),
                relative: true
            }

            this.$store.dispatch('updateCart', item);
        },

        /**
         *
         * @param add
         */
        checkAvailability(add = false) {
            this.disabled = false;

            if (this.available == undefined) {
                this.is_available = 0;
            }

            if (add) {
                this.has_in_cart = parseFloat(this.has_in_cart) + parseFloat(this.quantity);
            }

            if (this.is_available <= this.has_in_cart) {
                this.disabled = true;
                this.has_in_cart = this.is_available;
            }

            if (Object.keys(this.color_options).length && !Object.keys(this.selected_color).length) {
                this.disabled = true;
            }
            if (Object.keys(this.size_options).length && !Object.keys(this.selected_size).length) {
                this.disabled = true;
            }
        },

        /**
         *
         * @param option
         * @param type
         */
        checkAvailableOptions(option, type) {
            let is_parent = (type == this.parent) ? 1 : 0;

            if (option != 0) {
                if (Object.keys(this.color_options).length && Object.keys(this.size_options).length) {
                    this.$store.state.service.checkOptions(option, is_parent).then((response) => {
                        if (type == 'color') {
                            this.size_options = response.size.options;
                            this.setSelectedColor(option);

                        } else {
                            this.color_options = response.color.options;
                            this.setSelectedSize(option);
                        }

                        this.checkAvailability();
                    });

                } else {
                    if (Object.keys(this.color_options).length) {
                        this.setSelectedColor(option);
                    }

                    if (Object.keys(this.size_options).length) {
                        this.setSelectedSize(option);
                    }

                    this.checkAvailability();
                }

            } else {
                if (type == 'color') {
                    for (let item in this.size_options) {
                        this.size_options[item].active = 1;
                    }

                } else {
                    for (let item in this.color_options) {
                        this.color_options[item].active = 1;
                    }
                }
            }

        },

        /**
         *
         * @returns {{}}
         */
        setRequestOptions() {
            let response = {};

            if (Object.keys(this.color_options).length) {
                response = {
                    id: this.selected_color.id
                };
            }

            if (Object.keys(this.size_options).length) {
                response = {
                    id: this.selected_size.id
                };
            }

            return response;
        },

        /**
         *
         * @param id
         */
        setSelectedColor(id) {
            for (let item in this.color_options) {
                if (id == this.color_options[item].id) {
                    this.selected_color = this.color_options[item];
                    this.color_name = this.selected_color.name;
                }
            }
        },

        /**
         *
         * @param id
         */
        setSelectedSize(id) {
            for (let item in this.size_options) {
                if (id == this.size_options[item].id) {
                    this.selected_size = this.size_options[item];
                    this.size_name = this.selected_size.name;
                }

            }
        }
    }
};
</script>


