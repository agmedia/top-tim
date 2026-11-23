const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');

const partial = fs.readFileSync(path.join(__dirname, '../../resources/views/back/layouts/partials/bulk-delete.blade.php'), 'utf8');
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];
const url = 'https://shop.test/admin/products?search=majica&status=active&page=3';
const settle = () => new Promise(resolve => setImmediate(resolve));

function markup(kind, size, firstId) {
    const rows = Array.from({ length: size }, (_, index) => `<tr><td><input type="checkbox" data-bulk-row value="${firstId + index}"></td><td><input type="checkbox" class="status-toggle" checked></td></tr>`).join('');
    return `<section data-bulk-delete data-bulk-url="/admin/${kind}/bulk-delete" data-bulk-kind="${kind}">
        <span data-bulk-count>0</span><button type="button" data-bulk-submit disabled><span data-bulk-label>Obriši označene</span></button>
        <div data-bulk-error hidden></div><table><thead><tr><th><input type="checkbox" data-bulk-all></th></tr></thead><tbody>${rows}</tbody></table>
    </section>`;
}

function page({ confirm = true, post = async () => ({ data: { success: true } }), size = 50 } = {}) {
    const dom = new JSDOM(`<meta name="csrf-token" content="test-csrf"><input id="unrelated" type="checkbox">${markup('products', size, 101)}${markup('actions', 12, 501)}`, { url });
    const calls = [], confirmations = [], reloads = [];
    const window = {
        confirm(message) { confirmations.push(message); return confirm; },
        axios: { post(...args) { calls.push(args); return post(...args); } },
        location: { href: url, reload() { reloads.push(this.href); } },
    };
    new Function('document', 'window', script)(dom.window.document, window);
    const roots = Array.from(dom.window.document.querySelectorAll('[data-bulk-delete]'));
    return {
        dom, calls, confirmations, reloads, roots,
        rows: root => Array.from(root.querySelectorAll('tbody [data-bulk-row]')),
        change(element, checked) { element.checked = checked; element.dispatchEvent(new dom.window.Event('change', { bubbles: true })); },
        button: root => root.querySelector('[data-bulk-submit]'),
        all: root => root.querySelector('[data-bulk-all]'),
        count: root => root.querySelector('[data-bulk-count]').textContent,
        error: root => root.querySelector('[data-bulk-error]'),
    };
}

test('select all affects only current-page rows, updates count and indeterminate, and ignores statuses and other lists', () => {
    const p = page();
    try {
        const [products, actions] = p.roots;
        assert.equal(p.button(products).disabled, true);
        p.change(p.rows(products)[0], true);
        assert.equal(p.count(products), '1');
        assert.equal(p.all(products).indeterminate, true);
        assert.equal(p.all(products).checked, false);
        p.change(p.all(products), true);
        assert.equal(p.count(products), '50');
        assert.equal(p.all(products).checked, true);
        assert.equal(p.all(products).indeterminate, false);
        assert.equal(p.rows(actions).filter(row => row.checked).length, 0);
        assert.equal(p.dom.window.document.getElementById('unrelated').checked, false);
        assert.ok(Array.from(products.querySelectorAll('.status-toggle')).every(row => row.checked));
        p.change(p.rows(products)[3], false);
        assert.equal(p.count(products), '49');
        assert.equal(p.all(products).indeterminate, true);
        p.change(p.all(products), false);
        assert.equal(p.count(products), '0');
        assert.equal(p.button(products).disabled, true);
        p.change(p.all(actions), true);
        assert.equal(p.count(actions), '12');
        assert.ok(Array.from(products.querySelectorAll('.status-toggle')).every(row => row.checked));
    } finally { p.dom.window.close(); }
});

test('cancel leaves selection intact and sends no mutation or reload', async () => {
    const p = page({ confirm: false });
    try {
        const root = p.roots[0];
        p.change(p.rows(root)[0], true);
        p.button(root).click();
        await settle();
        assert.equal(p.confirmations.length, 1);
        assert.match(p.confirmations[0], /\(1\)/);
        assert.equal(p.calls.length, 0);
        assert.equal(p.reloads.length, 0);
        assert.equal(p.count(root), '1');
        assert.equal(p.button(root).disabled, false);
    } finally { p.dom.window.close(); }
});

