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
                                <h3 class="widget-title" >Brands<span v-if="!brands_loaded" class="spinner-border spinner-border-sm" style="float: right;"></span></h3>
                                <div class="input-group input-group-sm mb-2 autocomplete">
                                    <input type="search" v-model="searchBrand" class="form-control rounded-end pe-5" placeholder="Pretraži brandove"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                </div>
                                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11rem;" data-simplebar data-simplebar-auto-hide="false">
                                    <div class="simplebar-scroll-content">
                                        <div class="simplebar-content">
                                            <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-for="brand in brands">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :id="brand.translation.slug" v-bind:value="brand.translation.slug" v-model="selectedBrands" @click="closeSidebar">
                                                    <label class="form-check-label widget-filter-item-text" :for="brand.translation.slug">{{ brand.title }}</label>
                                                </div>
                                              <!--  <span class="fs-xs text-muted"><a :href="origin + brand.url">{{-- Number(brand.products_count).toLocaleString('hr-HR') --}}</a></span> -->
                                            </li>
                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Filter by Attributes -->
                <div class="col-lg-12" >
                    <div class=" mb-grid-gutter" v-if="attributes.length">
                        <div class=" px-2">
                            <div class="widget widget-filter mb-4 pb-4 border-bottom" v-if="show_attributes">
                                <h3 class="widget-title">Filteri<span v-if="!attributes_loaded" class="spinner-border spinner-border-sm" style="float: right;"></span></h3>
                                <div class="input-group input-group-sm mb-2 autocomplete" v-if="attributes.length > 6">
                                    <input type="search" v-model="searchAttribute" class="form-control rounded-end pe-5" placeholder="Pretraži filtere"><i class="ci-search position-absolute top-50 end-0 translate-middle-y fs-sm me-3"></i>
                                </div>
                                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11rem;" data-simplebar data-simplebar-auto-hide="false">
                                    <div class="simplebar-scroll-content">
                                        <div class="simplebar-content">
                                            <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-for="attribute in attributes" >
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" :id="attribute.id" v-bind:value="attribute.id" v-model="selectedAttributes" @click="closeSidebar">
                                                    <label class="form-check-label widget-filter-item-text" :for="attribute.id">{{ attribute.title }}</label>
                                                </div>
                                                <span class="fs-xs text-muted">{{ Number(attribute.products_count).toLocaleString('hr-HR') }}</span>
                                            </li>
                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <!-- Filter by Size-->
                    <div class=" mb-grid-gutter" v-if="options.length">
                        <div class=" px-2">
                            <div class="widget widget-filter">
                                <h3  class="widget-title"  v-if="show_size">Veličina <span v-if="!options_loaded" class="spinner-border spinner-border-sm" style="float: right;"></span></h3>
                                <ul class="widget-list widget-filter-list list-unstyled pt-1" style="max-height: 11rem;" data-simplebar data-simplebar-auto-hide="false">
                                    <div class="simplebar-scroll-content">
                                        <div class="simplebar-content">
                                            <template v-for="option in options">
                                                <li class="widget-filter-item d-flex justify-content-between align-items-center mb-1" v-if="option.type == 'size'">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" :id="option.id" :value="option.id" v-model="selectedOptions" @click="closeSidebar">
                                                        <label class="form-check-label widget-filter-item-text" :for="option.id">{{ option.title }}</label>
                                                    </div>

                                                </li>
                                            </template>
                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <!-- Filter by Color-->
                    <div class="mb-grid-gutter" v-if="options.length">
                        <div class=" px-2" >
                            <div class="widget widget-filter2">
                                <h3 class="widget-title" v-if="show_color">Boja <span v-if="!options_loaded" class="spinner-border spinner-border-sm" style="float: right;"></span></h3>
                                <div class="d-flex flex-wrap">
                                    <template v-for="option in options">
                                        <div class="form-check form-option text-center mb-2 mx-1" v-if="option.type == 'color'"  v-tooltip:top="option.title"  >
                                            <input class="form-check-input" type="checkbox" :id="option.id" :value="option.id" v-model="selectedOptions" @click="closeSidebar">
                                            <label class="form-option-label rounded-circle" :for="option.id"><span class="form-option-color rounded-circle" :style="option.style "></span></label>
                                            <label class="d-block fs-xs text-muted mt-n1" :for="option.id"></label>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              <!--  <div class="col-lg-12">
                    <button type="button" class="btn btn-sm btn-primary mt-4" v-on:click="cleanQuery"><i class=" ci-trash"></i> Očisti sve</button>
                </div> -->
            </div>
        </div>
    </div>
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
    props: {
        ids: String,
        group: String,
        cat: String,
        subcat: String,
        bladeoptions:String,
    },

    //
    data() {
        return {
            categories: [],
            category: null,
            subcategory: null,
            brands: [],
            attributes: [],
            options: [],
            selectedBrands: [],
            selectedAttributes: [],
            selectedOptions: [],
            start: '',
            end: '',
            autor: '',
            brand: '',
            attribute: '',
            option: '',
            nakladnik: '',
            search_query: '',
            searchBrand: '',
            searchAttribute: '',
            searchOption: '',
            show_brands: false,
            show_attributes: false,
            show_size: false,
            show_color: false,
            show_options: false,
            brands_loaded: false,
            attributes_loaded: false,
            options_loaded: false,
            origin: location.origin + '/',
            trans: window.trans,
            activeColor: 'black',
            fontSize: 12,
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
            if (value.length > 2 || value === '') {
                return this.getBrands();
            }
        },
        //
        selectedAttributes(value) {
            this.attribute = value.join('+');
            this.setQueryParamOther('attribute', this.attribute);
        },
        searchAttribute(value) {
            if (value.length > 2 || value === '') {
                return this.getAttributes();
            }
        },
        //
        selectedOptions(value) {
            this.option = value.join('+');
            this.setQueryParamOther('option', this.option);
        },
        searchOption(value) {
            if (value.length > 0 || value === '') {
                return this.getOptions();
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

        if (this.brand === '') {
            this.show_brands = true;
            this.getBrands();
        }
        if (this.attribute === '') {
            this.show_attributes = true;
           // this.getAttributes();
        }

        if (this.option === '') {
            this.show_options = true;
            this.getOptions();
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
            if (this.cat !== '') {
                this.category = JSON.parse(this.cat);
            }
            if (this.subcat !== '') {
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
        getAttributes() {
            this.attributes_loaded = false;
            let params = this.setParams();

            axios.post('filter/getAttributes', { params }).then(response => {
                this.attributes_loaded = true;
                this.attributes = response.data;

                console.log('attributi')
                console.log(response.data)
            });
        },

        /**
         *
         **/
        getOptions() {
            this.options_loaded = false;
            let params = this.setParams();
            axios.post('filter/getOptions', { params }).then(response => {
                this.options_loaded = true;
                this.options = response.data;

                for (const element of this.options) { // You can use `let` instead of `const` if you like
                   if(element.type ==='size'){
                       this.show_size = true;
                   }

                }

                for (const element of this.options) { // You can use `let` instead of `const` if you like
                    if(element.type ==='color'){
                        this.show_color = true;
                    }
                }



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

            if (value === '') {
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

            if (value === '') {
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
                attribute: this.attribute,
                option: this.option,
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
            if (param.nakladnik || param.autor || param.option || param.brand || param.attribute || param.start || param.end) {
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
            this.attribute = params.query.attribute ? params.query.attribute : '';
            this.option = params.query.option ? params.query.option : '';

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
                attribute: this.attribute,
                option: this.option,
                search_brand: this.searchBrand,
                search_attribute: this.searchAttribute,
                search_option: this.searchOption,
                pojam: this.search_query
            };

            if (this.brand !== '') {
                params.brand = this.brand;
            }

            if (this.attribute !== '') {
                params.attribute = this.attribute;
            }

            if (this.option !== '') {
                params.option = this.option;
            }


            return params;
        },

        /**
         *
         */
        preselect() {
            if (this.brand !== '') {
                if ((this.brand).includes('+')) {
                    this.selectedBrands = (this.brand).split('+');
                } else {
                    this.selectedBrands = [this.brand];
                }
            }

            if (this.attribute !== '') {
                if ((this.attribute).includes('+')) {
                    this.selectedAttributes = (this.attribute).split('+');
                } else {
                    this.selectedAttributes = [this.attribute];
                }
            }

            if (this.option !== '') {
                if ((this.option).includes('+')) {
                    this.selectedOption = (this.option).split('+');
                } else {
                    this.selectedOption = [this.option];
                }
            }
        },

        /**
         *
         */
        cleanQuery() {
            this.$router.push({query: {}}).catch(()=>{});
            this.selectedBrands = [];
            this.selectedAttributes = [];
            this.selectedOptions = [];
            this.start = '';
            this.end = '';
           // window.location.replace(location.pathname);
        },

        /**
         *
         */
        closeWindow() {
            $('#shop-sidebar').removeClass('collapse show');
        },

        closeSidebar() {


            let myOffcanvas = document.getElementById('offcanvasRight');
            let bsOffcanvas = bootstrap.Offcanvas.getInstance(myOffcanvas);
            bsOffcanvas.hide();

        }
    }
};
</script>
