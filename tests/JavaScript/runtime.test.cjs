const assert = require('node:assert/strict');
const { test } = require('node:test');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM, VirtualConsole } = require('jsdom');

const assets = path.resolve(__dirname, '../../public/js');
const emptyCart = { count: 0, items: {}, total: 0, secondary_price: false };
const item = {
    id: '42', name: 'Test product', quantity: 1, conditions: {},
    attributes: { path: 'product/42', slika: '/media/test.png' },
    associatedModel: { main_price_text: '15.00 €', secondary_price: false },
};
const fullCart = { ...emptyCart, count: 1, total: 15, items: { 42: item } };

async function waitFor(predicate) {
    const deadline = Date.now() + 4000;
    while (Date.now() < deadline) {
        if (predicate()) return;
        await new Promise(resolve => setTimeout(resolve, 15));
    }
    assert.fail('The built user interface did not reach the expected state.');
}

async function withPage(html, bundle, run) {
    const requests = [];
    const server = http.createServer((request, response) => {
        let body = '';
        request.on('data', chunk => { body += chunk; });
        request.on('end', () => {
            requests.push({ path: request.url, method: request.method, body });
            const data = request.url.endsWith('/settings/get')
                ? { 'currency.list': [{ main: true, value: 1, decimal_places: 2, symbol_right: ' €' }] }
                : request.url.endsWith('/cart/check') ? { cart: emptyCart }
                : request.url.endsWith('/cart/add') ? fullCart : emptyCart;
            response.writeHead(200, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify(data));
        });
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const errors = [];
    const console = new VirtualConsole();
    console.on('error', (...messages) => errors.push(messages.map(String).join(' ')));
    console.on('jsdomError', error => {
        // jsdom does not parse all of SweetAlert's current CSS syntax.
        if (!error.message.includes('Could not parse CSS stylesheet')) errors.push(error.message);
    });
    const dom = new JSDOM(html, {
        url: `http://127.0.0.1:${server.address().port}/catalog?brand=test`,
        runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole: console,
    });
    const window = dom.window;
    window.trans = new Proxy({}, { get: (target, key) => String(key) });
    window.scrollTo = () => {};
    window.matchMedia = () => ({ matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} });
    window.localStorage.setItem('pk_cart', JSON.stringify(emptyCart));
    try {
        window.eval(fs.readFileSync(path.join(assets, 'vendor/jquery.min.js'), 'utf8'));
        window.eval(fs.readFileSync(path.join(assets, bundle), 'utf8'));
        await run(window, requests);
        assert.deepEqual(errors, [], 'The built application reported an uncaught error.');
    } finally {
        window.close();
        server.closeAllConnections();
        await new Promise(resolve => server.close(resolve));
    }
}

test('built cart keeps add-to-cart, Vuex state, currency settings and pagination working', async () => {
    const pagination = JSON.stringify({ current_page: 1, last_page: 3, per_page: 10, total: 30, next_page_url: '/?page=2' });
    await withPage(`<div id="agapp">
        <cart-nav-icon carturl="/kosarica"></cart-nav-icon>
        <add-to-cart-btn-simple id="42" available="5"></add-to-cart-btn-simple>
        <pagination :data='${pagination}'></pagination>
    </div>`, 'cart.js', async (window, requests) => {
        await waitFor(() => window.document.querySelector('button.btn-shadow'));
        assert.equal(window.document.querySelector('.navbar-tool-label').textContent, '0');
        assert.equal(window.document.querySelectorAll('.pagination-page-nav').length, 3);
        window.document.querySelector('button.btn-shadow').click();
        await waitFor(() => window.document.querySelector('.navbar-tool-label').textContent === '1');
        await waitFor(() => window.document.querySelector('.fs-base.ms-1')?.textContent === '15.00 €');
        assert.ok(requests.some(request => request.path.endsWith('/settings/get')));
        const addition = requests.find(request => request.path.endsWith('/cart/add'));
        assert.equal(addition.method, 'POST');
        assert.deepEqual(JSON.parse(addition.body), { item: { id: '42', quantity: 1 } });
        assert.equal(JSON.parse(window.localStorage.getItem('pk_cart')).count, 1);
        assert.equal(window.location.search, '?brand=test');
    });
});

test('built rating component keeps the submitted star value synchronized', async () => {
    await withPage('<div id="ag-star-rating-component"><ag-star-rating value="4" increment="1"></ag-star-rating></div>',
        'ag-star-rating.js', async window => {
            await waitFor(() => window.document.querySelector('input[name="stars"]'));
            assert.equal(window.document.querySelector('input[name="stars"]').value, '4');
            const star = window.document.querySelector('svg.vue-star-rating-star');
            star.getBoundingClientRect = () => ({ left: 0, top: 0, width: 50, height: 50, right: 50, bottom: 50 });
            star.dispatchEvent(new window.MouseEvent('click', { clientX: 49, bubbles: true }));
            await waitFor(() => window.document.querySelector('input[name="stars"]').value === '1');
        });
});
