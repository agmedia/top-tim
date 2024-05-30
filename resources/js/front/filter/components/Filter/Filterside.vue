<template>

    <div class="offcanvas offcanvas-end" id="offcanvasRight" tabindex="-1">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Filter</h5>
            <button class="btn-close" type="button" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body" data-simplebar>
            <div class="row pt-2">

                <div class="col-lg-12" >

                    <!-- Filter by Brand-->
                    <div class=" mb-grid-gutter" v-if="show_brands">
                        <div class=" px-2">
                            <div class="widget widget-filter mb-4 pb-4 border-bottom" v-if="show_brands">
                                <h3 class="widget-title">Brands<span v-if="!brands_loaded" class="spinner-border spinner-border-sm" style="float: right;"></span></h3>
                                <div class="input-group input-group-sm mb-2 autocomplete">
                                    <input type="search" v-model="searchBrand" class="form-control rounded-end pe-5" placeholder="Pretraži nakladnike"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                </div>
                                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11rem;"  data-simplebar-auto-hide="false">
                                    <div class="simplebar-scroll-content">
                                        <div class="simplebar-content">
                                            <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-for="brand in brands">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :id="brand.translation.slug" v-bind:value="brand.translation.slug" v-model="selectedBrands">
                                                    <label class="form-check-label widget-filter-item-text" :for="brand.translation.slug">{{ brand.title }}</label>
                                                </div>
                                                <span class="fs-xs text-muted"><a :href="origin + brand.url">{{ Number(brand.products_count).toLocaleString('hr-HR') }}</a></span>
                                            </li>
                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 ">
                    <!-- Filter by Size-->
                    <div class=" mb-grid-gutter">
                        <div class=" px-2">
                            <div class="widget widget-filter">
                                <h3 class="widget-title">Veličina</h3>
                                <div class="input-group input-group-sm mb-2">
                                    <input class="widget-filter-search form-control rounded-end pe-5" type="text" placeholder="Pretraži"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                </div>
                                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11.5rem;" data-simplebar data-simplebar-auto-hide="false">
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-xs">
                                            <label class="form-check-label widget-filter-item-text" for="size-xs">XS</label>
                                        </div><span class="fs-xs text-muted">34</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-s">
                                            <label class="form-check-label widget-filter-item-text" for="size-s">S</label>
                                        </div><span class="fs-xs text-muted">57</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-m">
                                            <label class="form-check-label widget-filter-item-text" for="size-m">M</label>
                                        </div><span class="fs-xs text-muted">198</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-l">
                                            <label class="form-check-label widget-filter-item-text" for="size-l">L</label>
                                        </div><span class="fs-xs text-muted">72</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-xl">
                                            <label class="form-check-label widget-filter-item-text" for="size-xl">XL</label>
                                        </div><span class="fs-xs text-muted">46</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-39">
                                            <label class="form-check-label widget-filter-item-text" for="size-39">39</label>
                                        </div><span class="fs-xs text-muted">112</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-40">
                                            <label class="form-check-label widget-filter-item-text" for="size-40">40</label>
                                        </div><span class="fs-xs text-muted">85</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-41">
                                            <label class="form-check-label widget-filter-item-text" for="size-40">41</label>
                                        </div><span class="fs-xs text-muted">210</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-42">
                                            <label class="form-check-label widget-filter-item-text" for="size-42">42</label>
                                        </div><span class="fs-xs text-muted">57</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-43">
                                            <label class="form-check-label widget-filter-item-text" for="size-43">43</label>
                                        </div><span class="fs-xs text-muted">30</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-44">
                                            <label class="form-check-label widget-filter-item-text" for="size-44">44</label>
                                        </div><span class="fs-xs text-muted">61</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-45">
                                            <label class="form-check-label widget-filter-item-text" for="size-45">45</label>
                                        </div><span class="fs-xs text-muted">23</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-46">
                                            <label class="form-check-label widget-filter-item-text" for="size-46">46</label>
                                        </div><span class="fs-xs text-muted">19</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-47">
                                            <label class="form-check-label widget-filter-item-text" for="size-47">47</label>
                                        </div><span class="fs-xs text-muted">15</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-48">
                                            <label class="form-check-label widget-filter-item-text" for="size-48">48</label>
                                        </div><span class="fs-xs text-muted">12</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-49">
                                            <label class="form-check-label widget-filter-item-text" for="size-49">49</label>
                                        </div><span class="fs-xs text-muted">8</span>
                                    </li>
                                    <li class="widget-filter-item d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="size-50">
                                            <label class="form-check-label widget-filter-item-text" for="size-50">50</label>
                                        </div><span class="fs-xs text-muted">6</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 ">
                    <!-- Filter by Color-->
                    <div class=" mb-grid-gutter">
                        <div class=" px-2">
                            <div class="widget">
                                <h3 class="widget-title">Boja</h3>
                                <div class="d-flex flex-wrap">
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-blue-gray">
                                        <label class="form-option-label rounded-circle" for="color-blue-gray"><span class="form-option-color rounded-circle" style="background-color: #b3c8db;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-blue-gray">Plava</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-burgundy">
                                        <label class="form-option-label rounded-circle" for="color-burgundy"><span class="form-option-color rounded-circle" style="background-color: #ca7295;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-burgundy">Crvena</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-teal">
                                        <label class="form-option-label rounded-circle" for="color-teal"><span class="form-option-color rounded-circle" style="background-color: #91c2c3;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-teal">Zelena</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-brown">
                                        <label class="form-option-label rounded-circle" for="color-brown"><span class="form-option-color rounded-circle" style="background-color: #9a8480;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-brown">Smeđa</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-coral-red">
                                        <label class="form-option-label rounded-circle" for="color-coral-red"><span class="form-option-color rounded-circle" style="background-color: #ff7072;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-coral-red">Crvena</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-navy">
                                        <label class="form-option-label rounded-circle" for="color-navy"><span class="form-option-color rounded-circle" style="background-color: #696dc8;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-navy">Plava</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-charcoal">
                                        <label class="form-option-label rounded-circle" for="color-charcoal"><span class="form-option-color rounded-circle" style="background-color: #4e4d4d;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-charcoal">Siva</label>
                                    </div>
                                    <div class="form-check form-option text-center mb-2 mx-1" style="width: 4rem;">
                                        <input class="form-check-input" type="checkbox" id="color-sky-blue">
                                        <label class="form-option-label rounded-circle" for="color-sky-blue"><span class="form-option-color rounded-circle" style="background-color: #8bcdf5;"></span></label>
                                        <label class="d-block fs-xs text-muted mt-n1" for="color-sky-blue">Modra</label>
                                    </div>
                                </div>
                            </div>
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
        ids: String,
        group: String,
        cat: String,
        subcat: String,


    },


    //
    data() {
        return {
            categories: [],
            category: null,
            subcategory: null,
            brands: [],
            selectedBrands: [],
            start: '',
            end: '',
            autor: '',
            brand: '',
            nakladnik: '',
            search_query: '',
            searchBrand: '',
            show_brands: false,
            brands_loaded: false,
            origin: location.origin + '/',
            trans: window.trans,
        }
    },
    //
    watch: {
        start(currentValue) {
            this.setQueryParam('start', currentValue);
        },
        end(currentValue) {
            this.setQueryParam('end', currentValue);
        },

        selectedBrands(value) {
            this.brand = value.join('+');
            this.setQueryParamOther('brand', this.brand);
        },
        searchBrand(value) {
            if (value.length > 2 || value == '') {
                return this.getBrands();
            }
        },
        $route(params) {
            this.checkQuery(params);
        }
    },

    //
    mounted() {

        this.checkQuery(this.$route);
        this.checkCategory();
        this.getCategories();




        if (this.brand == '') {
            this.show_brands = true;
            this.getBrands();
        }



        this.preselect();
    },

    methods: {
        /**
         *
         **/
        getCategories() {
            let params = this.setParams();

            axios.post('filter/getCategories', { params }).then(response => {
                this.categories = response.data;
                console.log(this.categories);
            });
        },

        /**
         *
         **/
        checkCategory() {
            if (this.cat != '') {
                this.category = JSON.parse(this.cat);
            }
            if (this.subcat != '') {
                this.subcategory = JSON.parse(this.subcat);
            }
        },



        /**
         *
         **/
        getBrands() {
            this.brands_loaded = false;
            let params = this.setParams();

            axios.post('filter/getBrands', { params }).then(response => {
                this.brands_loaded = true;
                this.brands = response.data;

            });
        },


        /**
         *
         **/
        setQueryParam(type, value) {
            if (value.length > 3 && value.length < 5) {
                this.closeWindow();
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }

            if (value == '') {
                this.closeWindow();
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }
        },

        /**
         *
         **/
        setQueryParamOther(type, value) {
            this.closeWindow();
            this.$router.push({query: this.resolveQuery()}).catch(()=>{});

            if (value == '') {
                this.$router.push({query: this.resolveQuery()}).catch(()=>{});
            }
        },

        /**
         *
         **/
        resolveQuery() {
            let params = {
                start: this.start,
                end: this.end,

                brand: this.brand,

                page: this.page,
                pojam: this.search_query,
            };

            this.checkNoFollowQuery(params);

            return Object.entries(params).reduce((acc, [key, val]) => {
                if (!val) return acc
                return { ...acc, [key]: val }
            }, {});
        },

        /**
         *
         */
        checkNoFollowQuery(param) {
            if (param.nakladnik || param.autor || param.brand || param.start || param.end) {
                if (!document.querySelectorAll('meta[name="robots"]').length > 0) {
                    $('head').append('<meta name=robots content=noindex,nofollow>');
                }
            } else {
                if (document.querySelectorAll('meta[name="robots"]').length > 0) {
                    document.querySelector("[name='robots']").remove()
                }
            }
        },

        /**
         *
         **/
        checkQuery(params) {
            this.start = params.query.start ? params.query.start : '';
            this.end = params.query.end ? params.query.end : '';

            this.brand = params.query.brand ? params.query.brand : '';

            this.search_query = params.query.pojam ? params.query.pojam : '';
        },

        /**
         *
         */
        setParams() {
            let params = {
                ids: this.ids,
                group: this.group,
                cat: this.category ? this.category.id : this.cat,
                subcat: this.subcategory ? this.subcategory.id : this.subcat,
                brand: this.brand,
                search_brand: this.searchBrand,
                pojam: this.search_query
            };


            if (this.brand != '') {
                params.brand = this.brand;
            }


            return params;
        },

        /**
         *
         */
        preselect() {


            if (this.brand != '') {
                if ((this.brand).includes('+')) {
                    this.selectedBrands = (this.brand).split('+');
                } else {
                    this.selectedBrands = [this.brand];
                }
            }
        },

        /**
         *
         */
        cleanQuery() {
            this.$router.push({query: {}}).catch(()=>{});
            this.selectedBrands = [];
            this.start = '';
            this.end = '';
        },

        /**
         *
         */
        closeWindow() {
            $('#shop-sidebar').removeClass('collapse show');
        }
    }
};
</script>
