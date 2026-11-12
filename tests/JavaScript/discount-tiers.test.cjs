const assert = require('node:assert/strict');
const {test} = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const row = (id = '', quantity = '', percentage = '') => `<div data-tier-row>
    <input type="hidden" data-field="id" value="${id}">
    <label data-for="min_quantity">Od komada</label><input data-field="min_quantity" type="number" value="${quantity}">
    <label data-for="percentage">Popust</label><input data-field="percentage" type="number" value="${percentage}">
    <input type="hidden" data-field="active" value="0"><label data-for="active"><input type="checkbox" data-field="active" value="1" checked>Aktivno</label>
    <button type="button" data-remove-tier>Ukloni</button></div>`;

test('admin adds and removes levels while preserving IDs, values and distinct field names', async () => {
    const dom = new JSDOM(`<form><div data-discount-tiers><div data-tier-list>${row(11, 5, 5)}${row(12, 10, 10)}</div>
        <template data-tier-template>${row()}</template><button type="button" data-add-tier>Dodaj razinu</button></div></form>`,
        {runScripts: 'outside-only'});
    const {window} = dom;
    window.eval(fs.readFileSync(path.resolve(__dirname, '../../public/js/back/discount-tiers.js'), 'utf8'));
    if (window.document.readyState === 'loading') {
        await new Promise(resolve => window.document.addEventListener('DOMContentLoaded', resolve, {once: true}));
    }
    try {
        const list = window.document.querySelector('[data-tier-list]');
        const add = window.document.querySelector('[data-add-tier]');
        add.click();
        assert.equal(list.children.length, 3);
        const last = list.lastElementChild;
        last.querySelector('[data-field="min_quantity"]').value = '20';
        last.querySelector('[data-field="percentage"]').value = '15';
        assert.equal(window.document.activeElement, last.querySelector('[data-field="min_quantity"]'));
        list.children[1].querySelector('[data-remove-tier]').click();
        assert.equal(list.children.length, 2);
        const form = new window.FormData(window.document.querySelector('form'));
        assert.equal(form.get('tiers[0][id]'), '11');
        assert.equal(form.get('tiers[0][min_quantity]'), '5');
        assert.equal(form.get('tiers[1][id]'), '');
        assert.equal(form.get('tiers[1][min_quantity]'), '20');
        assert.equal(form.get('tiers[1][percentage]'), '15');
        assert.deepEqual(form.getAll('tiers[1][active]'), ['0', '1']);
        assert.equal(form.has('tiers[2][min_quantity]'), false);
        for (const label of list.querySelectorAll('[data-for]')) {
            assert.ok(window.document.getElementById(label.htmlFor), 'Each label points at its renumbered input.');
        }
        list.lastElementChild.querySelector('[data-remove-tier]').click();
        const only = list.querySelector('[data-remove-tier]');
        assert.equal(only.disabled, true);
        only.click();
        assert.equal(list.children.length, 1);
    } finally {
        window.close();
    }
});
