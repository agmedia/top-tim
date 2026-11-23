@once
<script>
(() => {
    document.querySelectorAll('[data-bulk-delete]').forEach(root => {
        if (root.dataset.bulkInitialized) return;
        root.dataset.bulkInitialized = 'true';

        const rows = Array.from(root.querySelectorAll('tbody input[type="checkbox"][data-bulk-row]'));
        const selectable = rows.filter(row => !row.disabled);
        const all = root.querySelector('[data-bulk-all]');
        const button = root.querySelector('[data-bulk-submit]');
        const label = root.querySelector('[data-bulk-label]');
        const count = root.querySelector('[data-bulk-count]');
        const errorBox = root.querySelector('[data-bulk-error]');
        let busy = false;

        const selected = () => selectable.filter(row => row.checked);
        const update = () => {
            const size = selected().length;
            count.textContent = size;
            all.checked = selectable.length > 0 && size === selectable.length;
            all.indeterminate = size > 0 && size < selectable.length;
            all.disabled = busy || selectable.length === 0;
            selectable.forEach(row => { row.disabled = busy; });
            button.disabled = busy || size === 0;
            label.textContent = busy ? 'Brisanje…' : 'Obriši označene';
        };
        const showError = message => {
            errorBox.textContent = message;
            errorBox.hidden = false;
        };
        const failureMessage = error => {
            const response = error.response;
            if (response?.status === 422) {
                const messages = Object.values(response.data?.errors || {}).flat();
                const detail = messages.find(message => typeof message === 'string');
                return detail ? 'Brisanje nije uspjelo. ' + detail : 'Provjerite označene stavke i pokušajte ponovno.';
            }
            if (response?.status === 419) return 'Vaša je prijava istekla. Osvježite stranicu i pokušajte ponovno.';
            if (response?.status === 403) return 'Nemate ovlasti za brisanje označenih stavki.';
            if (!response) return 'Veza sa serverom nije uspjela. Provjerite vezu i pokušajte ponovno.';
            return 'Brisanje nije uspjelo. Pokušajte ponovno.';
        };

        all.addEventListener('change', () => {
            if (busy) return;
            selectable.forEach(row => { row.checked = all.checked; });
            update();
        });
        rows.forEach(row => row.addEventListener('change', update));
        button.addEventListener('click', async () => {
            if (busy) return;
            const ids = selected().map(row => Number(row.value));
            if (!ids.length) return;
            if (ids.length > 50 || ids.some(id => !Number.isSafeInteger(id) || id < 1)) {
                showError('Označite najviše 50 valjanih stavki na ovoj stranici.');
                return;
            }
            if (!window.confirm('Jeste li sigurni da želite obrisati označene ' + root.dataset.bulkKind + ' (' + ids.length + ')? Ovu radnju nije moguće poništiti.')) return;

            busy = true;
            errorBox.hidden = true;
            update();
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || window.Laravel?.csrfToken || '';
                const response = await window.axios.post(root.dataset.bulkUrl, { ids }, {
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                if (response.data?.success !== true) throw { response };
                window.location.reload();
            } catch (error) {
                showError(failureMessage(error));
                busy = false;
                update();
            }
        });
        update();
    });
})();
</script>
@endonce
