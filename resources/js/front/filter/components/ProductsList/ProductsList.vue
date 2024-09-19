<template>
    <section class="col">
        <!-- Toolbar-->
        <div class="d-flex justify-content-between align-items-center pt-2 pb-4 pb-sm-2">
            <div class="d-flex flex-wrap pb-3 " >
                <button class="btn btn-primary btn-icon me-1 mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"><i class="ci-filter-alt me-1"></i> <span class="d-none d-sm-inline-block">Filteri</span></button>



                        <span v-if="attributes.length" v-for="attribute in attributes"  >

                            <button v-if="attribute.group === 'Dodatna kategorizacija' " class="btn btn-light btn-icon me-1 mb-2">
                                <input  class="form-check-input" type="checkbox" :id="attribute.id" v-bind:value="attribute.id" v-model="selectedAttributes" >
                                <label   class="form-check-label widget-filter-item-text" :for="attribute.id">{{ attribute.title }}</label>
                            </button>

                        </span>





                <button v-if=" this.attribute || this.brand ||  this.option" class="btn btn-outline-danger bg-white btn-icon me-1 mb-2" type="button" onclick="window.location.replace(location.pathname);" ><i class="ci-loading me-0 me-sm-2"></i> <span class="d-none d-sm-inline-block">Očisti</span></button>
            </div>
        </div>

            <div class="d-flex flex-wrap ">


                <div class="d-flex align-items-center flex-nowrap me-2  pb-3">
                    <select class="form-select pe-2" style="max-width: 120px;" v-model="sorting">
                        <option value="">{{ trans.sortiraj }}</option>
                        <option value="novi">{{ trans.najnovije }}</option>
                        <option value="price_up">{{ trans.najmanja_cijena }}</option>
                        <option value="price_down">{{ trans.najveca_cijena }}</option>
                        <option value="naziv_up">{{ trans.a_z }}</option>
                        <option value="naziv_down">{{ trans.z_a }}</option>
                    </select>
                </div>

                <div class="d-flex pb-3 d-none"><span class="fs-sm text-dark btn btn-white btn-sm text-nowrap ms-0 d-block">{{ products.total ? Number(products.total).toLocaleString('hr-HR') : 0 }} {{ trans.artikala }}</span></div>
                <div class="d-flex d-sm-none pb-3">
                    <button class="btn btn-outline-secondary bg-white btn-icon nav-link-style  me-1" v-on:click="tworow()" >2</button>

                    <button class="btn btn-outline-secondary bg-white  btn-icon btn-sm nav-link-style " v-on:click="onerow()">1</button>
                </div>


            </div>






        <!-- Offcanvas -->



        <!-- Products grid-->
        <div class="row row-cols-xxxl-5 row-cols-xxl-4 row-cols-xl-4 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 g-0 mx-n2 mb-5"  id="product-grid" v-if="products.total">
            <div class="px-2 mb-4 d-flex align-items-stretch" v-for="product in products.data">
                <div class="card product-card card-static pb-3">



                    <span class="badge bg-warning mt-1 ms-1 badge-end"  v-if="product.quantity <= 0">{{ trans.rasprodano }}</span>
                    <span class="badge rounded-pill bg-red mt-1 ms-1 badge-shadow" v-if="Number(product.main_price) > Number(product.main_special)">-{{ ($store.state.service.getDiscountAmount(product.main_price,  product.main_special)) }}%</span>

                    <a class="card-img-top d-block pb-2 overflow-hidden " :href="origin + product.url"><img load="lazy" :src="product.image.replace('.webp', '-thumb.webp')" width="400" height="400" :alt="product.name">
                    </a>
                    <div class="card-body py-2 pt-0">

                        <h3 class="product-title fs-sm text-truncate"><a :href="origin + product.url">{{ product.name }}</a></h3>
                        <div class="d-flex flex-wrap justify-content-between align-items-center" v-if="product.category_string">
                            <div class="fs-sm me-2"><span v-html="product.category_string"></span></div>
                        </div>
                        <div class="product-price">
                            <span class="fs-sm text-muted"  v-if="Number(product.main_price) > Number(product.main_special)"><small>NC 30 dana: {{ product.main_price_text }} </small> </span>
                        </div>
                        <div class="product-price">
                            <span class="text-red fs-md" v-if="Number(product.main_price) > Number(product.main_special)">{{ product.main_special_text }} </span>
                        </div>
                        <div class="product-price">
                            <span class="text-dark fs-md" v-if="Number(product.main_price) < Number(product.main_special)">{{ product.main_price_text }} </span>

                            <span class="text-dark fs-md" v-else >{{ product.main_price_text }} </span>
                        </div>

                        <div class="star-rating" v-if="product.stars">
                            <span v-for="item in 5 ">
                                <i  v-if="Math.floor(product.stars) - item >= 0" class="star-rating-icon ci-star-filled active"></i>

                                <i v-else-if="product.stars - item > -1 " class="star-rating-icon ci-star-half active"></i>



                               <i v-else class="star-rating-icon ci-star"></i>
                            </span>
                        </div>


                    </div>
                    <div class="product-floating-btn" v-if="product.quantity > 0 && !product.has_option">
                        <button class="btn btn-primary btn-shadow btn-sm" :disabled="product.disabled" v-on:click="add(product.id, product.quantity)" type="button">+<i class="ci-cart fs-base ms-1"></i></button>
                    </div>
                    <div class="product-floating-btn" v-if="product.quantity > 0 && product.has_option">
                        <a class="btn btn-primary btn-shadow btn-sm" :href="origin + product.url">+<i class="ci-cart fs-base ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <pagination :data="products" align="center" :show-disabled="true" :limit="4" @pagination-change-page="getProductsPage"></pagination>

        <div class="row" v-if="!products_loaded">
            <div class="col-md-12 d-flex justify-content-center mt-4">
                <div class="spinner-border text-primary opacity-75" role="status" style="width: 9rem; height: 9rem;"></div>
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-center mt-4" v-if="products.total">
            <p class="fs-sm">{{ trans.prikazano }}
                <span class="font-weight-bolder mx-1">{{ products.from ? Number(products.from).toLocaleString('hr-HR') : 0 }}</span> {{ trans.do }}
                <span class="font-weight-bolder mx-1">{{ products.to ? Number(products.to).toLocaleString('hr-HR') : 0 }}</span> {{ trans.od }}
                <span class="font-weight-bold mx-1">{{ products.total ? Number(products.total).toLocaleString('hr-HR') : 0 }}</span> {{ trans.rezultata }}
            </p>
        </div>
        <div class="col-md-12 px-2 mb-4" v-if="products_loaded && search_zero_result">
            <h2>{{ trans.nema_rezultata }}</h2>
            <p> {{ trans.vasa_pretraga }} <mark>{{ search_query }}</mark> {{ trans.pronasla_nula }}.</p>
            <h4 class="h5">{{ trans.s1 }}</h4>
            <ul class="list-style">
                <li>{{ trans.s2 }}</li>
                <li>{{ trans.s3 }}</li>
                <li>{{ trans.s4 }}</li>
            </ul>
            <hr class="d-sm-none">
        </div>
        <div class="col-md-12  mb-4" v-if="products_loaded && navigation_zero_result">
            <h2>{{ trans.t1 }}</h2>
            <p> {{ trans.t2 }}</p>
            <hr class="d-sm-none">
        </div>

    </section>
