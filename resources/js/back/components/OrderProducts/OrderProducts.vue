<template>
    <div class="OrderProducts">
        <div class="row mb-4">
            <div class="col-sm-12 col-md-3 text-right"><label class="pt-2">Upišite Proizvod za Dodati</label></div>
            <div class="col-sm-12 col-md-9">
                <input type="text" v-model="query" @keyup="autoComplete" class="form-control">
                <div class="panel-footer" v-if="results.length">
                    <ul class="list-group agm">
                        <li class="list-group-item" v-for="result in results" @click="select(result)">
                            {{ result.translation.name }} - {{ result.sku }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="block black mt-50" v-if="items.length">
            <!--<div class="block-header block-header-default">
                Proizvodi
            </div>-->
            <div class="block-content-full">
                <table class="table table-hover table-vcenter">
                    <thead>
                    <tr class="bg-light">
                        <th class="text-center px-0" style="width: 3%;"></th>
                        <th class="text-center" style="width: 5%;">#</th>
                        <th>Ime</th>
                        <th class="text-center" style="width: 7%;">Kol.</th>
                        <th class="text-center" style="width: 12%;">Jed.Cijena</th>
                        <th class="text-center" style="width: 12%;">Iznos</th>
                        <th class="text-center" style="width: 12%;">Rabat</th>
                        <th class="text-center" style="width: 12%;">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(product, index) in items">
                        <td class="text-center px-0">
                            <i class="si si-trash text-danger float-right" style="margin-top: 2px; cursor: pointer;" @click="removeRow(index)"></i>
                        </td>
                        <td class="text-center">{{ index + 1 }}</td>
                        <td>{{ product.name }}</td>
                        <td class="text-center">
                            <div class="form-material" style="padding-top: 0;">
                                <input type="text" class="form-control py-0" style="height: 26px;" :value="product.quantity" @keyup="ChangeQty(product.sku, $event)" @blur="Recalculate()">
                            </div>
                        </td>
                        <td class="text-right">
                            <input v-if="product.edit" type="text" class="form-control py-0" style="height: 26px;" :value="product.org_price" @keyup.enter="product.edit=false; $emit('update')" @blur="product.edit=false; ChangePrice(product.sku, $event); $emit('update')">
                            <span v-else @click="product.edit=true;">{{ Number(product.org_price).toLocaleString(localization, currency_style) }}</span>
                        </td>
                        <td class="text-right">{{ Number(product.org_price * product.quantity).toLocaleString(localization, currency_style) }}</td>
                        <td class="text-right">
                            <input v-if="product.edit" type="text" class="form-control py-0" style="height: 26px;" :value="product.rabat" @keyup.enter="product.edit=false; $emit('update')" @blur="product.edit=false; ChangeRabat(product.sku, $event); $emit('update')">
                            <span v-else @click="product.edit=true;">-{{ Number((product.rabat) * product.quantity).toLocaleString(localization, currency_style) }}</span>
                        </td>
                        <td class="text-right font-w600">{{ Number(product.total).toLocaleString(localization, currency_style) }}</td>
                    </tr>

                    <!-- Totals -->
                    <tr v-if="sums.length" v-for="(total, index) in sums">
                        <td colspan="6" class="text-right">{{ total.name }}:</td>
                        <td colspan="2" class="text-right font-w600">{{ Number(total.value).toLocaleString(localization, currency_style) }}</td>
                    </tr>

                    <input type="hidden" :value="JSON.stringify(items)" name="items">
                    <input type="hidden" :value="JSON.stringify(sums)" name="sums">

                    </tbody>
                </table>

            </div>
        </div>


        <div class="modal fade" id="options-modal" tabindex="-1" role="dialog" aria-labelledby="comment--modal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-popout" role="document">
                <div class="modal-content rounded">
                    <div class="block block-themed block-transparent mb-0">
                        <div class="block-header bg-primary">
                            <h3 class="block-title">Dodaj artikl</h3>
                            <div class="block-options">
                                <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-times"></i>
                                </a>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="row justify-content-center mb-3">
                                <div class="col-md-10">

                                    <div class="mw-500" v-if="Object.keys(this.color_options).length">
                                        <div class="fs-sm mb-4">
                                            <span class="text-heading fw-medium me-1"><span class="text-danger">*</span> Boja: <span class="font-weight-bold">{{ color_name }}</span></span>
                                        </div>
                                        <div class="position-relative me-n4 mb-3" id="select" >
                                            <div v-for="(option, index) in color_options" class="form-check form-option form-check-inline mb-2" :data-target="option.option_id">
                                                <input class="form-check-input" type="radio" :value="option.id" :id="option.id" :disabled="!option.active" v-model="color"/>
                                                <label v-bind:class="{ opacity: !option.active }"  class="form-option-label rounded-circle opacity-80" :for="option.id"><span class="form-option-color rounded-circle"  :style="option.style"></span> </label>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="mw-500" v-if="Object.keys(this.size_options).length && size_disabled">
                                        <div class="mb-3" >
                                            <div class="d-flex justify-content-between align-items-center pb-1 opac">
                                                <label class="form-label" for="product-size"><span class="text-danger">*</span>Veličina:</label>
                                            </div>
                                            <select class="form-select" required id="product-size" v-model="size">
                                                <option value="0">Veličina... </option>
                                                <option v-for="option in size_options" :disabled="!option.active" v-bind:value="option.id">{{ option.name }} </option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="block-content block-content-full text-right bg-light">
                            <button type="button" class="btn btn-sm btn-primary" @click="addOption()"> Dodaj artikl <i class="fa fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</template>

<script>
export default {
    props: {
        products: {
            type: String,
            required: false,
            default: []
        },
        totals: {
            type: String,
            required: false,
            default: []
        },
        products_autocomplete_url: {
            type: String,
            required: true
        },
        products_options_url: {
            type: String,
            required: true
        }
    },
    //
    data() {
        return {
            products_local: [],
            totals_local: [],
            query: '',
            results: [],
            items: [],
            sums: [],
            selected_product: {},
            is_shipping: true,
            shipping_value: 30,
            is_action: false,
            action_value: 0,
            currency_style: {
                style: 'currency',
                currency: 'EUR'
            },
            localization: 'de-DE',
            size_options: {},
            color_options: {},
            selected_size: {},
            selected_color: {},
            size_disabled: false,
            size: 0,
            color: '',
            size_name: '',
            color_name: '',
            parent: '',
            product_has_option: false,
        }
    },
    //
    watch: {
        size(value) {
            if (value) {
                this.checkAvailableOptions(value, 'size');
            }
        },
        color(value) {
            this.checkAvailableOptions(value, 'color');
        }
    },
    //
    mounted() {
        if (this.products.length && this.totals.length) {
            this.products_local = JSON.parse(this.products)
            this.totals_local = JSON.parse(this.totals)
            this.Sort()

            console.log(this.products_local)
        }
    },
    //
    methods: {

        /**
         *
         * @constructor
         */
        Sort() {
            this.products_local.forEach((item) => {
                this.items.push({
                    id: item.product_id,
                    sku: item.sku,
                    name: item.name,
                    image: item.image,
                    quantity: item.quantity,
                    price: item.price,
                    org_price: item.org_price,
                    rabat: item.org_price - item.price,
                    total: item.total,
                    edit: false
                })
            })

            this.Recalculate()
        },

        /**
         *
         * @param selected
         */
        select(selected) {
            this.selected_product = selected;

            axios.get(this.products_options_url, {params: {id: selected.id}}).then(response => {
                console.log('select(response)', response)

                if ((response.data.size && Object.keys(response.data.size).length) || (response.data.color && Object.keys(response.data.color).length)) {
                    $('#options-modal').modal('show');
                    this.setOptionsSelection(response.data);

                } else {
                    this.addProduct();
                }

            });
        },

        setOptionsSelection(options) {
            let res = options;

            this.parent = res.parent ? res.parent : null;

            console.log('this.parent', this.parent)

            if (!this.parent) {
                this.size_disabled = true;
            }

            this.size_options = res.size ? res.size.options : {};
            this.color_options = res.color ? res.color.options : {};

            console.log('this.size_options', this.size_options)
            console.log('this.color_options', this.color_options)
        },


        addProduct() {
            this.results = [];
            this.query = '';

            let price = this.selected_product.price;

            if (this.selected_product.actions) {
                if (this.selected_product.actions.price) {
                    price = this.selected_product.actions.price;
                }
                if (this.selected_product.actions.discount) {
                    price = this.selected_product.price - (this.selected_product.price * (this.selected_product.actions.discount / 100));
                }
            }

            this.items.push({
                id: this.selected_product.id,
                sku: this.selected_product.sku,
                name: this.selected_product.translation.name,
                image: this.selected_product.image,
                quantity: 1,
                price: price,
                org_price: this.selected_product.price,
                rabat: this.selected_product.price - price,
                total: price,
                edit: false
            })

            this.Recalculate();
        },


        addOption() {
            console.log(this.selected_product)
            console.log(this.selected_color)
            console.log(this.selected_size)
            console.log(this.size, this.color, this.parent)

            //return;

            $('#options-modal').modal('hide');
            this.results = [];
            this.query = '';

            let sku = this.selected_product.sku;
            let name = this.selected_product.translation.name;
            let price = this.selected_product.price;

            if (this.selected_color || this.selected_size) {
                if (this.selected_color.sku != '') {
                    sku = this.selected_color.sku;
                }
                if (this.selected_size.sku != '') {
                    sku = this.selected_size.sku;
                }

                console.log('price', price)

                if (this.selected_size.price || this.selected_size.price != '0.0000') {
                    price = Number(this.selected_product.price) + Number(this.selected_size.price)

                    console.log('price1', price)
                    console.log(this.selected_product.price, this.selected_size.price)
                }
                if (this.selected_color.price || this.selected_color.price != '0.0000') {
                    price = Number(this.selected_product.price) + Number(this.selected_color.price)

                    console.log('price2', price)
                }

                if (this.parent) {
                    if (this.selected_color) {
                        name = name + ', Boja: ' + this.selected_color.name;
                    }
                    if (this.selected_size) {
                        name = name + ' / Veličina: ' + this.selected_size.name;
                    }
                }
            }

            this.items.push({
                id: this.selected_product.id,
                sku: sku,
                name: name,
                image: null,
                quantity: 1,
                price: price,
                org_price: this.selected_product.price,
                rabat: this.selected_product.price - price,
                total: price,
                edit: false
            });

            console.log(this.items);

            this.Recalculate();
        },

        /**
         *
         * @param row
         * @param product
         */
        removeRow(row, product) {
            this.items.splice(row, 1);

            if (!this.items.length) {
                this.sums = [];
            }

            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangeQty(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].sku == id) {
                    this.items[i].quantity = Number(event.target.value);
                    this.items[i].total = this.items[i].price * Number(event.target.value);
                }
            }
            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangePrice(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].sku == id) {
                    let inserted_price = Number(event.target.value);

                    if (inserted_price > this.items[i].rabat) {
                        this.items[i].org_price = inserted_price;
                        this.items[i].price = Number(this.items[i].org_price) - this.items[i].rabat;
                        this.items[i].total = Number(this.items[i].price) * this.items[i].quantity;
                    }
                }
            }
            this.Recalculate();
        },

        /**
         *
         * @param id
         * @param event
         * @constructor
         */
        ChangeRabat(id, event) {
            for (let i = 0; i < this.items.length; i++) {
                if (this.items[i].sku == id) {
                    let inserted_rabat = Number(event.target.value);

                    if (inserted_rabat < this.items[i].org_price) {
                        this.items[i].rabat = inserted_rabat;
                        this.items[i].price = Number(this.items[i].org_price) - inserted_rabat;
                        this.items[i].total = Number(this.items[i].price) * this.items[i].quantity;
                    }
                }
            }
            this.Recalculate();
        },

        /**
         *
         */
        Recalculate() {
            this.sums = [];
            let subtotal = 0;
            let total = 0;

            this.items.forEach((item) => {
                subtotal = subtotal + Number(item.total);
            });

            total = subtotal;

            this.totals_local.forEach((item) => {
                if (item.code == 'shipping' || item.code == 'payment') {
                    total += Number(item.value);
                }
            });

            this.totals_local.forEach((item) => {
                let value = Number(item.value);

                if (item.code == 'subtotal') {
                    value = subtotal;
                }

                if (item.code == 'total') {
                    value = total;
                }

                this.sums.push({
                    name: item.title,
                    value: value,
                    code: item.code
                });
            });
        },

        /**
         *
         */
        autoComplete() {
            this.results = []

            if (this.query.length > 2) {
                axios.get(this.products_autocomplete_url, {params: {query: this.query}}).then(response => {
                    this.results = response.data;
                })
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
                    axios.get(location.origin + '/api/v2/products/options/' + option + '?is_parent=' + is_parent).then(response => {
                        console.log('response', response);
                        if (type == 'color') {
                            this.size_options = response.data.size.options;
                            this.setSelectedColor(option);
                            this.size_disabled = true;

                        } else {
                            this.color_options = response.data.color.options;
                            this.setSelectedSize(option);
                        }
                    });

                } else {
                    if (Object.keys(this.color_options).length) {
                        this.setSelectedColor(option);
                    }

                    if (Object.keys(this.size_options).length) {
                        this.setSelectedSize(option);
                    }

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
         * @param id
         */
        setSelectedColor(id) {
            for (let item in this.color_options) {
                if (id == this.color_options[item].id) {
                    this.selected_color = this.color_options[item];
                    this.color_name = this.selected_color.name;
                }
            }

            this.size = 0;
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


