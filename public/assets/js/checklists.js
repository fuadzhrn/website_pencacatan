function initializeDatabaseChecklists() {
    const root = document.querySelector('[data-checklists]');

    if (!root) {
        return;
    }

    const categoryDialog = document.querySelector('#category-form-dialog');
    const itemDialog = document.querySelector('#checklist-form-dialog');
    const categoryForm = document.querySelector('#category-form');
    const itemForm = document.querySelector('#checklist-form');

    const openDialog = (dialog) => {
        if (dialog && !dialog.open) {
            dialog.showModal();
        }
    };

    const closeDialog = (dialog) => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    const setValue = (form, name, value) => {
        const field = form?.elements.namedItem(name);

        if (field) {
            field.value = value ?? '';
        }
    };

    const configureForm = (form, action, method) => {
        form.action = action;
        setValue(form, '_method', method);
    };

    root.addEventListener('click', (event) => {
        const createCategoryButton = event.target.closest('[data-category-create]');
        const editCategoryButton = event.target.closest('[data-category-edit]');
        const createItemButton = event.target.closest('[data-checklist-create]');
        const editItemButton = event.target.closest('[data-checklist-edit]');

        if (createCategoryButton) {
            categoryForm.reset();
            configureForm(categoryForm, categoryForm.dataset.storeAction, 'POST');
            setValue(categoryForm, 'status', 'active');
            document.querySelector('[data-category-form-title]').textContent = 'Tambah Kategori';
            openDialog(categoryDialog);
        }

        if (editCategoryButton) {
            configureForm(categoryForm, editCategoryButton.dataset.action, 'PUT');
            setValue(categoryForm, 'name', editCategoryButton.dataset.name);
            setValue(categoryForm, 'frequency', editCategoryButton.dataset.frequency);
            setValue(categoryForm, 'description', editCategoryButton.dataset.description);
            setValue(categoryForm, 'status', editCategoryButton.dataset.status);
            document.querySelector('[data-category-form-title]').textContent = 'Edit Kategori';
            openDialog(categoryDialog);
        }

        if (createItemButton) {
            itemForm.reset();
            configureForm(itemForm, itemForm.dataset.storeAction, 'POST');
            setValue(itemForm, 'status', 'active');
            document.querySelector('[data-checklist-form-title]').textContent = 'Tambah Item Checklist';
            openDialog(itemDialog);
        }

        if (editItemButton) {
            configureForm(itemForm, editItemButton.dataset.action, 'PUT');
            setValue(itemForm, 'maintenance_category_id', editItemButton.dataset.categoryId);
            setValue(itemForm, 'item_name', editItemButton.dataset.itemName);
            setValue(itemForm, 'criteria', editItemButton.dataset.criteria);
            setValue(itemForm, 'input_type', editItemButton.dataset.inputType);
            setValue(itemForm, 'sort_order', editItemButton.dataset.sortOrder);
            setValue(itemForm, 'status', editItemButton.dataset.status);
            document.querySelector('[data-checklist-form-title]').textContent = 'Edit Item Checklist';
            openDialog(itemDialog);
        }
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(button.closest('dialog')));
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                closeDialog(dialog);
            }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    if (root.dataset.openForm === 'category') {
        openDialog(categoryDialog);
    }

    if (root.dataset.openForm === 'item') {
        openDialog(itemDialog);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDatabaseChecklists, { once: true });
} else {
    initializeDatabaseChecklists();
}
