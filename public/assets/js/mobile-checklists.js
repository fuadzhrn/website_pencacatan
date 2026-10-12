export function filterChecklistItems(items, filters) {
    const query = (filters.query ?? '').trim().toLocaleLowerCase('id-ID');

    return items.filter((item) => {
        const matchesCategory = !filters.category || item.category === filters.category;
        const matchesStatus = !filters.status || item.status === filters.status;
        const searchableText = [
            item.name,
            item.criteria,
            item.inputType,
            item.note,
        ].join(' ').toLocaleLowerCase('id-ID');

        return matchesCategory && matchesStatus && (!query || searchableText.includes(query));
    });
}

export function summarizeChecklistItems(items) {
    return {
        categories: new Set(items.map((item) => item.category)).size,
        total: items.length,
        active: items.filter((item) => item.status === 'active').length,
        inactive: items.filter((item) => item.status === 'inactive').length,
    };
}

export function upsertChecklistItem(items, checklistItem) {
    const itemIndex = items.findIndex((item) => item.id === checklistItem.id);

    if (itemIndex < 0) {
        return [...items, checklistItem];
    }

    return items.map((item, index) => index === itemIndex ? checklistItem : item);
}

export function createSaveMessage(checklistItem, isEditing) {
    const action = isEditing ? 'diperbarui' : 'ditambahkan';

    return `${checklistItem.name} berhasil ${action}.`;
}

export function getTrappedFocusIndex(currentIndex, focusableCount, backwards) {
    if (focusableCount <= 0) {
        return -1;
    }

    if (currentIndex < 0) {
        return backwards ? focusableCount - 1 : 0;
    }

    return backwards
        ? (currentIndex - 1 + focusableCount) % focusableCount
        : (currentIndex + 1) % focusableCount;
}

export function shouldCloseMobileSheet(viewportWidth) {
    return viewportWidth > 768;
}

function initializeMobileChecklists() {
    const root = document.querySelector('[data-mobile-checklists]');

    if (!root) {
        return;
    }

    const categories = [...root.querySelectorAll('[data-mobile-checklist-category]')];
    const itemForm = root.querySelector('[data-mobile-checklist-form]');
    const categoryForm = root.querySelector('[data-mobile-category-form]');
    let activeSheet = null;
    let focusBeforeSheet = null;

    const setExpanded = (category, expanded) => {
        const toggle = category.querySelector('[data-mobile-checklist-category-toggle]');
        const panel = category.querySelector('[data-mobile-checklist-category-panel]');
        toggle?.setAttribute('aria-expanded', String(expanded));

        if (panel) {
            panel.hidden = !expanded;
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

    const openSheet = (name, trigger) => {
        const sheet = root.querySelector(`[data-mobile-checklist-sheet="${name}"]`);

        if (!sheet) {
            return;
        }

        focusBeforeSheet = trigger ?? document.activeElement;
        activeSheet = sheet;
        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-checklist-sheet-open');
        window.requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            sheet.querySelector('[data-mobile-checklist-sheet-panel]')?.focus();
        });
    };

    const closeSheet = () => {
        if (!activeSheet) {
            return;
        }

        const sheet = activeSheet;
        activeSheet = null;
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-checklist-sheet-open');
        window.setTimeout(() => {
            sheet.hidden = true;
            focusBeforeSheet?.focus();
            focusBeforeSheet = null;
        }, 220);
    };

    root.addEventListener('click', (event) => {
        const categoryToggle = event.target.closest('[data-mobile-checklist-category-toggle]');
        const createCategoryButton = event.target.closest('[data-mobile-category-create]');
        const editCategoryButton = event.target.closest('[data-mobile-category-edit]');
        const createItemButton = event.target.closest('[data-mobile-checklist-create]');
        const editItemButton = event.target.closest('[data-mobile-checklist-edit]');
        const closeButton = event.target.closest('[data-mobile-checklist-sheet-close]');

        if (categoryToggle) {
            const selectedCategory = categoryToggle.closest('[data-mobile-checklist-category]');
            const shouldExpand = categoryToggle.getAttribute('aria-expanded') !== 'true';
            categories.forEach((category) => setExpanded(category, false));

            if (selectedCategory && shouldExpand) {
                setExpanded(selectedCategory, true);
            }
        }

        if (createCategoryButton) {
            categoryForm.reset();
            configureForm(categoryForm, categoryForm.dataset.storeAction, 'POST');
            setValue(categoryForm, 'status', 'active');
            root.querySelector('[data-mobile-category-form-title]').textContent = 'Tambah Kategori';
            openSheet('category-form', createCategoryButton);
        }

        if (editCategoryButton) {
            configureForm(categoryForm, editCategoryButton.dataset.action, 'PUT');
            setValue(categoryForm, 'name', editCategoryButton.dataset.name);
            setValue(categoryForm, 'frequency', editCategoryButton.dataset.frequency);
            setValue(categoryForm, 'description', editCategoryButton.dataset.description);
            setValue(categoryForm, 'status', editCategoryButton.dataset.status);
            root.querySelector('[data-mobile-category-form-title]').textContent = 'Edit Kategori';
            openSheet('category-form', editCategoryButton);
        }

        if (createItemButton) {
            itemForm.reset();
            configureForm(itemForm, itemForm.dataset.storeAction, 'POST');
            setValue(itemForm, 'status', 'active');
            root.querySelector('[data-mobile-checklist-form-title]').textContent = 'Tambah Checklist';
            openSheet('form', createItemButton);
        }

        if (editItemButton) {
            configureForm(itemForm, editItemButton.dataset.action, 'PUT');
            setValue(itemForm, 'maintenance_category_id', editItemButton.dataset.categoryId);
            setValue(itemForm, 'item_name', editItemButton.dataset.itemName);
            setValue(itemForm, 'criteria', editItemButton.dataset.criteria);
            setValue(itemForm, 'input_type', editItemButton.dataset.inputType);
            setValue(itemForm, 'sort_order', editItemButton.dataset.sortOrder);
            setValue(itemForm, 'status', editItemButton.dataset.status);
            root.querySelector('[data-mobile-checklist-form-title]').textContent = 'Edit Checklist';
            openSheet('form', editItemButton);
        }

        if (closeButton) {
            closeSheet();
        }
    });

    root.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSheet();
        }
    });

    if (root.dataset.openForm === 'category') {
        openSheet('category-form');
    }

    if (root.dataset.openForm === 'item') {
        openSheet('form');
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobileChecklists, { once: true });
    } else {
        initializeMobileChecklists();
    }
}
