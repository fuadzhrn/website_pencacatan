document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-finding-detail-select]').forEach((detailSelect) => {
        detailSelect.addEventListener('change', () => {
            const form = detailSelect.closest('form');
            const option = detailSelect.options[detailSelect.selectedIndex];

            form.querySelector('[data-finding-check]').value = option.dataset.check || '';
            form.querySelector('[data-finding-machine]').value = option.dataset.machine || '';
            form.querySelector('[data-finding-item]').value = option.dataset.item || '';
        });
    });
});
