const formDialog = document.querySelector('#machine-form-dialog');
const detailDialog = document.querySelector('#machine-detail-dialog');
const machineForm = document.querySelector('#machine-form');
const formTitle = document.querySelector('#machine-form-title');

const formFields = {
    name: document.querySelector('#machine-name'),
    brand: document.querySelector('#machine-brand'),
    type: document.querySelector('#machine-type'),
    serial: document.querySelector('#machine-serial'),
    location: document.querySelector('#machine-location'),
    airport: document.querySelector('#machine-airport'),
    status: document.querySelector('#machine-status'),
};

const openDialog = (dialog) => {
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
};

const closeDialog = (dialog) => {
    if (dialog?.open) {
        dialog.close();
    }
};

const setFieldValue = (fieldName, value = '') => {
    if (formFields[fieldName]) {
        formFields[fieldName].value = value;
    }
};

document.querySelector('[data-machine-create]')?.addEventListener('click', () => {
    machineForm?.reset();
    setFieldValue('airport', 'UPBU Malikussaleh');

    if (formTitle) {
        formTitle.textContent = 'Tambah Mesin';
    }

    openDialog(formDialog);
});

document.querySelectorAll('[data-machine-edit]').forEach((button) => {
    button.addEventListener('click', () => {
        Object.keys(formFields).forEach((fieldName) => {
            setFieldValue(fieldName, button.dataset[fieldName]);
        });

        if (formTitle) {
            formTitle.textContent = 'Edit Mesin';
        }

        openDialog(formDialog);
    });
});

document.querySelectorAll('[data-machine-detail]').forEach((button) => {
    button.addEventListener('click', () => {
        detailDialog?.querySelectorAll('[data-detail-field]').forEach((field) => {
            const fieldName = field.dataset.detailField;
            field.textContent = button.dataset[fieldName] || '-';
        });

        const status = detailDialog?.querySelector('[data-detail-status]');

        if (status) {
            const statusModifier = button.dataset.status === 'Aktif'
                ? 'active'
                : button.dataset.status === 'Maintenance'
                    ? 'maintenance'
                    : 'inactive';

            status.className = `machine-status machine-status--${statusModifier}`;
            status.textContent = button.dataset.status || '-';
        }

        openDialog(detailDialog);
    });
});

document.querySelectorAll('[data-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => {
        closeDialog(button.closest('dialog'));
    });
});

[formDialog, detailDialog].forEach((dialog) => {
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog(dialog);
        }
    });
});

machineForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    closeDialog(formDialog);
});
