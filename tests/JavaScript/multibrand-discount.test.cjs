const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const template = fs.readFileSync(path.resolve(__dirname, '../../resources/views/back/discount/edit.blade.php'), 'utf8');
const source = template.match(/<script>\s*([\s\S]*?)<\/script>/)[1]
    .replace(/@json\(route\('discounts\.users'\)\)/g, '"/admin/discounts/users"');
const tick = () => new Promise(resolve => setImmediate(resolve));

async function setup() {
    const dom = new JSDOM(`<form id="discount-form">
        <select id="discount-target" name="target"><option value="user">Korisnika</option><option value="group">Grupu korisnika</option><option value="everyone" selected>Svima</option></select>
        <small id="discount-everyone-help">Vrijedi za goste i sve prijavljene kupce.</small>
        <div id="discount-user-field"><select id="discount-user" name="user_id"><option value="21" selected>Test kupac</option></select></div>
        <div id="discount-group-field"><select id="discount-group" name="user_group_id"><option value="3" selected>Test grupa</option></select></div>
        <select id="discount-brand" name="brand_ids[]" multiple required><option value="7" selected>Zeus</option><option value="9" selected>Macron</option><option value="13">Joma</option></select>
        <input name="tiers[0][min_quantity]" value="10" required><input name="tiers[0][percentage]" value="8" required>
    </form>`, {runScripts: 'outside-only', url: 'https://shop.test/admin/discounts/1/edit'});
    const {window} = dom;
    window.eval(fs.readFileSync(require.resolve('jquery/dist/jquery.js'), 'utf8'));
    window.eval(fs.readFileSync(path.resolve(__dirname, '../../public/js/plugins/select2/js/select2.full.min.js'), 'utf8'));
    window.eval(source);
    const deadline = Date.now() + 2000;
    while (!window.jQuery('#discount-brand').data('select2')) {
        if (Date.now() > deadline) throw new Error('The actual discount form did not initialize Select2.');
        await tick();
    }
    return {dom, window, $: window.jQuery,
        get: id => window.document.getElementById(id),
        data: () => new window.FormData(window.document.getElementById('discount-form'))};
}

function assertEveryone(p) {
    assert.equal(p.get('discount-target').value, 'everyone');
    for (const field of ['user', 'group']) {
        assert.equal(p.get(`discount-${field}-field`).hidden, true);
        assert.equal(p.get(`discount-${field}`).disabled, true);
        assert.equal(p.get(`discount-${field}`).required, false);
    }
    assert.equal(p.get('discount-everyone-help').hidden, false);
    assert.equal(p.data().has('user_id'), false);
    assert.equal(p.data().has('user_group_id'), false);
}

test('actual form keeps preselected brands, adds another through Select2, and preserves everyone and shared tiers', async () => {
    const p = await setup();
    try {
        assert.deepEqual(p.data().getAll('brand_ids[]'), ['7', '9']);
        assertEveryone(p);
        p.$('#discount-brand').select2('open');
        const option = Array.from(p.window.document.querySelectorAll('.select2-results__option')).find(node => node.textContent === 'Joma');
        assert.ok(option, 'The third brand is selectable in the actual dropdown.');
        p.$(option).trigger('mouseup');
        await tick();
        assert.deepEqual(p.data().getAll('brand_ids[]'), ['7', '9', '13']);
        assert.equal(p.$('#discount-brand').select2('isOpen'), true);
        assertEveryone(p);
        assert.equal(p.data().get('tiers[0][min_quantity]'), '10');
        assert.equal(p.data().get('tiers[0][percentage]'), '8');
        assert.equal(p.data().has('brand_id'), false);
        assert.equal(p.get('discount-brand').required, true);
        assert.equal(p.$('#discount-brand').data('select2').options.get('maximumSelectionLength'), 100);
    } finally { p.dom.window.close(); }
});

test('target changes use the real event handler without losing multiple brands or leaking private IDs', async () => {
    const p = await setup();
    try {
        p.$('#discount-target').val('group').trigger('change');
        await tick();
        assert.equal(p.get('discount-group').required, true);
        assert.equal(p.data().get('user_group_id'), '3');
        assert.equal(p.data().has('user_id'), false);
        p.$('#discount-brand').val(['9', '13']).trigger('change');
        p.$('#discount-target').val('everyone').trigger('change');
        await tick();
        assertEveryone(p);
        assert.deepEqual(p.data().getAll('brand_ids[]'), ['9', '13']);
        p.$('#discount-target').val('user').trigger('change');
        await tick();
        assert.equal(p.get('discount-user').required, true);
        assert.equal(p.data().get('user_id'), '21');
        assert.equal(p.data().has('user_group_id'), false);
        assert.deepEqual(p.data().getAll('brand_ids[]'), ['9', '13']);
    } finally { p.dom.window.close(); }
});
