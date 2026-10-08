document.addEventListener('DOMContentLoaded', () => {
    const formDialog = document.querySelector('#checklist-form-dialog');
    const detailDialog = document.querySelector('#checklist-detail-dialog');
    const checklistForm = document.querySelector('#checklist-form');
    const formDialogTitle = document.querySelector('#checklist-form-title');
    const addChecklistButton = document.querySelector('[data-checklist-create]');
    const categoryTabs = document.querySelectorAll('[data-category-filter]');
    const checklistRows = document.querySelectorAll('[data-checklist-row]');
    const checklistCount = document.querySelector('[data-checklist-count]');
    const checklistRange = document.querySelector('[data-checklist-range]');
    const emptyRow = document.querySelector('[data-checklist-empty]');

    const formFields = {
        category: document.querySelector('#checklist-category'),
        group: document.querySelector('#checklist-group'),
        item: document.querySelector('#checklist-item'),
        criteria: document.querySelector('#checklist-criteria'),
        frequency: document.querySelector('#checklist-frequency'),
        inputType: document.querySelector('#checklist-input-type'),
        status: document.querySelector('#checklist-status'),
        note: document.querySelector('#checklist-note'),
    };

    const openDialog = (dialog) => {
        if (!dialog) {
            return;
        }

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
    };

    const closeDialog = (dialog) => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    const setFormValue = (field, value) => {
        if (field) {
            field.value = value ?? '';
        }
    };

    const updateVisibleRows = (category) => {
        let visibleCount = 0;

        checklistRows.forEach((row) => {
            const isVisible = category === 'Semua' || row.dataset.category === category;
            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (checklistCount) {
            checklistCount.textContent = `${visibleCount} data`;
        }

        if (checklistRange) {
            checklistRange.textContent = visibleCount > 0
                ? `Menampilkan 1–${visibleCount} dari ${visibleCount} item`
                : 'Tidak ada item ditampilkan';
        }

        if (emptyRow) {
            emptyRow.hidden = visibleCount !== 0;
        }
    };

    categoryTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            categoryTabs.forEach((candidate) => {
                const isSelected = candidate === tab;
                candidate.classList.toggle('is-active', isSelected);
                candidate.setAttribute('aria-selected', String(isSelected));
            });

            updateVisibleRows(tab.dataset.categoryFilter ?? 'Semua');
        });
    });

    addChecklistButton?.addEventListener('click', () => {
        checklistForm?.reset();
        setFormValue(formFields.category, 'Harian');
        setFormValue(formFields.frequency, 'daily');
        setFormValue(formFields.inputType, 'checklist');
        setFormValue(formFields.status, 'Aktif');

        if (formDialogTitle) {
            formDialogTitle.textContent = 'Tambah Item Checklist';
        }

        openDialog(formDialog);
    });

    document.querySelectorAll('[data-checklist-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            setFormValue(formFields.category, button.dataset.category);
            setFormValue(formFields.group, button.dataset.group);
            setFormValue(formFields.item, button.dataset.item);
            setFormValue(formFields.criteria, button.dataset.criteria);
            setFormValue(formFields.frequency, button.dataset.frequency);
            setFormValue(formFields.inputType, button.dataset.inputType);
            setFormValue(formFields.status, button.dataset.status);
            setFormValue(formFields.note, button.dataset.note);

            if (formDialogTitle) {
                formDialogTitle.textContent = 'Edit Item Checklist';
            }

            openDialog(formDialog);
        });
    });

    document.querySelectorAll('[data-checklist-detail]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-detail-field]').forEach((field) => {
                const fieldName = field.dataset.detailField;

                if (fieldName) {
                    field.textContent = button.dataset[fieldName] ?? '-';
                }
            });

            const categoryBadge = document.querySelector('[data-detail-category]');
            const statusBadge = document.querySelector('[data-detail-status]');
            const normalizedCategory = (button.dataset.category ?? '').toLowerCase();
            const normalizedStatus = button.dataset.status === 'Aktif' ? 'active' : 'inactive';

            if (categoryBadge) {
                categoryBadge.className = `checklist-category checklist-category--${normalizedCategory}`;
                categoryBadge.textContent = button.dataset.category ?? '-';
            }

            if (statusBadge) {
                statusBadge.className = `checklist-status checklist-status--${normalizedStatus}`;
                statusBadge.textContent = button.dataset.status ?? '-';
            }

            openDialog(detailDialog);
        });
    });

    checklistForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        closeDialog(formDialog);
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
});