</template>

<script>

Vue.directive('tooltip', function(el, binding){
    $(el).tooltip({
        title: binding.value,
        placement: binding.arg,
        trigger: 'hover'
    })
})

export default {
    name: 'ProductsList',
    props: {
        ids: String,
        group: String,
        cat: String,
        subcat: String,
        author: String,
        publisher: String,


    },
    //
    data() {
        return {
            products: {},
            autor: '',
            brand: '',
            option: '',
            attribute: '',
            selectedAttributes: [],
            nakladnik: '',
            start: '',
            end: '',
            sorting: '',
            attributes: [],
            search_query: '',
            page: 1,
            origin: location.origin + '/',
            hr_total: 'rezultata',
            products_loaded: false,
            search_zero_result: false,
            navigation_zero_result: false,
            trans: window.trans,
        }
    },
    //
    watch: {
        sorting(value) {
            this.setQueryParam('sort', value);
        },
        $route(params) {
            this.checkQuery(params);
        },
        selectedAttributes(value) {
            this.attribute = value.join('+');
            this.setQueryParamOther('attribute', this.attribute);
        },
    },
    //
    mounted() {
        this.checkQuery(this.$route);
        if (this.attribute === '') {
            this.show_attributes = true;
            this.getAttributes();
        }
    },

    methods: {
        /**
         *
         */
        getProducts() {
            this.search_zero_result = false;
            this.navigation_zero_result = false;
            this.products_loaded = false;
            let params = this.setParams();

            console.log('tu sam...')
            console.log(params)

            axios.post('filter/getProducts', { params }).then(response => {
                this.products_loaded = true;
                this.products = response.data;
                this.checkHrTotal();
                this.checkSpecials();

                if (this.$store.state.storage.getCart()) {
                    this.checkAvailables();
                }

                console.log('Response::data.data')
                console.log(response.data.data)

                if (params.pojam != '' && !this.products.total) {
                    this.search_zero_result = true;
                }

                if (params.pojam == '' && !this.products.total) {
                    this.navigation_zero_result = true;
                }
            });
        },

        /**
         *
         * @param page
         */
        getProductsPage(page = 1) {
            this.products_loaded = false;
            this.page = page;
            this.setQueryParam('page', page);

            let params = this.setParams();
            window.scrollTo({top: 0, behavior: 'smooth'});

            axios.post('filter/getProducts?page=' + page, { params }).then(response => {
                this.products_loaded = true;
                this.products = response.data;
                this.checkHrTotal();
                this.checkSpecials();
                this.checkAvailables();
            });
        },

        /**
         *
         * @param type
         * @param value
         */
        setQueryParam(type, value) {
            this.closeFilter();
            this.$router.push({query: this.resolveQuery()}).catch(()=>{});

            if (value == '' || value == 1) {
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }
        },

        /**
         *
         **/
        setQueryParamOther(type, value) {

            this.$router.push({query: this.resolveQuery()}).catch(()=>{});

            if (value === '') {
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }
        },

        /**
         *
         * @return {{}}
         */
        resolveQuery() {
            let params = {
                start: this.start,
                end: this.end,
                autor: this.autor,
                brand: this.brand,
                option: this.option,
                attribute: this.attribute,
                nakladnik: this.nakladnik,
                sort: this.sorting,
                pojam: this.search_query,
                page: this.page
            };

            return Object.entries(params).reduce((acc, [key, val]) => {
                if (!val) return acc
                return { ...acc, [key]: val }
            }, {});
        },

        /**
         *
         * @param params
         */
        checkQuery(params) {
            this.start = params.query.start ? params.query.start : '';
            this.end = params.query.end ? params.query.end : '';
            this.autor = params.query.autor ? params.query.autor : '';
            this.brand = params.query.brand ? params.query.brand : '';
            this.option = params.query.option ? params.query.option : '';
            this.attribute = params.query.attribute ? params.query.attribute : '';
            this.nakladnik = params.query.nakladnik ? params.query.nakladnik : '';
            this.page = params.query.page ? params.query.page : '';
            this.sorting = params.query.sort ? params.query.sort : '';
            this.search_query = params.query.pojam ? params.query.pojam : '';

            if (this.page != '') {
                this.getProductsPage(this.page);
            } else {
                this.getProducts();
            }
        },

        /**
         *
         * @return {{cat: String, start: string, pojam: string, subcat: String, end: string, sort: string, nakladnik: string, autor: string, group: String}}
         */
        setParams() {
            let params = {
                ids: this.ids,
                group: this.group,
                cat: this.cat,
                subcat: this.subcat,
                autor: this.autor,
                brand: this.brand ? this.brand : this.brand,
                option: this.option ? this.option : this.option,
                attribute: this.attribute ? this.attribute : this.attribute,
                nakladnik: this.nakladnik,
                start: this.start,
                end: this.end,
                sort: this.sorting,
                pojam: this.search_query
            };

            if (this.author !== '') {
                params.autor = this.author;
            }

            if (this.brand !== '') {
                params.brand = this.brand;
            }
            if (this.attribute !== '') {
                params.attribute = this.attribute;
            }

            if (this.option !== '') {
                params.option = this.option;
            }
            if (this.publisher !== '') {
                params.nakladnik = this.publisher;
            }

            return params;
        },

        /**
         *
         */
        checkSpecials() {
            let now = new Date();

            for (let i = 0; i < this.products.data.length; i++) {
                if (Number(this.products.data[i].main_price) <= Number(this.products.data[i].main_special)) {
                    this.products.data[i].special = false;
                }
            }
        },

        getAttributes() {

            let params = this.setParams();

            axios.post('filter/getAttributes', { params }).then(response => {

                this.attributes = response.data;

                console.log('attributi')
                console.log(response.data)
            });
        },

        /**
         *
         */
        checkAvailables() {
            let cart = this.$store.state.storage.getCart();
            if (cart) {
                for (let i = 0; i < this.products.data.length; i++) {
                    this.products.data[i].disabled = false;

                    for (const key in cart.items) {
                        if (this.products.data[i].id == cart.items[key].id) {
                            if (this.products.data[i].quantity <= cart.items[key].quantity) {
                                this.products.data[i].disabled = true;
                            }
                        }
                    }
                }
            }
        },

        /**
         *
         */
        checkHrTotal() {
            this.hr_total = 'rezultata';

            if ((this.products.total).toString().slice(-1) == '1') {
                this.hr_total = 'rezultat';
            }
        },

        /**
         *
         * @param id
         */
        add(id, product_quantity) {
            let cart = this.$store.state.storage.getCart();
            if (cart) {
                for (const key in cart.items) {
                    if (id == cart.items[key].id) {
                        if (product_quantity <= cart.items[key].quantity) {
                            return window.ToastWarning.fire('Nažalost nema dovoljnih količina artikla..!');
                        }
                    }
                }
            }

            this.$store.dispatch('addToCart', {
                id: id,
                quantity: 1
            })
        },



        /**
         *
         */
        preselect() {


            if (this.attribute !== '') {
                if ((this.attribute).includes('+')) {
                    this.selectedAttributes = (this.attribute).split('+');
                } else {
                    this.selectedAttributes = [this.attribute];
                }
            }


        },

        /**
         *
         */
        cleanQuery() {
            this.$router.push({query: {}}).catch(()=>{});

            this.selectedAttributes = [];

            this.start = '';
            this.end = '';
            window.location.replace(location.pathname);
        },


        /**
         *
         */
        closeFilter() {
            $('#shop-sidebar').removeClass('collapse show');
        },

        onerow() {
            $('#product-grid').removeClass('row-cols-2');
            $('#product-grid').addClass('row-cols-1');
        },

        tworow() {
            $('#product-grid').removeClass('row-cols-1');
            $('#product-grid').addClass('row-cols-2');
        }
    }
};
</script>

<style>
</style>
