<template>
    <div class="offcanvas offcanvas-end" id="offcanvasRight" tabindex="-1" aria-labelledby="filter-title">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="filter-title">Filter</h5>
            <button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="Zatvori"></button>
        </div>
        <div class="offcanvas-body" data-simplebar>
            <div class="px-2 pt-2">
                <div class="widget mb-4 pb-4 border-bottom" v-if="!brand">
                    <h3 class="widget-title">Brendovi <span v-if="!brandsLoaded" class="spinner-border spinner-border-sm float-end"></span></h3>
                    <div class="input-group input-group-sm mb-2">
                        <input type="search" v-model="searchBrand" class="form-control" placeholder="Pretraži brendove" aria-label="Pretraži brendove">
                    </div>
                    <ul class="list-unstyled pt-1" style="max-height: 11rem; overflow-y: auto;">
                        <li v-for="item in filteredBrands" :key="item.id" class="mb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" :id="'filter-brand-' + item.id" :value="item.translation.slug" v-model="selectedBrands" @change="closeSidebar">
                                <label class="form-check-label" :for="'filter-brand-' + item.id">{{ item.title }}</label>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-if="!optionsLoaded" class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Učitavanje filtera</span></div>
                <div class="widget mb-4" v-if="sizes.length">
                    <h3 class="widget-title">Veličina</h3>
                    <ul class="list-unstyled pt-1" style="max-height: 11rem; overflow-y: auto;">
                        <li v-for="option in sizes" :key="option.id" class="mb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" :id="'filter-option-' + option.id" :value="String(option.id)" v-model="selectedOptions" @change="closeSidebar">
                                <label class="form-check-label" :for="'filter-option-' + option.id">{{ option.title }}</label>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="widget mb-4" v-if="colors.length">
                    <h3 class="widget-title">Boja</h3>
                    <div class="d-flex flex-wrap">
                        <div v-for="option in colors" :key="option.id" class="form-check form-option text-center mb-2 mx-1" :title="option.title">
                            <input class="form-check-input" type="checkbox" :id="'filter-' + option.id" :value="option.id" :aria-label="option.title" v-model="selectedOptions" @change="closeSidebar">
                            <label class="form-option-label rounded-circle" :for="'filter-' + option.id">
                                <span class="form-option-color rounded-circle" :style="option.style"></span>
                                <span class="visually-hidden">{{ option.title }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div v-if="loadError" class="mb-3" role="alert">
                    <p>Filter se nije uspio učitati.</p>
                    <button class="btn btn-sm btn-outline-primary" type="button" @click="loadFilters">Pokušaj ponovno</button>
                </div>
                <button type="button" class="btn btn-sm btn-primary mt-4" @click="cleanQuery"><i class="ci-trash"></i> Očisti sve</button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        ids: {type: String, default: ''},
        group: {type: String, default: ''},
        cat: {type: String, default: ''},
        subcat: {type: String, default: ''},
        brand: {type: String, default: ''},
        bladeoptions: {type: String, default: ''},
    },
    data() {
        return {
            brands: [],
            options: [],
            searchBrand: '',
            brandsLoaded: false,
            optionsLoaded: false,
            loadError: false,
        };
    },
    computed: {
        filteredBrands() {
            const search = this.searchBrand.trim().toLocaleLowerCase();
            return this.brands.filter(brand => brand.title.toLocaleLowerCase().includes(search));
        },
        sizes() {
            return this.options.filter(option => option.type === 'size');
        },
        colors() {
            return this.options.filter(option => option.type === 'color');
        },
        selectedBrands: {
            get() { return String(this.$route.query.brand || '').split('+').filter(Boolean); },
            set(value) { this.updateQuery('brand', value); },
        },
        selectedOptions: {
            get() {
                const selected = String(this.$route.query.option || '').split('+').filter(Boolean);
                const colors = this.colors.filter(color => color.option_ids.some(id => selected.includes(String(id))));
                const legacyIds = new Set(colors.flatMap(color => color.option_ids.map(String)));
                return [...selected.filter(id => !legacyIds.has(id)), ...colors.map(color => color.id)];
            },
            set(value) { this.updateQuery('option', [...new Set(value)]); },
        },
    },
    mounted() {
        this.loadFilters();
    },
    methods: {
        async loadFilters() {
            const params = {
                ids: this.ids,
                group: this.group,
                cat: this.categoryId(this.cat),
                subcat: this.categoryId(this.subcat),
                brand: this.brand,
                option: '',
                attribute: '',
            };
            this.loadError = false;
            this.optionsLoaded = false;
            this.brandsLoaded = false;
            const requests = [axios.post('filter/getOptions', {params}).then(response => {
                this.options = response.data;
            }).finally(() => { this.optionsLoaded = true; })];
            if (!this.brand) {
                requests.push(axios.post('filter/getBrands', {params}).then(response => {
                    this.brands = response.data;
                }).finally(() => { this.brandsLoaded = true; }));
            }
            const results = await Promise.allSettled(requests);
            this.loadError = results.some(result => result.status === 'rejected');
        },
        categoryId(value) {
            if (!value) return '';
            const category = JSON.parse(value);
            return typeof category === 'object' ? category.id : category;
        },
        updateQuery(key, values) {
            const query = {...this.$route.query};
            delete query.page;
            if (values.length) query[key] = values.join('+');
            else delete query[key];
            this.$router.push({query}).catch(() => {});
        },
        cleanQuery() {
            const query = {...this.$route.query};
            for (const key of ['brand', 'option', 'attribute', 'start', 'end', 'page']) delete query[key];
            this.searchBrand = '';
            this.$router.push({query}).catch(() => {});
        },
        closeSidebar() {
            const panel = document.getElementById('offcanvasRight');
            window.bootstrap?.Offcanvas.getInstance(panel)?.hide();
        },
    },
};
</script>
