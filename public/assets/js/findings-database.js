(() => {
    if (window.findingsDatabaseInitialized) {
        return;
    }

    window.findingsDatabaseInitialized = true;

    const allowedStatusTransitions = {
        open: ['open', 'review'],
        review: ['review', 'resolved'],
        resolved: ['resolved'],
    };

    const openDialog = (dialog) => {
        if (dialog && !dialog.open && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
    };

    const closeDialog = (dialog) => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    const syncDetailSelection = (detailSelect) => {
        const form = detailSelect.closest('form');
        const option = detailSelect.options[detailSelect.selectedIndex];

        form.querySelector('[data-finding-check]').value = option?.dataset.check || '';
        form.querySelector('[data-finding-machine]').value = option?.dataset.machine || '';
        form.querySelector('[data-finding-item]').value = option?.dataset.item || '';
    };

    const configureStatusDialog = (button) => {
        const dialog = document.querySelector('#finding-status-dialog');
        const form = dialog?.querySelector('[data-status-form]');

        if (!form) {
            return;
        }

        const currentStatus = button.dataset.currentStatus;
        const allowedStatuses = allowedStatusTransitions[currentStatus] ?? [];
        const select = form.querySelector('[data-status-select]');

        form.action = button.dataset.action;
        form.querySelector('[data-status-finding-label]').textContent = button.dataset.label || 'Pilih status terbaru.';

        Array.from(select.options).forEach((option) => {
            option.disabled = !allowedStatuses.includes(option.value);
        });
        select.value = allowedStatuses.at(-1) ?? currentStatus;

        openDialog(dialog);
    };

    const showPhoto = (button) => {
        const dialog = document.querySelector('#finding-photo-dialog');
        const image = dialog?.querySelector('[data-photo-dialog-image]');

        if (!image) {
            return;
        }

        image.src = button.dataset.photoUrl;
        dialog.querySelector('[data-photo-dialog-label]').textContent = button.dataset.photoLabel || 'Dokumentasi pemeriksaan';
        openDialog(dialog);
    };

    const previewUpload = (input) => {
        const wrapper = input.closest('.finding-upload');
        const preview = wrapper?.querySelector('[data-upload-preview]');
        const placeholder = wrapper?.querySelector('[data-upload-placeholder]');
        const [file] = input.files ?? [];

        if (!preview) {
            return;
        }

        if (preview.src.startsWith('blob:')) {
            URL.revokeObjectURL(preview.src);
        }

        preview.hidden = !file;
        placeholder.hidden = Boolean(file);
        preview.src = file ? URL.createObjectURL(file) : '';
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-finding-detail-select]').forEach((detailSelect) => {
            detailSelect.addEventListener('change', () => syncDetailSelection(detailSelect));

            if (detailSelect.value) {
                syncDetailSelection(detailSelect);
            }
        });

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-dialog-open]');
            const closeButton = event.target.closest('[data-dialog-close]');
            const statusButton = event.target.closest('[data-finding-status]');
            const photoButton = event.target.closest('[data-photo-preview]');

            if (openButton) {
                openDialog(document.getElementById(openButton.dataset.dialogOpen));
            } else if (closeButton) {
                closeDialog(closeButton.closest('dialog'));
            } else if (statusButton) {
                configureStatusDialog(statusButton);
            } else if (photoButton) {
                showPhoto(photoButton);
            } else if (event.target.matches('dialog.findings-dialog')) {
                closeDialog(event.target);
            }
        });

        document.querySelectorAll('[data-photo-input]').forEach((input) => {
            input.addEventListener('change', () => previewUpload(input));
        });

        const pageDialog = document.querySelector('[data-findings-page]')?.dataset.openDialog;

        if (pageDialog) {
            openDialog(document.getElementById(pageDialog));
        }

        const toast = document.querySelector('[data-findings-toast]');

        if (toast) {
            setTimeout(() => toast.classList.remove('is-visible'), 5000);
        }
    });
})();
