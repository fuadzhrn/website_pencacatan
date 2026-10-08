document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.querySelector('[data-schedule-filter]');
    const resetButton = document.querySelector('[data-filter-reset]');
    const scheduleRows = document.querySelectorAll('[data-schedule-row]');
    const scheduleCount = document.querySelector('[data-schedule-count]');
    const scheduleRange = document.querySelector('[data-schedule-range]');
    const emptyRow = document.querySelector('[data-schedule-empty]');
    const actionDialog = document.querySelector('#schedule-action-dialog');
    const dialogTitle = actionDialog?.querySelector('[data-dialog-title]');
    const dialogEyebrow = actionDialog?.querySelector('[data-dialog-eyebrow]');
    const dialogMessage = actionDialog?.querySelector('[data-dialog-message]');
    const dialogDetails = actionDialog?.querySelector('[data-dialog-details]');
    const dialogConfirm = actionDialog?.querySelector('[data-dialog-confirm]');

    const fields = {
        month: document.querySelector('#schedule-month'),
        year: document.querySelector('#schedule-year'),
        machine: document.querySelector('#schedule-machine'),
        frequency: document.querySelector('#schedule-frequency'),
        status: document.querySelector('#schedule-status'),
    };

    const updateTable = () => {
        const filters = Object.fromEntries(
            Object.entries(fields).map(([name, field]) => [name, field?.value ?? '']),
        );
        let visibleCount = 0;

        scheduleRows.forEach((row) => {
            const isVisible = Object.entries(filters).every(([name, value]) => {
                return value === '' || row.dataset[name] === value;
            });

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (scheduleCount) {
            scheduleCount.textContent = `${visibleCount} data`;
        }

        if (scheduleRange) {
            scheduleRange.textContent = visibleCount > 0
                ? `Menampilkan 1–${visibleCount} dari ${visibleCount} jadwal`
                : 'Tidak ada jadwal yang ditampilkan';
        }

        if (emptyRow) {
            emptyRow.hidden = visibleCount !== 0;
        }
    };

    const closeDialog = () => {
        if (actionDialog?.open) {
            actionDialog.close();
        }
    };

    const openDialog = () => {
        if (actionDialog && typeof actionDialog.showModal === 'function') {
            actionDialog.showModal();
        }
    };

    const fillDialogDetails = (button) => {
        actionDialog?.querySelectorAll('[data-dialog-field]').forEach((field) => {
            const fieldName = field.dataset.dialogField;

            if (fieldName) {
                field.textContent = button.dataset[fieldName] ?? '-';
            }
        });
    };

    const actionCopy = {
        create: {
            eyebrow: 'Jadwal baru',
            title: 'Tambah Jadwal',
            message: 'Form penambahan jadwal akan dihubungkan pada tahap CRUD dan database.',
            confirm: 'Mengerti',
        },
        detail: {
            eyebrow: 'Informasi jadwal',
            title: 'Detail Jadwal',
            message: 'Berikut ringkasan jadwal preventive maintenance yang dipilih.',
            confirm: 'Tutup',
        },
        edit: {
            eyebrow: 'Perubahan jadwal',
            title: 'Edit Jadwal',
            message: 'Fungsi perubahan data akan diaktifkan saat tahap CRUD dijalankan.',
            confirm: 'Mengerti',
        },
        start: {
            eyebrow: 'Pemeriksaan mesin',
            title: 'Mulai Pemeriksaan',
            message: 'Pemeriksaan akan diarahkan ke checklist digital pada tahap berikutnya.',
            confirm: 'Mengerti',
        },
    };

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        updateTable();
    });

    resetButton?.addEventListener('click', () => {
        filterForm?.reset();
        updateTable();
    });

    document.querySelectorAll('[data-schedule-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.scheduleAction ?? 'detail';
            const copy = actionCopy[action] ?? actionCopy.detail;
            const isCreateAction = action === 'create';

            if (dialogEyebrow) {
                dialogEyebrow.textContent = copy.eyebrow;
            }

            if (dialogTitle) {
                dialogTitle.textContent = copy.title;
            }

            if (dialogMessage) {
                dialogMessage.textContent = copy.message;
            }

            if (dialogConfirm) {
                dialogConfirm.textContent = copy.confirm;
            }

            if (dialogDetails) {
                dialogDetails.hidden = isCreateAction;
            }

            if (!isCreateAction) {
                fillDialogDetails(button);
            }

            openDialog();
        });
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeDialog);
    });

    actionDialog?.addEventListener('click', (event) => {
        if (event.target === actionDialog) {
            closeDialog();
        }
    });
});
