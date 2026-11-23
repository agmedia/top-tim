const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const template = fs.readFileSync(path.resolve(__dirname, '../../resources/views/back/discount/edit.blade.php'), 'utf8');
const selectTarget = template.match(/function selectTarget\(\) \{[\s\S]*?\n            \}/)[0];
const settle = () => new Promise(resolve => setImmediate(resolve));

function setup() {
    const dom = new JSDOM(`<form><select id="discount-target" name="target"><option value="user">Korisnika</option><option value="group">Grupu korisnika</option><option value="everyone">Svima</option></select>
        <small id="discount-everyone-help" hidden>Vrijedi za goste i sve prijavljene kupce.</small>
        <div id="discount-user-field"><select id="discount-user" name="user_id"><option value="21" selected>Test kupac</option></select></div>
        <div id="discount-group-field"><select id="discount-group" name="user_group_id"><option value="3" selected>Test grupa</option></select></div>
        <select id="discount-brand" name="brand_id" required><option value="7" selected>Test brend</option></select>
        <input name="tiers[0][min_quantity]" value="10" required><input name="tiers[0][percentage]" value="8" required>
    </form>`, {runScripts: 'outside-only'});
    const {window} = dom;
    window.eval(fs.readFileSync(require.resolve('jquery/dist/jquery.js'), 'utf8'));
    window.eval(fs.readFileSync(path.resolve(__dirname, '../../public/js/plugins/select2/js/select2.full.min.js'), 'utf8'));
    window.jQuery('#discount-user, #discount-group, #discount-brand').select2();
    window.eval(`${selectTarget}\nwindow.updateDiscountTarget = selectTarget;`);
    const set = value => {
        window.document.getElementById('discount-target').value = value;
        window.updateDiscountTarget();
    };
    return {dom, window, set, get: id => window.document.getElementById(id), data: () => new window.FormData(window.document.querySelector('form'))};
}

test('everyone hides and disables both actual Select2 controls, omits private IDs and preserves brand and tiers', async () => {
    const p = setup();
    try {
        p.set('everyone');
        await settle();
        for (const field of ['user', 'group']) {
            assert.equal(p.get(`discount-${field}-field`).hidden, true);
            assert.equal(p.get(`discount-${field}`).disabled, true);
            assert.equal(p.get(`discount-${field}`).required, false);
            assert.equal(p.window.jQuery(`#discount-${field}`).next('.select2-container').hasClass('select2-container--disabled'), true);
        }
        assert.equal(p.get('discount-everyone-help').hidden, false);
        const data = p.data();
        assert.equal(data.get('target'), 'everyone');
        assert.equal(data.has('user_id'), false);
        assert.equal(data.has('user_group_id'), false);
        assert.equal(data.get('brand_id'), '7');
        assert.equal(data.get('tiers[0][min_quantity]'), '10');
        assert.equal(data.get('tiers[0][percentage]'), '8');
    } finally { p.dom.window.close(); }
});

test('switching between everyone, user and group restores only the selected private target', async () => {
    const p = setup();
    try {
        for (const target of ['user', 'everyone', 'group', 'everyone', 'user']) {
            p.set(target);
            await settle();
            for (const field of ['user', 'group']) {
                const active = field === target;
                assert.equal(p.get(`discount-${field}-field`).hidden, !active);
                assert.equal(p.get(`discount-${field}`).disabled, !active);
                assert.equal(p.get(`discount-${field}`).required, active);
            }
            const data = p.data();
            assert.equal(data.has('user_id'), target === 'user');
            assert.equal(data.has('user_group_id'), target === 'group');
            assert.equal(data.get('brand_id'), '7');
            assert.equal(data.get('tiers[0][min_quantity]'), '10');
            assert.equal(data.get('tiers[0][percentage]'), '8');
        }
        assert.equal(p.data().get('user_id'), '21');
        assert.equal(p.get('discount-everyone-help').hidden, true);
    } finally { p.dom.window.close(); }
});