test('confirmed delete POSTs only selected numeric IDs with CSRF, blocks duplicate submission and reloads the same filtered URL', async () => {
    let finish;
    const pending = new Promise(resolve => { finish = resolve; });
    const p = page({ post: () => pending });
    try {
        const root = p.roots[0];
        p.change(p.rows(root)[0], true);
        p.change(p.rows(root)[2], true);
        p.button(root).click();
        p.button(root).dispatchEvent(new p.dom.window.Event('click'));
        assert.equal(p.calls.length, 1);
        assert.equal(p.confirmations.length, 1);
        assert.match(p.confirmations[0], /\(2\)/);
        assert.equal(p.button(root).disabled, true);
        assert.equal(p.all(root).disabled, true);
        assert.ok(p.rows(root).every(row => row.disabled));
        assert.deepEqual(p.calls[0], ['/admin/products/bulk-delete', { ids: [101, 103] }, {
            headers: { 'X-CSRF-TOKEN': 'test-csrf', Accept: 'application/json' },
        }]);
        finish({ data: { success: true, deleted: 2 } });
        await settle();
        assert.deepEqual(p.reloads, [url]);
    } finally { p.dom.window.close(); }
});

test('actions use their own endpoint and only selected action rows', async () => {
    const p = page();
    try {
        const [products, actions] = p.roots;
        p.change(p.rows(products)[0], true);
        p.change(p.all(actions), true);
        p.button(actions).click();
        await settle();
        assert.equal(p.calls[0][0], '/admin/actions/bulk-delete');
        assert.deepEqual(p.calls[0][1].ids, Array.from({ length: 12 }, (_, i) => 501 + i));
        assert.deepEqual(p.reloads, [url]);
    } finally { p.dom.window.close(); }
});

test('validation errors render as text, retain selection and re-enable retry', async () => {
    const p = page({ post: async () => { throw { response: { status: 422, data: { errors: { ids: ['<img src=x onerror=alert(1)> Stavka više ne postoji.'] } } } }; } });
    try {
        const root = p.roots[0];
        p.change(p.rows(root)[0], true);
        p.button(root).click();
        await settle();
        assert.equal(p.error(root).hidden, false);
        assert.match(p.error(root).textContent, /Stavka više ne postoji/);
        assert.equal(p.error(root).querySelector('img'), null);
        assert.equal(p.count(root), '1');
        assert.equal(p.button(root).disabled, false);
        assert.equal(p.all(root).disabled, false);
        assert.ok(p.rows(root).every(row => !row.disabled));
        assert.equal(p.reloads.length, 0);
        p.button(root).click();
        await settle();
        assert.equal(p.calls.length, 2);
    } finally { p.dom.window.close(); }
});

test('network failure shows a Croatian message and permits retry without reloading', async () => {
    const p = page({ post: async () => { throw new Error('Network Error'); } });
    try {
        const root = p.roots[1];
        p.change(p.rows(root)[0], true);
        p.button(root).click();
        await settle();
        assert.match(p.error(root).textContent, /Veza sa serverom nije uspjela/);
        assert.equal(p.button(root).disabled, false);
        assert.equal(p.reloads.length, 0);
    } finally { p.dom.window.close(); }
});

test('empty tables and oversized selections cannot trigger requests', async () => {
    const empty = page({ size: 0 });
    const tooMany = page({ size: 51 });
    try {
        assert.equal(empty.all(empty.roots[0]).disabled, true);
        assert.equal(empty.button(empty.roots[0]).disabled, true);
        tooMany.change(tooMany.all(tooMany.roots[0]), true);
        tooMany.button(tooMany.roots[0]).click();
        await settle();
        assert.match(tooMany.error(tooMany.roots[0]).textContent, /najviše 50/);
        assert.equal(tooMany.confirmations.length, 0);
        assert.equal(tooMany.calls.length, 0);
    } finally { empty.dom.window.close(); tooMany.dom.window.close(); }
});
