const assert = require('node:assert/strict');
const {test} = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM, VirtualConsole} = require('jsdom');

const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(predicate) {
    for (let i = 0; i < 150; i++) {
        if (predicate()) return;
        await pause(10);
    }
    assert.fail('The catalog did not reach the expected state.');
}

test('built catalog uses one request per change, restores filters and ignores stale responses', async () => {
    const errors = [];
    const virtualConsole = new VirtualConsole();
    virtualConsole.on('error', error => errors.push(String(error)));
    virtualConsole.on('jsdomError', error => {
        if (!error.message.includes('Could not parse CSS stylesheet')) errors.push(error.message);
    });
    const dom = new JSDOM(`<div id="agapp">
        <products-view ids="[1,2]" group="" cat="" subcat="" author="" publisher="" brand_def="" admin="0"></products-view>
        <filter-side-view ids="[1,2]" group="" cat="" subcat=""></filter-side-view>
    </div>`, {url: 'https://shop.test/catalog?sort=price_up&page=2', runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole});
    const window = dom.window;
    window.trans = new Proxy({}, {get: (_, key) => String(key)});
    window.scrollTo = () => {};
    window.bootstrap = {Offcanvas: {getInstance: () => ({hide() {}})}};
    window.matchMedia = () => ({matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {}});
    const assets = path.resolve(__dirname, '../../public/js');
    window.eval(fs.readFileSync(path.join(assets, 'vendor/jquery.min.js'), 'utf8'));
    window.eval(fs.readFileSync(path.join(assets, 'cart.js'), 'utf8'));
    const requests = [];
    window.axios.defaults.adapter = async config => {
        const params = JSON.parse(config.data || '{}').params || {};
        requests.push({url: config.url, params, page: config.params?.page});
        let data;
        if (config.url === 'filter/getOptions') {
            data = [
                {id: 20, type: 'size', title: 'M'},
                {id: 'color-blue', type: 'color', title: 'Plava', option_ids: [10, 11], style: 'background-color: #1959d1'},
                {id: 'color-white', type: 'color', title: 'Bijela', option_ids: [11], style: 'background-color: #ffffff'},
            ];
        } else if (config.url === 'filter/getBrands') {
            data = [{id: 1, title: 'Macron', translation: {slug: 'macron'}}, {id: 2, title: 'Joma', translation: {slug: 'joma'}}];
        } else if (config.url === 'filter/getProducts') {
            const page = Number(config.params.page);
            await pause(params.option === 'color-blue' ? 80 : 5);
            const name = params.option || 'All products';
            data = {current_page: page, per_page: 1, total: 3, last_page: 3, from: page, to: page,
                next_page_url: page < 3 ? '/?page=' + (page + 1) : null,
                prev_page_url: page > 1 ? '/?page=' + (page - 1) : null,
                data: [{id: 1, name, image: '/product.webp', url: 'product', quantity: 5, main_price: 10, main_special: 10, main_price_text: '10 €', has_option: true, stars: 0,
                    anchor_price: params.option?.includes('color-white') ? null : {price: 12, formatted: '12,00 €', date: '10.09.2026.'}}]};
        } else {
            throw new Error('Unexpected request ' + config.url);
        }
        return {data, status: 200, statusText: 'OK', headers: {}, config};
    };
    const productRequests = () => requests.filter(request => request.url === 'filter/getProducts');
    try {
        await until(() => window.document.querySelector('.product-title') && window.document.querySelector('#filter-color-blue'));
        assert.equal(productRequests().length, 1, 'Opening a paginated/sorted URL loads products once.');
        assert.equal(productRequests()[0].page, '2');
        assert.equal(requests.length, 3, 'Only products, brands and options are requested.');
        assert.equal(window.document.querySelectorAll('.form-option-color').length, 2);
        assert.match(window.document.querySelector('.anchor-price').textContent, /Cijena na 10\.09\.2026\.: 12,00 €/);
        assert.ok(window.document.querySelector('.anchor-price').classList.contains('fs-xs'));

        const search = window.document.querySelector('input[type=search]');
        search.value = 'mac';
        search.dispatchEvent(new window.Event('input', {bubbles: true}));
        await pause(20);
        assert.equal(window.document.querySelectorAll('[id^=filter-brand-]').length, 1);
        assert.equal(requests.length, 3, 'Brand search filters the loaded brands locally.');

        window.document.querySelector('#filter-color-blue').click();
        await until(() => productRequests().length === 2);
        assert.equal(new URL(window.location.href).searchParams.get('page'), null);
        assert.equal(new URL(window.location.href).searchParams.get('sort'), 'price_up');
        assert.equal(window.document.querySelector('#filter-color-white').checked, false, 'Shared two-color variants do not select another base color.');
        window.document.querySelector('#filter-color-white').click();
        await until(() => window.document.querySelector('.product-title').textContent.includes('color-blue+color-white'));
        await pause(100);
        assert.match(window.document.querySelector('.product-title').textContent, /color-blue\+color-white/);
        assert.equal(productRequests().length, 3);
        assert.equal(window.document.querySelector('.anchor-price'), null, 'Missing or disabled anchor prices are not displayed.');

        window.history.back();
        await until(() => !window.document.querySelector('#filter-color-white').checked);
        await until(() => window.document.querySelector('.product-title').textContent.trim() === 'color-blue');
        assert.equal(productRequests().length, 4);
        assert.match(window.document.querySelector('.anchor-price').textContent, /12,00 €/);
        window.document.querySelectorAll('.pagination-page-nav a')[1].click();
        await until(() => new URL(window.location.href).searchParams.get('page') === '2');
        await pause(100);
        assert.equal(productRequests().length, 5, 'Pagination loads the selected page once.');
        assert.equal(productRequests()[4].page, '2');

        window.document.querySelector('#offcanvasRight button.btn-primary').click();
        await until(() => window.document.querySelector('.product-title').textContent.trim() === 'All products');
        assert.equal(window.document.querySelector('#filter-color-blue').checked, false);
        assert.equal(productRequests().length, 6, 'Clearing filters updates products without reloading the document.');
        assert.deepEqual(errors, []);
    } finally {
        window.close();
    }
});
