const assert = require('node:assert/strict');
const {test} = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM, VirtualConsole} = require('jsdom');
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

async function until(predicate) {
    for (let i = 0; i < 100; i++) {
        if (predicate()) return;
        await pause(10);
    }
    assert.fail('Pricing did not reach the expected state.');
}

test('quantity preview rounds cents and keeps the best eligible price', async () => {
    const source = fs.readFileSync(path.resolve(__dirname, '../../resources/js/front/cart/quantity-pricing.js'));
    const {quantityPrice} = await import('data:text/javascript;base64,' + source.toString('base64'));
    const tiers = [{min_quantity: 10, percentage: 12.5}, {min_quantity: 20, percentage: 20}];
    assert.equal(quantityPrice(106.9, 106.9, tiers, 9), 106.9);
    assert.equal(quantityPrice(106.9, 106.9, tiers, 10), 93.54);
    assert.equal(quantityPrice(106.9, 80, tiers, 20), 80);
    assert.equal(quantityPrice(100, 100, [{min_quantity: 1, percentage: 100}], 1), 0);
});

test('built product view recalculates personal price for quantities, brand totals and variant surcharges', async () => {
    const errors = [];
    const virtualConsole = new VirtualConsole();
    virtualConsole.on('error', error => errors.push(String(error)));
    virtualConsole.on('jsdomError', error => {
        if (!error.message.includes('Could not parse CSS stylesheet')) errors.push(error.message);
    });
    const dom = new JSDOM('<div id="agapp"><add-to-cart-btn></add-to-cart-btn></div>', {
        url: 'https://shop.test/product', runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole,
    });
    const {window} = dom;
    const component = window.document.querySelector('add-to-cart-btn');
    component.setAttribute('product', JSON.stringify({id: 1, brand_id: 1, main_price: 100, main_special: 95, campaign_price: 95,
        anchor_price: {price: 125, formatted: '125,00 €', date: '10.09.2026.'},
        quantity_discounts: [{min_quantity: 10, percentage: 10}, {min_quantity: 20, percentage: 15}]}));
    component.setAttribute('action', JSON.stringify({type: 'P', discount: 5}));
    component.setAttribute('available', '100');
    component.setAttribute('options', JSON.stringify({variant_anchors: {'0:200': {formatted: '131,90 €', date: '11.09.2026.'}}, size: {options: [{id: 10, option_id: 200, name: 'L', active: 1, quantity: 100, price: 6.9}]}}));
    window.trans = new Proxy({}, {get: (_, key) => String(key)});
    window.scrollTo = () => {};
    window.matchMedia = () => ({matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {}});
    const assets = path.resolve(__dirname, '../../public/js');
    window.eval(fs.readFileSync(path.join(assets, 'vendor/jquery.min.js'), 'utf8'));
    window.eval(fs.readFileSync(path.join(assets, 'cart.js'), 'utf8'));
    const settings = {'currency.list': [{status: true, main: true, value: 1, decimal_places: 2, symbol_left: '', symbol_right: ' €'}]};
    window.axios.defaults.adapter = async config => ({data: settings, status: 200, statusText: 'OK', headers: {}, config});
    try {
        await until(() => window.document.querySelector('.cart .h3'));
        const store = window.document.querySelector('#agapp').__vue_app__.config.globalProperties.$store;
        store.state.settings = settings;
        store.state.cart = {items: {
            a: {quantity: 8, associatedModel: {brand_id: 1}},
            b: {quantity: 100, associatedModel: {brand_id: 2}},
        }};
        const price = () => window.document.querySelector('.cart .h3').textContent.trim();
        await until(() => price().includes('95,00'));
        assert.match(window.document.querySelector('.cart .anchor-price').textContent, /Bazna cijena na 10\.09\.2026\.: 125,00 €/);
        assert.equal(window.document.querySelectorAll('.cart tbody tr').length, 2);
        const quantity = window.document.querySelector('.cart input[type=number]');
        quantity.value = '2';
        quantity.dispatchEvent(new window.Event('input', {bubbles: true}));
        await until(() => price().includes('90,00'));
        assert.match(window.document.querySelector('.cart strike').textContent, /100,00/);
        quantity.value = '12';
        quantity.dispatchEvent(new window.Event('input', {bubbles: true}));
        await until(() => price().includes('85,00'));
        quantity.value = '2';
        quantity.dispatchEvent(new window.Event('input', {bubbles: true}));
        const size = window.document.querySelector('#product-size');
        size.value = '10';
        size.dispatchEvent(new window.Event('change', {bubbles: true}));
        await until(() => price().includes('96,21'));
        store.state.cart = {items: {b: {quantity: 100, associatedModel: {brand_id: 2}}}};
        await until(() => price().includes('101,56'));
        assert.match(window.document.querySelector('.cart .anchor-price').textContent, /11\.09\.2026\.: 131,90 €/);
        assert.deepEqual(errors, []);
    } finally {
        window.close();
    }
});

test('built cart shows the server price and original price without waiting for currency settings', async () => {
    const errors = [];
    const virtualConsole = new VirtualConsole();
    virtualConsole.on('error', error => errors.push(String(error)));
    const dom = new JSDOM('<div id="agapp"><cart-view freeship="1000"></cart-view></div>', {
        url: 'https://shop.test/kosarica', runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole,
    });
    const {window} = dom;
    window.trans = new Proxy({}, {get: (_, key) => String(key)});
    window.scrollTo = () => {};
    const assets = path.resolve(__dirname, '../../public/js');
    window.eval(fs.readFileSync(path.join(assets, 'vendor/jquery.min.js'), 'utf8'));
    window.eval(fs.readFileSync(path.join(assets, 'cart.js'), 'utf8'));
    const settings = {'currency.list': [{main: true, value: 1, decimal_places: 2, symbol_right: ' €'}]};
    window.axios.defaults.adapter = async config => ({data: settings, status: 200, statusText: 'OK', headers: {}, config});
    try {
        await until(() => window.document.querySelector('#agapp').__vue_app__);
        const store = window.document.querySelector('#agapp').__vue_app__.config.globalProperties.$store;
        const item = {id: 1, name: 'Majica', quantity: 10, price: 100, price_text: '90,00 €', conditions: [],
            associatedModel: {quantity: 100}, attributes: {path: 'product', slika: '', options: {}, unit_price: 90, original_price_text: '100,00 €', quantity_discount: 10}};
        store.state.cart = {count: 10, total: 900, items: {1: item}};
        await until(() => window.document.querySelector('s'));
        assert.match(window.document.querySelector('.fs-lg.text-primary').textContent, /90,00/);
        assert.match(window.document.querySelector('s').textContent, /100,00/);
        assert.match(window.document.body.textContent, /Vaš količinski popust: 10 %/);
        store.state.cart = {count: 9, total: 900, items: {1: {...item, quantity: 9, price_text: '100,00 €',
            attributes: {...item.attributes, unit_price: 100, quantity_discount: 0}}}};
        await until(() => !window.document.querySelector('s'));
        assert.doesNotMatch(window.document.body.textContent, /Vaš količinski popust:/);
        assert.deepEqual(errors, []);
    } finally {
        window.close();
    }
});
