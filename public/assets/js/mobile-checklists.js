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

    const categoryLabels = {
        daily: 'Harian',
        weekly: 'Mingguan',
        monthly: 'Bulanan',
        quarterly: 'Triwulan',
        semester: 'Semesteran',
        yearly: 'Tahunan',
    };
    const inputLabels = {
        boolean: 'Sesuai/Tidak Sesuai',
        note: 'Catatan',
        numeric: 'Nilai + Catatan',
        boolean_note: 'Sesuai/Tidak Sesuai + Catatan',
    };
    const statusLabels = {
        active: 'Aktif',
        inactive: 'Nonaktif',
    };
    const filterForm = root.querySelector('[data-mobile-checklists-filter]');
    const resetButton = root.querySelector('[data-mobile-checklists-reset]');
    const form = root.querySelector('[data-mobile-checklist-form]');
    const formTitle = root.querySelector('[data-mobile-checklist-form-title]');
    const categories = [...root.querySelectorAll('[data-mobile-checklist-category]')];
    const emptyState = root.querySelector('[data-mobile-checklists-empty]');
    const accordion = root.querySelector('[data-mobile-checklists-accordion]');
    const resultCount = root.querySelector('[data-mobile-checklists-result-count]');
    const toast = root.querySelector('[data-mobile-checklists-toast]');
    const toastMessage = root.querySelector('[data-mobile-checklists-toast-message]');
    const mobileShell = root.closest('[data-mobile-shell]');
    let checklistItems = [...root.querySelectorAll('[data-mobile-checklist-item]')].map(readChecklistCard);
    let activeSheet = null;
    let lastFocusedElement = null;
    let editingItemId = null;
    let createdItemSequence = 1;
    let toastTimer = null;
    let backgroundInertStates = new Map();

    function readChecklistCard(card) {
        return {
            id: card.dataset.id,
            name: card.dataset.name,
            category: card.dataset.category,
            categoryLabel: card.dataset.categoryLabel,
            frequency: card.dataset.frequency,
            frequencyLabel: card.dataset.frequencyLabel,
            criteria: card.dataset.criteria,
            inputType: card.dataset.inputType,
            inputLabel: card.dataset.inputLabel,
            status: card.dataset.status,
            statusLabel: card.dataset.statusLabel,
            note: card.dataset.note,
        };
    }

    function refreshIcons() {
        window.lucide?.createIcons();
    }

    function readFilters() {
        const formData = new FormData(filterForm);

        return {
            category: formData.get('category')?.toString() ?? '',
            status: formData.get('status')?.toString() ?? '',
            query: formData.get('query')?.toString() ?? '',
        };
    }

    function setCategoryExpanded(category, expanded) {
        const toggle = category.querySelector('[data-mobile-checklist-category-toggle]');
        const panel = category.querySelector('[data-mobile-checklist-category-panel]');

        toggle?.setAttribute('aria-expanded', String(expanded));

        if (panel) {
            panel.hidden = !expanded;
        }
    }

    function expandOnly(selectedCategory) {
        categories.forEach((category) => setCategoryExpanded(category, category === selectedCategory));
    }

    function updateSummary(visibleItems) {
        const summary = summarizeChecklistItems(visibleItems);

        Object.entries(summary).forEach(([key, value]) => {
            const output = root.querySelector(`[data-mobile-checklists-summary="${key}"]`);

            if (output) {
                output.textContent = value.toString();
            }
        });
    }

    function applyFilters(preferredCategoryName = null) {
        const visibleItems = filterChecklistItems(checklistItems, readFilters());
        const visibleIds = new Set(visibleItems.map((item) => item.id));
        let firstVisibleCategory = null;
        let preferredCategory = null;

        categories.forEach((category) => {
            const cards = [...category.querySelectorAll('[data-mobile-checklist-item]')];
            const visibleCards = cards.filter((card) => visibleIds.has(card.dataset.id));

            cards.forEach((card) => {
                card.hidden = !visibleIds.has(card.dataset.id);
            });

            category.hidden = visibleCards.length === 0;

            const count = category.querySelector('[data-mobile-checklist-category-count]');

            if (count) {
                count.textContent = visibleCards.length.toString();
            }

            if (!firstVisibleCategory && visibleCards.length > 0) {
                firstVisibleCategory = category;
            }

            if (category.dataset.mobileChecklistCategory === preferredCategoryName && visibleCards.length > 0) {
                preferredCategory = category;
            }
        });

        if (firstVisibleCategory) {
            expandOnly(preferredCategory ?? firstVisibleCategory);
        }

        if (accordion) {
            accordion.hidden = visibleItems.length === 0;
        }

        if (emptyState) {
            emptyState.hidden = visibleItems.length > 0;
        }

        if (resultCount) {
            resultCount.textContent = `${visibleItems.length} item ditampilkan`;
        }

        updateSummary(visibleItems);
    }

    function closeToast() {
        window.clearTimeout(toastTimer);

        if (toast) {
            toast.hidden = true;
        }
    }

    function showToast(message) {
        if (!toast || !toastMessage) {
            return;
        }

        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(closeToast, 4200);
    }

    function getBackgroundElements(sheet) {
        return [
            ...[...root.children].filter((element) => element !== sheet),
            ...[...(mobileShell?.children ?? [])].filter((element) => element !== root),
        ];
    }

    function openSheet(sheetName, trigger) {
        const sheet = root.querySelector(`[data-mobile-checklist-sheet="${sheetName}"]`);

        if (!sheet) {
            return;
        }

        lastFocusedElement = trigger;
        activeSheet = sheet;
        backgroundInertStates = new Map();
        getBackgroundElements(sheet).forEach((element) => {
            backgroundInertStates.set(element, element.inert);
            element.inert = true;
        });

        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-checklist-sheet-open');
        window.requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            sheet.querySelector('[data-mobile-checklist-sheet-panel]')?.focus();
        });
    }

    function closeSheet({ immediate = false, restoreFocus = true } = {}) {
        if (!activeSheet) {
            return;
        }

        const closingSheet = activeSheet;
        const focusTarget = lastFocusedElement;
        const closingInertStates = backgroundInertStates;
        closingSheet.classList.remove('is-open');
        closingSheet.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-checklist-sheet-open');
        activeSheet = null;
        lastFocusedElement = null;
        backgroundInertStates = new Map();

        const finishClosingSheet = () => {
            closingSheet.hidden = true;
            closingInertStates.forEach((wasInert, element) => {
                element.inert = wasInert;
            });
            closingInertStates.clear();

            if (restoreFocus) {
                const isFocusTargetVisible = focusTarget
                    && !focusTarget.closest('[hidden]')
                    && focusTarget.getClientRects().length > 0;
                const fallbackFocusTarget = root.querySelector('[data-mobile-checklist-create]');
                (isFocusTargetVisible ? focusTarget : fallbackFocusTarget)?.focus();
            }
        };

        if (immediate) {
            finishClosingSheet();
        } else {
            window.setTimeout(finishClosingSheet, 220);
        }
    }

    function findChecklistItem(itemId) {
        return checklistItems.find((item) => item.id === itemId) ?? null;
    }

    function openDetail(itemId, trigger) {
        const item = findChecklistItem(itemId);

        if (!item) {
            return;
        }

        root.querySelectorAll('[data-mobile-checklist-detail-field]').forEach((output) => {
            const fieldName = output.dataset.mobileChecklistDetailField;
            output.textContent = item[fieldName] || 'Tidak ada catatan tambahan.';
        });

        const statusBadge = root.querySelector('[data-mobile-checklist-sheet="detail"] [data-mobile-checklist-detail-field="statusLabel"]');

        if (statusBadge) {
            statusBadge.className = `mobile-checklists-badge mobile-checklists-badge--${item.status}`;
        }

        openSheet('detail', trigger);
    }

    function setFormValue(fieldName, value) {
        const field = form?.elements.namedItem(fieldName);

        if (field) {
            field.value = value ?? '';
        }
    }

    function openCreateForm(trigger) {
        editingItemId = null;
        form?.reset();
        setFormValue('category', 'daily');
        setFormValue('frequency', 'daily');
        setFormValue('inputType', 'boolean');
        setFormValue('status', 'active');

        if (formTitle) {
            formTitle.textContent = 'Tambah Checklist';
        }

        openSheet('form', trigger);
    }

    function openEditForm(itemId, trigger) {
        const item = findChecklistItem(itemId);

        if (!item) {
            return;
        }

        editingItemId = item.id;
        setFormValue('name', item.name);
        setFormValue('category', item.category);
        setFormValue('frequency', item.frequency);
        setFormValue('criteria', item.criteria);
        setFormValue('inputType', item.inputType);
        setFormValue('status', item.status);
        setFormValue('note', item.note);

        if (formTitle) {
            formTitle.textContent = 'Edit Checklist';
        }

        openSheet('form', trigger);
    }

    function createItemCard() {
        const card = document.createElement('article');
        card.className = 'mobile-checklist-item';
        card.setAttribute('data-mobile-checklist-item', '');
        card.innerHTML = `
            <header>
                <span aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                <div><p data-mobile-checklist-item-frequency></p><h3 data-mobile-checklist-item-name></h3></div>
                <span class="mobile-checklists-badge" data-mobile-checklist-item-status></span>
            </header>
            <div class="mobile-checklist-item__criteria"><span>Kriteria pemeriksaan</span><p data-mobile-checklist-item-criteria></p></div>
            <dl>
                <div><dt>Tipe input</dt><dd data-mobile-checklist-item-input></dd></div>
                <div><dt>Frekuensi</dt><dd data-mobile-checklist-item-frequency-detail></dd></div>
            </dl>
            <footer>
                <button type="button" data-mobile-checklist-detail><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                <button type="button" data-mobile-checklist-edit><i data-lucide="pencil" aria-hidden="true"></i>Edit</button>
            </footer>`;

        return card;
    }

    function writeItemToCard(item) {
        let card = root.querySelector(`[data-mobile-checklist-item][data-id="${item.id}"]`);

        if (!card) {
            card = createItemCard();
        }

        Object.assign(card.dataset, {
            id: item.id,
            name: item.name,
            category: item.category,
            categoryLabel: item.categoryLabel,
            frequency: item.frequency,
            frequencyLabel: item.frequencyLabel,
            criteria: item.criteria,
            inputType: item.inputType,
            inputLabel: item.inputLabel,
            status: item.status,
            statusLabel: item.statusLabel,
            note: item.note,
        });

        card.querySelector('[data-mobile-checklist-item-frequency]').textContent = item.frequencyLabel;
        card.querySelector('[data-mobile-checklist-item-name]').textContent = item.name;
        card.querySelector('[data-mobile-checklist-item-criteria]').textContent = item.criteria;
        card.querySelector('[data-mobile-checklist-item-input]').textContent = item.inputLabel;
        card.querySelector('[data-mobile-checklist-item-frequency-detail]').textContent = item.frequencyLabel;

        const statusBadge = card.querySelector('[data-mobile-checklist-item-status]');
        statusBadge.textContent = item.statusLabel;
        statusBadge.className = `mobile-checklists-badge mobile-checklists-badge--${item.status}`;

        card.querySelectorAll('[data-mobile-checklist-detail], [data-mobile-checklist-edit]').forEach((button) => {
            button.dataset.checklistId = item.id;
        });

        const destination = root.querySelector(
            `[data-mobile-checklist-category="${item.category}"] [data-mobile-checklist-item-list]`,
        );
        destination?.append(card);
    }

    function readFormItem() {
        const formData = new FormData(form);
        const category = formData.get('category')?.toString() ?? 'daily';
        const frequency = formData.get('frequency')?.toString() ?? category;
        const inputType = formData.get('inputType')?.toString() ?? 'boolean';
        const status = formData.get('status')?.toString() ?? 'active';

        return {
            id: editingItemId ?? `created-checklist-${createdItemSequence++}`,
            name: formData.get('name')?.toString().trim() ?? '',
            category,
            categoryLabel: categoryLabels[category],
            frequency,
            frequencyLabel: categoryLabels[frequency],
            criteria: formData.get('criteria')?.toString().trim() ?? '',
            inputType,
            inputLabel: inputLabels[inputType],
            status,
            statusLabel: statusLabels[status],
            note: formData.get('note')?.toString().trim() ?? '',
        };
    }

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    resetButton?.addEventListener('click', () => {
        filterForm.reset();
        applyFilters();
    });

    form?.elements.namedItem('category')?.addEventListener('change', (event) => {
        setFormValue('frequency', event.target.value);
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        const wasEditing = editingItemId !== null;
        const item = readFormItem();
        checklistItems = upsertChecklistItem(checklistItems, item);
        writeItemToCard(item);
        applyFilters(item.category);
        refreshIcons();
        closeSheet();
        showToast(createSaveMessage(item, wasEditing));
    });

    root.addEventListener('click', (event) => {
        const categoryToggle = event.target.closest('[data-mobile-checklist-category-toggle]');
        const detailButton = event.target.closest('[data-mobile-checklist-detail]');
        const editButton = event.target.closest('[data-mobile-checklist-edit]');
        const createButton = event.target.closest('[data-mobile-checklist-create]');
        const closeButton = event.target.closest('[data-mobile-checklist-sheet-close]');
        const toastClose = event.target.closest('[data-mobile-checklists-toast-close]');

        if (categoryToggle) {
            const category = categoryToggle.closest('[data-mobile-checklist-category]');
            const willExpand = categoryToggle.getAttribute('aria-expanded') !== 'true';
            categories.forEach((candidate) => setCategoryExpanded(candidate, false));

            if (willExpand && category) {
                setCategoryExpanded(category, true);
            }
        }

        if (detailButton) {
            openDetail(detailButton.dataset.checklistId, detailButton);
        }

        if (editButton) {
            openEditForm(editButton.dataset.checklistId, editButton);
        }

        if (createButton) {
            openCreateForm(createButton);
        }

        if (closeButton) {
            closeSheet();
        }

        if (toastClose) {
            closeToast();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeSheet) {
            closeSheet();
        }

        if (event.key === 'Tab' && activeSheet && !shouldCloseMobileSheet(window.innerWidth)) {
            const panel = activeSheet.querySelector('[data-mobile-checklist-sheet-panel]');
            const focusableElements = [...panel.querySelectorAll(
                'button:not([disabled]):not([tabindex="-1"]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )];
            const currentIndex = focusableElements.indexOf(document.activeElement);
            const nextIndex = getTrappedFocusIndex(currentIndex, focusableElements.length, event.shiftKey);

            if (nextIndex >= 0) {
                event.preventDefault();
                focusableElements[nextIndex].focus();
            }
        }
    });

    window.addEventListener('resize', () => {
        if (activeSheet && shouldCloseMobileSheet(window.innerWidth)) {
            closeSheet({ immediate: true, restoreFocus: false });
        }
    });

    applyFilters();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobileChecklists, { once: true });
    } else {
        initializeMobileChecklists();
    }
}
