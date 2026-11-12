(() => {
    function initialize() {
        document.querySelectorAll('[data-discount-tiers]').forEach(editor => {
            const list = editor.querySelector('[data-tier-list]');
            const template = editor.querySelector('[data-tier-template]');
            const add = editor.querySelector('[data-add-tier]');
            function renumber() {
                const rows = list.querySelectorAll('[data-tier-row]');
                rows.forEach((row, index) => {
                    row.querySelectorAll('[data-field]').forEach(input => {
                        input.name = `tiers[${index}][${input.dataset.field}]`;
                        if (input.type !== 'hidden') input.id = `discount-level-${index}-${input.dataset.field}`;
                    });
                    row.querySelectorAll('[data-for]').forEach(label => {
                        label.htmlFor = `discount-level-${index}-${label.dataset.for}`;
                    });
                    row.querySelector('[data-remove-tier]').disabled = rows.length === 1;
                });
                add.disabled = rows.length >= 100;
            }
            add.addEventListener('click', () => {
                if (list.children.length >= 100) return;
                list.append(template.content.cloneNode(true));
                renumber();
                list.lastElementChild.querySelector('[data-field="min_quantity"]').focus();
            });
            list.addEventListener('click', event => {
                const button = event.target.closest('[data-remove-tier]');
                if (!button || list.children.length <= 1) return;
                button.closest('[data-tier-row]').remove();
                renumber();
            });
            renumber();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
})();
